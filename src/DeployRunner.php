<?php

namespace Hypernode\Deploy;

use Deployer\Console\Application;
use Deployer\Console\Output\OutputWatcher;
use Deployer\Deployer;
use Deployer\Exception\Exception;
use Deployer\Exception\GracefulShutdownException;
use Hypernode\Deploy\Deployer\RecipeLoader;
use Hypernode\Deploy\Exception\InvalidConfigurationException;
use Hypernode\Deploy\Deployer\Task\ConfigurableTaskInterface;
use Hypernode\Deploy\Deployer\Task\RegisterAfterInterface;
use Hypernode\Deploy\Deployer\Task\TaskFactory;
use Hypernode\DeployConfiguration\Configuration;
use Hypernode\DeployConfiguration\Server;
use Hypernode\DeployConfiguration\ServerRoleConfigurableInterface;
use Hypernode\DeployConfiguration\Stage;
use Hypernode\DeployConfiguration\StageConfigurableInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

use function Deployer\host;
use function Deployer\localhost;
use function Deployer\run;
use function Deployer\task;

class DeployRunner
{
    /**
     * @var TaskFactory
     */
    private $taskFactory;

    /**
     * @var InputInterface
     */
    private $input;

    /**
     * @var LoggerInterface
     */
    private $log;

    /**
     * @var RecipeLoader
     */
    private $recipeLoader;

    public function __construct(
        TaskFactory $taskFactory,
        InputInterface $input,
        LoggerInterface $log,
        RecipeLoader $recipeLoader
    ) {
        $this->taskFactory = $taskFactory;
        $this->input = $input;
        $this->log = $log;
        $this->recipeLoader = $recipeLoader;
    }

    /**
     * @throws GracefulShutdownException
     * @throws Throwable
     * @throws Exception
     *
     * @return void
     */
    public function run(OutputInterface $output, string $stage, string $task = 'deploy')
    {
        $console = new Application();
        $deployer = new Deployer($console);
        $deployer['output'] = new OutputWatcher($output);
        $deployer['input'] = new ArrayInput([]);

        try {
            $this->initializeDeployer($deployer);
        } catch (InvalidConfigurationException $e) {
            $output->write($e->getMessage());
            return;
        }
        $this->runStage($deployer, $stage, $task);
    }

    /**
     * Initialize deployer settings
     *
     * @throws Exception
     * @throws GracefulShutdownException
     * @throws Throwable
     * @throws InvalidConfigurationException
     */
    private function initializeDeployer(Deployer $deployer): void
    {
        $this->recipeLoader->load('common.php');
        $tasks = $this->taskFactory->loadAll();
        $config = $this->getConfiguration($deployer);
        $config->setLogger($this->log);
        $this->configureStages($config);

        foreach ($tasks as $task) {
            $task->configure($config);

            if ($task instanceof ConfigurableTaskInterface) {
                $this->initializeConfigurableTask($task, $config);
            }
        }

        foreach ($config->getPostInitializeCallbacks() as $callback) {
            if (!is_callable($callback)) {
                continue;
            }
            call_user_func($callback);
        }

        foreach ($tasks as $task) {
            if ($task instanceof RegisterAfterInterface) {
                $task->registerAfter();
            }
        }
    }

    /**
     * Configure deploy tasks based on specific configuration in Hipex deploy configuration
     * @throws InvalidConfigurationException
     */
    private function initializeConfigurableTask(ConfigurableTaskInterface $task, Configuration $mainConfig): void
    {
        $configurations = array_merge(
            $mainConfig->getBuildCommands(),
            $mainConfig->getDeployCommands(),
            $mainConfig->getAfterDeployTasks()
        );

        foreach ($configurations as $taskConfig) {
            if (!$task->supports($taskConfig)) {
                continue;
            }

            $task->configureTask($taskConfig);

            $deployerTask = $task->build($taskConfig);
            if ($deployerTask) {
                if ($taskConfig instanceof StageConfigurableInterface && $taskConfig->getStage()) {
                    $deployerTask->onStage($taskConfig->getStage()->getName());
                }

                if ($taskConfig instanceof ServerRoleConfigurableInterface && $taskConfig->getServerRoles()) {
                    $deployerTask->onRoles($taskConfig->getServerRoles());
                }
            }
        }
    }

    /**
     * @throws Exception
     * @throws GracefulShutdownException
     * @throws Throwable
     */
    private function getConfiguration(Deployer $deployer): Configuration
    {
        try {
            return $this->tryGetConfiguration();
        } catch (\Throwable $e) {
            $this->log->warning(sprintf('Failed to initialize deploy.php configuration file: %s', $e->getMessage()));
            $this->tryComposerInstall($deployer);
            $this->initializeAppAutoloader();
            return $this->tryGetConfiguration();
        }
    }

    private function configureStages(Configuration $config): void
    {
        $this->initializeBuildStage();

        foreach ($config->getStages() as $stage) {
            foreach ($stage->getServers() as $server) {
                $this->configureStageServer($stage, $server);
            }
        }
    }

    private function configureStageServer(Stage $stage, Server $server): void
    {
        /** @psalm-suppress InvalidArgument deployer will have proper typing in 7.x */
        $host = host($stage->getName() . ':' . $server->getHostname());
        $host->hostname($server->getHostname());
        $host->port(22);
        $host->stage($stage->getName());
        $host->user('app');
        $host->forwardAgent();
        $host->multiplexing(true);
        $host->roles($server->getRoles());
        $host->set('domain', $stage->getDomain());
        $host->set('deploy_path', function () {
            return run('realpath ~/apps/{{domain}}');
        });
        $host->set('configuration_stage', $stage);

        foreach ($server->getOptions() as $optionName => $optionValue) {
            $host->set($optionName, $optionValue);
        }

        foreach ($server->getSshOptions() as $optionName => $optionValue) {
            $host->addSshOption($optionName, $optionValue);
        }
    }

    /**
     * Initialize build stage
     */
    private function initializeBuildStage(): void
    {
        /** @psalm-suppress InvalidArgument deployer will have proper typing in 7.x */
        $host = localhost('build');
        $host->stage('build');
        $host->set('bin/php', 'php');
    }

    /**
     * @throws GracefulShutdownException
     * @throws Throwable
     * @throws Exception
     */
    private function runStage(Deployer $deployer, string $stage, string $task = 'deploy'): void
    {
        $hosts = $deployer->hostSelector->getHosts($stage);
        if (empty($hosts)) {
            throw new \RuntimeException(sprintf('No host(s) found in stage %s', $stage));
        }

        $tasks = $deployer->scriptManager->getTasks($task, $hosts);
        $executor = $deployer->seriesExecutor;

        try {
            $executor->run($tasks, $hosts);
        } catch (Throwable $exception) {
            $deployer->logger->log('[' . \get_class($exception) . '] ' . $exception->getMessage());
            $deployer->logger->log($exception->getTraceAsString());

            if ($exception instanceof GracefulShutdownException) {
                throw $exception;
            }

            // Check if we have tasks to execute on failure
            if ($deployer['fail']->has($task)) {
                $taskName = $deployer['fail']->get($task);
                $tasks = $deployer->scriptManager->getTasks($taskName, $hosts);

                $executor->run($tasks, $hosts);
            }
            throw $exception;
        }
    }

    private function tryGetConfiguration(): Configuration
    {
        $file = $this->input->getOption('file');
        if (!$file) {
            $file = 'deploy.php';
        }

        if (!is_readable($file)) {
            throw new \RuntimeException(sprintf('No %s file found in project root %s', $file, getcwd()));
        }

        $configuration = \call_user_func(function () use ($file) {
            /** @noinspection PhpIncludeInspection */
            return require $file;
        });

        if (!$configuration instanceof Configuration) {
            throw new \RuntimeException(sprintf('%s/deploy.php dit not return object of type %s', getcwd(), Configuration::class));
        }

        return $configuration;
    }

    /**
     * @throws GracefulShutdownException
     * @throws Throwable
     * @throws Exception
     */
    private function tryComposerInstall(Deployer $deployer): void
    {
        /** @psalm-suppress InvalidArgument deployer will have proper typing in 7.x */
        $host = localhost('composer-prepare');
        $host->stage('composer-prepare');
        $host->set('bin/php', 'php');

        task('composer-prepare:install', function () {
            run('composer install --ignore-platform-reqs --optimize-autoloader --no-dev');
        });

        task('composer-prepare', [
            'deploy:vendors:auth',
            'composer-prepare:install',
        ]);

        $this->runStage($deployer, 'composer-prepare', 'composer-prepare');
    }

    /**
     * Initialize autoloader of the application being deployed
     */
    private function initializeAppAutoloader(): void
    {
        /** @psalm-suppress UndefinedConstant */
        if (file_exists(WORKING_DIR . '/vendor/autoload.php')) {
            require_once WORKING_DIR . '/vendor/autoload.php';
        }
    }
}
