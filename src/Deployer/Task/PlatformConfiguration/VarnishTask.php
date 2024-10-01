<?php

namespace Hypernode\Deploy\Deployer\Task\PlatformConfiguration;

use Deployer\Task\Task;
use Hypernode\Deploy\Deployer\Task\ConfigurableTaskInterface;
use Hypernode\Deploy\Deployer\Task\IncrementedTaskTrait;
use Hypernode\Deploy\Deployer\Task\TaskBase;
use Hypernode\DeployConfiguration\PlatformConfiguration\VarnishConfiguration;
use Hypernode\DeployConfiguration\TaskConfigurationInterface;

use function Deployer\after;
use function Deployer\task;
use function Hypernode\Deploy\Deployer\before;

class VarnishTask extends TaskBase implements ConfigurableTaskInterface
{
    use IncrementedTaskTrait;

    private const TASK_NAME = 'deploy:varnish';

    protected function getIncrementalNamePrefix(): string
    {
        return 'deploy:configuration:varnish:';
    }

    public function supports(TaskConfigurationInterface $config): bool
    {
        return $config instanceof VarnishConfiguration;
    }

    public function configureWithTaskConfig(TaskConfigurationInterface $config): ?Task
    {
        $task = task('deploy:varnish', [
            'deploy:varnish:enable',
            'deploy:varnish:prepare',
            'deploy:varnish:upload',
            'deploy:varnish:sync',
            'deploy:varnish:load',
            'deploy:varnish:activate',
            'deploy:varnish:cleanup',
        ]);

        before('deploy:symlink', self::TASK_NAME);
        foreach ($this->getRegisteredTasks() as $taskName) {
            after('deploy:varnish:prepare', $taskName);
        }

        return $task;
    }
}
