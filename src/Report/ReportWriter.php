<?php

declare(strict_types=1);

namespace Hypernode\Deploy\Report;

class ReportWriter
{
    private string $reportPath;

    public function __construct(string $reportPath = Report::REPORT_FILENAME)
    {
        $this->reportPath = $reportPath;
    }

    public function write(Report $report): void
    {
        file_put_contents($this->reportPath, json_encode($report->toArray()));
    }
}
