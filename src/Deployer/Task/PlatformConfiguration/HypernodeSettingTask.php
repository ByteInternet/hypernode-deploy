<?php

namespace Hypernode\Deploy\Deployer\Task\PlatformConfiguration;

use Deployer\Task\Task;
use function Deployer\after;
use function Deployer\run;
use function Deployer\task;

use Hypernode\Deploy\Deployer\Task\ConfigurableTaskInterface;
use Hypernode\Deploy\Deployer\Task\IncrementedTaskTrait;
use Hypernode\DeployConfiguration\Configuration;
use Hypernode\DeployConfiguration\TaskConfigurationInterface;
use Hypernode\DeployConfiguration\PlatformConfiguration\HypernodeSettingConfiguration;

class HypernodeSettingTask implements ConfigurableTaskInterface
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
        after('deploy:prepare', 'deploy:hypernode:setting');
    }

    /**
     * @param TaskConfigurationInterface|HypernodeSettingConfiguration $config
     */
    public function build(TaskConfigurationInterface $config): ?Task
    {
        return task(
            "deploy:hypernode:setting",
            function () use ($config) {
                $attribute = $config->getAttribute();
                $value = $config->getValue();

                run("hypernode-systemctl settings {$attribute} {$value}");
            }
        );
    }

    public function configure(Configuration $config): void
    {
    }
}
