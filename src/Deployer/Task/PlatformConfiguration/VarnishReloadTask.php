<?php

namespace Hypernode\Deploy\Deployer\Task\PlatformConfiguration;

use Hypernode\Deploy\Deployer\Task\IncrementedTaskTrait;
use Deployer\Task\Task;
use Hypernode\Deploy\Deployer\Task\ConfigurableTaskInterface;
use Hypernode\Deploy\Deployer\Task\TaskBase;
use Hypernode\DeployConfiguration\PlatformConfiguration\VarnishConfiguration;
use Hypernode\DeployConfiguration\TaskConfigurationInterface;

use function Deployer\fail;
use function Deployer\run;
use function Deployer\set;
use function Deployer\task;

class VarnishReloadTask extends TaskBase implements ConfigurableTaskInterface
{
    use IncrementedTaskTrait;

    protected function getIncrementalNamePrefix(): string
    {
        return 'deploy:configuration:varnish:reload:';
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
        set('varnishadm_path', function () use ($config) {
            if ($config->useSupervisor()) {
                return "varnishadm -n /data/var/run/app/varnish/{{domain}}.{{release_name}}";
            }
            return "varnishadm";
        });

        set('varnish/vcl', function () {
            return '/data/web/varnish/{{domain}}/varnish.vcl';
        });

        task($this->getTaskName(), function () {
            run('{{varnishadm_path}} vcl.load {{domain}}.{{release_name}}_varnish {{varnish/vcl}}');
        });

        fail($this->getTaskName(), 'deploy:varnish:cleanup');

        return null;
    }
}
