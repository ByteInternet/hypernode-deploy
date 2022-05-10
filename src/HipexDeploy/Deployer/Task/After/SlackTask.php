<?php
/**
 * @author Hipex <info@hipex.io>
 * @copyright (c) Hipex B.V. 2018
 */

namespace HipexDeploy\Deployer\Task\After;

use function HipexDeploy\Deployer\after;
use function HipexDeploy\Deployer\before;
use function Deployer\set;
use Deployer\Task\Task;
use HipexDeploy\Deployer\RecipeLoader;
use HipexDeploy\Deployer\Task\ConfigurableTaskInterface;
use HipexDeploy\Deployer\Task\RegisterAfterInterface;
use HipexDeployConfiguration\AfterDeployTask\SlackWebhook;
use HipexDeployConfiguration\Command\Command;
use HipexDeployConfiguration\Configuration;
use HipexDeployConfiguration\TaskConfigurationInterface;

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
     */
    public function configureTask(TaskConfigurationInterface $config)
    {
        $this->recipeLoader->load('slack.php');

        set('slack_webhook', $config->getWebHook());
        set('slack_text', '{{release_message}}');
    }

    /**
     * @param Command $config
     * @return bool
     */
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
     */
    public function configure(Configuration $config)
    {
    }
}
