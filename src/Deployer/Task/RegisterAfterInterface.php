<?php

namespace Hypernode\Deploy\Deployer\Task;

interface RegisterAfterInterface
{
    /**
     * Use this method to register your task after another task
     * i.e. after('taska', 'taskb')
     */
    public function registerAfter(): void;
}
