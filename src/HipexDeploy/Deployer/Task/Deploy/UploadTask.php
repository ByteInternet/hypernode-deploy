<?php
/**
 * @author Hipex <info@hipex.io>
 * @copyright (c) Hipex B.V. 2018
 */

namespace HipexDeploy\Deployer\Task\Deploy;

use HipexDeployConfiguration\ServerRole;
use function Deployer\fail;
use function Deployer\task;
use HipexDeploy\Deployer\RecipeLoader;
use HipexDeploy\Deployer\Task\TaskInterface;
use HipexDeployConfiguration\Configuration;

class UploadTask implements TaskInterface
{
    /**
     * @var RecipeLoader
     */
    private $loader;

    /**
     * DeployTask constructor.
     *
     * @param RecipeLoader $loader
     */
    public function __construct(RecipeLoader $loader)
    {
        $this->loader = $loader;
    }

    /**
     * Configure using hipex configuration
     *
     * @param Configuration $config
     */
    public function configure(Configuration $config)
    {
        $this->loader->load('deploy/info.php');

        task('deploy:upload', [
            'deploy:info',
            'prepare:ssh',
            'deploy:prepare_release',
            'deploy:copy',
            'deploy:deploy',
        ])->onRoles(ServerRole::APPLICATION);

        fail('deploy', 'deploy:failed');
    }
}
