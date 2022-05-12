<?php

namespace Hypernode\Deploy\Deployer\Task\PlatformConfiguration;

use Hypernode\Deploy\Deployer\Task\IncrementedTaskTrait;
use Hypernode\DeployConfiguration\Exception\ConfigurationException;
use function Deployer\after;
use function Deployer\fail;
use function Deployer\get;
use function Deployer\run;
use function Deployer\runLocally;
use function Deployer\set;
use function Deployer\task;
use function Deployer\test;
use function Deployer\upload;
use Deployer\Task\Task;
use function Deployer\writeln;
use function Hypernode\Deploy\Deployer\before;
use Hypernode\Deploy\Deployer\Task\ConfigurableTaskInterface;
use Hypernode\Deploy\Deployer\Task\RegisterAfterInterface;
use Hypernode\DeployConfiguration\Configuration;
use Hypernode\DeployConfiguration\PlatformConfiguration\NginxConfiguration;
use Hypernode\DeployConfiguration\TaskConfigurationInterface;

class NginxTask implements ConfigurableTaskInterface, RegisterAfterInterface
{
    use IncrementedTaskTrait;

    /**
     * @return string
     */
    protected function getIncrementalNamePrefix(): string
    {
        return 'deploy:configuration:nginx:';
    }

    /**
     * @param TaskConfigurationInterface|NginxConfiguration $config
     */
    public function configureTask(TaskConfigurationInterface $config): void
    {
    }

    /**
     * @param TaskConfigurationInterface $config
     * @return bool
     */
    public function supports(TaskConfigurationInterface $config): bool
    {
        return $config instanceof NginxConfiguration;
    }

    /**
     * Use this method to register your task after another task
     * i.e. after('taska', 'taskb')
     */
    public function registerAfter(): void
    {
        before('deploy:symlink', 'deploy:nginx');
        foreach ($this->getRegisteredTasks() as $taskName) {
            after('deploy:nginx:prepare', $taskName);
        }
    }

    /**
     * Define deployer task using Hipex configuration
     *
     * @param TaskConfigurationInterface|NginxConfiguration $config
     * @return Task|null
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


    /**
     * Configure using hipex configuration
     *
     * @param Configuration $config
     */
    public function configure(Configuration $config)
    {
        set('nginx/config_path', function () {
            return '/tmp/nginx-config-' . get('hostname');
        });

        task('deploy:nginx:prepare', function () {
            run('if [ -d {{nginx/config_path}} ]; then rm -Rf {{nginx/config_path}}; fi');
            run('mkdir -p {{nginx/config_path}}');
        });

        task('deploy:nginx:cleanup', function () {
            run('if [ -d {{nginx/config_path}} ]; then rm -Rf {{nginx/config_path}}; fi');
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
            run("rsync {$args} {{nginx/config_path}}/ {{domain_path}}/var/etc/");
        });

        task('deploy:nginx', [
            'deploy:nginx:prepare',
            'deploy:nginx:upload',
            'deploy:nginx:cleanup',
        ]);
        fail('deploy:nginx:prepare', 'deploy:nginx:cleanup');
        fail('deploy:nginx:upload', 'deploy:nginx:cleanup');
    }
}
