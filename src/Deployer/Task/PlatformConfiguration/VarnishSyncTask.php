<?php

namespace Hypernode\Deploy\Deployer\Task\PlatformConfiguration;

use Hypernode\Deploy\Deployer\Task\IncrementedTaskTrait;
use Deployer\Task\Task;
use Hypernode\Deploy\Deployer\Task\ConfigurableTaskInterface;
use Hypernode\Deploy\Deployer\Task\RegisterAfterInterface;
use Hypernode\DeployConfiguration\Configuration;
use Hypernode\DeployConfiguration\PlatformConfiguration\VarnishConfiguration;
use Hypernode\DeployConfiguration\TaskConfigurationInterface;

use function Deployer\set;
use function Deployer\after;
use function Deployer\fail;
use function Deployer\run;
use function Deployer\task;
use function Deployer\test;
use function Deployer\writeln;

class VarnishSyncTask implements ConfigurableTaskInterface, RegisterAfterInterface
{
    use IncrementedTaskTrait;

    protected function getIncrementalNamePrefix(): string
    {
        return 'deploy:configuration:varnish:sync:';
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
        set('varnish/vcl_dir', function () {
            return '/data/web/varnish/{{domain}}';
        });

        task($this->getTaskName(), function () {
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
        after($this->getTaskName(), 'deploy:varnish:reload');
        fail($this->getTaskName(), 'deploy:varnish:cleanup');
    }
}
