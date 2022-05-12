<?php

namespace Hypernode\Deploy\Deployer\Task\Deploy;

use function Deployer\task;
use Hypernode\Deploy\Deployer\Task\TaskInterface;
use Hypernode\DeployConfiguration\Configuration;
use Hypernode\DeployConfiguration\ServerRole;

class PrepareTask implements TaskInterface
{
    /**
     * Configure using hipex configuration
     *
     * @param Configuration $config
     */
    public function configure(Configuration $config)
    {
        task('deploy:prepare_release', [
            'deploy:prepare',
            'deploy:release',
        ])->onRoles(ServerRole::APPLICATION);
    }
}
