<?php

/**
 * @author Hypernode
 * @copyright Copyright (c) Hypernode
 */

namespace Hypernode\Deploy\Deployer\Task\Deploy;

use function Deployer\task;

use Hypernode\Deploy\Deployer\Task\TaskInterface;
use Hypernode\Deploy\Deployer\TaskBuilder;
use Hypernode\DeployConfiguration\Configuration;
use Hypernode\DeployConfiguration\ServerRole;

class DeployTaskGlobal implements TaskInterface
{
    /**
     * @var TaskBuilder
     */
    private $taskBuilder;

    /**
     * CompileTask constructor.
     *
     * @param TaskBuilder $taskBuilder
     */
    public function __construct(TaskBuilder $taskBuilder)
    {
        $this->taskBuilder = $taskBuilder;
    }

    /**
     * {@inheritdoc}
     */
    public function configure(Configuration $config)
    {
        $tasks = $this->taskBuilder->buildAll($config->getDeployCommands(), 'deploy:deploy');

        if (count($tasks)) {
            task('deploy:deploy', $tasks);
        } else {
            task('deploy:deploy', function () {
            });
        }


        task('deploy:deploy', $tasks)->onRoles(ServerRole::APPLICATION);
    }
}
