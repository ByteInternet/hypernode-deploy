<?php

namespace Hypernode\Deploy\Deployer\Task\Deploy;

use Hypernode\Deploy\Deployer\Task\TaskBase;
use Hypernode\DeployConfiguration\Configuration;
use Hypernode\DeployConfiguration\ServerRole;

use function Deployer\task;
use function Hypernode\Deploy\Deployer\noop;

class DeployTaskGlobal extends TaskBase
{
    public function configure(Configuration $config): void
    {
        $tasks = $config->getDeployTasks();
        $role = ServerRole::APPLICATION;

        if (count($tasks)) {
            task('deploy:deploy', $tasks);
        } else {
            task('deploy:deploy', noop());
        }

        task('deploy:deploy', $tasks)->select("roles=$role");
    }
}
