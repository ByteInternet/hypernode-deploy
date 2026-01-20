<?php

declare(strict_types=1);

namespace Hypernode\Deploy\Tests\Unit\Report;

use Hypernode\Deploy\Report\Report;
use PHPUnit\Framework\TestCase;

class ReportTest extends TestCase
{
    public function testToArrayReturnsCorrectStructure(): void
    {
        $report = new Report(
            'production',
            ['app1.hypernode.io', 'app2.hypernode.io'],
            ['app1-ephabc123.hypernode.io'],
            'v1'
        );

        $result = $report->toArray();

        $this->assertSame('v1', $result['version']);
        $this->assertSame('production', $result['stage']);
        $this->assertSame(['app1.hypernode.io', 'app2.hypernode.io'], $result['hostnames']);
        $this->assertSame(['app1-ephabc123.hypernode.io'], $result['brancher_hypernodes']);
    }

    public function testFromArrayCreatesReportWithCorrectValues(): void
    {
        $data = [
            'version' => 'v1',
            'stage' => 'staging',
            'hostnames' => ['staging.hypernode.io'],
            'brancher_hypernodes' => ['staging-ephabc123.hypernode.io'],
        ];

        $report = Report::fromArray($data);

        $this->assertSame('v1', $report->getVersion());
        $this->assertSame('staging', $report->getStage());
        $this->assertSame(['staging.hypernode.io'], $report->getHostnames());
        $this->assertSame(['staging-ephabc123.hypernode.io'], $report->getBrancherHypernodes());
    }

    public function testToArrayFromArrayRoundTripProducesEqualData(): void
    {
        $originalData = [
            'version' => 'v1',
            'stage' => 'production',
            'hostnames' => ['app.hypernode.io'],
            'brancher_hypernodes' => ['app-ephabc123.hypernode.io'],
        ];

        $report = Report::fromArray($originalData);
        $resultData = $report->toArray();

        $this->assertSame($originalData, $resultData);
    }

    public function testDefaultVersionIsV1(): void
    {
        $report = new Report(
            'production',
            ['app.hypernode.io'],
            ['app-ephabc123.hypernode.io']
        );

        $this->assertSame(Report::REPORT_VERSION, $report->getVersion());
        $this->assertSame('v1', $report->getVersion());
    }

    public function testEmptyHostnamesArrayIsHandled(): void
    {
        $report = new Report('production', [], ['app-ephabc123.hypernode.io']);

        $this->assertSame([], $report->getHostnames());
        $this->assertSame([], $report->toArray()['hostnames']);
    }

    public function testEmptyBrancherHypernodesArrayIsHandled(): void
    {
        $report = new Report('production', ['app.hypernode.io'], []);

        $this->assertSame([], $report->getBrancherHypernodes());
        $this->assertSame([], $report->toArray()['brancher_hypernodes']);
    }
}
