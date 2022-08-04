<?php

namespace Hypernode\Deploy\Deployer\Task\Build;

use Hypernode\Deploy\Deployer\Task\RegisterAfterInterface;
use Hypernode\Deploy\Deployer\Task\TaskInterface;
use Hypernode\DeployConfiguration\Configuration;

use function Deployer\before;
use function Deployer\run;
use function Deployer\task;

class CompilePrepareTask implements TaskInterface, RegisterAfterInterface
{
    public function configure(Configuration $config): void
    {
        task('build:compile:prepare', function () {
            run('rm -Rf build');
            run('mkdir -p build');
        })->select("stage=build");
    }

    public function registerAfter(): void
    {
        before('build:compile', 'build:compile:prepare');
    }
}
