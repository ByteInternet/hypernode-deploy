<?php

declare(strict_types=1);

namespace Hypernode\Deploy\Report;

class ReportWriter
{
    public function write(Report $report): void
    {
        file_put_contents(Report::REPORT_FILENAME, json_encode($report->toArray()));
    }
}
