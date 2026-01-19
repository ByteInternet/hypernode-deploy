<?php

declare(strict_types=1);

namespace Hypernode\Deploy\Tests\Unit\Brancher;

use Hypernode\Api\Exception\HypernodeApiClientException;
use Hypernode\Api\HypernodeClient;
use Hypernode\Api\Resource\Logbook\Flow;
use Hypernode\Api\Service\Logbook;
use Hypernode\Deploy\Brancher\BrancherHypernodeManager;
use Hypernode\Deploy\Exception\CreateBrancherHypernodeFailedException;
use Hypernode\Deploy\Exception\TimeoutException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

class BrancherHypernodeManagerTest extends TestCase
{
    private LoggerInterface&MockObject $logger;
    private HypernodeClient&MockObject $hypernodeClient;
    private Logbook&MockObject $logbook;
    private TestSshPoller $sshPoller;
    private BrancherHypernodeManager $manager;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->hypernodeClient = $this->createMock(HypernodeClient::class);
        $this->logbook = $this->createMock(Logbook::class);
        $this->hypernodeClient->logbook = $this->logbook;
        $this->sshPoller = new TestSshPoller();
        $this->sshPoller->setMicrotime(1000.0);

        $this->manager = new BrancherHypernodeManager(
            $this->logger,
            $this->hypernodeClient,
            $this->sshPoller
        );
    }

    public function testSshFirstCheckSucceedsReturnsEarly(): void
    {
        $this->sshPoller->pollResults = [true, true, true];

        $this->logbook->expects($this->never())->method('getList');

        $this->manager->waitForAvailability('test-brancher', 1500, 6, 10);

        $this->assertSame(3, $this->sshPoller->pollCount);
    }

    public function testSshFirstCheckFailsFallsBackToLogbook(): void
    {
        $this->sshPoller->pollResults = array_merge(
            array_fill(0, 5, false),
            array_fill(0, 6, true)
        );

        $flow = $this->createFlow('ensure_app', Flow::STATE_SUCCESS);
        $this->logbook->expects($this->once())
            ->method('getList')
            ->with('test-brancher')
            ->willReturn([$flow]);

        $this->manager->waitForAvailability('test-brancher', 1500, 6, 10);

        $this->assertSame(11, $this->sshPoller->pollCount);
    }

    public function testLogbookAllFlowsRevertedThrowsException(): void
    {
        $this->sshPoller->pollResults = array_fill(0, 5, false);

        $flow = $this->createFlow('ensure_app', Flow::STATE_REVERTED);
        $this->logbook->expects($this->once())
            ->method('getList')
            ->with('test-brancher')
            ->willReturn([$flow]);

        $this->expectException(CreateBrancherHypernodeFailedException::class);

        $this->manager->waitForAvailability('test-brancher', 1500, 6, 10);
    }

    public function testTimeoutDuringDeliveryThrowsException(): void
    {
        $this->sshPoller->pollResults = array_fill(0, 5, false);
        $this->sshPoller->sleepTimeAdvance = 10;

        $flow = $this->createFlow('ensure_app', Flow::STATE_RUNNING);
        $this->logbook->method('getList')
            ->with('test-brancher')
            ->willReturn([$flow]);

        $this->expectException(TimeoutException::class);
        $this->expectExceptionMessage('Timed out waiting for brancher Hypernode test-brancher to be delivered');

        $this->manager->waitForAvailability('test-brancher', 30, 6, 10);
    }

    public function testTimeoutDuringReachabilityThrowsException(): void
    {
        $this->sshPoller->pollResults = array_fill(0, 100, false);
        $this->sshPoller->sleepTimeAdvance = 10;

        $flow = $this->createFlow('ensure_app', Flow::STATE_SUCCESS);
        $this->logbook->method('getList')
            ->with('test-brancher')
            ->willReturn([$flow]);

        $this->expectException(TimeoutException::class);
        $this->expectExceptionMessage('Timed out waiting for brancher Hypernode test-brancher to become reachable');

        $this->manager->waitForAvailability('test-brancher', 100, 6, 10);
    }

    public function testLogbook404DuringAllowedWindowContinuesPolling(): void
    {
        $this->sshPoller->pollResults = array_merge(
            array_fill(0, 5, false),
            array_fill(0, 6, true)
        );

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(404);
        $response->method('getBody')->willReturn('Not Found');
        $exception404 = new HypernodeApiClientException($response);

        $flow = $this->createFlow('ensure_app', Flow::STATE_SUCCESS);
        $this->logbook->expects($this->exactly(2))
            ->method('getList')
            ->with('test-brancher')
            ->willReturnOnConsecutiveCalls(
                $this->throwException($exception404),
                [$flow]
            );

        $this->manager->waitForAvailability('test-brancher', 1500, 6, 10);

        $this->assertSame(11, $this->sshPoller->pollCount);
    }

    public function testLogbookNon404ErrorPropagates(): void
    {
        $this->sshPoller->pollResults = array_fill(0, 5, false);

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(500);
        $response->method('getBody')->willReturn('Internal Server Error');
        $exception500 = new HypernodeApiClientException($response);

        $this->logbook->expects($this->once())
            ->method('getList')
            ->with('test-brancher')
            ->willThrowException($exception500);

        $this->expectException(HypernodeApiClientException::class);

        $this->manager->waitForAvailability('test-brancher', 1500, 6, 10);
    }

    public function testSshFirstCheckIntermittentFailuresResetCounter(): void
    {
        $this->sshPoller->pollResults = [true, true, false, true, true, true];

        $this->logbook->expects($this->never())->method('getList');

        $this->manager->waitForAvailability('test-brancher', 1500, 6, 10);

        $this->assertSame(6, $this->sshPoller->pollCount);
    }

    public function testSshFirstCheckExhaustsMaxFailuresBeforeFallback(): void
    {
        $this->sshPoller->pollResults = array_merge(
            array_fill(0, 5, false),
            array_fill(0, 6, true)
        );

        $flow = $this->createFlow('ensure_app', Flow::STATE_SUCCESS);
        $this->logbook->expects($this->once())
            ->method('getList')
            ->willReturn([$flow]);

        $this->manager->waitForAvailability('test-brancher', 1500, 6, 10);

        $this->assertSame(11, $this->sshPoller->pollCount);
    }

    public function testMultipleFlowsAllMustComplete(): void
    {
        $this->sshPoller->pollResults = array_merge(
            array_fill(0, 5, false),
            array_fill(0, 6, true)
        );

        $flowComplete = $this->createFlow('ensure_app', Flow::STATE_SUCCESS);
        $flowRunning = $this->createFlow('ensure_copied_app', Flow::STATE_RUNNING);
        $flowComplete2 = $this->createFlow('ensure_copied_app', Flow::STATE_SUCCESS);

        $this->logbook->expects($this->exactly(2))
            ->method('getList')
            ->willReturnOnConsecutiveCalls(
                [$flowComplete, $flowRunning],
                [$flowComplete, $flowComplete2]
            );

        $this->manager->waitForAvailability('test-brancher', 1500, 6, 10);

        $this->assertSame(11, $this->sshPoller->pollCount);
    }

    public function testEmptyLogbookContinuesPolling(): void
    {
        $this->sshPoller->pollResults = array_merge(
            array_fill(0, 5, false),
            array_fill(0, 6, true)
        );

        $flow = $this->createFlow('ensure_app', Flow::STATE_SUCCESS);
        $this->logbook->expects($this->exactly(2))
            ->method('getList')
            ->willReturnOnConsecutiveCalls([], [$flow]);

        $this->manager->waitForAvailability('test-brancher', 1500, 6, 10);

        $this->assertSame(11, $this->sshPoller->pollCount);
    }

    public function testIrrelevantFlowsAreIgnored(): void
    {
        $this->sshPoller->pollResults = array_merge(
            array_fill(0, 5, false),
            array_fill(0, 6, true)
        );

        $irrelevantFlow = $this->createFlow('some_other_flow', Flow::STATE_RUNNING);
        $relevantFlow = $this->createFlow('ensure_app', Flow::STATE_SUCCESS);

        $this->logbook->expects($this->exactly(2))
            ->method('getList')
            ->willReturnOnConsecutiveCalls(
                [$irrelevantFlow],
                [$relevantFlow, $irrelevantFlow]
            );

        $this->manager->waitForAvailability('test-brancher', 1500, 6, 10);

        $this->assertSame(11, $this->sshPoller->pollCount);
    }

    private function createFlow(string $name, string $state): Flow
    {
        return new Flow([
            'name' => $name,
            'state' => $state,
        ]);
    }
}
