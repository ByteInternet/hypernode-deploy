<?php

namespace Hypernode\Deploy\Deployer\Task\PlatformConfiguration;

use Deployer\Task\Task;
use Hypernode\Deploy\Deployer\Task\ConfigurableTaskInterface;
use Hypernode\Deploy\Deployer\Task\IncrementedTaskTrait;
use Hypernode\Deploy\Deployer\Task\RegisterAfterInterface;
use Hypernode\DeployConfiguration\Configuration;
use Hypernode\DeployConfiguration\PlatformConfiguration\SupervisorConfiguration;
use Hypernode\DeployConfiguration\Stage;
use Hypernode\DeployConfiguration\TaskConfigurationInterface;

use function Deployer\before;
use function Deployer\fail;
use function Deployer\get;
use function Deployer\run;
use function Deployer\set;
use function Deployer\task;
use function Deployer\test;
use function Deployer\upload;
use function Deployer\writeln;
use function Hypernode\Deploy\Deployer\after;

class SupervisorTask implements ConfigurableTaskInterface, RegisterAfterInterface
{
    use IncrementedTaskTrait;

    protected function getIncrementalNamePrefix(): string
    {
        return 'deploy:configuration:supervisor:';
    }

    public function configureTask(TaskConfigurationInterface $config): void
    {
    }

    public function supports(TaskConfigurationInterface $config): bool
    {
        return $config instanceof SupervisorConfiguration;
    }

    public function registerAfter(): void
    {
        before('deploy:symlink', 'deploy:configuration:supervisor');
        foreach ($this->getRegisteredTasks() as $taskName) {
            after('deploy:supervisor:prepare', $taskName);
        }
        before('deploy:after', 'deploy:supervisor-reload');
    }

    /**
     * @param TaskConfigurationInterface|SupervisorConfiguration $config
     */
    public function build(TaskConfigurationInterface $config): ?Task
    {
        $taskName = $this->getTaskName();

        return task(
            $taskName,
            function () use ($config) {
                $args = [
                    '--archive',
                    '--recursive',
                    '--verbose',
                    '--ignore-errors',
                    '--copy-links'
                ];
                $args = array_map('escapeshellarg', $args);
                upload(rtrim($config->getSourceFolder(), '/') . '/', '{{supervisor/config_path}}/', ['options' => $args]);
            }
        )->onRoles($config->getServerRoles());
    }

    public function configure(Configuration $config): void
    {
        // @TODO(timon): what's the use case for SupervisorTask? if it's for DaaS, we can disable it
        set('supervisor/config_path', function () {
            return '/tmp/supervisor-config-' . get('hostname');
        });

        task('deploy:supervisor:prepare', function () {
            run('if [ -d {{supervisor/config_path}} ]; then rm -Rf {{supervisor/config_path}}; fi');
            run('mkdir -p {{supervisor/config_path}}');
        });

        task('deploy:supervisor:cleanup', function () {
            run('if [ -d {{supervisor/config_path}} ]; then rm -Rf {{supervisor/config_path}}; fi');
        });

        task('deploy:supervisor:upload', function () {
            if (!test('[ "$(ls -A {{supervisor/config_path}})" ]')) {
                writeln('No supervisor configuration defined.');
                return;
            }

            $args = [
                '-azP',
                '--delete',
                '--ignore-errors',
                '--copy-links'
            ];
            $args = implode(' ', array_map('escapeshellarg', $args));
            run("rsync {$args} {{supervisor/config_path}}/ ~/supervisor/supervisor.d/deployed");
        });

        task('deploy:configuration:supervisor', [
            'deploy:supervisor:prepare',
            'deploy:supervisor:upload',
            'deploy:supervisor:cleanup',
        ]);
        fail('deploy:supervisor:prepare', 'deploy:supervisor:cleanup');
        fail('deploy:supervisor:upload', 'deploy:supervisor:cleanup');

        task('deploy:supervisor-reload', function () {
            /** @var Stage $stage */
            $stage = get('configuration_stage');
            run('/usr/bin/supervisorctl reread');
            run('/usr/bin/supervisorctl update');
        });
    }
}
