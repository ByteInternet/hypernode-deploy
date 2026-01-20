<?php

declare(strict_types=1);

namespace Hypernode\Deploy\Report;

use Webmozart\Assert\Assert;

class ReportLoader
{
    private string $reportPath;

    public function __construct(string $reportPath = Report::REPORT_FILENAME)
    {
        $this->reportPath = $reportPath;
    }

    public function loadReport(): ?Report
    {
        if (file_exists($this->reportPath)) {
            $contents = file_get_contents($this->reportPath);
            $data = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);
            Assert::isArray($data);
            return Report::fromArray($data);
        }

        return null;
    }
}
