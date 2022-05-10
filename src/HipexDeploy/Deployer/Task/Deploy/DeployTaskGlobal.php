<?php
/**
 * @author Hipex <info@hipex.io>
 * @copyright (c) Hipex B.V. 2018
 */

namespace HipexDeploy\Deployer\Task\Deploy;

use function Deployer\task;

use HipexDeploy\Deployer\Task\TaskInterface;
use HipexDeploy\Deployer\TaskBuilder;
use HipexDeployConfiguration\Configuration;
use HipexDeployConfiguration\ServerRole;

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
            task('deploy:deploy', function() {});
        }


        task('deploy:deploy', $tasks)->onRoles(ServerRole::APPLICATION);
    }
}
