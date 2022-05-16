<?php

namespace Hypernode\Deploy\Deployer\Task\After;

use Deployer\Task\Task;
use Hypernode\Deploy\Deployer\RecipeLoader;
use Hypernode\Deploy\Deployer\Task\ConfigurableTaskInterface;
use Hypernode\Deploy\Deployer\Task\RegisterAfterInterface;
use Hypernode\DeployConfiguration\AfterDeployTask\NewRelic;
use Hypernode\DeployConfiguration\Configuration;
use Hypernode\DeployConfiguration\TaskConfigurationInterface;

use function Hypernode\Deploy\Deployer\after;
use function Deployer\set;

class NewRelicTask implements ConfigurableTaskInterface, RegisterAfterInterface
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

    public function registerAfter(): void
    {
        after('deploy:symlink', 'newrelic:notify');
    }

    public function build(TaskConfigurationInterface $config): ?Task
    {
        return null;
    }

    /**
     * @param TaskConfigurationInterface|NewRelic $config
     */
    public function configureTask(TaskConfigurationInterface $config): void
    {
        $this->recipeLoader->load('newrelic.php');

        set('newrelic_app_id', $config->getAppId());
        set('newrelic_api_key', $config->getApiKey());
    }

    public function configure(Configuration $config): void
    {
    }
}
