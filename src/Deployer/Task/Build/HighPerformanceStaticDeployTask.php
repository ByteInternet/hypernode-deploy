<?php

declare(strict_types=1);

namespace Hypernode\Deploy\Deployer\Task\Build;

use Hypernode\Deploy\Deployer\Task\TaskBase;
use Hypernode\DeployConfiguration\Configuration;

use function Deployer\get;
use function Deployer\run;
use function Deployer\task;
use function Deployer\within;

/**
 * High-performance static content deployment using elgentos/magento2-static-deploy.
 *
 * @see https://github.com/elgentos/magento2-static-deploy
 */
class HighPerformanceStaticDeployTask extends TaskBase
{
    private const BINARY_URL_VERSIONED = 'https://github.com/elgentos/magento2-static-deploy/releases/download/%s/magento2-static-deploy-linux-amd64';
    private const BINARY_URL_LATEST = 'https://github.com/elgentos/magento2-static-deploy/releases/latest/download/magento2-static-deploy-linux-amd64';
    private const DEFAULT_VERSION = 'latest';

    public function configure(Configuration $config): void
    {
        if (!$this->isEnabled($config)) {
            return;
        }

        $version = $this->getVersion($config);

        task('magento:deploy:assets', function () use ($version) {
            within('{{release_or_current_path}}', function () use ($version) {
                run(sprintf('curl -sL -o /tmp/magento2-static-deploy %s', $this->getBinaryUrl($version)));
                run('chmod +x /tmp/magento2-static-deploy');
            });

            $themes = get('magento_themes', []);
            $themeArgs = $this->buildThemeArgs($themes);
            $locales = get('static_content_locales', 'en_US');
            $contentVersion = get('content_version', time());

            within('{{release_or_current_path}}', function () use ($themeArgs, $locales, $contentVersion) {
                run("/tmp/magento2-static-deploy --force --area=frontend --area=adminhtml $themeArgs --content-version=$contentVersion --verbose $locales");
            });
        })->select('stage=build');
    }

    public function isEnabled(Configuration $config): bool
    {
        $variables = $config->getVariables();
        $buildVariables = $config->getVariables('build');

        return $variables['high_performance_static_deploy']
            ?? $buildVariables['high_performance_static_deploy']
            ?? false;
    }

    public function getVersion(Configuration $config): string
    {
        $variables = $config->getVariables();
        $buildVariables = $config->getVariables('build');

        return $variables['high_performance_static_deploy_version']
            ?? $buildVariables['high_performance_static_deploy_version']
            ?? self::DEFAULT_VERSION;
    }

    public function getBinaryUrl(string $version): string
    {
        if ($version === 'latest') {
            return self::BINARY_URL_LATEST;
        }

        return sprintf(self::BINARY_URL_VERSIONED, $version);
    }

    /**
     * @param array<string, string> $themes
     */
    public function buildThemeArgs(array $themes): string
    {
        return implode(' ', array_map(fn($t) => "--theme=$t", array_keys($themes)));
    }
}
