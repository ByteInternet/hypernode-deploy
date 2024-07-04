<?php

namespace Hypernode\Deploy;

use Deployer\Deployer;
use Deployer\Exception\Exception;
use Deployer\Exception\GracefulShutdownException;
use Deployer\Host\Host;
use Deployer\Task\Task;
use Hypernode\Deploy\Brancher\BrancherHypernodeManager;
use Hypernode\Deploy\Deployer\RecipeLoader;
use Hypernode\Deploy\Deployer\Task\ConfigurableTaskInterface;
use Hypernode\Deploy\Deployer\Task\TaskFactory;
use Hypernode\Deploy\Exception\CreateBrancherHypernodeFailedException;
use Hypernode\Deploy\Exception\InvalidConfigurationException;
use Hypernode\Deploy\Exception\TimeoutException;
use Hypernode\Deploy\Exception\ValidationException;
use Hypernode\DeployConfiguration\Configurable\ServerRoleConfigurableInterface;
use Hypernode\DeployConfiguration\Configurable\StageConfigurableInterface;
use Hypernode\DeployConfiguration\Configuration;
use Hypernode\DeployConfiguration\Server;
use Hypernode\DeployConfiguration\Stage;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

use function Deployer\host;
use function Deployer\localhost;
use function Deployer\run;

class DeployRunner
{
    public const TASK_BUILD = 'build';
    public const TASK_DEPLOY = 'deploy';

    private TaskFactory $taskFactory;
    private InputInterface $input;
    private LoggerInterface $log;
    private RecipeLoader $recipeLoader;
    private DeployerLoader $deployerLoader;
    private ConfigurationLoader $configurationLoader;
    private BrancherHypernodeManager $brancherHypernodeManager;

    /**
     * Registered brancher Hypernodes to stop/cancel after running.
     *
     * @var string[]
     */
    private array $brancherHypernodesRegistered = [];

    private array $deployedHostnames = [];
    private string $deployedStage = '';

    public function __construct(
        TaskFactory $taskFactory,
        InputInterface $input,
        LoggerInterface $log,
        RecipeLoader $recipeLoader,
        DeployerLoader $deployerLoader,
        ConfigurationLoader $configurationLoader,
        BrancherHypernodeManager $brancherHypernodeManager
    ) {
        $this->taskFactory = $taskFactory;
        $this->input = $input;
        $this->log = $log;
        $this->recipeLoader = $recipeLoader;
        $this->deployerLoader = $deployerLoader;
        $this->configurationLoader = $configurationLoader;
        $this->brancherHypernodeManager = $brancherHypernodeManager;
    }

    /**
     * @throws GracefulShutdownException
     * @throws Throwable
     * @throws Exception
     */
    public function run(OutputInterface $output, string $stage, string $task, bool $configureBuildStage, bool $configureServers, bool $reuseBrancher): int
    {
        $deployer = $this->deployerLoader->getOrCreateInstance($output);

        try {
            $this->prepare($configureBuildStage, $configureServers, $stage, $reuseBrancher);
        } catch (InvalidConfigurationException | ValidationException $e) {
            $output->write($e->getMessage());
            return 1;
        }

        return $this->runStage($deployer, $stage, $task);
    }

    /**
     * Prepare deploy runner before running stage
     *
     * @throws Exception
     * @throws GracefulShutdownException
     * @throws InvalidConfigurationException
     * @throws Throwable
     */
    private function prepare(bool $configureBuildStage, bool $configureServers, string $stage, bool $reuseBrancher): void
    {
        $this->recipeLoader->load('common.php');
        $tasks = $this->taskFactory->loadAll();
        $config = $this->configurationLoader->load(
            $this->input->getOption('file') ?: 'deploy.php'
        );
        $config->setLogger($this->log);

        if ($configureBuildStage) {
            $this->initializeBuildStage($config);
        }

        if ($configureServers) {
            $this->configureServers($config, $stage, $reuseBrancher);
        }

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
     * Configure deploy tasks based on specific configuration in Hypernode Deploy configuration
     * @throws InvalidConfigurationException
     */
    private function initializeConfigurableTask(ConfigurableTaskInterface $task, Configuration $mainConfig): void
    {
        $configurations = array_merge(
            $mainConfig->getPlatformConfigurations(),
            $mainConfig->getAfterDeployTasks()
        );

        foreach ($configurations as $taskConfig) {
            if ($task->supports($taskConfig)) {
                $task = $task->configureWithTaskConfig($taskConfig);

                if ($task && $taskConfig instanceof ServerRoleConfigurableInterface) {
                    $this->configureTaskOnServerRoles($task, $taskConfig);
                }

                if ($task && $taskConfig instanceof StageConfigurableInterface) {
                    $this->configureTaskOnStage($task, $taskConfig);
                }
            }
        }
    }

    private function configureTaskOnServerRoles(Task $task, ServerRoleConfigurableInterface $taskConfiguration)
    {
        $task->select('role=' . implode(',role=', $taskConfiguration->getServerRoles()));
    }

    private function configureTaskOnStage(Task $task, StageConfigurableInterface $taskConfiguration)
    {
        if (!$taskConfiguration->getStage()) {
            return;
        }

        $task->select('stage=' . $taskConfiguration->getStage()->getName());
    }

    private function configureServers(Configuration $config, string $stage, bool $reuseBrancher): void
    {
        foreach ($config->getStages() as $configStage) {
            if ($configStage->getName() !== $stage) {
                continue;
            }

            foreach ($configStage->getServers() as $server) {
                $this->configureStageServer($configStage, $server, $config, $reuseBrancher);
            }
        }
    }

    private function configureStageServer(Stage $stage, Server $server, Configuration $config, bool $reuseBrancher): void
    {
        $this->maybeConfigureBrancherServer($server, $reuseBrancher);

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

    private function maybeConfigureBrancherServer(Server $server, bool $reuseBrancher): void
    {
        $serverOptions = $server->getOptions();
        $isBrancher = $serverOptions[Server::OPTION_HN_BRANCHER] ?? false;
        $parentApp = $serverOptions[Server::OPTION_HN_PARENT_APP] ?? null;
        if ($isBrancher && $parentApp) {
            $settings = $serverOptions[Server::OPTION_HN_BRANCHER_SETTINGS] ?? [];
            $labels = $serverOptions[Server::OPTION_HN_BRANCHER_LABELS] ?? [];

            $this->log->info(sprintf('Creating an brancher Hypernode based on %s.', $parentApp));
            if ($settings) {
                $this->log->info(
                    sprintf('Settings to be applied: [%s].', implode(', ', $settings))
                );
            }
            if ($labels) {
                $this->log->info(
                    sprintf('Labels to be applied: [%s].', implode(', ', $labels))
                );
            }

            $data = $settings;
            $data['labels'] = $labels;
            if ($reuseBrancher && $brancherApp = $this->brancherHypernodeManager->reuseExistingBrancherHypernode($parentApp, $labels)) {
                $this->log->info(sprintf('Found existing brancher Hypernode, name is %s.', $brancherApp));
                $server->setHostname(sprintf("%s.hypernode.io", $brancherApp));
            } else {
                $brancherApp = $this->brancherHypernodeManager->createForHypernode($parentApp, $data);
                $this->log->info(sprintf('Successfully requested brancher Hypernode, name is %s.', $brancherApp));
                $server->setHostname(sprintf("%s.hypernode.io", $brancherApp));
                $this->brancherHypernodesRegistered[] = $brancherApp;

                try {
                    $this->log->info('Waiting for brancher Hypernode to become available...');
                    $this->brancherHypernodeManager->waitForAvailability($brancherApp);
                    $this->log->info('Brancher Hypernode has become available!');
                } catch (CreateBrancherHypernodeFailedException | TimeoutException $e) {
                    $this->brancherHypernodeManager->cancel($brancherApp);
                    throw $e;
                }
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

        pcntl_signal(SIGINT, function () {
            $this->log->warning("Received signal SIGINT, running fail jobs");
            // We don't have to do anything here. Underlying processes will receive the SIGINT signal as well
            // and that will cause the $exitCode below to be 255, which will cause the fail tasks to be run.
        });

        /**
         * Set the env variable to tell deployer to deploy the hosts sequentially instead of parallel.
         * @see \Deployer\Executor\Master::runTask()
         */
        putenv('DEPLOYER_LOCAL_WORKER=true');
        $exitCode = $executor->run($tasks, $hosts);

        if ($exitCode === 0) {
            $this->deployedHostnames = array_map(fn(Host $host) => $host->getHostname(), $hosts);
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

        $this->brancherHypernodeManager->cancel(...$this->brancherHypernodesRegistered);

        return $exitCode;
    }

    public function getDeploymentReport()
    {
        return new Report\Report(
            $this->deployedStage,
            $this->deployedHostnames,
            $this->brancherHypernodesRegistered
        );
    }
}
