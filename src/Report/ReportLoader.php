<?php

declare(strict_types=1);

namespace Hypernode\Deploy\Report;

use Webmozart\Assert\Assert;

class ReportLoader
{
    public function loadReport(): ?Report
    {
        if (file_exists(Report::REPORT_FILENAME)) {
            $contents = file_get_contents(Report::REPORT_FILENAME);
            $data = json_decode($contents, true, JSON_THROW_ON_ERROR);
            Assert::isArray($data);
            return Report::fromArray($data);
        }

        return null;
    }
}
