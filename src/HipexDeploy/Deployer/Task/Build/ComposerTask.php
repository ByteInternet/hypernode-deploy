<?php
/**
 * @author Hipex <info@hipex.io>
 * @copyright (c) Hipex B.V. 2018
 */

namespace HipexDeploy\Deployer\Task\Build;

use HipexDeploy\Deployer\Task\RegisterAfterInterface;
use HipexDeployConfiguration\ServerRole;
use function Deployer\before;
use function Deployer\run;
use function Deployer\set;
use Deployer\Task\Task;
use HipexDeploy\Deployer\RecipeLoader;
use HipexDeploy\Deployer\Task\ConfigurableTaskInterface;
use HipexDeployConfiguration\Command\Build\Composer;
use HipexDeployConfiguration\Configuration;
use HipexDeployConfiguration\TaskConfigurationInterface;
use function Deployer\task;
use function Deployer\test;

class ComposerTask implements ConfigurableTaskInterface
{
    /**
     * Setup composer
     */
    private const TASK_NAME = 'build:composer';

    /**
     * @var RecipeLoader
     */
    private $recipeLoader;

    /**
     * DeployTask constructor.
     *
     * @param RecipeLoader $loader
     */
    public function __construct(RecipeLoader $loader)
    {
        $this->recipeLoader = $loader;
    }

    /**
     * Configure using hipex configuration
     *
     * @param Configuration $config
     */
    public function configure(Configuration $config)
    {
        $this->recipeLoader->load('composer.php');
    }

    /**
     * {@inheritDoc}
     *
     * @param TaskConfigurationInterface|Composer $config
     */
    public function configureTask(TaskConfigurationInterface $config)
    {
        set('bin/composer', 'composer');
        set('composer/install_arguments', implode(' ', $config->getInstallArguments()));
    }

    /**
     * @param TaskConfigurationInterface $config
     * @return bool
     */
    public function supports(TaskConfigurationInterface $config): bool
    {
        return $config instanceof Composer;
    }

    /**
     * @param TaskConfigurationInterface $config
     * @return Task|null
     */
    public function build(TaskConfigurationInterface $config): ?Task
    {
        return null;
    }
}
