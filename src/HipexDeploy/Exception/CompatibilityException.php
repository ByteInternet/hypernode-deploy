<?php
/**
 * @author Emico <info@emico.nl>
 * @copyright (c) Emico B.V. 2020
 */
declare(strict_types = 1);

namespace HipexDeploy\Exception;

use Exception;

class CompatibilityException extends Exception
{
    /**
     * @param string $minimalVersion
     * @param string $package
     */
    public function __construct(string $minimalVersion, string $package)
    {
        parent::__construct("Package `{$package}` version must be >= {$minimalVersion}");
    }
}
