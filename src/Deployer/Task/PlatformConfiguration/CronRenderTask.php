<?php

namespace Hypernode\Deploy\Deployer\Task\PlatformConfiguration;

use Hypernode\Deploy\Deployer\Task\IncrementedTaskTrait;
use Deployer\Task\Task;
use Hypernode\Deploy\Deployer\Task\ConfigurableTaskInterface;
use Hypernode\Deploy\Deployer\Task\TaskBase;
use Hypernode\DeployConfiguration\PlatformConfiguration\CronConfiguration;
use Hypernode\DeployConfiguration\TaskConfigurationInterface;
use Twig\Environment;
use Webmozart\Assert\Assert;

use function Deployer\get;
use function Deployer\set;
use function Deployer\task;

class CronRenderTask extends TaskBase implements ConfigurableTaskInterface
{
    use IncrementedTaskTrait;

    /**
     * @var Environment
     */
    private $twig;

    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }

    protected function getIncrementalNamePrefix(): string
    {
        return 'deploy:configuration:cron:render:';
    }

    public function supports(TaskConfigurationInterface $config): bool
    {
        return $config instanceof CronConfiguration;
    }

    public function render(string $newCronBlock): string
    {
        return $this->twig->load('cron.twig')->render(
            [
                'domain' => get("domain"),
                'crontab' => $newCronBlock,
            ]
        );
    }

    public function configureWithTaskConfig(TaskConfigurationInterface $config): ?Task
    {
        Assert::isInstanceOf($config, CronConfiguration::class);

        return task(
            "deploy:cron:render",
            function () use ($config) {
                $sourceFile = rtrim($config->getSourceFile(), '/');
                $newCronBlock = $this->render(file_get_contents($sourceFile));
                set("new_crontab", $newCronBlock);
            }
        );
    }
}
