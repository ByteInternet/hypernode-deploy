<?php

namespace Hypernode\Deploy\Deployer\Task\PlatformConfiguration;

use Hypernode\Deploy\Deployer\Task\IncrementedTaskTrait;
use Deployer\Task\Task;
use Hypernode\Deploy\Deployer\Task\ConfigurableTaskInterface;
use Hypernode\Deploy\Deployer\Task\TaskBase;
use Hypernode\DeployConfiguration\PlatformConfiguration\VarnishConfiguration;
use Hypernode\DeployConfiguration\TaskConfigurationInterface;

use function Deployer\fail;
use function Deployer\task;
use function Deployer\upload;

class VarnishUploadTask extends TaskBase implements ConfigurableTaskInterface
{
    use IncrementedTaskTrait;

    protected function getIncrementalNamePrefix(): string
    {
        return 'deploy:configuration:varnish:upload:';
    }

    public function supports(TaskConfigurationInterface $config): bool
    {
        return $config instanceof VarnishConfiguration;
    }

    /**
     * @param TaskConfigurationInterface|VarnishConfiguration $config
     */
    public function configureWithTaskConfig(TaskConfigurationInterface $config): ?Task
    {
        task($this->getTaskName(), function () use ($config) {
            upload($config->getConfigFile(), "{{varnish_release_path}}/varnish.vcl");
        });

        fail($this->getTaskName(), 'deploy:varnish:cleanup');

        return null;
    }
}
