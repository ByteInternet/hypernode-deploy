<?php

declare(strict_types=1);

namespace Hypernode\Deploy\Tests\Unit\Deployer\Task;

use Hypernode\Deploy\Deployer\Task\IncrementedTaskTrait;

/**
 * Concrete implementation of the trait for testing purposes.
 */
class TestIncrementedTask
{
    use IncrementedTaskTrait {
        getTaskName as public;
        getRegisteredTasks as public;
    }

    protected function getIncrementalNamePrefix(): string
    {
        return 'test:task:';
    }
}
