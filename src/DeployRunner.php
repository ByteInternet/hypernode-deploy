<?php

namespace Hypernode\Deploy;

use Deployer\Deployer;
use Deployer\Exception\Exception;
use Deployer\Exception\GracefulShutdownException;
use Deployer\Host\Host;
use Hypernode\Deploy\Console\Output\OutputWatcher;
use Hypernode\Deploy\Deployer\RecipeLoader;
use Hypernode\Deploy\Deployer\Task\ConfigurableTaskInterface;
use Hypernode\Deploy\Deployer\Task\TaskFactory;
use Hypernode\Deploy\Ephemeral\EphemeralHypernodeManager;
use Hypernode\Deploy\Exception\CreateEphemeralHypernodeFailedException;
use Hypernode\Deploy\Exception\InvalidConfigurationException;
use Hypernode\Deploy\Exception\TimeoutException;
use Hypernode\DeployConfiguration\Configurable\ServerRoleConfigurableInterface;
use Hypernode\DeployConfiguration\Configurable\StageConfigurableInterface;
use Hypernode\DeployConfiguration\Configuration;
use Hypernode\DeployConfiguration\Server;
use Hypernode\DeployConfiguration\Stage;
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
    public const TASK_BUILD = 'build';
    public const TASK_DEPLOY = 'deploy';

    private TaskFactory $taskFactory;
    private InputInterface $input;
    private LoggerInterface $log;
    private RecipeLoader $recipeLoader;
    private EphemeralHypernodeManager $ephemeralHypernodeManager;

    /**
     * Registered ephemeral Hypernodes to stop/cancel after running.
     *
     * @var string[]
     */
    private array $ephemeralHypernodesRegistered = [];

    private array $deployedHostnames = [];
    private string $deployedStage = '';

    public function __construct(
        TaskFactory $taskFactory,
        InputInterface $input,
        LoggerInterface $log,
        RecipeLoader $recipeLoader,
        EphemeralHypernodeManager $ephemeralHypernodeManager
    ) {
        $this->taskFactory = $taskFactory;
        $this->input = $input;
        $this->log = $log;
        $this->recipeLoader = $recipeLoader;
        $this->ephemeralHypernodeManager = $ephemeralHypernodeManager;
    }

    /**
     * @throws GracefulShutdownException
     * @throws Throwable
     * @throws Exception
     *
     * @return int
     */
    public function run(OutputInterface $output, string $stage, string $task = self::TASK_DEPLOY): int
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
            $this->initializeDeployer($deployer, $task);
        } catch (InvalidConfigurationException $e) {
            $output->write($e->getMessage());
            return 1;
        }

        return $this->runStage($deployer, $stage, $task);
    }

    /**
     * Initialize deployer settings
     *
     * @throws Exception
     * @throws GracefulShutdownException
     * @throws Throwable
     * @throws InvalidConfigurationException
     */
    private function initializeDeployer(Deployer $deployer, string $task): void
    {
        $this->recipeLoader->load('common.php');
        $tasks = $this->taskFactory->loadAll();
        $config = $this->getConfiguration($deployer);
        $config->setLogger($this->log);
        $this->configureStages($config, $task);

        foreach ($tasks as $task) {
            $task->configure($config);
            $this->log->debug("Running configure for task " . get_class($task));

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

    private function configureStages(Configuration $config, string $task): void
    {
        if ($task === self::TASK_BUILD) {
            $this->initializeBuildStage($config);
        }

        if ($task === self::TASK_DEPLOY) {
            foreach ($config->getStages() as $stage) {
                foreach ($stage->getServers() as $server) {
                    $this->configureStageServer($stage, $server, $config);
                }
            }
        }
    }

    private function configureStageServer(Stage $stage, Server $server, Configuration $config): void
    {
        $this->maybeConfigureEphemeralServer($server);

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
        $host->set('nginx_release_path', '{{release_path}}/.hypernode/nginx');
        $host->set('nginx_current_path', '{{current_path}}/.hypernode/nginx');
        $host->set('supervisor_release_path', '{{release_path}}/.hypernode/supervisor');
        $host->set('supervisor_current_path', '{{current_path}}/.hypernode/supervisor');
        $host->set('varnish_release_path', '{{release_path}}/.hypernode/varnish');
        $host->set('varnish_current_path', '{{current_path}}/.hypernode/varnish');
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
            $this->log->debug(
                sprintf('Setting var "%s" to %s for stage "%s"', $key, json_encode($value), $stage->getName())
            );
            $host->set($key, $value);
        }
        foreach ($config->getVariables('deploy') as $key => $value) {
            $this->log->debug(
                sprintf('Setting var "%s" to %s for stage "%s"', $key, json_encode($value), $stage->getName())
            );
            $host->set($key, $value);
        }
    }

    private function maybeConfigureEphemeralServer(Server $server): void
    {
        $serverOptions = $server->getOptions();
        $isEphemeral = $serverOptions[Server::OPTION_HN_EPHEMERAL] ?? false;
        $parentApp = $serverOptions[Server::OPTION_HN_PARENT_APP] ?? null;
        if ($isEphemeral && $parentApp) {
            $this->log->info(sprintf('Creating an ephemeral Hypernode based on %s.', $parentApp));
            $ephemeralApp = $this->ephemeralHypernodeManager->createForHypernode($parentApp);
            $server->setHostname(sprintf("%s.hypernode.io", $ephemeralApp));
            $this->ephemeralHypernodesRegistered[] = $ephemeralApp;
            $this->log->info(sprintf('Successfully requested ephemeral Hypernode, name is %s.', $ephemeralApp));

            try {
                $this->log->info('Waiting for ephemeral Hypernode to become available...');
                $this->ephemeralHypernodeManager->waitForAvailability($ephemeralApp);
                $this->log->info('Ephemeral Hypernode has become available!');
            } catch (CreateEphemeralHypernodeFailedException | TimeoutException $e) {
                $this->ephemeralHypernodeManager->cancel($ephemeralApp);
                throw $e;
            }
        }
    }

    /**
     * Initialize build stage
     */
    private function initializeBuildStage(Configuration $config): void
    {
        $host = localhost('build');
        $host->set('labels', ['stage' => 'build']);
        $host->set('bin/php', 'php');
        $host->set('deploy_path', '.');
        $host->set('release_or_current_path', '.');

        foreach ($config->getVariables() as $key => $value) {
            $this->log->debug(
                sprintf('Setting var "%s" to %s for stage "build"', $key, json_encode($value))
            );
            $host->set($key, $value);
        }

        foreach ($config->getVariables('build') as $key => $value) {
            $this->log->debug(
                sprintf('Setting var "%s" to %s for stage "build"', $key, json_encode($value))
            );
            $host->set($key, $value);
        }
    }

    /**
     * @throws GracefulShutdownException
     * @throws Throwable
     * @throws Exception
     */
    private function runStage(Deployer $deployer, string $stage, string $task = 'deploy'): int
    {
        $hosts = $deployer->selector->select("stage=$stage");
        if (empty($hosts)) {
            throw new \RuntimeException(sprintf('No host(s) found in stage %s', $stage));
        }

        $tasks = $deployer->scriptManager->getTasks($task);
        $executor = $deployer->master;

        /**
         * Set the env variable to tell deployer to deploy the hosts sequentially instead of parallel.
         * @see \Deployer\Executor\Master::runTask()
         */
        putenv('DEPLOYER_LOCAL_WORKER=true');
        $exitCode = $executor->run($tasks, $hosts);

        if ($exitCode === 0) {
            $this->deployedHostnames = array_map(fn (Host $host) => $host->getHostname(), $hosts);
            $this->deployedStage = $stage;
            return 0;
        }

        if ($exitCode === GracefulShutdownException::EXIT_CODE) {
            return 1;
        }

        // Check if we have tasks to execute on failure
        if ($deployer['fail']->has($task)) {
            $taskName = $deployer['fail']->get($task);
            $tasks = $deployer->scriptManager->getTasks($taskName);

            $executor->run($tasks, $hosts);
        }

        $this->ephemeralHypernodeManager->cancel(...$this->ephemeralHypernodesRegistered);

        return $exitCode;
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

    public function getDeploymentReport()
    {
        return new Report\Report(
            $this->deployedStage,
            $this->deployedHostnames,
            $this->ephemeralHypernodesRegistered
        );
    }
}
