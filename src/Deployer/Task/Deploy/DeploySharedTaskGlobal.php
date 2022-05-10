<?php

/**
 * @author Hypernode
 * @copyright Copyright (c) Hypernode
 */

namespace Hypernode\Deploy\Deployer\Task\Deploy;

use function Deployer\get;
use function Deployer\set;
use Hypernode\Deploy\Deployer\RecipeLoader;

use Hypernode\Deploy\Deployer\Task\TaskInterface;
use Hypernode\DeployConfiguration\Configuration;

class DeploySharedTaskGlobal implements TaskInterface
{
    /**
     * @var RecipeLoader
     */
    private $recipeLoader;

    /**
     * DeploySharedTask constructor.
     *
     * @param RecipeLoader $recipeLoader
     */
    public function __construct(RecipeLoader $recipeLoader)
    {
        $this->recipeLoader = $recipeLoader;
    }

    /**
     * {@inheritdoc}
     */
    public function configure(Configuration $config)
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
