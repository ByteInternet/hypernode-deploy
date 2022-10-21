<?php

declare(strict_types=1);

namespace Hypernode\Deploy\Report;

class Report
{
    public const REPORT_FILENAME = 'deployment-report.json';
    public const REPORT_VERSION = 'v1';

    private string $stage;
    /**
     * @var string[]
     */
    private array $hostnames;
    /**
     * @var string[]
     */
    private array $brancherHypernodes;
    private string $version;

    /**
     * @param string $stage
     * @param string[] $hostnames
     * @param string[] $brancherHypernodes
     * @param string $version Version of the report file
     */
    public function __construct(
        string $stage,
        array $hostnames,
        array $brancherHypernodes,
        string $version = self::REPORT_VERSION
    ) {
        $this->stage = $stage;
        $this->hostnames = $hostnames;
        $this->brancherHypernodes = $brancherHypernodes;
        $this->version = $version;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function getStage(): string
    {
        return $this->stage;
    }

    /**
     * @return string[]
     */
    public function getHostnames(): array
    {
        return $this->hostnames;
    }

    /**
     * @return string[]
     */
    public function getBrancherHypernodes(): array
    {
        return $this->brancherHypernodes;
    }

    public function toArray(): array
    {
        return [
            'version' => $this->version,
            'stage' => $this->stage,
            'hostnames' => $this->hostnames,
            'brancher_hypernodes' => $this->brancherHypernodes,
        ];
    }

    public static function fromArray(array $data): Report
    {
        return new Report(
            $data['stage'],
            $data['hostnames'],
            $data['brancher_hypernodes'],
            $data['version'],
        );
    }
}
