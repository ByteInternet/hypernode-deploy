<?php


namespace Hypernode\Deploy\Deployer\Task\PlatformConfiguration;

use Deployer\Task\Task;
use Hypernode\Deploy\Deployer\Task\ConfigurableTaskInterface;
use Hypernode\Deploy\Deployer\Task\IncrementedTaskTrait;
use Hypernode\Deploy\Deployer\Task\RegisterAfterInterface;
use Hypernode\DeployConfiguration\Configuration;
use Hypernode\DeployConfiguration\PlatformConfiguration\VarnishConfiguration;
use Hypernode\DeployConfiguration\TaskConfigurationInterface;

use function Deployer\get;
use function Deployer\set;
use function Deployer\run;
use function Deployer\task;
use function Deployer\fail;

class VarnishPrepareTask implements ConfigurableTaskInterface, RegisterAfterInterface
{
    use IncrementedTaskTrait;

    protected function getIncrementalNamePrefix(): string
    {
        return 'deploy:configuration:varnish:prepare:';
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
        set('varnish/config_path', function () {
            return '/tmp/varnish-config-' . get('domain');
        });

        task($this->getTaskName(), function () {
            run('if [ -d {{varnish/config_path}} ]; then rm -Rf {{varnish/config_path}}; fi');
            run('mkdir -p {{varnish/config_path}}');
        });

        fail($this->getTaskName(), 'deploy:varnish:cleanup');
    }
}
