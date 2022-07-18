<?php

namespace Hypernode\DeployConfiguration;

/**
 * Start by setting up the configuration
 *
 * The magento 2 configuration contains some default configuration for shared folders / files and running installers
 * @see ApplicationTemplate\Magento2::initializeDefaultConfiguration
 */
$configuration = new ApplicationTemplate\Magento2('https://github.com/ByteInternet/deploy-configuration.git', ['nl_NL'], ['en_GB', 'nl_NL']);

$productionStage = $configuration->addStage('production', 'magento2.komkommer.store', 'app');
$productionStage->addServer('hypernode', [], [], [
    'user' => 'app',
    'port' => 22,
]);

return $configuration;
