<?php

namespace Hypernode\Deploy\Deployer\Task\After;

use Hypernode\Deploy\Deployer\Task\TaskInterface;
use Hypernode\Deploy\Deployer\TaskBuilder;
use Hypernode\DeployConfiguration\Configuration;
use Hypernode\DeployConfiguration\ServerRole;

use function Deployer\task;
use function Deployer\writeln;

class AfterTaskGlobal implements TaskInterface
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
     *
     * @return void
     */
    public function configure(Configuration $config)
    {
        $tasks = $this->taskBuilder->buildAll($config->getAfterDeployTasks(), 'deploy:after');
        if (\count($tasks) === 0) {
            $tasks = function (): void {
                writeln('No after deploy tasks defined');
            };
        }

        task('deploy:after', $tasks)
            ->once()
            ->onRoles(ServerRole::APPLICATION);
    }
}
