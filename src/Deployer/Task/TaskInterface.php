<?php

namespace Hypernode\Deploy\Deployer\Task;

use Hypernode\DeployConfiguration\Configuration;

interface TaskInterface
{
    /**
     * Configure using hipex configuration
     *
     * @param Configuration $config
     * @return void
     */
    public function configure(Configuration $config): void;
}
