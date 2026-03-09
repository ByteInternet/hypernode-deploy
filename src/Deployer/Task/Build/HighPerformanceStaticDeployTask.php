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
    private const BINARY_PATH = '/opt/magento2-static-deploy';

    public function configure(Configuration $config): void
    {
        if (!$this->isEnabled($config)) {
            return;
        }

        task('magento:deploy:assets', function () {
            $themes = get('magento_themes', []);
            $themeArgs = $this->buildThemeArgs($themes);
            $locales = get('static_content_locales', 'en_US');
            $contentVersion = get('content_version', time());

            within('{{release_or_current_path}}', function () use ($themeArgs, $locales, $contentVersion) {
                run(self::BINARY_PATH . " --force --area=frontend --area=adminhtml $themeArgs --content-version=$contentVersion --verbose $locales");
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

    /**
     * @param array<string, string> $themes
     */
    public function buildThemeArgs(array $themes): string
    {
        return implode(' ', array_map(fn($t) => "--theme=$t", array_keys($themes)));
    }
}
