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
$stagingStage->addServer('hndeployintegr8.hypernode.io', null, [], [
    'user' => 'app',
    'port' => 22,
]);

$productionStage = $configuration->addStage('test', 'banaan.store');
$productionStage->addBrancherServer('hndeployintegr8')
    ->setLabels(['gitref='.\getenv('GITHUB_REF_NAME') ?: 'unknown']);

return $configuration;
