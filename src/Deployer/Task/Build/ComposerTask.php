<?php

namespace Hypernode\Deploy\Deployer\Task\Build;

use Hypernode\Deploy\Deployer\Task\RegisterAfterInterface;
use Hypernode\DeployConfiguration\ServerRole;
use Deployer\Task\Task;
use Hypernode\Deploy\Deployer\RecipeLoader;
use Hypernode\Deploy\Deployer\Task\ConfigurableTaskInterface;
use Hypernode\DeployConfiguration\Command\Build\Composer;
use Hypernode\DeployConfiguration\Configuration;
use Hypernode\DeployConfiguration\TaskConfigurationInterface;

use function Deployer\before;
use function Deployer\get;
use function Deployer\run;
use function Deployer\set;
use function Deployer\task;
use function Deployer\test;

class ComposerTask implements ConfigurableTaskInterface
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

    /**
     * @param TaskConfigurationInterface|Composer $config
     */
    public function configureTask(TaskConfigurationInterface $config): void
    {
        set('bin/composer', get('bin/composer', 'composer2'));
        set('composer/install_arguments', implode(' ', $config->getInstallArguments()));
    }

    public function supports(TaskConfigurationInterface $config): bool
    {
        return $config instanceof Composer;
    }

    public function build(TaskConfigurationInterface $config): ?Task
    {
        return null;
    }
}
