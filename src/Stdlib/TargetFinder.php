<?php

namespace Hypernode\Deploy\Stdlib;

use function Deployer\get;

class TargetFinder
{
    public function getTarget(): string
    {
        $branch = get('branch', 'HEAD');
        if (!empty($branch) && $branch != 'HEAD') {
            return $branch;
        }

        $branch = $this->getBranchFromCI();
        if (!empty($branch)) {
            return $branch;
        }

        return get('branch', 'HEAD');
    }

    private function getBranchFromCI(): ?string
    {
        // Check GitHub Actions
        if ($githubBranch = getenv('GITHUB_HEAD_REF')) {
            return $githubBranch;
        }
        if ($githubBaseRef = getenv('GITHUB_REF')) {
            return $this->parseGithubRef($githubBaseRef);
        }

        // Check GitLab CI
        if ($gitlabBranch = getenv('CI_COMMIT_REF_NAME')) {
            return $gitlabBranch;
        }

        // Check Bitbucket Pipelines
        if ($bitbucketBranch = getenv('BITBUCKET_BRANCH')) {
            return $bitbucketBranch;
        }

        // Check Azure Pipelines
        if ($azureBranch = getenv('BUILD_SOURCEBRANCH')) {
            return $this->parseAzureBranch($azureBranch);
        }

        return null;
    }

    private function parseGithubRef(string $ref): ?string
    {
        // Extract branch or tag name from refs/heads/ or refs/tags/
        if (preg_match('#refs/heads/(.+)#', $ref, $matches)) {
            return $matches[1];
        }
        if (preg_match('#refs/tags/(.+)#', $ref, $matches)) {
            return $matches[1];
        }

        return null;
    }

    private function parseAzureBranch(string $branch): ?string
    {
        // Extract branch name from refs/heads/
        if (strpos($branch, 'refs/heads/') === 0) {
            return substr($branch, strlen('refs/heads/'));
        }

        return $branch;
    }
}
