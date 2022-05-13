<?php

namespace Hypernode\Deploy\Stdlib;

use Hypernode\DeployConfiguration\Stage;

use function Deployer\get;
use function Deployer\parse;
use function Deployer\runLocally;
use function Deployer\write;

class ReleaseInfo
{
    /**
     * Merge pattern for parsing joined streams
     */
    private const MERGE_PATTERN = "/Merge branch '(.*)' into/";

    /**
     * @return string
     */
    public function getCommitSha(): string
    {
        return runLocally('git rev-parse HEAD');
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        $body = [];
        $body[] = parse('Succesvolle deploy naar **{{stage}}**');
        $body[] = parse('Branch: `{{branch}}`');
        $body[] = parse('User: `{{user}}`');
        $body[] = parse('Commit: `{{commit_sha}}`');

        $branches = $this->branchList();
        if (\count($branches)) {
            $body[] = '';
            $body[] = '**Merge branches:**';
            foreach ($this->branchList() as $branch) {
                $body[] = '- ' . $branch;
            }
        }

        $body[] = '';
        $body[] = '**Servers:**';
        foreach ($this->getServers() as $server) {
            $body[] = '- ' . $server;
        }

        return implode(PHP_EOL, $body);
    }

    /**
     * @return string[]
     */
    private function branchList(): array
    {
        $gitLogOutput = runLocally('git log --merges -n 1');

        if (!preg_match(self::MERGE_PATTERN, $gitLogOutput, $matches)) {
            write('No merge commit found');
            return [];
        }

        $sourceBranch = $matches[1];
        $branchesList = [$sourceBranch];

        // Check if we are working with release branches. If this is the case we want to list all merges into this release branch
        if (strpos($sourceBranch, 'release') !== false) {
            $gitLogOutput = runLocally(sprintf("git log --merges --grep='into %s'", $sourceBranch));

            $foundIssueMerges = preg_match_all(self::MERGE_PATTERN, $gitLogOutput, $matches);
            if ($foundIssueMerges) {
                $branchesList = $matches[1];
            }
        }
        return $branchesList;
    }

    /**
     * @return iterable
     */
    private function getServers(): iterable
    {
        $stage = get('configuration_stage');
        if (!$stage instanceof Stage) {
            return [get('hostname')];
        }

        foreach ($stage->getServers() as $server) {
            if (\count($server->getRoles())) {
                yield sprintf('%s (%s)', $server->getHostname(), implode(', ', $server->getRoles()));
            } else {
                yield $server->getHostname();
            }
        }
    }
}
