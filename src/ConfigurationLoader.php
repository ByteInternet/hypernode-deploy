<?php

declare(strict_types=1);

namespace Hypernode\Deploy;

use Deployer\Deployer;
use Deployer\Exception\Exception;
use Deployer\Exception\GracefulShutdownException;
use Hypernode\DeployConfiguration\Configuration;
use Throwable;

class ConfigurationLoader
{
    /**
     * @throws Exception
     * @throws GracefulShutdownException
     * @throws Throwable
     */
    public function load(string $file): Configuration
    {
        if (!is_readable($file)) {
            throw new \RuntimeException(sprintf('No %s file found in project root %s', $file, getcwd()));
        }

        $configuration = \call_user_func(function () use ($file) {
            return require $file;
        });

        if (!$configuration instanceof Configuration) {
            throw new \RuntimeException(
                sprintf('%s/deploy.php did not return object of type %s', getcwd(), Configuration::class)
            );
        }

        return $configuration;
    }
}
