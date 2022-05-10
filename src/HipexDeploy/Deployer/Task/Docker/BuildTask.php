<?php
/**
 * @author Hipex <info@hipex.io>
 * @copyright (c) Hipex B.V. 2018
 */

namespace HipexDeploy\Deployer\Task\Docker;

use function Deployer\task;
use HipexDeploy\Deployer\Task\TaskInterface;
use HipexDeployConfiguration\Configuration;

class BuildTask implements TaskInterface
{
    /**
     * Configure using hipex configuration
     *
     * @param Configuration $config
     */
    public function configure(Configuration $config)
    {
        task('docker:build', [
            'docker:dockerfile',
            'docker:compile',
            'docker:push',
        ])
        ->onStage('build');
    }
}
