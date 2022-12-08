<?php

namespace Hypernode\Deploy\Deployer\Task\PlatformConfiguration;

use Deployer\Task\Task;
use Hypernode\Deploy\Deployer\Task\ConfigurableTaskInterface;
use Hypernode\Deploy\Deployer\Task\IncrementedTaskTrait;
use Hypernode\Deploy\Deployer\Task\TaskBase;
use Hypernode\DeployConfiguration\Configuration;
use Hypernode\DeployConfiguration\TaskConfigurationInterface;
use Hypernode\DeployConfiguration\PlatformConfiguration\NginxConfiguration;

use function Deployer\after;
use function Deployer\fail;
use function Deployer\get;
use function Deployer\run;
use function Deployer\set;
use function Deployer\task;
use function Deployer\test;
use function Deployer\writeln;

class NginxSyncTask extends TaskBase implements ConfigurableTaskInterface
{
    use IncrementedTaskTrait;

    protected function getIncrementalNamePrefix(): string
    {
        return 'deploy:configuration:nginx:sync:';
    }

    public function supports(TaskConfigurationInterface $config): bool
    {
        return $config instanceof NginxConfiguration;
    }

    public function configureWithTaskConfig(TaskConfigurationInterface $config): ?Task
    {
        set('nginx/config_path', function () {
            return '/tmp/nginx-config-' . get('domain');
        });

        task('deploy:nginx:sync', function () {
            if (!test('[ "$(ls -A {{nginx/config_path}})" ]')) {
                writeln('No nginx configuration defined.');
                return;
            }
            if (test('test ! -L /data/web/nginx/{{domain}}')) {
                writeln('/data/web/nginx/{{domain}} is not a symlink. Removing this.');
                // TODO: Raise error instead of removing dir?
                run('rm -Rf /data/web/nginx/{{domain}}');
            }

            $args = [
                '-azP',
                '--recursive',
                '--verbose',
                '--ignore-errors',
                '--copy-links',
                '--delete',
            ];
            $args = implode(' ', array_map('escapeshellarg', $args));
            run("mkdir -p {{nginx_release_path}}");
            run("rsync {$args} {{nginx/config_path}}/ {{nginx_release_path}}/");
            run("ln -sf {{nginx_current_path}} /data/web/nginx/{{domain}}");
        });
        fail('deploy:nginx:sync', 'deploy:nginx:cleanup');

        return null;
    }
}
