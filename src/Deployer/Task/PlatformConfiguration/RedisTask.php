<?php

namespace Hypernode\Deploy\Deployer\Task\PlatformConfiguration;

use Hypernode\Deploy\Deployer\Task\IncrementedTaskTrait;
use Deployer\Task\Task;
use Hypernode\Deploy\Deployer\Task\ConfigurableTaskInterface;
use Hypernode\Deploy\Deployer\Task\TaskBase;
use Hypernode\DeployConfiguration\PlatformConfiguration\RedisConfiguration;
use Hypernode\DeployConfiguration\TaskConfigurationInterface;

use function Deployer\task;
use function Hypernode\Deploy\Deployer\before;

class RedisTask extends TaskBase implements ConfigurableTaskInterface
{
    use IncrementedTaskTrait;

    private const TASK_NAME = 'deploy:redis';

    protected function getIncrementalNamePrefix(): string
    {
        return 'deploy:configuration:redis:';
    }

    public function supports(TaskConfigurationInterface $config): bool
    {
        return $config instanceof RedisConfiguration;
    }

    public function configureWithTaskConfig(TaskConfigurationInterface $config): ?Task
    {
        task('deploy:redis', [
            'deploy:redis:enable',
        ]);

        before('deploy:symlink', self::TASK_NAME);
        return null;
    }
}
