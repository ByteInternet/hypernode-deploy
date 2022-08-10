<?php

namespace Hypernode\Deploy\Deployer\Task\PlatformConfiguration;

use Deployer\Task\Task;
use function Deployer\after;
use function Deployer\run;
use function Deployer\task;

use Hypernode\Deploy\Deployer\Task\ConfigurableTaskInterface;
use Hypernode\Deploy\Deployer\Task\IncrementedTaskTrait;
use Hypernode\Deploy\Deployer\Task\RegisterAfterInterface;
use Hypernode\DeployConfiguration\Configuration;
use Hypernode\DeployConfiguration\TaskConfigurationInterface;
use Hypernode\DeployConfiguration\PlatformConfiguration\HypernodeSettingConfiguration;

class HypernodeSettingTask implements ConfigurableTaskInterface, RegisterAfterInterface
{
    use IncrementedTaskTrait;

    protected function getIncrementalNamePrefix(): string
    {
        return 'deploy:configuration:hypernode:setting:';
    }

    public function configureTask(TaskConfigurationInterface $config): void
    {
    }

    public function supports(TaskConfigurationInterface $config): bool
    {
        return $config instanceof HypernodeSettingConfiguration;
    }

    public function registerAfter(): void
    {
        after('deploy:setup', 'deploy:hypernode:setting');
    }

    /**
     * @param TaskConfigurationInterface|HypernodeSettingConfiguration $config
     */
    public function build(TaskConfigurationInterface $config): ?Task
    {
        return null;
    }

    public function configure(Configuration $config): void
    {
        $tasks = [];
        foreach ($config->getPlatformConfigurations() as $platformConfiguration) {
            if ($platformConfiguration instanceof HypernodeSettingConfiguration) {
                $attribute = $platformConfiguration->getAttribute();
                $value = $platformConfiguration->getValue();
                $task = task("deploy:hypernode:setting:{$attribute}", function () use ($attribute, $value) {
                    run("hypernode-systemctl settings {$attribute} {$value}");
                });
                $tasks[] = $task->getName();
            }
        }
        task('deploy:hypernode:setting', $tasks);
    }
}
