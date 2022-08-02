<?php

namespace Hypernode\Deploy\Deployer\Task\PlatformConfiguration;

use Deployer\Task\Task;
use function Deployer\fail;
use function Deployer\get;
use function Deployer\run;
use function Deployer\set;
use function Deployer\task;
use function Deployer\test;
use function Deployer\upload;
use function Deployer\writeln;

use Hypernode\Deploy\Deployer\Task\ConfigurableTaskInterface;
use Hypernode\Deploy\Deployer\Task\IncrementedTaskTrait;
use Hypernode\DeployConfiguration\Configuration;
use Hypernode\DeployConfiguration\TaskConfigurationInterface;
use Hypernode\DeployConfiguration\PlatformConfiguration\NginxConfiguration;

class NginxUploadTask implements ConfigurableTaskInterface
{
    use IncrementedTaskTrait;

    protected function getIncrementalNamePrefix(): string
    {
        return 'deploy:configuration:nginx:upload:';
    }

    public function configureTask(TaskConfigurationInterface $config): void
    {
    }

    public function supports(TaskConfigurationInterface $config): bool
    {
        return $config instanceof NginxConfiguration;
    }

    /**
     * @param TaskConfigurationInterface|NginxConfiguration $config
     */
    public function build(TaskConfigurationInterface $config): ?Task
    {
        $taskName = $this->getTaskName();
        return task(
            $taskName,
            function () use ($config) {
                $sourceDir = rtrim($config->getSourceFolder(), '/');

                $args = [
                    '--archive',
                    '--recursive',
                    '--verbose',
                    '--ignore-errors',
                    '--copy-links'
                ];
                $args = array_map('escapeshellarg', $args);
                upload($sourceDir . '/', '{{nginx/config_path}}/', ['options' => $args]);
            }
        )->onRoles($config->getServerRoles());
    }

    public function configure(Configuration $config): void
    {
        set('nginx/config_path', function () {
            return '/tmp/nginx-config-' . get('hostname');
        });

        task('deploy:nginx:upload', function () {
            if (!test('[ "$(ls -A {{nginx/config_path}})" ]')) {
                writeln('No nginx configuration defined.');
                return;
            }

            $args = [
                '-azP',
                '--archive',
                '--recursive',
                '--verbose',
                '--ignore-errors',
                '--copy-links',
                '--filter=- scope-http/rate-limit.nginx.conf',
                '--filter=+ **/*.nginx*',
                '--filter=+ */',
                '--filter=- **',
                '--delete',
            ];
            $args = implode(' ', array_map('escapeshellarg', $args));
            run("rsync {$args} {{nginx/config_path}}/ /data/web/{{hostname}}/");
        });
        fail('deploy:nginx:upload', 'deploy:nginx:cleanup');
    }
}
