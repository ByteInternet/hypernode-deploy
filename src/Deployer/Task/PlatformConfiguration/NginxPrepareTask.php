<?php

namespace Hypernode\Deploy\Deployer\Task\PlatformConfiguration;

use Hypernode\Deploy\Deployer\Task\IncrementedTaskTrait;
use Deployer\Task\Task;
use Hypernode\Deploy\Deployer\Task\ConfigurableTaskInterface;
use Hypernode\Deploy\Deployer\Task\TaskBase;
use Hypernode\DeployConfiguration\Configuration;
use Hypernode\DeployConfiguration\PlatformConfiguration\NginxConfiguration;
use Hypernode\DeployConfiguration\TaskConfigurationInterface;

use function Deployer\fail;
use function Deployer\get;
use function Deployer\run;
use function Deployer\set;
use function Deployer\task;

class NginxPrepareTask extends TaskBase implements ConfigurableTaskInterface
{
    use IncrementedTaskTrait;

    protected function getIncrementalNamePrefix(): string
    {
        return 'deploy:configuration:nginx:prepare:';
    }

    public function supports(TaskConfigurationInterface $config): bool
    {
        return $config instanceof NginxConfiguration;
    }

    public function configureWithTaskConfig(TaskConfigurationInterface $config): ?Task
    {
        set('nginx/config_path', function () {
            return '/tmp/nginx-config-' . get('domain');
        });

        task('deploy:nginx:prepare', function () {
            run('if [ -d {{nginx/config_path}} ]; then rm -Rf {{nginx/config_path}}; fi');
            run('mkdir -p {{nginx/config_path}}');
        });
        fail('deploy:nginx:prepare', 'deploy:nginx:cleanup');

        return null;
    }
}
