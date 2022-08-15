<?php

namespace Hypernode\Deploy\Deployer\Task\PlatformConfiguration;

use Hypernode\Deploy\Deployer\Task\IncrementedTaskTrait;
use Deployer\Task\Task;
use Hypernode\Deploy\Deployer\Task\ConfigurableTaskInterface;
use Hypernode\Deploy\Deployer\Task\TaskBase;
use Hypernode\DeployConfiguration\PlatformConfiguration\NginxConfiguration;
use Hypernode\DeployConfiguration\TaskConfigurationInterface;

use function Deployer\after;
use function Deployer\get;
use function Deployer\set;
use function Deployer\task;
use function Hypernode\Deploy\Deployer\before;

class NginxTask extends TaskBase implements ConfigurableTaskInterface
{
    use IncrementedTaskTrait;

    protected function getIncrementalNamePrefix(): string
    {
        return 'deploy:configuration:nginx:';
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

        task('deploy:nginx', [
            'deploy:nginx:prepare',
            'deploy:nginx:manage_vhost',
            'deploy:nginx:upload',
            'deploy:nginx:sync',
            'deploy:nginx:cleanup',
        ]);

        before('deploy:symlink', 'deploy:nginx');
        foreach ($this->getRegisteredTasks() as $taskName) {
            after('deploy:nginx:prepare', $taskName);
        }

        return null;
    }
}
