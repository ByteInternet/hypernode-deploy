<?php

namespace Hypernode\Deploy\Deployer\Task\After;

use Deployer\Task\Task;
use Hypernode\DeployConfiguration\RecipeLoader;
use Hypernode\Deploy\Deployer\Task\ConfigurableTaskInterface;
use Hypernode\Deploy\Deployer\Task\TaskBase;
use Hypernode\DeployConfiguration\AfterDeployTask\Rumvision;
use Hypernode\DeployConfiguration\TaskConfigurationInterface;

use function Hypernode\Deploy\Deployer\after;
use function Deployer\set;

class RumvisionTask extends TaskBase implements ConfigurableTaskInterface
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
        return $config instanceof Rumvision;
    }

    /**
     * @param TaskConfigurationInterface|Rumvision $config
     */
    public function configureWithTaskConfig(TaskConfigurationInterface $config): ?Task
    {
        $this->recipeLoader->loadRecipe('rumvision.php');

        set('rumvision', [
            'api_key' => $config->getApiKey(),
            'domains' => $config->getDomains(),
            'title' => $config->getTitle(),
            'name_prefix' => $config->getNamePrefix(),
            'description' => $config->getDescription(),
            'url_scope' => $config->getUrlScope(),
            'url_category_ids' => $config->getUrlCategoryIds(),
            'impact_at' => $config->getImpactAt(),
            'category_slug' => $config->getCategorySlug(),
        ]);

        return null;
    }

    public function register(): void
    {
        after('deploy', 'deploy:rumvision');
    }
}
