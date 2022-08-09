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

    public function __construct(RecipeLoader $loader)
    {
        $this->recipeLoader = $loader;
    }

    public function configure(Configuration $config): void
    {
        $this->recipeLoader->load('deploy/info.php');

        task('build', [
            'deploy:info',
            'prepare:ssh',
            'build:compile',
            'build:package',
        ])->select("stage=build");
    }
}
