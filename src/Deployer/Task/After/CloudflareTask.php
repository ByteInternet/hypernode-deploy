<?php

namespace Hypernode\Deploy\Deployer\Task\After;

use Deployer\Task\Task;
use Hypernode\Deploy\Deployer\RecipeLoader;
use Hypernode\Deploy\Deployer\Task\ConfigurableTaskInterface;
use Hypernode\Deploy\Deployer\Task\TaskBase;
use Hypernode\DeployConfiguration\AfterDeployTask\Cloudflare;
use Hypernode\DeployConfiguration\TaskConfigurationInterface;

use function Hypernode\Deploy\Deployer\after;
use function Deployer\get;
use function Deployer\set;

class CloudflareTask extends TaskBase implements ConfigurableTaskInterface
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
        return $config instanceof Cloudflare;
    }

    /**
     * @param TaskConfigurationInterface|Cloudflare $config
     */
    public function configureWithTaskConfig(TaskConfigurationInterface $config): ?Task
    {
        $this->recipeLoader->load('cloudflare.php');

        set('cloudflare', [
            'service_key' => $config->getServiceKey(),
            'domain' => get('domain'),
        ]);

        return null;
    }

    public function register(): void
    {
        after('deploy:symlink', 'deploy:cloudflare');
    }
}
