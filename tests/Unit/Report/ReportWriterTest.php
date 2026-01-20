<?php

declare(strict_types=1);

namespace Hypernode\Deploy\Tests\Unit\Report;

use Hypernode\Deploy\Report\Report;
use Hypernode\Deploy\Report\ReportWriter;
use PHPUnit\Framework\TestCase;

class ReportWriterTest extends TestCase
{
    private string $tempDir;
    private string $reportPath;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/hypernode-deploy-test-' . uniqid();
        mkdir($this->tempDir, 0777, true);
        $this->reportPath = $this->tempDir . '/' . Report::REPORT_FILENAME;
    }

    protected function tearDown(): void
    {
        if (file_exists($this->reportPath)) {
            unlink($this->reportPath);
        }
        if (is_dir($this->tempDir)) {
            rmdir($this->tempDir);
        }
    }

    public function testWriteCreatesJsonFile(): void
    {
        $report = new Report('production', ['app.hypernode.io'], ['app-ephabc123.hypernode.io']);

        $writer = new ReportWriter($this->reportPath);
        $writer->write($report);

        $this->assertFileExists($this->reportPath);
    }

    public function testWriteCreatesValidJson(): void
    {
        $report = new Report('production', ['app.hypernode.io'], ['app-ephabc123.hypernode.io']);

        $writer = new ReportWriter($this->reportPath);
        $writer->write($report);

        $contents = file_get_contents($this->reportPath);
        $decoded = json_decode($contents, true);

        $this->assertNotNull($decoded);
        $this->assertIsArray($decoded);
    }

    public function testWriteContainsCorrectData(): void
    {
        $report = new Report(
            'staging',
            ['staging.hypernode.io', 'staging2.hypernode.io'],
            ['staging-ephabc123.hypernode.io'],
            'v1'
        );

        $writer = new ReportWriter($this->reportPath);
        $writer->write($report);

        $contents = file_get_contents($this->reportPath);
        $decoded = json_decode($contents, true);

        $this->assertSame('v1', $decoded['version']);
        $this->assertSame('staging', $decoded['stage']);
        $this->assertSame(['staging.hypernode.io', 'staging2.hypernode.io'], $decoded['hostnames']);
        $this->assertSame(['staging-ephabc123.hypernode.io'], $decoded['brancher_hypernodes']);
    }

    public function testWriteOverwritesExistingFile(): void
    {
        file_put_contents($this->reportPath, '{"old": "data"}');

        $report = new Report('production', ['new.hypernode.io'], []);

        $writer = new ReportWriter($this->reportPath);
        $writer->write($report);

        $contents = file_get_contents($this->reportPath);
        $decoded = json_decode($contents, true);

        $this->assertSame('production', $decoded['stage']);
        $this->assertArrayNotHasKey('old', $decoded);
    }

    public function testWriteWithEmptyArrays(): void
    {
        $report = new Report('test', [], []);

        $writer = new ReportWriter($this->reportPath);
        $writer->write($report);

        $contents = file_get_contents($this->reportPath);
        $decoded = json_decode($contents, true);

        $this->assertSame([], $decoded['hostnames']);
        $this->assertSame([], $decoded['brancher_hypernodes']);
    }
}
