<?php
/**
 * @author Hipex <info@hipex.io>
 * @copyright (c) Hipex B.V. 2018
 */

namespace HipexDeploy\Deployer\Task\After;

use function HipexDeploy\Deployer\after;
use function Deployer\get;
use function Deployer\set;
use Deployer\Task\Task;
use HipexDeploy\Deployer\RecipeLoader;
use HipexDeploy\Deployer\Task\ConfigurableTaskInterface;
use HipexDeploy\Deployer\Task\RegisterAfterInterface;
use HipexDeployConfiguration\AfterDeployTask\Cloudflare;
use HipexDeployConfiguration\Configuration;
use HipexDeployConfiguration\TaskConfigurationInterface;

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
     */
    public function configure(Configuration $config)
    {
    }
}
