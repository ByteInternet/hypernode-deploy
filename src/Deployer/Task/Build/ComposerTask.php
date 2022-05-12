<?php

namespace Hypernode\Deploy\Deployer\Task\Build;

use Hypernode\Deploy\Deployer\Task\RegisterAfterInterface;
use Hypernode\DeployConfiguration\ServerRole;
use function Deployer\before;
use function Deployer\run;
use function Deployer\set;
use Deployer\Task\Task;
use Hypernode\Deploy\Deployer\RecipeLoader;
use Hypernode\Deploy\Deployer\Task\ConfigurableTaskInterface;
use Hypernode\DeployConfiguration\Command\Build\Composer;
use Hypernode\DeployConfiguration\Configuration;
use Hypernode\DeployConfiguration\TaskConfigurationInterface;
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
