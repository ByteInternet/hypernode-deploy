<?php

namespace Hypernode\Deploy\Deployer\Task\PlatformConfiguration;

use Hypernode\Deploy\Deployer\Task\IncrementedTaskTrait;
use Deployer\Task\Task;
use Hypernode\Deploy\Deployer\Task\ConfigurableTaskInterface;
use Hypernode\Deploy\Deployer\Task\RegisterAfterInterface;
use Hypernode\DeployConfiguration\Configuration;
use Hypernode\DeployConfiguration\PlatformConfiguration\SupervisorConfiguration;
use Hypernode\DeployConfiguration\TaskConfigurationInterface;

use function Deployer\fail;
use function Deployer\get;
use function Deployer\run;
use function Deployer\set;
use function Deployer\task;
use function Deployer\upload;

class SupervisorUploadTask implements ConfigurableTaskInterface, RegisterAfterInterface
{
    use IncrementedTaskTrait;

    protected function getIncrementalNamePrefix(): string
    {
        return 'deploy:configuration:supervisor:upload:';
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
    }

    /**
     * @param TaskConfigurationInterface|SupervisorConfiguration $config
     */
    public function build(TaskConfigurationInterface $config): ?Task
    {
        return task(
            "deploy:supervisor:upload",
            function () use ($config) {
                $sourceDir = rtrim($config->getSourceFolder(), '/');

                $args = [
                    '--archive',
                    '--recursive',
                    '--verbose',
                    '--ignore-errors',
                    '--copy-links'
                ];
                upload($sourceDir . '/', '{{supervisor/config_path}}/');
                $args = implode(' ', array_map('escapeshellarg', $args));
                run("rsync {$args} {{supervisor/config_path}}/ {{supervisor_release_path}}/");
            }
        );
    }

    public function configure(Configuration $config): void
    {
        set('supervisor/config_path', function () {
            return '/tmp/supervisor-config-' . get('domain');
        });

        fail('deploy:supervisor:upload', 'deploy:supervisor:cleanup');
    }
}
