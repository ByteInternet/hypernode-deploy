<?php

namespace Hypernode\Deploy\Deployer\Task\PlatformConfiguration;

use Deployer\Task\Task;
use Hypernode\Deploy\Deployer\Task\IncrementedTaskTrait;
use Hypernode\Deploy\Deployer\Task\ConfigurableTaskInterface;
use Hypernode\Deploy\Deployer\Task\TaskBase;
use Hypernode\DeployConfiguration\PlatformConfiguration\SupervisorConfiguration;
use Hypernode\DeployConfiguration\TaskConfigurationInterface;

use function Deployer\get;
use function Deployer\set;
use function Deployer\run;
use function Deployer\fail;
use function Deployer\task;

class SupervisorPrepareTask extends TaskBase implements ConfigurableTaskInterface
{
    use IncrementedTaskTrait;

    protected function getIncrementalNamePrefix(): string
    {
        return 'deploy:configuration:supervisor:prepare:';
    }

    public function supports(TaskConfigurationInterface $config): bool
    {
        return $config instanceof SupervisorConfiguration;
    }

    public function configureWithTaskConfig(TaskConfigurationInterface $config): ?Task
    {
        set('supervisor/config_path', function () {
            return '/tmp/supervisor-config-' . get('domain');
        });

        task('deploy:supervisor:prepare', function () {
            run('if [ -d {{supervisor/config_path}} ]; then rm -Rf {{supervisor/config_path}}; fi');
            run('mkdir -p {{supervisor/config_path}}');
        });
        fail('deploy:supervisor:prepare', 'deploy:supervisor:cleanup');

        return null;
    }
}
