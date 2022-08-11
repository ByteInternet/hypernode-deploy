<?php

namespace Hypernode\Deploy\Deployer\Task\After;

use Deployer\Task\Task;
use Hypernode\Deploy\Deployer\RecipeLoader;
use Hypernode\Deploy\Deployer\Task\TaskBase;
use Hypernode\DeployConfiguration\AfterDeployTask\NewRelic;
use Hypernode\DeployConfiguration\TaskConfigurationInterface;

use function Hypernode\Deploy\Deployer\after;
use function Deployer\set;

class NewRelicTask extends TaskBase
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
        return $config instanceof NewRelic;
    }

    /**
     * @param TaskConfigurationInterface|NewRelic $config
     */
    public function configureWithTaskConfig(TaskConfigurationInterface $config): ?Task
    {
        $this->recipeLoader->load('newrelic.php');

        set('newrelic_app_id', $config->getAppId());
        set('newrelic_api_key', $config->getApiKey());

        return null;
    }

    public function register(): void
    {
        after('deploy:symlink', 'newrelic:notify');
    }
}
