<?php

declare(strict_types=1);

namespace Hypernode\Deploy\Tests\Unit\Printer;

use Deployer\Host\Host;
use Hypernode\Deploy\Printer\GithubWorkflowPrinter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class GithubWorkflowPrinterTest extends TestCase
{
    private OutputInterface&MockObject $output;
    private GithubWorkflowPrinter $printer;

    protected function setUp(): void
    {
        $this->output = $this->createMock(OutputInterface::class);
        $this->printer = new GithubWorkflowPrinter($this->output);
    }

    public function testContentHasWorkflowCommandReturnsTrueForWarningCommand(): void
    {
        $this->assertSame(true, $this->printer->contentHasWorkflowCommand('::warning::some message'));
    }

    public function testContentHasWorkflowCommandReturnsTrueForErrorCommand(): void
    {
        $this->assertSame(true, $this->printer->contentHasWorkflowCommand('::error::something went wrong'));
    }

    public function testContentHasWorkflowCommandReturnsTrueForSetOutputCommand(): void
    {
        $this->assertSame(true, $this->printer->contentHasWorkflowCommand('::set-output name=foo::bar'));
    }

    public function testContentHasWorkflowCommandReturnsTrueForDebugCommand(): void
    {
        $this->assertSame(true, $this->printer->contentHasWorkflowCommand('::debug::debug info'));
    }

    public function testContentHasWorkflowCommandReturnsTrueForGroupCommand(): void
    {
        $this->assertSame(true, $this->printer->contentHasWorkflowCommand('::group::My Group'));
    }

    public function testContentHasWorkflowCommandReturnsTrueForEndGroupCommand(): void
    {
        $this->assertSame(true, $this->printer->contentHasWorkflowCommand('::endgroup::'));
    }

    public function testContentHasWorkflowCommandReturnsFalseForRegularText(): void
    {
        $this->assertSame(false, $this->printer->contentHasWorkflowCommand('Hello world'));
    }

    public function testContentHasWorkflowCommandReturnsFalseForEmptyString(): void
    {
        $this->assertSame(false, $this->printer->contentHasWorkflowCommand(''));
    }

    public function testContentHasWorkflowCommandReturnsFalseForPartialCommand(): void
    {
        $this->assertSame(false, $this->printer->contentHasWorkflowCommand('::invalid'));
    }

    public function testContentHasWorkflowCommandReturnsFalseForColonsWithoutCommand(): void
    {
        $this->assertSame(false, $this->printer->contentHasWorkflowCommand(':: ::'));
    }

    public function testContentHasWorkflowCommandReturnsTrueForCommandInMultilineContent(): void
    {
        $content = "Some regular output\n::warning::a warning message\nMore output";
        $this->assertSame(true, $this->printer->contentHasWorkflowCommand($content));
    }

    public function testContentHasWorkflowCommandReturnsTrueForCommandWithFileAndLine(): void
    {
        $this->assertSame(true, $this->printer->contentHasWorkflowCommand('::error file=app.js,line=10::error message'));
    }

    // Tests for writeln() method

    public function testWritelnOutputsWorkflowCommandDirectlyForStdout(): void
    {
        $host = $this->createMock(Host::class);

        $this->output->expects($this->once())
            ->method('writeln')
            ->with('::warning::test message');

        $this->printer->writeln(Process::OUT, $host, '::warning::test message');
    }

    public function testWritelnDoesNotOutputEmptyLine(): void
    {
        $host = $this->createMock(Host::class);

        $this->output->expects($this->never())
            ->method('writeln');

        $this->printer->writeln(Process::OUT, $host, '');
    }

    public function testWritelnDelegatesToParentForNonWorkflowStdout(): void
    {
        $host = $this->createMock(Host::class);
        $host->method('__toString')->willReturn('test-host');

        // Parent's writeln will be called which writes "[host] line" format
        $this->output->expects($this->once())
            ->method('writeln')
            ->with($this->stringContains('regular output'));

        $this->printer->writeln(Process::OUT, $host, 'regular output');
    }

    public function testWritelnDelegatesToParentForStderr(): void
    {
        $host = $this->createMock(Host::class);
        $host->method('__toString')->willReturn('test-host');

        // Even workflow commands on stderr should go through parent
        $this->output->expects($this->once())
            ->method('writeln')
            ->with($this->stringContains('::warning::'));

        $this->printer->writeln(Process::ERR, $host, '::warning::error stream message');
    }

    // Tests for callback() method

    public function testCallbackPrintsWhenForceOutputIsTrue(): void
    {
        $host = $this->createMock(Host::class);
        $host->method('__toString')->willReturn('test-host');

        $this->output->method('isVerbose')->willReturn(false);
        $this->output->expects($this->once())
            ->method('writeln')
            ->with($this->stringContains('forced output'));

        $callback = $this->printer->callback($host, true);
        $callback(Process::OUT, 'forced output');
    }

    public function testCallbackPrintsWhenVerbose(): void
    {
        $host = $this->createMock(Host::class);
        $host->method('__toString')->willReturn('test-host');

        $this->output->method('isVerbose')->willReturn(true);
        $this->output->expects($this->once())
            ->method('writeln')
            ->with($this->stringContains('verbose output'));

        $callback = $this->printer->callback($host, false);
        $callback(Process::OUT, 'verbose output');
    }

    public function testCallbackPrintsWorkflowCommandOnStdoutEvenWhenNotVerbose(): void
    {
        $host = $this->createMock(Host::class);

        $this->output->method('isVerbose')->willReturn(false);
        $this->output->expects($this->once())
            ->method('writeln')
            ->with('::warning::workflow command');

        $callback = $this->printer->callback($host, false);
        $callback(Process::OUT, '::warning::workflow command');
    }

    public function testCallbackDoesNotPrintRegularOutputWhenNotVerboseAndNotForced(): void
    {
        $host = $this->createMock(Host::class);

        $this->output->method('isVerbose')->willReturn(false);
        $this->output->expects($this->never())->method('writeln');

        $callback = $this->printer->callback($host, false);
        $callback(Process::OUT, 'regular output that should be suppressed');
    }

    public function testCallbackDoesNotPrintWorkflowCommandOnStderrWhenNotVerbose(): void
    {
        $host = $this->createMock(Host::class);

        $this->output->method('isVerbose')->willReturn(false);
        // Workflow command on stderr should NOT be printed when not verbose/forced
        // because the condition checks $type == Process::OUT
        $this->output->expects($this->never())->method('writeln');

        $callback = $this->printer->callback($host, false);
        $callback(Process::ERR, '::warning::workflow on stderr');
    }
}
