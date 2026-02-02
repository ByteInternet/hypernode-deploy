<?php

declare(strict_types=1);

namespace Hypernode\Deploy\Tests\Unit\Deployer\Task\Build;

use Hypernode\Deploy\Deployer\Task\Build\HighPerformanceStaticDeployTask;
use Hypernode\DeployConfiguration\Configuration;
use PHPUnit\Framework\TestCase;

class HighPerformanceStaticDeployTaskTest extends TestCase
{
    private HighPerformanceStaticDeployTask $task;

    protected function setUp(): void
    {
        $this->task = new HighPerformanceStaticDeployTask();
    }

    public function testIsEnabledReturnsFalseWhenNotConfigured(): void
    {
        $config = $this->createMock(Configuration::class);
        $config->method('getVariables')->willReturn([]);

        $this->assertFalse($this->task->isEnabled($config));
    }

    public function testIsEnabledReturnsTrueWhenEnabledInVariables(): void
    {
        $config = $this->createMock(Configuration::class);
        $config->method('getVariables')
            ->willReturnCallback(fn(string $stage = 'all') => match ($stage) {
                'all' => ['high_performance_static_deploy' => true],
                default => [],
            });

        $this->assertTrue($this->task->isEnabled($config));
    }

    public function testIsEnabledReturnsTrueWhenEnabledInBuildVariables(): void
    {
        $config = $this->createMock(Configuration::class);
        $config->method('getVariables')
            ->willReturnCallback(fn(string $stage = 'all') => match ($stage) {
                'build' => ['high_performance_static_deploy' => true],
                default => [],
            });

        $this->assertTrue($this->task->isEnabled($config));
    }

    public function testIsEnabledReturnsFalseWhenExplicitlyDisabled(): void
    {
        $config = $this->createMock(Configuration::class);
        $config->method('getVariables')
            ->willReturnCallback(fn(string $stage = 'all') => match ($stage) {
                'all' => ['high_performance_static_deploy' => false],
                default => [],
            });

        $this->assertFalse($this->task->isEnabled($config));
    }

    public function testGetVersionReturnsDefaultWhenNotConfigured(): void
    {
        $config = $this->createMock(Configuration::class);
        $config->method('getVariables')->willReturn([]);

        $this->assertSame('latest', $this->task->getVersion($config));
    }

    public function testGetVersionReturnsConfiguredVersion(): void
    {
        $config = $this->createMock(Configuration::class);
        $config->method('getVariables')
            ->willReturnCallback(fn(string $stage = 'all') => match ($stage) {
                'all' => ['high_performance_static_deploy_version' => '1.0.0'],
                default => [],
            });

        $this->assertSame('1.0.0', $this->task->getVersion($config));
    }

    public function testGetVersionReturnsBuildVersionWhenNotInAllVariables(): void
    {
        $config = $this->createMock(Configuration::class);
        $config->method('getVariables')
            ->willReturnCallback(fn(string $stage = 'all') => match ($stage) {
                'build' => ['high_performance_static_deploy_version' => '2.0.0'],
                default => [],
            });

        $this->assertSame('2.0.0', $this->task->getVersion($config));
    }

    public function testGetBinaryUrlReturnsLatestUrl(): void
    {
        $result = $this->task->getBinaryUrl('latest');

        $this->assertSame(
            'https://github.com/elgentos/magento2-static-deploy/releases/latest/download/magento2-static-deploy-linux-amd64',
            $result
        );
    }

    public function testGetBinaryUrlReturnsVersionedUrl(): void
    {
        $result = $this->task->getBinaryUrl('0.0.8');

        $this->assertSame(
            'https://github.com/elgentos/magento2-static-deploy/releases/download/0.0.8/magento2-static-deploy-linux-amd64',
            $result
        );
    }

    public function testGetBinaryUrlWithDifferentVersion(): void
    {
        $result = $this->task->getBinaryUrl('1.2.3');

        $this->assertStringContainsString('1.2.3', $result);
        $this->assertStringContainsString('magento2-static-deploy-linux-amd64', $result);
    }

    public function testBuildThemeArgsWithSingleTheme(): void
    {
        $themes = ['Vendor/theme' => 'nl_NL en_US'];

        $result = $this->task->buildThemeArgs($themes);

        $this->assertSame('--theme=Vendor/theme', $result);
    }

    public function testBuildThemeArgsWithMultipleThemes(): void
    {
        $themes = [
            'Vendor/theme1' => 'nl_NL',
            'Vendor/theme2' => 'en_US',
            'Vendor/theme3' => 'de_DE',
        ];

        $result = $this->task->buildThemeArgs($themes);

        $this->assertSame('--theme=Vendor/theme1 --theme=Vendor/theme2 --theme=Vendor/theme3', $result);
    }

    public function testBuildThemeArgsWithEmptyArray(): void
    {
        $result = $this->task->buildThemeArgs([]);

        $this->assertSame('', $result);
    }
}
