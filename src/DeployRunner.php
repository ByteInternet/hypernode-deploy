<?php

namespace Hypernode\Deploy;

use Deployer\Deployer;
use Deployer\Exception\Exception;
use Deployer\Exception\GracefulShutdownException;
use Hypernode\Deploy\Console\Output\OutputWatcher;
use Hypernode\Deploy\Deployer\RecipeLoader;
use Hypernode\Deploy\Exception\InvalidConfigurationException;
use Hypernode\Deploy\Deployer\Task\ConfigurableTaskInterface;
use Hypernode\Deploy\Deployer\Task\TaskFactory;
use Hypernode\DeployConfiguration\Configuration;
use Hypernode\DeployConfiguration\Server;
use Hypernode\DeployConfiguration\ServerRoleConfigurableInterface;
use Hypernode\DeployConfiguration\Stage;
use Hypernode\DeployConfiguration\StageConfigurableInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
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
        $deployer['input'] = new ArrayInput(
            [],
            new InputDefinition([
                new InputOption('limit'),
                new InputOption('profile'),
            ])
        );

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
            $this->log->warning("Running configure for task " . get_class($task));

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
            $task->register();
        }
    }

    /**
     * Configure deploy tasks based on specific configuration in Hipex deploy configuration
     * @throws InvalidConfigurationException
     */
    private function initializeConfigurableTask(ConfigurableTaskInterface $task, Configuration $mainConfig): void
    {
        $configurations = array_merge(
            $mainConfig->getPlatformConfigurations(),
            $mainConfig->getAfterDeployTasks()
        );

        foreach ($configurations as $taskConfig) {
            if (!$task->supports($taskConfig)) {
                continue;
            }

            $deployerTask = $task->configureWithTaskConfig($taskConfig);
            if ($deployerTask) {
                if ($taskConfig instanceof StageConfigurableInterface && $taskConfig->getStage()) {
                    $deployerTask->select("stage={$taskConfig->getStage()->getName()}");
                }

                if ($taskConfig instanceof ServerRoleConfigurableInterface && $taskConfig->getServerRoles()) {
                    $roles = implode("&", $taskConfig->getServerRoles());
                    $deployerTask->select("roles={$roles}");
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
        $this->initializeBuildStage($config);

        foreach ($config->getStages() as $stage) {
            foreach ($stage->getServers() as $server) {
                $this->configureStageServer($stage, $server, $config);
            }
        }
    }

    private function configureStageServer(Stage $stage, Server $server, Configuration $config): void
    {
        $host = host($stage->getName() . ':' . $server->getHostname());
        $host->setHostname($server->getHostname());
        $host->setPort(22);
        $host->set('labels', ['stage' => $stage->getName(), 'roles' => $server->getRoles()]);
        $host->setRemoteUser('app');
        $host->setForwardAgent(true);
        $host->setSshMultiplexing(true);
        $host->set('roles', $server->getRoles());
        $host->set('domain', $stage->getDomain());
        $host->set('deploy_path', function () {
            // Ensure directory exists before returning it
            run('mkdir -p ~/apps/{{domain}}/shared');
            return run('realpath ~/apps/{{domain}}');
        });
        $host->set('current_path', '{{deploy_path}}/current');
        $host->set('app_release_path', '{{release_path}}/app');
        $host->set('app_current_path', '{{current_path}}/app');
        $host->set('nginx_release_path', '{{release_path}}/nginx');
        $host->set('nginx_current_path', '{{current_path}}/nginx');
        $host->set('supervisor_release_path', '{{release_path}}/supervisor');
        $host->set('supervisor_current_path', '{{current_path}}/supervisor');
        $host->set('configuration_stage', $stage);
        $host->set('writable_mode', 'chmod');

        foreach ($server->getOptions() as $optionName => $optionValue) {
            $host->set($optionName, $optionValue);
        }

        $sshOptions = [];
        foreach ($server->getSshOptions() as $optionName => $optionValue) {
            $sshOptions[] = "-o $optionName=$optionValue";
        }

        if ($sshOptions) {
            $host->setSshArguments($sshOptions);
        }

        foreach ($config->getVariables() as $key => $value) {
            $host->set($key, $value);
        }
        foreach ($config->getVariables('deploy') as $key => $value) {
            $host->set($key, $value);
        }
    }

    /**
     * Initialize build stage
     */
    private function initializeBuildStage(Configuration $config): void
    {
        /** @psalm-suppress InvalidArgument deployer will have proper typing in 7.x */
        $host = localhost('build');
        $host->set('labels', ['stage' => 'build']);
        $host->set('bin/php', 'php');
        $host->set('deploy_path', '.');
        $host->set('release_or_current_path', '.');
        foreach ($config->getVariables() as $key => $value) {
            $host->set($key, $value);
        }
        foreach ($config->getVariables('build') as $key => $value) {
            $host->set($key, $value);
        }
    }

    /**
     * @throws GracefulShutdownException
     * @throws Throwable
     * @throws Exception
     */
    private function runStage(Deployer $deployer, string $stage, string $task = 'deploy'): void
    {
        $hosts = $deployer->selector->select("stage=$stage");
        if (empty($hosts)) {
            throw new \RuntimeException(sprintf('No host(s) found in stage %s', $stage));
        }

        $tasks = $deployer->scriptManager->getTasks($task);
        $executor = $deployer->master;

        try {
            /**
             * Set the env variable to tell deployer to deploy the hosts sequentially instead of parallel.
             * @see \Deployer\Executor\Master::runTask()
             */
            putenv('DEPLOYER_LOCAL_WORKER=true');
            $executor->run($tasks, $hosts);
        } catch (Throwable $exception) {
            $deployer->output->writeln('[' . \get_class($exception) . '] ' . $exception->getMessage());
            $deployer->output->writeln($exception->getTraceAsString());

            if ($exception instanceof GracefulShutdownException) {
                throw $exception;
            }

            // Check if we have tasks to execute on failure
            if ($deployer['fail']->has($task)) {
                $taskName = $deployer['fail']->get($task);
                $tasks = $deployer->scriptManager->getTasks($taskName);

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
            return require $file;
        });

        if (!$configuration instanceof Configuration) {
            throw new \RuntimeException(
                sprintf('%s/deploy.php did not return object of type %s', getcwd(), Configuration::class)
            );
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
        $host->set('labels', ['stage' => 'composer-prepare']);
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
