<?php

namespace Hypernode\Deploy\Stdlib;

class RevisionFinder
{
    public function getRevision(): string
    {
        $revision = (string) getenv('CI_COMMIT_SHA');
        if ($revision) {
            return $revision;
        }
        return 'master';
    }
}
