<?php
/**
 * @author Hipex <info@hipex.io>
 * @copyright (c) Hipex B.V. 2018
 */

namespace HipexDeploy\Deployer\Task\Build;

use HipexDeploy\Deployer\Task\RegisterAfterInterface;
use function Deployer\before;
use function Deployer\run;
use function Deployer\task;
use HipexDeploy\Deployer\Task\TaskInterface;
use HipexDeployConfiguration\Configuration;

class CompilePrepareTask implements TaskInterface, RegisterAfterInterface
{
    /**
     * {@inheritdoc}
     */
    public function configure(Configuration $config)
    {
        task('build:compile:prepare', function() {
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
