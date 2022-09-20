<?php

namespace Hypernode\Deploy\Ephemeral;

use Hypernode\Api\Exception\HypernodeApiClientException;
use Hypernode\Api\Exception\HypernodeApiServerException;
use Hypernode\Api\HypernodeClient;
use Hypernode\Api\HypernodeClientFactory;
use Hypernode\Api\Resource\Logbook\Flow;
use Hypernode\Deploy\Exception\CreateEphemeralHypernodeFailedException;
use Hypernode\Deploy\Exception\TimeoutException;
use Psr\Log\LoggerInterface;

class EphemeralHypernodeManager
{
    private LoggerInterface $log;
    private HypernodeClient $hypernodeClient;

    public function __construct(LoggerInterface $log)
    {
        $this->log = $log;
        $this->hypernodeClient = HypernodeClientFactory::create(getenv('HYPERNODE_API_TOKEN') ?: '');
    }

    /**
     * Create ephemeral Hypernode instance for given Hypernode.
     *
     * @param string $hypernode Name of the Hypernode
     * @return string Name of the created ephemeral Hypernode
     * @throws HypernodeApiClientException
     * @throws HypernodeApiServerException
     */
    public function createForHypernode(string $hypernode): string
    {
        return $this->hypernodeClient->ephemeralApp->create($hypernode);
    }

    /**
     * Wait for ephemeral Hypernode to become available.
     *
     * @param string $ephemeralHypernode Name of the ephemeral Hypernode
     * @param int $timeout Maximum time to wait for availability
     * @return void
     * @throws CreateEphemeralHypernodeFailedException
     * @throws HypernodeApiClientException
     * @throws HypernodeApiServerException
     * @throws TimeoutException
     */
    public function waitForAvailability(string $ephemeralHypernode, int $timeout = 900): void
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
                $flows = $this->hypernodeClient->logbook->getList($ephemeralHypernode);
                $relevantFlows = array_filter($flows, fn (Flow $flow) => $flow->name === 'ensure_app');
                $failedFlows = array_filter($flows, fn (Flow $flow) => $flow->isReverted());
                $completedFlows = array_filter($flows, fn (Flow $flow) => $flow->isComplete());

                if (count($failedFlows) === count($relevantFlows)) {
                    throw new CreateEphemeralHypernodeFailedException();
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
                        $ephemeralHypernode
                    );
                    continue;
                }
            }

            sleep($interval);
        }

        if (!$resolved) {
            throw new TimeoutException(
                sprintf('Timed out waiting for ephemeral Hypernode %s to become available', $ephemeralHypernode)
            );
        }
    }

    /**
     * Cancel one or multiple ephemeral Hypernodes.
     *
     * @param string ...$ephemeralHypernodes Name(s) of the ephemeral Hypernode(s)
     * @throws HypernodeApiClientException
     * @throws HypernodeApiServerException
     */
    public function cancel(string ...$ephemeralHypernodes): void
    {
        foreach ($ephemeralHypernodes as $ephemeralHypernode) {
            $this->log->info(sprintf('Stopping ephemeral Hypernode %s...', $ephemeralHypernode));
            $this->hypernodeClient->ephemeralApp->cancel($ephemeralHypernode);
        }
    }
}
