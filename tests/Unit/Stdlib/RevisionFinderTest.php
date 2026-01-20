<?php

declare(strict_types=1);

namespace Hypernode\Deploy\Tests\Unit\Stdlib;

use Hypernode\Deploy\Stdlib\RevisionFinder;
use PHPUnit\Framework\TestCase;

class RevisionFinderTest extends TestCase
{
    private const ENV_VAR = 'CI_COMMIT_SHA';

    protected function tearDown(): void
    {
        putenv(self::ENV_VAR);
    }

    public function testGetRevisionReturnsCommitShaWhenEnvVarIsSet(): void
    {
        putenv(self::ENV_VAR . '=abc123def456');

        $finder = new RevisionFinder();
        $result = $finder->getRevision();

        $this->assertSame('abc123def456', $result);
    }

    public function testGetRevisionReturnsMasterWhenEnvVarIsNotSet(): void
    {
        putenv(self::ENV_VAR);

        $finder = new RevisionFinder();
        $result = $finder->getRevision();

        $this->assertSame('master', $result);
    }

    public function testGetRevisionReturnsMasterWhenEnvVarIsEmptyString(): void
    {
        putenv(self::ENV_VAR . '=');

        $finder = new RevisionFinder();
        $result = $finder->getRevision();

        $this->assertSame('master', $result);
    }
}
