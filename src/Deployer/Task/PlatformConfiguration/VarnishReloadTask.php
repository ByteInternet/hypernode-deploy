<?php

namespace Hypernode\Deploy\Deployer\Task\PlatformConfiguration;

use Hypernode\Deploy\Deployer\Task\IncrementedTaskTrait;
use Deployer\Task\Task;
use Hypernode\Deploy\Deployer\Task\ConfigurableTaskInterface;
use Hypernode\Deploy\Deployer\Task\RegisterAfterInterface;
use Hypernode\DeployConfiguration\Configuration;
use Hypernode\DeployConfiguration\PlatformConfiguration\VarnishConfiguration;
use Hypernode\DeployConfiguration\TaskConfigurationInterface;

use function Deployer\fail;
use function Deployer\run;
use function Deployer\set;
use function Deployer\task;

class VarnishReloadTask implements ConfigurableTaskInterface, RegisterAfterInterface
{
    use IncrementedTaskTrait;

    protected function getIncrementalNamePrefix(): string
    {
        return 'deploy:configuration:varnish:reload:';
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
        return null;
    }

    public function configure(Configuration $config): void
    {
        set('varnish/vcl', function () {
            return '/data/web/varnish/{{domain}}/varnish.vcl';
        });

        task($this->getTaskName(), function () {
            run('varnishadm vcl.load {{domain}}_varnish {{varnish/vcl}}');
            run('varnishadm vcl.use {{domain}}_varnish');
        });

        fail($this->getTaskName(), 'deploy:varnish:cleanup');
    }
}
