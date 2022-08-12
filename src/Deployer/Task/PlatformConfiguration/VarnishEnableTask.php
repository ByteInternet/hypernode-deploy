<?php

namespace Hypernode\Deploy\Deployer\Task\PlatformConfiguration;

use Deployer\Task\Task;
use Hypernode\Deploy\Deployer\Task\ConfigurableTaskInterface;
use Hypernode\Deploy\Deployer\Task\IncrementedTaskTrait;
use Hypernode\Deploy\Deployer\Task\TaskBase;
use Hypernode\DeployConfiguration\PlatformConfiguration\VarnishConfiguration;
use Hypernode\DeployConfiguration\TaskConfigurationInterface;

use function Deployer\run;
use function Deployer\task;
use function Deployer\fail;

class VarnishEnableTask extends TaskBase implements ConfigurableTaskInterface
{
    use IncrementedTaskTrait;

    private const TASK_NAME = 'deploy:varnish:enable:';

    protected function getIncrementalNamePrefix(): string
    {
        return 'deploy:configuration:varnish:enable:';
    }

    public function supports(TaskConfigurationInterface $config): bool
    {
        return $config instanceof VarnishConfiguration;
    }

    /**
     * @param TaskConfigurationInterface|VarnishConfiguration $config
     */
    public function configureWithTaskConfig(TaskConfigurationInterface $config): ?Task
    {
        if ($config->useSupervisor()) {
            task(self::TASK_NAME, function () use ($config) {
                run("hypernode-systemctl settings varnish_version {$config->getVersion()} --block");
                run("hypernode-systemctl settings supervisor_enabled True --block");
                run("hypernode-manage-supervisor {{domain}}.{{release_name}} --service varnish --ram {$config->getMemory()}");
            });
        } else {
            task(self::TASK_NAME, function () use ($config) {
                run("hypernode-systemctl settings varnish_enabled True --block");
                run("hypernode-systemctl settings varnish_version {$config->getVersion()} --block");
                run('hypernode-manage-vhosts {{domain}} --varnish');
            });
        }
        fail(self::TASK_NAME, 'deploy:varnish:cleanup');

        return null;
    }
}
