<?php

/**
 * @author Hypernode
 * @copyright Copyright (c) Hypernode
 */

namespace Hypernode\Deploy\Stdlib;

use function Deployer\run;
use function Deployer\test;

class CpuCoreInfo
{
    /**
     * @return int
     */
    public function count(): int
    {
        return max($this->getActualCores(), 10);
    }

    /**
     * @return int
     */
    private function getActualCores(): int
    {
        if (test('[ -f /proc/cpuinfo ]')) {
            return (int) run('cat /proc/cpuinfo | grep processor | wc -l');
        }

        $count = (int) run('/usr/sbin/sysctl -a 2>/dev/null | grep hw.ncpu | awk \'{print $2}\'');
        if ($count > 0) {
            return $count;
        }

        return 4;
    }
}
