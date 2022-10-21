<?php

namespace Hypernode\Deploy\Brancher;

use Hypernode\Api\Exception\HypernodeApiClientException;
use Hypernode\Api\Exception\HypernodeApiServerException;
use Hypernode\Api\HypernodeClient;
use Hypernode\Api\HypernodeClientFactory;
use Hypernode\Api\Resource\Logbook\Flow;
use Hypernode\Deploy\Exception\CreateBrancherHypernodeFailedException;
use Hypernode\Deploy\Exception\TimeoutException;
use Psr\Log\LoggerInterface;

class BrancherHypernodeManager
{
    private LoggerInterface $log;
    private HypernodeClient $hypernodeClient;

    public function __construct(LoggerInterface $log)
    {
        $this->log = $log;
        $this->hypernodeClient = HypernodeClientFactory::create(getenv('HYPERNODE_API_TOKEN') ?: '');
    }

    /**
     * Create brancher Hypernode instance for given Hypernode.
     *
     * @param string $hypernode Name of the Hypernode
     * @return string Name of the created brancher Hypernode
     * @throws HypernodeApiClientException
     * @throws HypernodeApiServerException
     */
    public function createForHypernode(string $hypernode): string
    {
        return $this->hypernodeClient->brancherApp->create($hypernode);
    }

    /**
     * Wait for brancher Hypernode to become available.
     *
     * @param string $brancherHypernode Name of the brancher Hypernode
     * @param int $timeout Maximum time to wait for availability
     * @return void
     * @throws CreateBrancherHypernodeFailedException
     * @throws HypernodeApiClientException
     * @throws HypernodeApiServerException
     * @throws TimeoutException
     */
    public function waitForAvailability(string $brancherHypernode, int $timeout = 900): void
    {
        $latest = microtime(true);
        $timeElapsed = 0;
        $resolved = false;
        $interval = 3;
        $allowedErrorWindow = 3;

        while ($timeElapsed < $timeout && !$resolved) {
            $now = microtime(true);
            $timeElapsed += $now - $latest;
            $latest = $now;

            try {
                $flows = $this->hypernodeClient->logbook->getList($brancherHypernode);
                $relevantFlows = array_filter($flows, fn (Flow $flow) => $flow->name === 'ensure_app');
                $failedFlows = array_filter($flows, fn (Flow $flow) => $flow->isReverted());
                $completedFlows = array_filter($flows, fn (Flow $flow) => $flow->isComplete());

                if (count($failedFlows) === count($relevantFlows)) {
                    throw new CreateBrancherHypernodeFailedException();
                }

                if ($relevantFlows && count($completedFlows) === count($relevantFlows)) {
                    $resolved = true;
                    break;
                }
            } catch (HypernodeApiClientException $e) {
                // A 404 not found means there are no flows in the logbook yet, we should wait.
                // Otherwise, there's an error, and it should be propagated.
                if ($e->getCode() !== 404) {
                    throw $e;
                } elseif ($timeElapsed < $allowedErrorWindow) {
                    // Sometimes we get an error where the logbook is not yet available, but it will be soon.
                    // We allow a small window for this to happen, and then we throw an exception.
                    sprintf(
                        'Got an expected exception during the allowed error window of HTTP code %d, waiting for %s to become available',
                        $e->getCode(),
                        $brancherHypernode
                    );
                    continue;
                }
            }

            sleep($interval);
        }

        if (!$resolved) {
            throw new TimeoutException(
                sprintf('Timed out waiting for brancher Hypernode %s to become available', $brancherHypernode)
            );
        }
    }

    /**
     * Cancel one or multiple brancher Hypernodes.
     *
     * @param string ...$brancherHypernodes Name(s) of the brancher Hypernode(s)
     * @throws HypernodeApiClientException
     * @throws HypernodeApiServerException
     */
    public function cancel(string ...$brancherHypernodes): void
    {
        foreach ($brancherHypernodes as $brancherHypernode) {
            $this->log->info(sprintf('Stopping brancher Hypernode %s...', $brancherHypernode));
            $this->hypernodeClient->brancherApp->cancel($brancherHypernode);
        }
    }
}
