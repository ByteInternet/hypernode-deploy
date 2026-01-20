<?php

declare(strict_types=1);

namespace Hypernode\Deploy\Tests\Unit\Console\Output;

use Hypernode\Deploy\Console\Output\OutputWatcher;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Formatter\OutputFormatterInterface;
use Symfony\Component\Console\Output\OutputInterface;

class OutputWatcherTest extends TestCase
{
    private OutputInterface&MockObject $wrappedOutput;
    private OutputWatcher $watcher;

    protected function setUp(): void
    {
        $this->wrappedOutput = $this->createMock(OutputInterface::class);
        $this->wrappedOutput->method('getVerbosity')->willReturn(OutputInterface::VERBOSITY_NORMAL);
        $this->watcher = new OutputWatcher($this->wrappedOutput);
    }

    public function testGetWasWrittenReturnsFalseInitially(): void
    {
        $this->assertSame(false, $this->watcher->getWasWritten());
    }

    public function testWriteSetsWasWrittenToTrue(): void
    {
        $this->watcher->write('test message');

        $this->assertSame(true, $this->watcher->getWasWritten());
    }

    public function testWritelnSetsWasWrittenToTrue(): void
    {
        $this->watcher->writeln('test message');

        $this->assertSame(true, $this->watcher->getWasWritten());
    }

    public function testSetWasWrittenCanResetFlag(): void
    {
        $this->watcher->write('test');
        $this->assertSame(true, $this->watcher->getWasWritten());

        $this->watcher->setWasWritten(false);
        $this->assertSame(false, $this->watcher->getWasWritten());
    }

    public function testSetWasWrittenCanSetFlagDirectly(): void
    {
        $this->watcher->setWasWritten(true);
        $this->assertSame(true, $this->watcher->getWasWritten());
    }

    public function testWriteDelegatesToWrappedOutput(): void
    {
        $this->wrappedOutput->expects($this->once())
            ->method('write')
            ->with('test message', false, OutputInterface::OUTPUT_NORMAL);

        $this->watcher->write('test message');
    }

    public function testWritelnDelegatesToWrappedOutputWithNewline(): void
    {
        $this->wrappedOutput->expects($this->once())
            ->method('write')
            ->with('test message', true, OutputInterface::OUTPUT_NORMAL);

        $this->watcher->writeln('test message');
    }

    public function testGetVerbosityDelegatesToWrappedOutput(): void
    {
        $verboseOutput = $this->createMock(OutputInterface::class);
        $verboseOutput->expects($this->atLeastOnce())
            ->method('getVerbosity')
            ->willReturn(OutputInterface::VERBOSITY_VERBOSE);

        $watcher = new OutputWatcher($verboseOutput);

        $this->assertSame(OutputInterface::VERBOSITY_VERBOSE, $watcher->getVerbosity());
    }

    public function testSetVerbosityDelegatesToWrappedOutput(): void
    {
        $this->wrappedOutput->expects($this->once())
            ->method('setVerbosity')
            ->with(OutputInterface::VERBOSITY_DEBUG);

        $this->watcher->setVerbosity(OutputInterface::VERBOSITY_DEBUG);
    }

    public function testIsQuietReturnsTrueWhenVerbosityIsQuiet(): void
    {
        $quietOutput = $this->createMock(OutputInterface::class);
        $quietOutput->method('getVerbosity')->willReturn(OutputInterface::VERBOSITY_QUIET);

        $watcher = new OutputWatcher($quietOutput);

        $this->assertSame(true, $watcher->isQuiet());
    }

    public function testIsQuietReturnsFalseWhenVerbosityIsNormal(): void
    {
        $this->assertSame(false, $this->watcher->isQuiet());
    }

    public function testIsVerboseReturnsTrueWhenVerbosityIsVerbose(): void
    {
        $verboseOutput = $this->createMock(OutputInterface::class);
        $verboseOutput->method('getVerbosity')->willReturn(OutputInterface::VERBOSITY_VERBOSE);

        $watcher = new OutputWatcher($verboseOutput);

        $this->assertSame(true, $watcher->isVerbose());
    }

    public function testIsVerboseReturnsFalseWhenVerbosityIsNormal(): void
    {
        $this->assertSame(false, $this->watcher->isVerbose());
    }

    public function testIsVeryVerboseReturnsTrueWhenVerbosityIsVeryVerbose(): void
    {
        $veryVerboseOutput = $this->createMock(OutputInterface::class);
        $veryVerboseOutput->method('getVerbosity')->willReturn(OutputInterface::VERBOSITY_VERY_VERBOSE);

        $watcher = new OutputWatcher($veryVerboseOutput);

        $this->assertSame(true, $watcher->isVeryVerbose());
    }

    public function testIsDebugReturnsTrueWhenVerbosityIsDebug(): void
    {
        $debugOutput = $this->createMock(OutputInterface::class);
        $debugOutput->method('getVerbosity')->willReturn(OutputInterface::VERBOSITY_DEBUG);

        $watcher = new OutputWatcher($debugOutput);

        $this->assertSame(true, $watcher->isDebug());
    }

    public function testIsDecoratedDelegatesToWrappedOutput(): void
    {
        $this->wrappedOutput->expects($this->once())
            ->method('isDecorated')
            ->willReturn(true);

        $this->assertSame(true, $this->watcher->isDecorated());
    }

    public function testSetDecoratedDelegatesToWrappedOutput(): void
    {
        $this->wrappedOutput->expects($this->once())
            ->method('setDecorated')
            ->with(true);

        $this->watcher->setDecorated(true);
    }

    public function testGetFormatterDelegatesToWrappedOutput(): void
    {
        $formatter = $this->createMock(OutputFormatterInterface::class);
        $this->wrappedOutput->expects($this->once())
            ->method('getFormatter')
            ->willReturn($formatter);

        $this->assertSame($formatter, $this->watcher->getFormatter());
    }

    public function testSetFormatterDelegatesToWrappedOutput(): void
    {
        $formatter = $this->createMock(OutputFormatterInterface::class);
        $this->wrappedOutput->expects($this->once())
            ->method('setFormatter')
            ->with($formatter);

        $this->watcher->setFormatter($formatter);
    }
}
