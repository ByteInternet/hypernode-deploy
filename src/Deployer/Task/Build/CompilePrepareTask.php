<?php

namespace Hypernode\Deploy\Deployer\Task\Build;

use Hypernode\Deploy\Deployer\Task\TaskBase;
use Hypernode\DeployConfiguration\Configuration;

use function Deployer\before;
use function Deployer\run;
use function Deployer\task;

class CompilePrepareTask extends TaskBase
{
    public function configure(Configuration $config): void
    {
        task('build:compile:prepare', function () {
            run('rm -Rf build');
            run('mkdir -p build');
        })->select("stage=build");
    }

    public function register(): void
    {
        before('build:compile', 'build:compile:prepare');
    }
}
