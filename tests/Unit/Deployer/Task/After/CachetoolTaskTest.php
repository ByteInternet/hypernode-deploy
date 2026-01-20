<?php

declare(strict_types=1);

namespace Hypernode\Deploy\Tests\Unit\Deployer\Task\After;

use Hypernode\Deploy\Deployer\Task\After\CachetoolTask;
use PHPUnit\Framework\TestCase;

class CachetoolTaskTest extends TestCase
{
    private CachetoolTask $task;

    protected function setUp(): void
    {
        $this->task = new CachetoolTask();
    }

    /**
     * @dataProvider phpVersionToCachetoolUrlProvider
     */
    public function testGetCachetoolUrlReturnsCorrectVersionForPhpVersion(
        string $phpVersion,
        string $expectedUrlPart
    ): void {
        $result = $this->task->getCachetoolUrl($phpVersion);

        $this->assertStringContainsString($expectedUrlPart, $result);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function phpVersionToCachetoolUrlProvider(): array
    {
        return [
            // PHP 8.1+ should get cachetool 10.x
            'PHP 8.3.15 gets cachetool 10' => ['8.3.15', '10.0.0'],
            'PHP 8.2.0 gets cachetool 10' => ['8.2.0', '10.0.0'],
            'PHP 8.1.0 gets cachetool 10' => ['8.1.0', '10.0.0'],
            'PHP 8.1.27 gets cachetool 10' => ['8.1.27', '10.0.0'],

            // PHP 8.0.x should get cachetool 8.x
            'PHP 8.0.0 gets cachetool 8' => ['8.0.0', '8.6.1'],
            'PHP 8.0.30 gets cachetool 8' => ['8.0.30', '8.6.1'],

            // PHP 7.3+ should get cachetool 7.x
            'PHP 7.4.33 gets cachetool 7' => ['7.4.33', '7.1.0'],
            'PHP 7.3.0 gets cachetool 7' => ['7.3.0', '7.1.0'],
            'PHP 7.3.33 gets cachetool 7' => ['7.3.33', '7.1.0'],

            // PHP 7.2.x should get cachetool 5.x
            'PHP 7.2.0 gets cachetool 5' => ['7.2.0', '5.1.3'],
            'PHP 7.2.34 gets cachetool 5' => ['7.2.34', '5.1.3'],

            // PHP 7.1.x should get cachetool 4.x
            'PHP 7.1.0 gets cachetool 4' => ['7.1.0', '4.1.1'],
            'PHP 7.1.33 gets cachetool 4' => ['7.1.33', '4.1.1'],

            // Unsupported/old PHP versions fall back to latest (10.x)
            'PHP 7.0.33 falls back to cachetool 10' => ['7.0.33', '10.0.0'],
            'PHP 5.6.40 falls back to cachetool 10' => ['5.6.40', '10.0.0'],

            // Future PHP versions should get latest (10.x)
            'PHP 9.0.0 gets cachetool 10' => ['9.0.0', '10.0.0'],
            'PHP 8.4.0 gets cachetool 10' => ['8.4.0', '10.0.0'],
        ];
    }

    public function testGetCachetoolUrlReturnsFullGithubUrl(): void
    {
        $result = $this->task->getCachetoolUrl('8.2.0');

        $this->assertStringStartsWith('https://github.com/gordalina/cachetool/releases/download/', $result);
        $this->assertStringEndsWith('/cachetool.phar', $result);
    }

    public function testGetCachetoolUrlForPhp71ReturnsLegacyUrl(): void
    {
        $result = $this->task->getCachetoolUrl('7.1.0');

        // Cachetool 4.x uses a different URL format (gordalina.github.io)
        $this->assertStringStartsWith('https://gordalina.github.io/cachetool/downloads/', $result);
    }
}
