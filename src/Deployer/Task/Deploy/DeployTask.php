<?php

namespace Hypernode\Deploy\Deployer\Task\Deploy;

use Hypernode\DeployConfiguration\ServerRole;
use Hypernode\Deploy\Deployer\RecipeLoader;
use Hypernode\Deploy\Deployer\Task\TaskInterface;
use Hypernode\DeployConfiguration\Configuration;

use function Deployer\fail;
use function Deployer\task;

class DeployTask implements TaskInterface
{
    /**
     * @var RecipeLoader
     */
    private $loader;

    public function __construct(RecipeLoader $loader)
    {
        $this->loader = $loader;
    }

    public function configure(Configuration $config): void
    {
        $this->loader->load('deploy/info.php');
        $role = ServerRole::APPLICATION;

        task('deploy', [
            'deploy:upload',
            'deploy:link',
            'deploy:finalize',
        ])->select("role={$role}");

        fail('deploy', 'deploy:failed');
    }
}
