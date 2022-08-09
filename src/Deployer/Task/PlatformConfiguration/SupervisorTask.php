<?php

namespace Hypernode\Deploy\Deployer\Task\PlatformConfiguration;

use Hypernode\Deploy\Deployer\Task\IncrementedTaskTrait;
use Deployer\Task\Task;
use Hypernode\Deploy\Deployer\Task\ConfigurableTaskInterface;
use Hypernode\Deploy\Deployer\Task\RegisterAfterInterface;
use Hypernode\DeployConfiguration\Configuration;
use Hypernode\DeployConfiguration\PlatformConfiguration\SupervisorConfiguration;
use Hypernode\DeployConfiguration\TaskConfigurationInterface;

use function Deployer\after;
use function Deployer\get;
use function Deployer\set;
use function Deployer\task;
use function Hypernode\Deploy\Deployer\before;

class SupervisorTask implements ConfigurableTaskInterface, RegisterAfterInterface
{
    use IncrementedTaskTrait;

    protected function getIncrementalNamePrefix(): string
    {
        return 'deploy:configuration:supervisor:';
    }

    public function configureTask(TaskConfigurationInterface $config): void
    {
    }

    public function supports(TaskConfigurationInterface $config): bool
    {
        return $config instanceof SupervisorConfiguration;
    }

    public function registerAfter(): void
    {
        before('deploy:symlink', 'deploy:supervisor');
        foreach ($this->getRegisteredTasks() as $taskName) {
            after('deploy:supervisor:prepare', $taskName);
        }
    }

    public function build(TaskConfigurationInterface $config): ?Task
    {
        return null;
    }

    public function configure(Configuration $config): void
    {
        set('supervisor/config_path', function () {
            return '/tmp/supervisor-config-' . get('domain');
        });

        task('deploy:supervisor', [
            'deploy:supervisor:prepare',
            'deploy:supervisor:upload',
            'deploy:supervisor:sync',
            'deploy:supervisor:cleanup',
        ]);
    }
}
