<?php

declare(strict_types=1);

namespace Hypernode\Deploy\Tests\Unit\Deployer\Task;

use PHPUnit\Framework\TestCase;

class IncrementedTaskTraitTest extends TestCase
{
    public function testGetTaskNameReturnsIncrementedName(): void
    {
        $task = new TestIncrementedTask();

        $this->assertSame('test:task:0', $task->getTaskName());
        $this->assertSame('test:task:1', $task->getTaskName());
        $this->assertSame('test:task:2', $task->getTaskName());
    }

    public function testGetTaskNameWithIdentifierIncludesIdentifier(): void
    {
        $task = new TestIncrementedTask();

        $result = $task->getTaskName('foo');

        $this->assertSame('test:task:foo:0', $result);
    }

    public function testGetTaskNameWithIdentifierIncrementsCounter(): void
    {
        $task = new TestIncrementedTask();

        $this->assertSame('test:task:foo:0', $task->getTaskName('foo'));
        $this->assertSame('test:task:bar:1', $task->getTaskName('bar'));
        $this->assertSame('test:task:foo:2', $task->getTaskName('foo'));
    }

    public function testGetTaskNameMixedWithAndWithoutIdentifier(): void
    {
        $task = new TestIncrementedTask();

        $this->assertSame('test:task:0', $task->getTaskName());
        $this->assertSame('test:task:foo:1', $task->getTaskName('foo'));
        $this->assertSame('test:task:2', $task->getTaskName());
    }

    public function testGetRegisteredTasksReturnsAllGeneratedNames(): void
    {
        $task = new TestIncrementedTask();

        $task->getTaskName();
        $task->getTaskName('foo');
        $task->getTaskName();

        $this->assertSame(
            ['test:task:0', 'test:task:foo:1', 'test:task:2'],
            $task->getRegisteredTasks()
        );
    }

    public function testGetRegisteredTasksReturnsEmptyArrayInitially(): void
    {
        $task = new TestIncrementedTask();

        $this->assertSame([], $task->getRegisteredTasks());
    }

    public function testGetTaskNameWithEmptyIdentifierTreatedAsNoIdentifier(): void
    {
        $task = new TestIncrementedTask();

        // Empty string should be treated same as no identifier due to !empty() check
        $this->assertSame('test:task:0', $task->getTaskName(''));
    }
}
