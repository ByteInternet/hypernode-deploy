<?php

declare(strict_types=1);

namespace Hypernode\Deploy\Tests\Unit\Report;

use Hypernode\Deploy\Report\Report;
use Hypernode\Deploy\Report\ReportLoader;
use JsonException;
use PHPUnit\Framework\TestCase;

class ReportLoaderTest extends TestCase
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

    public function testLoadReportReturnsNullWhenFileDoesNotExist(): void
    {
        $loader = new ReportLoader($this->reportPath);

        $result = $loader->loadReport();

        $this->assertNull($result);
    }

    public function testLoadReportReturnsReportWhenFileExists(): void
    {
        $data = [
            'version' => 'v1',
            'stage' => 'production',
            'hostnames' => ['app.hypernode.io'],
            'brancher_hypernodes' => ['app-ephabc123.hypernode.io'],
        ];
        file_put_contents($this->reportPath, json_encode($data));

        $loader = new ReportLoader($this->reportPath);

        $result = $loader->loadReport();

        $this->assertInstanceOf(Report::class, $result);
    }

    public function testLoadReportParsesJsonCorrectly(): void
    {
        $data = [
            'version' => 'v1',
            'stage' => 'staging',
            'hostnames' => ['staging.hypernode.io', 'staging2.hypernode.io'],
            'brancher_hypernodes' => ['staging-ephabc123.hypernode.io'],
        ];
        file_put_contents($this->reportPath, json_encode($data));

        $loader = new ReportLoader($this->reportPath);

        $result = $loader->loadReport();

        $this->assertSame('v1', $result->getVersion());
        $this->assertSame('staging', $result->getStage());
        $this->assertSame(['staging.hypernode.io', 'staging2.hypernode.io'], $result->getHostnames());
        $this->assertSame(['staging-ephabc123.hypernode.io'], $result->getBrancherHypernodes());
    }

    public function testLoadReportThrowsExceptionForInvalidJson(): void
    {
        file_put_contents($this->reportPath, 'this is not valid json');

        $loader = new ReportLoader($this->reportPath);

        $this->expectException(JsonException::class);

        $loader->loadReport();
    }

    public function testLoadReportHandlesEmptyArrays(): void
    {
        $data = [
            'version' => 'v1',
            'stage' => 'test',
            'hostnames' => [],
            'brancher_hypernodes' => [],
        ];
        file_put_contents($this->reportPath, json_encode($data));

        $loader = new ReportLoader($this->reportPath);

        $result = $loader->loadReport();

        $this->assertSame([], $result->getHostnames());
        $this->assertSame([], $result->getBrancherHypernodes());
    }

    public function testLoadReportRoundTripWithWriter(): void
    {
        $originalReport = new Report(
            'production',
            ['app.hypernode.io', 'app2.hypernode.io'],
            ['app-ephabc123.hypernode.io'],
            'v1'
        );

        file_put_contents($this->reportPath, json_encode($originalReport->toArray()));

        $loader = new ReportLoader($this->reportPath);

        $loadedReport = $loader->loadReport();

        $this->assertSame($originalReport->toArray(), $loadedReport->toArray());
    }
}
