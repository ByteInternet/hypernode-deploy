<?php

namespace Hypernode\Deploy\Deployer\Task\PlatformConfiguration;

use Hypernode\Deploy\Deployer\Task\IncrementedTaskTrait;
use Deployer\Task\Task;
use Hypernode\Deploy\Deployer\Task\ConfigurableTaskInterface;
use Hypernode\Deploy\Deployer\Task\TaskBase;
use Hypernode\DeployConfiguration\PlatformConfiguration\SupervisorConfiguration;
use Hypernode\DeployConfiguration\TaskConfigurationInterface;

use function Deployer\fail;
use function Deployer\get;
use function Deployer\run;
use function Deployer\set;
use function Deployer\task;
use function Deployer\upload;

class SupervisorUploadTask extends TaskBase implements ConfigurableTaskInterface
{
    use IncrementedTaskTrait;

    protected function getIncrementalNamePrefix(): string
    {
        return 'deploy:configuration:supervisor:upload:';
    }

    public function supports(TaskConfigurationInterface $config): bool
    {
        return $config instanceof SupervisorConfiguration;
    }

    /**
     * @param TaskConfigurationInterface|SupervisorConfiguration $config
     */
    public function configureWithTaskConfig(TaskConfigurationInterface $config): ?Task
    {
        set('supervisor/config_path', function () {
            return '/tmp/supervisor-config-' . get('domain');
        });

        fail('deploy:supervisor:upload', 'deploy:supervisor:cleanup');

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
                run("mkdir -p {{supervisor_release_path}}");
                run("rsync {$args} {{supervisor/config_path}}/ {{supervisor_release_path}}/");
            }
        );
    }
}
