<?php

namespace Hypernode\Deploy\Deployer\Task\Deploy;

use Hypernode\Deploy\Deployer\Task\TaskBase;
use Hypernode\DeployConfiguration\Configuration;
use Hypernode\DeployConfiguration\ServerRole;

use function Deployer\task;

class PrepareTask extends TaskBase
{
    public function configure(Configuration $config): void
    {
        $role = ServerRole::APPLICATION;

        // Overridden from deploy:prepare in Deployer common.php recipe. We remove the deploy:update_code task.
        task('deploy:prepare', [
            'deploy:info',
            'deploy:setup',
            'deploy:lock',
            'deploy:release',
            'deploy:shared',
            'deploy:writable',
        ]);
    }
}
