<?php

namespace Hypernode\Deploy\Command;

use Hypernode\Deploy\DeployRunner;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RunTaskTest extends TestCase
{
    /**
     * @var DeployRunner|MockObject
     */
    private $deployRunner;
    /**
     * @var InputInterface|MockObject
     */
    private $input;
    /**
     * @var OutputInterface|MockObject
     */
    private $output;

    /**
     * @var Build
     */
    private $command;

    protected function setUp(): void
    {
        $this->deployRunner = $this->createMock(DeployRunner::class);
        $this->input = $this->createMock(InputInterface::class);
        $this->output = $this->createMock(OutputInterface::class);
        $this->command = new RunTask($this->deployRunner);
    }

    protected function assertPreConditions(): void
    {
        $this->input->expects($this->exactly(2))
            ->method('getArgument')
            ->withConsecutive(['stage'], ['task'])
            ->willReturnOnConsecutiveCalls('production', 'sample_task');
    }

    public function test_it_calls_deploy_runner_correctly()
    {
        $this->deployRunner->expects($this->once())
            ->method('run')
            ->with($this->output, 'production', 'sample_task');

        $this->command->run($this->input, $this->output);
    }

    public function test_it_returns_zero()
    {
        $this->assertEquals(0, $this->command->run($this->input, $this->output));
    }
}
