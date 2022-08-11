<?php

namespace Hypernode\Deploy\Deployer\Task\PlatformConfiguration;

use Hypernode\Deploy\Deployer\Task\IncrementedTaskTrait;
use Deployer\Task\Task;
use Hypernode\Deploy\Deployer\Task\ConfigurableTaskInterface;
use Hypernode\Deploy\Deployer\Task\TaskBase;
use Hypernode\DeployConfiguration\PlatformConfiguration\VarnishConfiguration;
use Hypernode\DeployConfiguration\TaskConfigurationInterface;

use function Deployer\get;
use function Deployer\set;
use function Deployer\run;
use function Deployer\task;

class VarnishCleanupTask extends TaskBase implements ConfigurableTaskInterface
{
    use IncrementedTaskTrait;

    protected function getIncrementalNamePrefix(): string
    {
        return 'deploy:configuration:varnish:cleanup:';
    }

    public function supports(TaskConfigurationInterface $config): bool
    {
        return $config instanceof VarnishConfiguration;
    }

    public function configureWithTaskConfig(TaskConfigurationInterface $config): ?Task
    {
        set('varnish/config_path', function () {
            return '/tmp/varnish-config-' . get('domain');
        });

        task($this->getTaskName(), function () {
            run('if [ -d {{varnish/config_path}} ]; then rm -Rf {{varnish/config_path}}; fi');
        });

        return null;
    }
}
