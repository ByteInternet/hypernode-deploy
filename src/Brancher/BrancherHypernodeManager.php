<?php

declare(strict_types=1);

namespace Hypernode\Deploy\Brancher;

use Hypernode\Api\Exception\HypernodeApiClientException;
use Hypernode\Api\Exception\HypernodeApiServerException;
use Hypernode\Api\Exception\ResponseException;
use Hypernode\Api\HypernodeClient;
use Hypernode\Api\HypernodeClientFactory;
use Hypernode\Api\Resource\Logbook\Flow;
use Hypernode\Deploy\Exception\CreateBrancherHypernodeFailedException;
use Hypernode\Deploy\Exception\TimeoutException;
use Psr\Log\LoggerInterface;

class BrancherHypernodeManager
{
    /**
     * Relevant flow names to poll for delivery
     *
     * @var string[]
     */
    public const RELEVANT_FLOW_NAMES = ['ensure_app', 'ensure_copied_app'];
    public const PRE_POLL_SUCCESS_COUNT = 3;
    public const PRE_POLL_FAIL_COUNT = 5;

    private LoggerInterface $log;
    private HypernodeClient $hypernodeClient;
    private SshPoller $sshPoller;

    public function __construct(
        LoggerInterface $log,
        ?HypernodeClient $hypernodeClient = null,
        ?SshPoller $sshPoller = null
    ) {
        $this->log = $log;
        $this->hypernodeClient = $hypernodeClient
            ?? HypernodeClientFactory::create(getenv('HYPERNODE_API_TOKEN') ?: '');
        $this->sshPoller = $sshPoller ?? new SshPoller();
    }

    /**
     * Query brancher instances for given Hypernode and return the Brancher instance names.
     *
     * @param string $hypernode The parent hypernode to query the Brancher instances from
     * @param string[] $labels Labels to match against, may be empty
     * @return string[] The found Brancher instance names
     * @throws ResponseException
     */
    public function queryBrancherHypernodes(string $hypernode, array $labels = []): array
    {
        $result = [];

        $hypernodes = $this->hypernodeClient->app->getList([
            'parent' => $hypernode,
            'type' => 'brancher',
            'destroyed' => 'False',
        ]);
        foreach ($hypernodes as $brancher) {
            $match = true;

            foreach ($labels as $label) {
                if (!in_array($label, $brancher->labels)) {
                    $match = false;
                    break;
                }
            }

            if ($match) {
                $result[] = $brancher->name;
            }
        }

        return $result;
    }

    /**
     * Query brancher instances for the given Hypernode and label and return the
     * most recent Brancher instance name.
     *
     * @param string $hypernode The parent hypernode to query the Brancher instances from
     * @param string[] $labels Labels to match against, may be empty
     * @return string|null The found Brancher instance name, or null if none was found
     */
    public function reuseExistingBrancherHypernode(string $hypernode, array $labels = []): ?string
    {
        try {
            $brancherHypernodes = $this->queryBrancherHypernodes($hypernode, $labels);
            if (count($brancherHypernodes) > 0) {
                // Return the last brancher Hypernode, which is the most recently created one
                return $brancherHypernodes[count($brancherHypernodes) - 1];
            }
        } catch (ResponseException $e) {
            $this->log->error(
                sprintf(
                    'Got an API exception (code %d) while querying for existing brancher Hypernodes for Hypernode %s with labels (%s).',
                    $e->getCode(),
                    $hypernode,
                    implode(', ', $labels)
                )
            );
        }

        return null;
    }

    /**
     * Create brancher Hypernode instance for given Hypernode.
     *
     * @param string $hypernode Name of the Hypernode
     * @param string[] $data Extra data to be applied to brancher instance
     * @return string Name of the created brancher Hypernode
     * @throws HypernodeApiClientException
     * @throws HypernodeApiServerException
     */
    public function createForHypernode(string $hypernode, array $data = []): string
    {
        return $this->hypernodeClient->brancherApp->create($hypernode, $data);
    }

    /**
     * Wait for brancher Hypernode to become available.
     *
     * This method first attempts a quick SSH connectivity check. If the brancher is already
     * reachable (e.g., when reusing an existing brancher), it returns early. Otherwise, it
     * falls back to polling the API logbook for delivery status, then performs a final SSH
     * reachability check.
     *
     * @param string $brancherHypernode Name of the brancher Hypernode
     * @param int $timeout Maximum time to wait for availability
     * @param int $reachabilityCheckCount Number of consecutive successful checks required
     * @param int $reachabilityCheckInterval Seconds between reachability checks
     * @return void
     * @throws CreateBrancherHypernodeFailedException
     * @throws HypernodeApiClientException
     * @throws HypernodeApiServerException
     * @throws TimeoutException
     */
    public function waitForAvailability(
        string $brancherHypernode,
        int $timeout = 1500,
        int $reachabilityCheckCount = 6,
        int $reachabilityCheckInterval = 10
    ): void {
        $latest = $this->sshPoller->microtime();
        $timeElapsed = 0.0;

        // Phase 1: SSH-first check, early return for reused delivered branchers
        $this->log->info(
            sprintf('Attempting SSH connectivity check for brancher Hypernode %s...', $brancherHypernode)
        );

        $isReachable = $this->pollSshConnectivity(
            $brancherHypernode,
            self::PRE_POLL_SUCCESS_COUNT,
            self::PRE_POLL_FAIL_COUNT,
            $reachabilityCheckInterval,
            $timeElapsed,
            $latest,
            $timeout
        );
        if ($isReachable) {
            $this->log->info(
                sprintf('Brancher Hypernode %s is reachable!', $brancherHypernode)
            );
            return;
        }

        $this->log->info(
            sprintf(
                'SSH check inconclusive for brancher Hypernode %s, falling back to delivery check...',
                $brancherHypernode
            )
        );

        // Phase 2: Wait for delivery by polling the logbook
        $resolved = false;
        $interval = 3;
        $allowedErrorWindow = 3;
        $logbookStartTime = $timeElapsed;

        while ($timeElapsed < $timeout) {
            $now = $this->sshPoller->microtime();
            $timeElapsed += $now - $latest;
            $latest = $now;

            try {
                $flows = $this->hypernodeClient->logbook->getList($brancherHypernode);
                $relevantFlows = array_filter(
                    $flows,
                    fn(Flow $flow) => in_array($flow->name, self::RELEVANT_FLOW_NAMES, true)
                );
                $failedFlows = array_filter($relevantFlows, fn(Flow $flow) => $flow->isReverted());
                $completedFlows = array_filter($relevantFlows, fn(Flow $flow) => $flow->isComplete());

                if (count($relevantFlows) > 0 && count($failedFlows) === count($relevantFlows)) {
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
                } elseif (($timeElapsed - $logbookStartTime) < $allowedErrorWindow) {
                    // Sometimes we get an error where the logbook is not yet available, but it will be soon.
                    // We allow a small window for this to happen, and then we continue polling.
                    $this->log->info(
                        sprintf(
                            'Got an expected exception during the allowed error window of HTTP code %d, waiting for %s to become available.',
                            $e->getCode(),
                            $brancherHypernode
                        )
                    );
                }
            }

            $this->sshPoller->sleep($interval);
        }

        if (!$resolved) {
            throw new TimeoutException(
                sprintf('Timed out waiting for brancher Hypernode %s to be delivered', $brancherHypernode)
            );
        }

        $this->log->info(
            sprintf(
                'Brancher Hypernode %s was delivered. Now waiting for node to become reachable...',
                $brancherHypernode
            )
        );

        // Phase 3: Final SSH reachability check
        $isReachable = $this->pollSshConnectivity(
            $brancherHypernode,
            $reachabilityCheckCount,
            0, // No max failures, rely on timeout
            $reachabilityCheckInterval,
            $timeElapsed,
            $latest,
            $timeout
        );
        if (!$isReachable) {
            throw new TimeoutException(
                sprintf('Timed out waiting for brancher Hypernode %s to become reachable', $brancherHypernode)
            );
        }

        $this->log->info(
            sprintf('Brancher Hypernode %s became reachable!', $brancherHypernode)
        );
    }

    /**
     * Poll SSH connectivity until we get enough consecutive successes or hit a limit.
     *
     * @param string $brancherHypernode Hostname to check
     * @param int $requiredConsecutiveSuccesses Number of consecutive successes required
     * @param int $maxFailedAttempts Maximum failed attempts before giving up (0 = no limit, use timeout only)
     * @param int $checkInterval Seconds between checks
     * @param float $timeElapsed Reference to track elapsed time
     * @param float $latest Reference to track latest timestamp
     * @param int $timeout Maximum time allowed
     * @return bool True if SSH check succeeded, false if we should fall back to other methods
     */
    private function pollSshConnectivity(
        string $brancherHypernode,
        int $requiredConsecutiveSuccesses,
        int $maxFailedAttempts,
        int $checkInterval,
        float &$timeElapsed,
        float &$latest,
        int $timeout
    ): bool {
        $consecutiveSuccesses = 0;
        $failedAttempts = 0;

        while ($timeElapsed < $timeout) {
            $now = $this->sshPoller->microtime();
            $timeElapsed += $now - $latest;
            $latest = $now;

            // Check if we've hit the max failed attempts limit (0 = unlimited)
            if ($maxFailedAttempts > 0 && $failedAttempts >= $maxFailedAttempts) {
                return false;
            }

            if ($this->sshPoller->poll($brancherHypernode)) {
                $consecutiveSuccesses++;
                $this->log->info(
                    sprintf(
                        'Brancher Hypernode %s reachability check %d/%d succeeded.',
                        $brancherHypernode,
                        $consecutiveSuccesses,
                        $requiredConsecutiveSuccesses
                    )
                );

                if ($consecutiveSuccesses >= $requiredConsecutiveSuccesses) {
                    return true;
                }
            } else {
                if ($consecutiveSuccesses > 0) {
                    $this->log->info(
                        sprintf(
                            'Brancher Hypernode %s reachability check failed, resetting counter (was at %d/%d).',
                            $brancherHypernode,
                            $consecutiveSuccesses,
                            $requiredConsecutiveSuccesses
                        )
                    );
                }
                $consecutiveSuccesses = 0;
                $failedAttempts++;
            }

            $this->sshPoller->sleep($checkInterval);
        }

        return false;
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
