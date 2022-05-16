<?php

namespace Hypernode\Deploy\Deployer\Task\After;

use Deployer\Task\Task;
use Hypernode\Deploy\Deployer\RecipeLoader;
use Hypernode\Deploy\Deployer\Task\ConfigurableTaskInterface;
use Hypernode\Deploy\Deployer\Task\RegisterAfterInterface;
use Hypernode\DeployConfiguration\AfterDeployTask\Cloudflare;
use Hypernode\DeployConfiguration\Configuration;
use Hypernode\DeployConfiguration\TaskConfigurationInterface;

use function Hypernode\Deploy\Deployer\after;
use function Deployer\get;
use function Deployer\set;

class CloudflareTask implements ConfigurableTaskInterface, RegisterAfterInterface
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

    public function build(TaskConfigurationInterface $config): ?Task
    {
        return null;
    }

    public function registerAfter(): void
    {
        after('deploy:symlink', 'deploy:cloudflare');
    }

    /**
     * @param TaskConfigurationInterface|Cloudflare $config
     */
    public function configureTask(TaskConfigurationInterface $config): void
    {
        $this->recipeLoader->load('cloudflare.php');

        set('cloudflare', [
            'service_key' => $config->getServiceKey(),
            'domain' => get('domain'),
        ]);
    }

    public function configure(Configuration $config): void
    {
    }
}
