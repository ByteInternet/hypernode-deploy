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

    /**
     * Use this method to register your task before or after another task
     * i.e. after('taska', 'taskb'), before('taska', 'taskb')
     */
    public function register(): void;
}
