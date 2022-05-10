<?php
/**
 * @author Hipex <info@hipex.io>
 * @copyright (c) Hipex B.V. 2018
 */

namespace HipexDeploy\Stdlib;

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
