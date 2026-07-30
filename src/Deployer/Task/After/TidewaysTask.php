<?php

namespace Hypernode\Deploy\Deployer\Task\After;

use Deployer\Task\Task;
use Hypernode\DeployConfiguration\RecipeLoader;
use Hypernode\Deploy\Deployer\Task\ConfigurableTaskInterface;
use Hypernode\Deploy\Deployer\Task\TaskBase;
use Hypernode\DeployConfiguration\AfterDeployTask\Tideways;
use Hypernode\DeployConfiguration\TaskConfigurationInterface;

use function Hypernode\Deploy\Deployer\after;
use function Deployer\set;

class TidewaysTask extends TaskBase implements ConfigurableTaskInterface
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
        return $config instanceof Tideways;
    }

    /**
     * @param TaskConfigurationInterface|Tideways $config
     */
    public function configureWithTaskConfig(TaskConfigurationInterface $config): ?Task
    {
        $this->recipeLoader->loadRecipe('tideways.php');

        set('tideways', [
            'api_key' => $config->getApiKey(),
            'name' => $config->getName(),
            'name_prefix' => $config->getNamePrefix(),
            'type' => $config->getType(),
            'description' => $config->getDescription(),
            'environment' => $config->getEnvironment(),
            'service' => $config->getService(),
            'compareAfterMinutes' => $config->getCompareAfterMinutes(),
        ]);

        return null;
    }

    public function register(): void
    {
        after('deploy', 'deploy:tideways');
    }
}
