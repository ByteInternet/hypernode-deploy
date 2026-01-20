<?php

declare(strict_types=1);

namespace Hypernode\Deploy\Brancher;

class SshPoller
{
    /**
     * Check if SSH port is reachable on the given hostname.
     *
     * @param string $hostname The hostname to check (without .hypernode.io suffix)
     * @return bool True if SSH port 22 is reachable
     */
    public function poll(string $hostname): bool
    {
        $connection = @fsockopen(sprintf('%s.hypernode.io', $hostname), 22);
        if ($connection) {
            fclose($connection);
            return true;
        }
        return false;
    }

    /**
     * Sleep for the given number of seconds.
     *
     * @param int $seconds Number of seconds to sleep
     */
    public function sleep(int $seconds): void
    {
        sleep($seconds);
    }

    /**
     * Get the current time in microseconds.
     *
     * @return float Current time as a float
     */
    public function microtime(): float
    {
        return microtime(true);
    }
}
