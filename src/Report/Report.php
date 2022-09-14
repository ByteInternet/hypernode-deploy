<?php

declare(strict_types=1);

namespace Hypernode\Deploy\Report;

class Report
{
    public const REPORT_FILENAME = 'deployment-report.json';

    private string $version;
    private string $stage;
    /**
     * @var string[]
     */
    private array $hostnames;
    /**
     * @var string[]
     */
    private array $ephemeralHypernodes;

    /**
     * @param string $version
     * @param string $stage
     * @param string[] $hostnames
     * @param string[] $ephemeralHypernodes
     */
    public function __construct(string $version, string $stage, array $hostnames, array $ephemeralHypernodes)
    {
        $this->version = $version;
        $this->stage = $stage;
        $this->hostnames = $hostnames;
        $this->ephemeralHypernodes = $ephemeralHypernodes;
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
            $data['version'],
            $data['stage'],
            $data['hostnames'],
            $data['ephemeral_hypernodes'],
        );
    }
}
