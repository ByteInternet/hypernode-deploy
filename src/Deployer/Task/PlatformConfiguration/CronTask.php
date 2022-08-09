<?php

namespace Hypernode\Deploy\Deployer\Task\PlatformConfiguration;

use Hypernode\Deploy\Deployer\Task\IncrementedTaskTrait;
use Deployer\Task\Task;
use Hypernode\Deploy\Deployer\Task\ConfigurableTaskInterface;
use Hypernode\Deploy\Deployer\Task\RegisterAfterInterface;
use Hypernode\DeployConfiguration\Configuration;
use Hypernode\DeployConfiguration\PlatformConfiguration\CronConfiguration;
use Hypernode\DeployConfiguration\TaskConfigurationInterface;

use function Deployer\before;
use function Deployer\get;
use function Deployer\set;
use function Deployer\task;
use function Deployer\writeln;

class CronTask implements ConfigurableTaskInterface, RegisterAfterInterface
{
    use IncrementedTaskTrait;

    protected function getIncrementalNamePrefix(): string
    {
        return 'deploy:configuration:cron:';
    }

    public function configureTask(TaskConfigurationInterface $config): void
    {
    }

    public function supports(TaskConfigurationInterface $config): bool
    {
        return $config instanceof CronConfiguration;
    }

    public function registerAfter(): void
    {
        before('deploy:symlink', 'deploy:cron');
    }

    /**
     * @param TaskConfigurationInterface|CronConfiguration $config
     */
    public function build(TaskConfigurationInterface $config): ?Task
    {
        return null;
    }

    public function configure(Configuration $config): void
    {
        task('deploy:cron', [
            'deploy:cron:render',
            'deploy:cron:sync',
        ]);
    }
}
