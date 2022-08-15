<?php

namespace Hypernode\Deploy\Deployer\Task\PlatformConfiguration;

use Hypernode\Deploy\Deployer\Task\IncrementedTaskTrait;
use Deployer\Task\Task;
use Hypernode\Deploy\Deployer\Task\ConfigurableTaskInterface;
use Hypernode\Deploy\Deployer\Task\TaskBase;
use Hypernode\DeployConfiguration\Configuration;
use Hypernode\DeployConfiguration\PlatformConfiguration\SupervisorConfiguration;
use Hypernode\DeployConfiguration\TaskConfigurationInterface;

use function Deployer\after;
use function Deployer\fail;
use function Deployer\run;
use function Deployer\task;
use function Deployer\test;
use function Deployer\writeln;

class SupervisorSyncTask extends TaskBase implements ConfigurableTaskInterface
{
    use IncrementedTaskTrait;

    protected function getIncrementalNamePrefix(): string
    {
        return 'deploy:configuration:supervisor:sync:';
    }

    public function supports(TaskConfigurationInterface $config): bool
    {
        return $config instanceof SupervisorConfiguration;
    }

    public function configureWithTaskConfig(TaskConfigurationInterface $config): ?Task
    {
        task('deploy:supervisor:sync', function () {
            if (!test('[ "$(ls -A {{supervisor/config_path}})" ]')) {
                writeln('No supervisor configuration defined.');
                return;
            }

            run("mkdir -p ~/supervisor");
            if (test('[ "$(test -d ~/supervisor/{{domain}})" ]')) {
                if (!test('[ "$(rmdir ~/supervisor/{{domain}})" ]')) {
                    throw new \RuntimeException(
                        'Found a non-empty Supervisor directory. Please remove it before deploying.'
                    );
                }
                writeln("Removed empty supervisor directory to make place for the new symlink");
            }

            run("ln -sf {{supervisor_current_path}} ~/supervisor/{{domain}}");
        });

        fail('deploy:supervisor:sync', 'deploy:supervisor:cleanup');

        after('deploy:supervisor:sync', 'deploy:supervisor:reload');

        return null;
    }
}
