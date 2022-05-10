<?php
/**
 * @author Hipex <info@hipex.io>
 * @copyright (c) Hipex B.V. 2018
 */

namespace HipexDeploy\Deployer\Task\After;

use function HipexDeploy\Deployer\after;
use function Deployer\set;
use Deployer\Task\Task;
use HipexDeploy\Deployer\RecipeLoader;
use HipexDeploy\Deployer\Task\ConfigurableTaskInterface;
use HipexDeploy\Deployer\Task\RegisterAfterInterface;
use HipexDeployConfiguration\AfterDeployTask\NewRelic;
use HipexDeployConfiguration\Command\Command;
use HipexDeployConfiguration\Configuration;
use HipexDeployConfiguration\TaskConfigurationInterface;

class NewRelicTask implements ConfigurableTaskInterface, RegisterAfterInterface
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
     * @param Command $config
     * @return bool
     */
    public function supports(TaskConfigurationInterface $config): bool
    {
        return $config instanceof NewRelic;
    }

    /**
     * Use this method to register your task after another task
     * i.e. after('taska', 'taskb')
     */
    public function registerAfter(): void
    {
        after('deploy:symlink', 'newrelic:notify');
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
     * Configure deployer using Hipex configuration
     *
     * @param TaskConfigurationInterface|NewRelic $config
     */
    public function configureTask(TaskConfigurationInterface $config)
    {
        $this->recipeLoader->load('newrelic.php');

        set('newrelic_app_id', $config->getAppId());
        set('newrelic_api_key', $config->getApiKey());
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
