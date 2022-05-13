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
     * @param TaskConfigurationInterface $config
     * @return bool
     */
    public function supports(TaskConfigurationInterface $config): bool
    {
        return $config instanceof Cloudflare;
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
        after('deploy:symlink', 'deploy:cloudflare');
    }

    /**
     * Configure deployer using Hipex configuration
     *
     * @param TaskConfigurationInterface|Cloudflare $config
     *
     * @return void
     */
    public function configureTask(TaskConfigurationInterface $config)
    {
        $this->recipeLoader->load('cloudflare.php');

        set('cloudflare', [
            'service_key' => $config->getServiceKey(),
            'domain' => get('domain'),
        ]);
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
