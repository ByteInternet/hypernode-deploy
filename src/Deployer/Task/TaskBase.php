<?php

namespace Hypernode\Deploy\Deployer\Task;

use Hypernode\DeployConfiguration\Configuration;

abstract class TaskBase implements TaskInterface
{
    public function configure(Configuration $config): void
    {
    }

    public function register(): void
    {
    }
}
