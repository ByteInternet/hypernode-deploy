<?php

namespace Hypernode\Deploy\Deployer\Task\PlatformConfiguration;

use Hypernode\Deploy\Deployer\Task\IncrementedTaskTrait;
use Deployer\Task\Task;
use Hypernode\Deploy\Deployer\Task\ConfigurableTaskInterface;
use Hypernode\Deploy\Deployer\Task\RegisterAfterInterface;
use Hypernode\DeployConfiguration\Configuration;
use Hypernode\DeployConfiguration\PlatformConfiguration\CronConfiguration;
use Hypernode\DeployConfiguration\TaskConfigurationInterface;

use function Deployer\get;
use function Deployer\set;
use function Deployer\upload;
use function Deployer\task;
use function Deployer\fail;

class CronUploadTask implements ConfigurableTaskInterface, RegisterAfterInterface
{
    use IncrementedTaskTrait;

    protected function getIncrementalNamePrefix(): string
    {
        return 'deploy:configuration:crontab:upload:';
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
    }

    /**
     * @param TaskConfigurationInterface|CronConfiguration $config
     */
    public function build(TaskConfigurationInterface $config): ?Task
    {
        return task(
            "deploy:cron:upload",
            function () use ($config) {
                $sourceDir = rtrim($config->getSourceFile(), '/');

                $args = [
                    '--archive',
                    '--recursive',
                    '--verbose',
                    '--ignore-errors',
                    '--copy-links'
                ];
                $args = array_map('escapeshellarg', $args);
                upload($sourceDir . '/', '{{cron/config_path}}/', ['options' => $args]);
            }
        );
    }

    public function configure(Configuration $config): void
    {
        set('cron/config_path', function () {
            return '/tmp/cron-config-' . get('domain');
        });
        fail('deploy:cron:prepare', 'deploy:cron:cleanup');
    }
}
