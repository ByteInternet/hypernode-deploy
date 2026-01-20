<?php

declare(strict_types=1);

namespace Hypernode\Deploy\Tests\Unit\Console\Output;

use DateTime;
use Hypernode\Deploy\Console\Output\ConsoleLogger;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\InvalidArgumentException;
use Psr\Log\LogLevel;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConsoleLoggerTest extends TestCase
{
    private OutputInterface&MockObject $output;
    private ConsoleLogger $logger;

    protected function setUp(): void
    {
        $this->output = $this->createMock(OutputInterface::class);
        $this->output->method('getVerbosity')->willReturn(OutputInterface::VERBOSITY_NORMAL);
        $this->logger = new ConsoleLogger($this->output);
    }

    public function testLogInterpolatesStringPlaceholder(): void
    {
        $this->output->expects($this->once())
            ->method('writeln')
            ->with(
                $this->stringContains('Hello World'),
                $this->anything()
            );

        $this->logger->info('Hello {name}', ['name' => 'World']);
    }

    public function testLogInterpolatesIntegerPlaceholder(): void
    {
        $this->output->expects($this->once())
            ->method('writeln')
            ->with(
                $this->stringContains('Count: 42'),
                $this->anything()
            );

        $this->logger->info('Count: {count}', ['count' => 42]);
    }

    public function testLogInterpolatesFloatPlaceholder(): void
    {
        $this->output->expects($this->once())
            ->method('writeln')
            ->with(
                $this->stringContains('Value: 3.14'),
                $this->anything()
            );

        $this->logger->info('Value: {value}', ['value' => 3.14]);
    }

    public function testLogInterpolatesBooleanPlaceholder(): void
    {
        $this->output->expects($this->once())
            ->method('writeln')
            ->with(
                $this->stringContains('Active: 1'),
                $this->anything()
            );

        $this->logger->info('Active: {active}', ['active' => true]);
    }

    public function testLogInterpolatesObjectWithToString(): void
    {
        $object = new class {
            public function __toString(): string
            {
                return 'StringableObject';
            }
        };

        $this->output->expects($this->once())
            ->method('writeln')
            ->with(
                $this->stringContains('Object: StringableObject'),
                $this->anything()
            );

        $this->logger->info('Object: {obj}', ['obj' => $object]);
    }

    public function testLogInterpolatesDateTimeInterface(): void
    {
        $date = new DateTime('2024-01-15T10:30:00+00:00');

        $this->output->expects($this->once())
            ->method('writeln')
            ->with(
                $this->stringContains('Date: 2024-01-15T10:30:00+00:00'),
                $this->anything()
            );

        $this->logger->info('Date: {date}', ['date' => $date]);
    }

    public function testLogInterpolatesObjectWithoutToStringAsClassName(): void
    {
        $object = new \stdClass();

        $this->output->expects($this->once())
            ->method('writeln')
            ->with(
                $this->stringContains('[object stdClass]'),
                $this->anything()
            );

        $this->logger->info('Object: {obj}', ['obj' => $object]);
    }

    public function testLogInterpolatesArrayAsArrayType(): void
    {
        $this->output->expects($this->once())
            ->method('writeln')
            ->with(
                $this->stringContains('[array]'),
                $this->anything()
            );

        $this->logger->info('Data: {data}', ['data' => ['foo', 'bar']]);
    }

    public function testLogInterpolatesNullAsEmpty(): void
    {
        $this->output->expects($this->once())
            ->method('writeln')
            ->with(
                $this->stringContains('Value: </info>'),
                $this->anything()
            );

        $this->logger->info('Value: {value}', ['value' => null]);
    }

    public function testLogWithoutPlaceholdersReturnsMessageUnchanged(): void
    {
        $this->output->expects($this->once())
            ->method('writeln')
            ->with(
                $this->stringContains('Simple message'),
                $this->anything()
            );

        $this->logger->info('Simple message');
    }

    public function testLogThrowsExceptionForInvalidLevel(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The log level "invalid" does not exist.');

        $this->logger->log('invalid', 'test message');
    }

    public function testHasErroredReturnsFalseInitially(): void
    {
        $this->assertSame(false, $this->logger->hasErrored());
    }

    public function testHasErroredReturnsTrueAfterErrorLog(): void
    {
        $this->logger->error('An error occurred');

        $this->assertSame(true, $this->logger->hasErrored());
    }

    public function testHasErroredReturnsTrueAfterCriticalLog(): void
    {
        $this->logger->critical('Critical error');

        $this->assertSame(true, $this->logger->hasErrored());
    }

    public function testHasErroredReturnsFalseAfterInfoLog(): void
    {
        $this->logger->info('Info message');

        $this->assertSame(false, $this->logger->hasErrored());
    }

    public function testErrorLevelWritesToErrorOutput(): void
    {
        $errorOutput = $this->createMock(OutputInterface::class);
        $errorOutput->method('getVerbosity')->willReturn(OutputInterface::VERBOSITY_NORMAL);
        $errorOutput->expects($this->once())
            ->method('writeln')
            ->with(
                $this->stringContains('error message'),
                $this->anything()
            );

        $consoleOutput = $this->createMock(ConsoleOutputInterface::class);
        $consoleOutput->method('getVerbosity')->willReturn(OutputInterface::VERBOSITY_NORMAL);
        $consoleOutput->method('getErrorOutput')->willReturn($errorOutput);

        $logger = new ConsoleLogger($consoleOutput);
        $logger->error('error message');
    }

    public function testDebugLevelNotWrittenAtNormalVerbosity(): void
    {
        $this->output->expects($this->never())->method('writeln');

        $this->logger->debug('debug message');
    }

    public function testDebugLevelWrittenAtVerboseLevel(): void
    {
        $verboseOutput = $this->createMock(OutputInterface::class);
        $verboseOutput->method('getVerbosity')->willReturn(OutputInterface::VERBOSITY_VERBOSE);
        $verboseOutput->expects($this->once())
            ->method('writeln')
            ->with(
                $this->stringContains('debug message'),
                $this->anything()
            );

        $logger = new ConsoleLogger($verboseOutput);
        $logger->debug('debug message');
    }

    // Additional log level tests

    public function testHasErroredReturnsTrueAfterEmergencyLog(): void
    {
        $this->logger->emergency('Emergency error');

        $this->assertSame(true, $this->logger->hasErrored());
    }

    public function testHasErroredReturnsTrueAfterAlertLog(): void
    {
        $this->logger->alert('Alert error');

        $this->assertSame(true, $this->logger->hasErrored());
    }

    public function testHasErroredReturnsFalseAfterWarningLog(): void
    {
        $this->logger->warning('Warning message');

        $this->assertSame(false, $this->logger->hasErrored());
    }

    public function testHasErroredReturnsFalseAfterNoticeLog(): void
    {
        $this->logger->notice('Notice message');

        $this->assertSame(false, $this->logger->hasErrored());
    }

    public function testCustomVerbosityLevelMapIsUsed(): void
    {
        // Create custom map where info requires verbose
        $customVerbosityMap = [
            LogLevel::INFO => OutputInterface::VERBOSITY_VERBOSE,
        ];

        $normalOutput = $this->createMock(OutputInterface::class);
        $normalOutput->method('getVerbosity')->willReturn(OutputInterface::VERBOSITY_NORMAL);
        $normalOutput->expects($this->never())->method('writeln');

        $logger = new ConsoleLogger($normalOutput, $customVerbosityMap);
        $logger->info('This should not be written at normal verbosity');
    }

    public function testCustomFormatLevelMapIsUsed(): void
    {
        // Create custom map where warning is treated as error (writes to error output)
        $customFormatMap = [
            LogLevel::WARNING => ConsoleLogger::ERROR,
        ];

        $errorOutput = $this->createMock(OutputInterface::class);
        $errorOutput->method('getVerbosity')->willReturn(OutputInterface::VERBOSITY_NORMAL);
        $errorOutput->expects($this->once())
            ->method('writeln')
            ->with(
                $this->stringContains('warning treated as error'),
                $this->anything()
            );

        $consoleOutput = $this->createMock(ConsoleOutputInterface::class);
        $consoleOutput->method('getVerbosity')->willReturn(OutputInterface::VERBOSITY_NORMAL);
        $consoleOutput->method('getErrorOutput')->willReturn($errorOutput);

        $logger = new ConsoleLogger($consoleOutput, [], $customFormatMap);
        $logger->warning('warning treated as error');

        // Warning with custom format map should set errored flag
        $this->assertSame(true, $logger->hasErrored());
    }
}
