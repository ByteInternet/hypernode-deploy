<?php

namespace Hypernode\Deploy\Command;

use Hypernode\Deploy\DeployRunner;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class BuildTest extends TestCase
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
        $this->command = new Build($this->deployRunner);
    }

    public function test_it_calls_deploy_runner_correctly()
    {
        $this->deployRunner->expects($this->once())
            ->method('run')
            ->with($this->output, 'build', 'build');
            
        $this->command->run($this->input, $this->output);
    }

    public function test_it_returns_zero()
    {
        $this->assertEquals(0, $this->command->run($this->input, $this->output));
    }
}
