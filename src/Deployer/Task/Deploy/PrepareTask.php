<?php

namespace Hypernode\Deploy\Deployer\Task\Deploy;

use Hypernode\Deploy\Deployer\Task\TaskInterface;
use Hypernode\DeployConfiguration\Configuration;
use Hypernode\DeployConfiguration\ServerRole;

use function Deployer\task;

class PrepareTask implements TaskInterface
{
    /**
     * Configure using hipex configuration
     *
     * @param Configuration $config
     *
     * @return void
     */
    public function configure(Configuration $config)
    {
        task('deploy:prepare_release', [
            'deploy:prepare',
            'deploy:release',
        ])->onRoles(ServerRole::APPLICATION);
    }
}
