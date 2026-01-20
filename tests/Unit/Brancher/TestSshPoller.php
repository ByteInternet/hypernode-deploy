<?php

declare(strict_types=1);

namespace Hypernode\Deploy\Tests\Unit\Brancher;

use Hypernode\Deploy\Brancher\SshPoller;

class TestSshPoller extends SshPoller
{
    /** @var bool[] Queue of poll results to return */
    public array $pollResults = [];
    private int $pollIndex = 0;

    /** @var int Count of poll calls */
    public int $pollCount = 0;

    /** @var int Count of sleep calls */
    public int $sleepCount = 0;

    /** @var int[] Accumulated sleep seconds */
    public array $sleepCalls = [];

    /** @var float Simulated microtime value */
    private float $currentMicrotime = 0.0;

    /** @var int Seconds to advance microtime on each sleep call */
    public int $sleepTimeAdvance = 0;

    /**
     * Set the starting microtime value
     */
    public function setMicrotime(float $time): void
    {
        $this->currentMicrotime = $time;
    }

    /**
     * Advance microtime by a specific amount
     */
    public function advanceMicrotime(float $seconds): void
    {
        $this->currentMicrotime += $seconds;
    }

    public function poll(string $hostname): bool
    {
        $this->pollCount++;
        if ($this->pollIndex >= count($this->pollResults)) {
            return false; // Default to false if we run out of results
        }
        return $this->pollResults[$this->pollIndex++];
    }

    public function sleep(int $seconds): void
    {
        $this->sleepCount++;
        $this->sleepCalls[] = $seconds;

        // Advance simulated time
        if ($this->sleepTimeAdvance > 0) {
            $this->currentMicrotime += $this->sleepTimeAdvance;
        } else {
            $this->currentMicrotime += $seconds;
        }
        // Don't actually sleep in tests
    }

    public function microtime(): float
    {
        return $this->currentMicrotime;
    }
}
