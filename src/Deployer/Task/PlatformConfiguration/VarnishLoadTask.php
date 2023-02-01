<?php

namespace Hypernode\Deploy\Deployer\Task\PlatformConfiguration;

use Deployer\Task\Task;
use Hypernode\Deploy\Deployer\Task\ConfigurableTaskInterface;
use Hypernode\Deploy\Deployer\Task\IncrementedTaskTrait;
use Hypernode\Deploy\Deployer\Task\TaskBase;
use Hypernode\DeployConfiguration\PlatformConfiguration\VarnishConfiguration;
use Hypernode\DeployConfiguration\TaskConfigurationInterface;

use function Deployer\fail;
use function Deployer\get;
use function Deployer\run;
use function Deployer\set;
use function Deployer\task;

class VarnishLoadTask extends TaskBase implements ConfigurableTaskInterface
{
    use IncrementedTaskTrait;

    private const TASK_NAME = 'deploy:varnish:load';

    protected function getIncrementalNamePrefix(): string
    {
        return 'deploy:configuration:varnish:load:';
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

        task(self::TASK_NAME, function () {
            $appName = get('domain');
            $appName = preg_replace('/[^a-zA-Z0-9-_]+/', '-', $appName);
            $appName = trim($appName, '-');

            $releaseName = get('release_name');
            $vclName = sprintf('varnish-%s-%s', $appName, $releaseName);
            set('varnish_vcl_name', $vclName);
            run('{{varnishadm_path}} vcl.load {{varnish_vcl_name}} {{varnish_release_path}}/varnish.vcl');
        });

        fail(self::TASK_NAME, 'deploy:varnish:cleanup');

        return null;
    }
}
