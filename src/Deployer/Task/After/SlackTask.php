<?php

namespace Hypernode\Deploy\Deployer\Task\After;

use Deployer\Task\Task;
use Hypernode\Deploy\Deployer\RecipeLoader;
use Hypernode\Deploy\Deployer\Task\ConfigurableTaskInterface;
use Hypernode\Deploy\Deployer\Task\RegisterAfterInterface;
use Hypernode\DeployConfiguration\AfterDeployTask\SlackWebhook;
use Hypernode\DeployConfiguration\Command\Command;
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

    /**
     * LinkTask constructor.
     *
     * @param RecipeLoader $recipeLoader
     */
    public function __construct(RecipeLoader $recipeLoader)
    {
        $this->recipeLoader = $recipeLoader;
    }

    /**
     * Configure using hipex configuration command
     *
     * @param TaskConfigurationInterface|SlackWebhook $config
     *
     * @return void
     */
    public function configureTask(TaskConfigurationInterface $config)
    {
        $this->recipeLoader->load('slack.php');

        set('slack_webhook', $config->getWebHook());
        set('slack_text', '{{release_message}}');
    }

    public function supports(TaskConfigurationInterface $config): bool
    {
        return $config instanceof SlackWebhook;
    }

    /**
     * Define deployer task using Hipex configuration
     *
     * @param TaskConfigurationInterface $config
     * @return Task|null
     */
    public function build(TaskConfigurationInterface $config): ?Task
    {
        return null;
    }

    /**
     * Use this method to register your task after another task
     * i.e. after('taska', 'taskb')
     */
    public function registerAfter(): void
    {
        before('deploy', 'slack:notify');
        after('success', 'slack:notify:success');
        after('deploy:failed', 'slack:notify:failure');
    }

    /**
     * Configure using hipex configuration
     *
     * @param Configuration $config
     *
     * @return void
     */
    public function configure(Configuration $config)
    {
    }
}
