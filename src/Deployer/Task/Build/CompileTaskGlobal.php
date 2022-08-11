<?php

namespace Hypernode\Deploy\Deployer\Task\Build;

use Hypernode\Deploy\Deployer\TaskBuilder;
use Hypernode\Deploy\Deployer\Task\TaskBase;
use Hypernode\DeployConfiguration\Configuration;

use function Deployer\run;
use function Deployer\task;

class CompileTaskGlobal extends TaskBase
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
        task('build:compile:prepare', function () {
            run('rm -Rf build');
            run('mkdir -p build');
            $dirs = ['generated', 'pub/static', 'var/view_preprocessed'];
            foreach ($dirs as $dir) {
                run(sprintf('rm -rf %s', $dir));
            }
        })->select("stage=build");

        $tasks = $this->taskBuilder->buildAll($config->getBuildCommands(), 'build:compile');
        array_unshift($tasks, 'build:compile:prepare');
        task('build:compile', $tasks)->select("stage=build");
    }
}
