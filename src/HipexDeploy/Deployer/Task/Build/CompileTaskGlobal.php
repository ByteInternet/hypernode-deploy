<?php
/**
 * @author Hipex <info@hipex.io>
 * @copyright (c) Hipex B.V. 2018
 */

namespace HipexDeploy\Deployer\Task\Build;

use HipexDeployConfiguration\ServerRole;
use function Deployer\run;
use function Deployer\task;

use HipexDeploy\Deployer\Task\TaskInterface;
use HipexDeploy\Deployer\TaskBuilder;
use HipexDeployConfiguration\Configuration;

class CompileTaskGlobal implements TaskInterface
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
        task('build:compile:prepare', function() {
            run('rm -Rf build');
            run('mkdir -p build');
        })->onStage('build');

        $tasks = $this->taskBuilder->buildAll($config->getBuildCommands(), 'build:compile');
        array_unshift($tasks, 'build:compile:prepare');
        task('build:compile', $tasks)->onStage('build');
    }
}
