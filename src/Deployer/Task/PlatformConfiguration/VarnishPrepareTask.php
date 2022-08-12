<?php

namespace Hypernode\Deploy\Deployer\Task\PlatformConfiguration;

use Deployer\Task\Task;
use Hypernode\Deploy\Deployer\Task\ConfigurableTaskInterface;
use Hypernode\Deploy\Deployer\Task\IncrementedTaskTrait;
use Hypernode\Deploy\Deployer\Task\TaskBase;
use Hypernode\DeployConfiguration\PlatformConfiguration\VarnishConfiguration;
use Hypernode\DeployConfiguration\TaskConfigurationInterface;

use function Deployer\get;
use function Deployer\set;
use function Deployer\run;
use function Deployer\task;
use function Deployer\fail;

class VarnishPrepareTask extends TaskBase implements ConfigurableTaskInterface
{
    use IncrementedTaskTrait;

    private const TASK_NAME = 'deploy:varnish:prepare:';

    protected function getIncrementalNamePrefix(): string
    {
        return 'deploy:configuration:varnish:prepare:';
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

        task(self::TASK_NAME, function () {
            run('if [ -d {{varnish/config_path}} ]; then rm -Rf {{varnish/config_path}}; fi');
            run('mkdir -p {{varnish/config_path}}');
        });

        fail(self::TASK_NAME, 'deploy:varnish:cleanup');

        return null;
    }
}
