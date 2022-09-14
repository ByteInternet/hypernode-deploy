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
    private array $ephemeralHypernodes;
    private string $version;

    /**
     * @param string $stage
     * @param string[] $hostnames
     * @param string[] $ephemeralHypernodes
     * @param string $version Version of the report file
     */
    public function __construct(
        string $stage,
        array $hostnames,
        array $ephemeralHypernodes,
        string $version = self::REPORT_VERSION
    ) {
        $this->stage = $stage;
        $this->hostnames = $hostnames;
        $this->ephemeralHypernodes = $ephemeralHypernodes;
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
    public function getEphemeralHypernodes(): array
    {
        return $this->ephemeralHypernodes;
    }

    public function toArray(): array
    {
        return [
            'version' => $this->version,
            'stage' => $this->stage,
            'hostnames' => $this->hostnames,
            'ephemeral_hypernodes' => $this->ephemeralHypernodes,
        ];
    }

    public static function fromArray(array $data): Report
    {
        return new Report(
            $data['stage'],
            $data['hostnames'],
            $data['ephemeral_hypernodes'],
            $data['version'],
        );
    }
}
