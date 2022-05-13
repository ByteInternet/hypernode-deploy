<?php

namespace Hypernode\Deploy\Deployer\Task\Deploy;

use Hypernode\DeployConfiguration\ServerRole;
use Hypernode\Deploy\Deployer\RecipeLoader;
use Hypernode\Deploy\Deployer\Task\TaskInterface;
use Hypernode\DeployConfiguration\Configuration;

use function Deployer\fail;
use function Deployer\task;

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
     *
     * @return void
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
