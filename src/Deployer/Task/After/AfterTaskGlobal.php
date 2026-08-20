<?php

namespace Hypernode\Deploy\Deployer\Task\After;

use Hypernode\Deploy\Deployer\Task\TaskBase;
use Hypernode\Deploy\Deployer\TaskBuilder;
use Hypernode\DeployConfiguration\Command\Command;
use Hypernode\DeployConfiguration\Configuration;
use Hypernode\DeployConfiguration\TaskConfigurationInterface;

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
        $commands = array_values(array_filter(
            $config->getAfterDeployTasks(),
            fn (TaskConfigurationInterface $task): bool => $task instanceof Command
        ));

        $tasks = $this->taskBuilder->buildAll($commands, 'deploy:after');
        if (count($tasks) === 0) {
            $tasks = function (): void {
                writeln('No after deploy tasks defined');
            };
        }

        task('deploy:after', $tasks)->once();
    }
}
