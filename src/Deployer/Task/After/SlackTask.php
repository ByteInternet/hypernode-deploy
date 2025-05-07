<?php

namespace Hypernode\Deploy\Deployer\Task\After;

use Deployer\Task\Task;
use Hypernode\Deploy\Deployer\RecipeLoader;
use Hypernode\Deploy\Deployer\Task\ConfigurableTaskInterface;
use Hypernode\Deploy\Deployer\Task\TaskBase;
use Hypernode\DeployConfiguration\AfterDeployTask\SlackWebhook;
use Hypernode\DeployConfiguration\TaskConfigurationInterface;

use function Hypernode\Deploy\Deployer\after;
use function Hypernode\Deploy\Deployer\before;
use function Deployer\set;

class SlackTask extends TaskBase implements ConfigurableTaskInterface
{
    /**
     * @var RecipeLoader
     */
    private $recipeLoader;

    public function __construct(RecipeLoader $recipeLoader)
    {
        $this->recipeLoader = $recipeLoader;
    }

    public function supports(TaskConfigurationInterface $config): bool
    {
        return $config instanceof SlackWebhook;
    }

    /**
     * @param TaskConfigurationInterface|SlackWebhook $config
     */
    public function configureWithTaskConfig(TaskConfigurationInterface $config): ?Task
    {
        $this->recipeLoader->load('../contrib/slack.php');

        set('slack_webhook', $config->getWebHook());
        set('slack_text', '{{release_message}}');

        return null;
    }

    public function register(): void
    {
        before('deploy', 'slack:notify');
        after('deploy:success', 'slack:notify:success');
        after('deploy:failed', 'slack:notify:failure');
    }
}
