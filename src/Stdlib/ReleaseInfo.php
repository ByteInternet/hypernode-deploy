<?php

namespace Hypernode\Deploy\Stdlib;

use Deployer\Exception\RunException;
use Hypernode\DeployConfiguration\Stage;

use function Deployer\get;
use function Deployer\parse;
use function Deployer\runLocally;
use function Deployer\output;

class ReleaseInfo
{
    /**
     * Merge pattern for parsing joined streams
     */
    private const MERGE_PATTERN = "/Merge branch '(.*)' into/";

    public function getCommitSha(): string
    {
        return runLocally('git rev-parse HEAD');
    }

    public function getMessage(): string
    {
        $body = [];
        $body[] = parse('Successful deployment to *{{stage}}*');
        $body[] = parse('Branch: `{{target}}`');
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
        $body[] = '*Servers:*';
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
        $gitLogOutput = '';

        try {
            $gitLogOutput = runLocally('git log --merges -n 1');
        } catch (RunException $e) {
            return [];
        }

        if (!preg_match(self::MERGE_PATTERN, $gitLogOutput, $matches)) {
            output()->write('No merge commit found');
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
