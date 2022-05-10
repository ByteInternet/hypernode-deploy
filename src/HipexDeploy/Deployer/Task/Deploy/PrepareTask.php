<?php
/**
 * @author Hipex <info@hipex.io>
 * @copyright (c) Hipex B.V. 2018
 */

namespace HipexDeploy\Deployer\Task\Deploy;

use function Deployer\task;
use HipexDeploy\Deployer\Task\TaskInterface;
use HipexDeployConfiguration\Configuration;
use HipexDeployConfiguration\ServerRole;

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
