<?php

namespace Hypernode\Deploy\Deployer\Task\PlatformConfiguration;

use Deployer\Task\Task;
use Hypernode\Deploy\Deployer\Task\ConfigurableTaskInterface;
use Hypernode\Deploy\Deployer\Task\IncrementedTaskTrait;
use Hypernode\Deploy\Deployer\Task\TaskBase;
use Hypernode\DeployConfiguration\TaskConfigurationInterface;
use Hypernode\DeployConfiguration\PlatformConfiguration\HypernodeSettingConfiguration;
use Webmozart\Assert\Assert;

use function Deployer\after;
use function Deployer\run;
use function Deployer\task;

class HypernodeSettingTask extends TaskBase implements ConfigurableTaskInterface
{
    use IncrementedTaskTrait;

    protected function getIncrementalNamePrefix(): string
    {
        return 'deploy:configuration:hypernode:setting:';
    }

    public function supports(TaskConfigurationInterface $config): bool
    {
        return $config instanceof HypernodeSettingConfiguration;
    }

    public function configureWithTaskConfig(TaskConfigurationInterface $config): ?Task
    {
        Assert::isInstanceOf($config, HypernodeSettingConfiguration::class);
        $attribute = $config->getAttribute();
        $value = $config->getValue();
        $taskName = "deploy:hypernode:setting:{$attribute}";
        $task = task($taskName, function () use ($attribute, $value) {
            run("hypernode-systemctl settings {$attribute} {$value}");
        });
        after('deploy:setup', $taskName);
        return $task;
    }
}
