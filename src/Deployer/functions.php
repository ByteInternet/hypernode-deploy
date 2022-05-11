<?php

namespace Hypernode\Deploy\Deployer;

use Deployer\Deployer;
use Hypernode\DeployConfiguration\Exception\EnvironmentVariableNotDefinedException;
use function Hypernode\DeployConfiguration\getenv;

/**
 * Call that task before specified task runs.
 *
 * @param string $it The task before $that should be run.
 * @param string $that The task to be run.
 */
function before($it, $that)
{
    $deployer = Deployer::get();
    if (!$deployer->tasks->has($that)) {
        return;
    }

    $beforeTask = $deployer->tasks->get($it);

    $beforeTask->addBefore($that);
}

/**
 * Call that task after specified task runs.
 *
 * @param string $it The task after $that should be run.
 * @param string $that The task to be run.
 */
function after($it, $that)
{
    $deployer = Deployer::get();
    if (!$deployer->tasks->has($that)) {
        return;
    }

    $afterTask = $deployer->tasks->get($it);

    $afterTask->addAfter($that);
}

/**
 * @param array $variables
 * @return string
 * @throws EnvironmentVariableNotDefinedException
 */
function getenvFallback(array $variables): string
{
    foreach ($variables as $variable) {
        try {
            return getenv($variable);
        } catch (EnvironmentVariableNotDefinedException $e) {
            // Try next
        }
    }

    throw new EnvironmentVariableNotDefinedException(
        sprintf('None of the requested environment variables %s is defined', implode(', ', $variables))
    );
}
