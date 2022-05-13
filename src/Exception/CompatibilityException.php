<?php

declare(strict_types=1);

namespace Hypernode\Deploy\Exception;

use Exception;

class CompatibilityException extends Exception
{
    public function __construct(string $minimalVersion, string $package)
    {
        parent::__construct("Package `{$package}` version must be >= {$minimalVersion}");
    }
}
