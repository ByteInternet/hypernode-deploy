<?php

namespace Hypernode\Deploy\Deployer\Task\After;

use Hypernode\Deploy\Deployer\Task\TaskBase;
use Hypernode\Deploy\Deployer\TaskBuilder;
use Hypernode\DeployConfiguration\Configuration;

use function count;
use function Deployer\task;
use function Deployer\writeln;

class AfterTaskGlobal extends TaskBase
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
        $tasks = $this->taskBuilder->buildAll($config->getAfterDeployTasks(), 'deploy:after');
        if (count($tasks) === 0) {
            $tasks = function (): void {
                writeln('No after deploy tasks defined');
            };
        }

        task('deploy:after', $tasks)->once();
    }
}
