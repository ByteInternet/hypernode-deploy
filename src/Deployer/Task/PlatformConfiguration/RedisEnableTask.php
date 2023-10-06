<?php

namespace Hypernode\Deploy\Deployer\Task\PlatformConfiguration;

use Deployer\Task\Task;
use Hypernode\Deploy\Deployer\Task\ConfigurableTaskInterface;
use Hypernode\Deploy\Deployer\Task\IncrementedTaskTrait;
use Hypernode\Deploy\Deployer\Task\TaskBase;
use Hypernode\DeployConfiguration\PlatformConfiguration\RedisConfiguration;
use Hypernode\DeployConfiguration\TaskConfigurationInterface;

use function Deployer\run;
use function Deployer\task;
use function Deployer\fail;

class RedisEnableTask extends TaskBase implements ConfigurableTaskInterface
{
    use IncrementedTaskTrait;

    private const TASK_NAME = 'deploy:redis:enable';

    protected function getIncrementalNamePrefix(): string
    {
        return 'deploy:configuration:redis:enable:';
    }

    public function supports(TaskConfigurationInterface $config): bool
    {
        return $config instanceof RedisConfiguration;
    }

    /**
     * @param TaskConfigurationInterface|RedisConfiguration $config
     */
    public function configureWithTaskConfig(TaskConfigurationInterface $config): ?Task
    {
        if ($config->useSupervisor()) {
            task(self::TASK_NAME, function () use ($config) {
                run("yes | hypernode-systemctl settings redis_version {$config->getVersion()} --block");
                run("hypernode-systemctl settings supervisor_enabled True --block");

                $instance = $config->getPersistence() ? 'redis-persistent' : 'redis';
                run("hypernode-manage-supervisor {{domain}}.{{release_name}} --service {$instance} --ram {$config->getMemory()}");
            });
        } else {
            task(self::TASK_NAME, function () use ($config) {
                run("yes | hypernode-systemctl settings redis_version {$config->getVersion()} --block");
                if ($config->getPersistence()) {
                    run("hypernode-systemctl settings redis_persistent_instance --value True --block");
                }
            });
        }
        fail(self::TASK_NAME, 'deploy:redis:cleanup');

        return null;
    }
}
