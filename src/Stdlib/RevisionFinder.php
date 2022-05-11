<?php

/**
 * @author Hypernode
 * @copyright Copyright (c) Hypernode
 */

namespace Hypernode\Deploy\Stdlib;

class RevisionFinder
{
    /**
     * @return string
     */
    public function getRevision(): string
    {
        $revision = (string) getenv('CI_COMMIT_SHA');
        if ($revision) {
            return $revision;
        }
        return 'master';
    }
}
