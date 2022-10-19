<?php

namespace Hypernode\DeployConfiguration;

/**
 * Start by setting up the configuration
 *
 * The magento 2 configuration contains some default configuration for shared folders / files and running installers
 * @see ApplicationTemplate\Magento2::initializeDefaultConfiguration
 */

$configuration = new ApplicationTemplate\Magento2(['en_US', 'nl_NL']);

$stagingStage = $configuration->addStage('staging', 'banaan.store');
$stagingStage->addServer('hypernode', null, [], [
    'user' => 'app',
    'port' => 22,
]);

$productionStage = $configuration->addStage('test', 'banaan.store');
$productionStage->addServer('hypernode', null, [], [
    'user' => 'app',
    'port' => 22,
]);

$productionStage->addEphemeralServer('hndeployintegr8');

return $configuration;
