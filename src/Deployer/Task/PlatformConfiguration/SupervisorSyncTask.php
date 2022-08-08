<?php

namespace Hypernode\Deploy\Deployer\Task\PlatformConfiguration;

use Hypernode\Deploy\Deployer\Task\IncrementedTaskTrait;
use Deployer\Task\Task;
use Hypernode\Deploy\Deployer\Task\ConfigurableTaskInterface;
use Hypernode\Deploy\Deployer\Task\RegisterAfterInterface;
use Hypernode\DeployConfiguration\Configuration;
use Hypernode\DeployConfiguration\PlatformConfiguration\SupervisorConfiguration;
use Hypernode\DeployConfiguration\TaskConfigurationInterface;

use function Deployer\after;
use function Deployer\fail;
use function Deployer\run;
use function Deployer\task;
use function Deployer\test;
use function Deployer\writeln;

class SupervisorSyncTask implements ConfigurableTaskInterface, RegisterAfterInterface
{
    use IncrementedTaskTrait;

    protected function getIncrementalNamePrefix(): string
    {
        return 'deploy:configuration:supervisor:sync:';
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
    }

    /**
     * @param TaskConfigurationInterface|NginxConfiguration $config
     */
    public function build(TaskConfigurationInterface $config): ?Task
    {
        return null;
    }

    public function configure(Configuration $config): void
    {
        task('deploy:supervisor:sync', function () {
            if (!test('[ "$(ls -A {{supervisor/config_path}})" ]')) {
                writeln('No supervisor configuration defined.');
                return;
            }

            run("mkdir -p ~/supervisor");
            if (test('[ "$(test -d ~/supervisor/{{domain}})" ]')) {
                if (!test('[ "$(rmdir ~/supervisor/{{domain}})" ]')) {
                    throw new \RuntimeException('Found a non-empty Supervisor directory. Please remove it before deploying.');
                }
                writeln("Removed empty supervisor directory to make place for the new symlink");
            }

            run("ln -sf {{supervisor_current_path}} ~/supervisor/{{domain}}");
        });
        after('deploy:supervisor:sync', 'deploy:supervisor:reload');
        fail('deploy:supervisor:sync', 'deploy:supervisor:cleanup');
    }
}
