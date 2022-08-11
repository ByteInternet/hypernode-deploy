<?php

namespace Hypernode\Deploy\Deployer\Task\PlatformConfiguration;

use Hypernode\Deploy\Deployer\Task\IncrementedTaskTrait;
use Deployer\Task\Task;
use Hypernode\Deploy\Deployer\Task\ConfigurableTaskInterface;
use Hypernode\Deploy\Deployer\Task\TaskBase;
use Hypernode\DeployConfiguration\Configuration;
use Hypernode\DeployConfiguration\PlatformConfiguration\CronConfiguration;
use Hypernode\DeployConfiguration\TaskConfigurationInterface;

use function Deployer\before;
use function Deployer\get;
use function Deployer\set;
use function Deployer\task;
use function Deployer\writeln;

class CronTask extends TaskBase implements ConfigurableTaskInterface
{
    use IncrementedTaskTrait;

    protected function getIncrementalNamePrefix(): string
    {
        return 'deploy:configuration:cron:';
    }

    public function configureWithTaskConfig(TaskConfigurationInterface $config): ?Task
    {
        task('deploy:cron', [
            'deploy:cron:render',
            'deploy:cron:sync',
        ]);

        before('deploy:symlink', 'deploy:cron');

        return null;
    }

    public function supports(TaskConfigurationInterface $config): bool
    {
        return $config instanceof CronConfiguration;
    }
}
