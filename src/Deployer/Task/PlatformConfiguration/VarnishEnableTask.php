<?php

namespace Hypernode\Deploy\Deployer\Task\PlatformConfiguration;

use Deployer\Task\Task;
use Hypernode\Deploy\Deployer\Task\ConfigurableTaskInterface;
use Hypernode\Deploy\Deployer\Task\IncrementedTaskTrait;
use Hypernode\Deploy\Deployer\Task\RegisterAfterInterface;
use Hypernode\DeployConfiguration\Configuration;
use Hypernode\DeployConfiguration\PlatformConfiguration\VarnishConfiguration;
use Hypernode\DeployConfiguration\TaskConfigurationInterface;

use function Deployer\run;
use function Deployer\task;
use function Deployer\fail;

class VarnishEnableTask implements ConfigurableTaskInterface, RegisterAfterInterface
{
    use IncrementedTaskTrait;

    protected function getIncrementalNamePrefix(): string
    {
        return 'deploy:configuration:varnish:enable:';
    }

    public function configureTask(TaskConfigurationInterface $config): void
    {
    }

    public function supports(TaskConfigurationInterface $config): bool
    {
        return $config instanceof VarnishConfiguration;
    }

    public function registerAfter(): void
    {
    }

    public function build(TaskConfigurationInterface $config): ?Task
    {
        return task($this->getTaskName(), function () use ($config) {
            run("hypernode-systemctl settings varnish_version {$config->getVersion()}");
            run("hypernode-systemctl settings varnish_enabled true");
        });
    }

    public function configure(Configuration $config): void
    {
        task($this->getTaskName(), function () {
            run('hypernode-manage-vhosts {{domain}} --varnish');
        });

        fail($this->getTaskName(), 'deploy:varnish:cleanup');
    }
}
