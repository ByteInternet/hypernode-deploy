<?php

namespace Hypernode\Deploy\Deployer\Task\Docker;

use Hypernode\Deploy\Deployer\Task\TaskInterface;
use Hypernode\DeployConfiguration\Configuration;

use function Deployer\task;

class BuildTask implements TaskInterface
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
        task('docker:build', [
            'docker:dockerfile',
            'docker:compile',
            'docker:push',
        ])
            ->onStage('build');
    }
}
