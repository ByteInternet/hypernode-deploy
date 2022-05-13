<?php

namespace Hypernode\Deploy\Deployer\Task\Build;

use Hypernode\Deploy\Deployer\RecipeLoader;
use Hypernode\Deploy\Deployer\Task\TaskInterface;
use Hypernode\DeployConfiguration\Configuration;

use function Deployer\task;

class BuildTask implements TaskInterface
{
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
     *
     * @return void
     */
    public function configure(Configuration $config)
    {
        $this->recipeLoader->load('deploy/info.php');

        task('build', [
            'deploy:info',
            'prepare:ssh',
            'build:compile',
            'build:package',
        ])
            ->onStage('build');
    }
}
