<?php

namespace Hypernode\Deploy\Deployer\Task\After;

use Deployer\Task\Task;
use Hypernode\Deploy\Deployer\RecipeLoader;
use Hypernode\Deploy\Deployer\Task\ConfigurableTaskInterface;
use Hypernode\Deploy\Deployer\Task\RegisterAfterInterface;
use Hypernode\DeployConfiguration\AfterDeployTask\SlackWebhook;
use Hypernode\DeployConfiguration\Configuration;
use Hypernode\DeployConfiguration\TaskConfigurationInterface;

use function Hypernode\Deploy\Deployer\after;
use function Hypernode\Deploy\Deployer\before;
use function Deployer\set;

class SlackTask implements ConfigurableTaskInterface, RegisterAfterInterface
{
    /**
     * @var RecipeLoader
     */
    private $recipeLoader;

    public function __construct(RecipeLoader $recipeLoader)
    {
        $this->recipeLoader = $recipeLoader;
    }

    /**
     * @param TaskConfigurationInterface|SlackWebhook $config
     */
    public function configureTask(TaskConfigurationInterface $config): void
    {
        $this->recipeLoader->load('slack.php');

        set('slack_webhook', $config->getWebHook());
        set('slack_text', '{{release_message}}');
    }

    public function supports(TaskConfigurationInterface $config): bool
    {
        return $config instanceof SlackWebhook;
    }

    public function build(TaskConfigurationInterface $config): ?Task
    {
        return null;
    }

    public function registerAfter(): void
    {
        before('deploy', 'slack:notify');
        after('success', 'slack:notify:success');
        after('deploy:failed', 'slack:notify:failure');
    }

    public function configure(Configuration $config): void
    {
    }
}
