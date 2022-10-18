<?php

namespace Hypernode\DeployConfiguration;

/**
 * Start by setting up the configuration
 *
 * The magento 2 configuration contains some default configuration for shared folders / files and running installers
 * @see ApplicationTemplate\Magento2::initializeDefaultConfiguration
 */

$configuration = new ApplicationTemplate\Magento2(['en_US', 'nl_NL']);

$configuration->addStage('staging', 'banaan.store');

$productionStage = $configuration->addStage('test', 'banaan.store');
$productionStage->addEphemeralServer('hndeployintegr8');

return $configuration;
