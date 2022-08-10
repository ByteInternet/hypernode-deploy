<?php

namespace Hypernode\Deploy\Deployer\Task\PlatformConfiguration;

use Hypernode\Deploy\Deployer\Task\IncrementedTaskTrait;
use Deployer\Task\Task;
use Hypernode\Deploy\Deployer\Task\ConfigurableTaskInterface;
use Hypernode\Deploy\Deployer\Task\RegisterAfterInterface;
use Hypernode\DeployConfiguration\Configuration;
use Hypernode\DeployConfiguration\PlatformConfiguration\CronConfiguration;
use Hypernode\DeployConfiguration\TaskConfigurationInterface;
use Twig\Environment;
use Webmozart\Assert\Assert;

use function Deployer\get;
use function Deployer\set;
use function Deployer\run;
use function Deployer\fail;
use function Deployer\task;
use function Deployer\writeln;

class CronRenderTask implements ConfigurableTaskInterface, RegisterAfterInterface
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

    public function configureTask(TaskConfigurationInterface $config): void
    {
    }

    public function supports(TaskConfigurationInterface $config): bool
    {
        return $config instanceof CronConfiguration;
    }

    public function registerAfter(): void
    {
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

    public function build(TaskConfigurationInterface $config): ?Task
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

    public function configure(Configuration $config): void
    {
    }
}
