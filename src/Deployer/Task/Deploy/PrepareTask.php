<?php

namespace Hypernode\Deploy\Deployer\Task\Deploy;

use Hypernode\Deploy\Deployer\Task\TaskInterface;
use Hypernode\DeployConfiguration\Configuration;
use Hypernode\DeployConfiguration\ServerRole;

use function Deployer\task;

class PrepareTask implements TaskInterface
{
    public function configure(Configuration $config): void
    {
        $role = ServerRole::APPLICATION;
        task('deploy:prepare_release', [
            'deploy:prepare',
            'deploy:release',
        ])->select("role={$role}");
    }
}
