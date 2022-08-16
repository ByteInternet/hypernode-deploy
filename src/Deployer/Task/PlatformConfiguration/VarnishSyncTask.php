<?php

namespace Hypernode\Deploy\Deployer\Task\PlatformConfiguration;

use Hypernode\Deploy\Deployer\Task\IncrementedTaskTrait;
use Deployer\Task\Task;
use Hypernode\Deploy\Deployer\Task\ConfigurableTaskInterface;
use Hypernode\Deploy\Deployer\Task\TaskBase;
use Hypernode\DeployConfiguration\PlatformConfiguration\VarnishConfiguration;
use Hypernode\DeployConfiguration\TaskConfigurationInterface;

use function Deployer\set;
use function Deployer\fail;
use function Deployer\run;
use function Deployer\task;
use function Deployer\test;
use function Deployer\writeln;

class VarnishSyncTask extends TaskBase implements ConfigurableTaskInterface
{
    use IncrementedTaskTrait;

    private const TASK_NAME = 'deploy:varnish:sync';

    protected function getIncrementalNamePrefix(): string
    {
        return 'deploy:configuration:varnish:sync:';
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
        set('varnish/vcl_dir', function () use ($config) {
            return '/data/web/varnish/{{domain}}';
        });

        task(self::TASK_NAME, function () {
            if (!test('[ "$(test -d {{varnish/vcl_dir}})" ]')) {
                run("mkdir -p {{varnish/vcl_dir}}");
                writeln("Created varnish directory for {{domain}}");
            }

            if (test('[ "$(test -e {{varnish/vcl_dir}}/varnish.vcl)" ]')) {
                run("unlink {{varnish/vcl_dir}}/varnish.vcl");
                writeln("Removed varnish link with previous release to make place for the new symlink");
            }

            run("ln -sf {{varnish_current_path}}/varnish.vcl {{varnish/vcl_dir}}/varnish.vcl");
        });

        fail(self::TASK_NAME, 'deploy:varnish:cleanup');

        return null;
    }
}
