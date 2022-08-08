<?php

namespace Hypernode\Deploy\Deployer\Task\Deploy;

use Hypernode\DeployConfiguration\ServerRole;
use Hypernode\Deploy\Deployer\RecipeLoader;
use Hypernode\Deploy\Deployer\Task\TaskInterface;
use Hypernode\DeployConfiguration\Configuration;

use function Deployer\fail;
use function Deployer\task;

class FinalizeTask implements TaskInterface
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

        task('deploy:finalize', [
            'deploy:after',
            'deploy:cleanup',
            'deploy:success',
        ])->select("roles=$role");

        fail('deploy', 'deploy:failed');
    }
}
