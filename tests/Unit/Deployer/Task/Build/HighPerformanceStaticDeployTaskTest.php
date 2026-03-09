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
