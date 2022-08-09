<?php

namespace Hypernode\DeployConfiguration;

/**
 * Start by setting up the configuration
 *
 * The magento 2 configuration contains some default configuration for shared folders / files and running installers
 * @see ApplicationTemplate\Magento2::initializeDefaultConfiguration
 */
$configuration = new ApplicationTemplate\Magento2('https://github.com/ByteInternet/hypernode-deploy-configuration.git', ['nl_NL'], ['en_GB', 'nl_NL']);

$productionStage = $configuration->addStage('production', 'magento2.komkommer.store', 'app');
$productionStage->addServer('hypernode', null, [], [
    'user' => 'app',
    'port' => 22,
]);

$configuration->setPlatformConfigurations([
    new PlatformConfiguration\NginxConfiguration("etc/nginx"),
    new PlatformConfiguration\SupervisorConfiguration("etc/supervisor"),
    new PlatformConfiguration\CronConfiguration("etc/cron"),
    new PlatformConfiguration\HypernodeSettingConfiguration("php_version", "8.1"),
    new PlatformConfiguration\HypernodeSettingConfiguration("rabbitmq_enabled", "True"),
]);
$configuration->setSharedFiles([
    'app/etc/env.php',
    'pub/errors/local.xml',
    '.user.ini',
    'pub/.user.ini'
]);

$configuration->setSharedFolders([
    'var/log',
    'var/session',
    'var/report',
    'var/export',
    'pub/media',
    'pub/sitemaps',
    'pub/static/_cache'
]);

return $configuration;
