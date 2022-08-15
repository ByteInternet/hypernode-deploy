<?php

namespace Hypernode\Deploy\Deployer\Task\PlatformConfiguration;

use Deployer\Task\Task;
use Hypernode\Deploy\Deployer\Task\ConfigurableTaskInterface;
use Hypernode\Deploy\Deployer\Task\IncrementedTaskTrait;
use Hypernode\Deploy\Deployer\Task\TaskBase;
use Hypernode\DeployConfiguration\Configuration;
use Hypernode\DeployConfiguration\TaskConfigurationInterface;
use Hypernode\DeployConfiguration\PlatformConfiguration\NginxConfiguration;
use Webmozart\Assert\Assert;

use function Deployer\fail;
use function Deployer\get;
use function Deployer\set;
use function Deployer\task;
use function Deployer\upload;

class NginxUploadTask extends TaskBase implements ConfigurableTaskInterface
{
    use IncrementedTaskTrait;

    protected function getIncrementalNamePrefix(): string
    {
        return 'deploy:configuration:nginx:upload:';
    }

    public function supports(TaskConfigurationInterface $config): bool
    {
        return $config instanceof NginxConfiguration;
    }

    public function configureWithTaskConfig(TaskConfigurationInterface $config): ?Task
    {
        Assert::isInstanceOf($config, NginxConfiguration::class);

        set('nginx/config_path', function () {
            return '/tmp/nginx-config-' . get('domain');
        });

        fail('deploy:nginx:upload', 'deploy:nginx:cleanup');

        return task(
            "deploy:nginx:upload",
            function () use ($config) {
                $sourceDir = rtrim($config->getSourceFolder(), '/');
                upload($sourceDir . '/', '{{nginx/config_path}}/');
            }
        );
    }
}
