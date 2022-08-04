<?php

namespace Hypernode\Deploy\Deployer\Task\PlatformConfiguration;

use Deployer\Task\Task;
use function Deployer\fail;
use function Deployer\get;
use function Deployer\set;
use function Deployer\task;
use function Deployer\upload;
use function Deployer\writeln;

use Hypernode\Deploy\Deployer\Task\ConfigurableTaskInterface;
use Hypernode\Deploy\Deployer\Task\IncrementedTaskTrait;
use Hypernode\DeployConfiguration\Configuration;
use Hypernode\DeployConfiguration\TaskConfigurationInterface;
use Hypernode\DeployConfiguration\PlatformConfiguration\NginxConfiguration;

class NginxUploadTask implements ConfigurableTaskInterface
{
    use IncrementedTaskTrait;

    protected function getIncrementalNamePrefix(): string
    {
        return 'deploy:configuration:nginx:upload:';
    }

    public function configureTask(TaskConfigurationInterface $config): void
    {
    }

    public function supports(TaskConfigurationInterface $config): bool
    {
        return $config instanceof NginxConfiguration;
    }

    /**
     * @param TaskConfigurationInterface|NginxConfiguration $config
     */
    public function build(TaskConfigurationInterface $config): ?Task
    {
        return task(
            "deploy:nginx:upload",
            function () use ($config) {
                $sourceDir = rtrim($config->getSourceFolder(), '/');

                $args = [
                    '--archive',
                    '--recursive',
                    '--verbose',
                    '--ignore-errors',
                    '--copy-links'
                ];
                $args = array_map('escapeshellarg', $args);
                upload($sourceDir . '/', '{{nginx/config_path}}/', ['options' => $args]);
            }
        );
    }

    public function configure(Configuration $config): void
    {
        set('nginx/config_path', function () {
            return '/tmp/nginx-config-' . get('domain');
        });

        fail('deploy:nginx:upload', 'deploy:nginx:cleanup');
    }
}
