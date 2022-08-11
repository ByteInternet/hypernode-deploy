<?php

namespace Hypernode\Deploy\Deployer\Task\Deploy;

use Hypernode\Deploy\Deployer\RecipeLoader;
use Hypernode\Deploy\Deployer\Task\TaskBase;
use Hypernode\DeployConfiguration\Configuration;

use function Deployer\get;
use function Deployer\set;

class DeploySharedTaskGlobal extends TaskBase
{
    /**
     * @var RecipeLoader
     */
    private $recipeLoader;

    public function __construct(RecipeLoader $recipeLoader)
    {
        $this->recipeLoader = $recipeLoader;
    }

    public function configure(Configuration $config): void
    {
        $this->recipeLoader->load('deploy/shared.php');

        set('shared_files', function () {
            return get('configured/shared_files');
        });

        set('shared_dirs', function () {
            return get('configured/shared_folders');
        });

        set('configured/shared_files', array_map('strval', $config->getSharedFiles()));
        set('configured/shared_folders', array_map('strval', $config->getSharedFolders()));
    }
}
