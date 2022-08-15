<?php

namespace Hypernode\Deploy\Deployer\Task\Build;

use Hypernode\Deploy\Deployer\Task\TaskBase;
use Hypernode\DeployConfiguration\ServerRole;
use Deployer\Task\Task;
use Hypernode\Deploy\Deployer\RecipeLoader;
use Hypernode\Deploy\Deployer\Task\ConfigurableTaskInterface;
use Hypernode\DeployConfiguration\Command\Build\Composer;
use Hypernode\DeployConfiguration\Configuration;
use Hypernode\DeployConfiguration\TaskConfigurationInterface;

use function Deployer\set;

class ComposerTask extends TaskBase implements ConfigurableTaskInterface
{
    /**
     * @var RecipeLoader
     */
    private $recipeLoader;

    public function __construct(RecipeLoader $loader)
    {
        $this->recipeLoader = $loader;
    }

    public function configure(Configuration $config): void
    {
        $this->recipeLoader->load('composer.php');
    }

    public function supports(TaskConfigurationInterface $config): bool
    {
        return $config instanceof Composer;
    }

    /**
     * @param TaskConfigurationInterface|Composer $config
     */
    public function configureWithTaskConfig(TaskConfigurationInterface $config): ?Task
    {
        set('composer/install_arguments', implode(' ', $config->getInstallArguments()));

        return null;
    }
}
