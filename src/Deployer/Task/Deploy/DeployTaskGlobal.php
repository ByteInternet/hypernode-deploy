<?php

namespace Hypernode\Deploy\Deployer\Task\Deploy;

use Hypernode\Deploy\Deployer\Task\TaskInterface;
use Hypernode\Deploy\Deployer\TaskBuilder;
use Hypernode\DeployConfiguration\Configuration;
use Hypernode\DeployConfiguration\ServerRole;

use function Deployer\task;

class DeployTaskGlobal implements TaskInterface
{
    /**
     * @var TaskBuilder
     */
    private $taskBuilder;

    public function __construct(TaskBuilder $taskBuilder)
    {
        $this->taskBuilder = $taskBuilder;
    }

    public function configure(Configuration $config): void
    {
        $tasks = $this->taskBuilder->buildAll($config->getDeployCommands(), 'deploy:deploy');
        $role = ServerRole::APPLICATION;

        if (count($tasks)) {
            task('deploy:deploy', $tasks);
        } else {
            task('deploy:deploy', function () {});
        }


        task('deploy:deploy', $tasks)->select("role={$role}");
    }
}
