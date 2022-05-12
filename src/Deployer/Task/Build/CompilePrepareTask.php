<?php

namespace Hypernode\Deploy\Deployer\Task\Build;

use Hypernode\Deploy\Deployer\Task\RegisterAfterInterface;
use function Deployer\before;
use function Deployer\run;
use function Deployer\task;
use Hypernode\Deploy\Deployer\Task\TaskInterface;
use Hypernode\DeployConfiguration\Configuration;

class CompilePrepareTask implements TaskInterface, RegisterAfterInterface
{
    /**
     * {@inheritdoc}
     */
    public function configure(Configuration $config)
    {
        task('build:compile:prepare', function () {
            run('rm -Rf build');
            run('mkdir -p build');
        })->onStage('build');
    }

    /**
     * {@inheritDoc}
     */
    public function registerAfter(): void
    {
        before('build:compile', 'build:compile:prepare');
    }
}
