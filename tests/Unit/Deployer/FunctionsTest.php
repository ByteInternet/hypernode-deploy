<?php

declare(strict_types=1);

namespace Hypernode\Deploy\Tests\Unit\Deployer;

use Hypernode\DeployConfiguration\Exception\EnvironmentVariableNotDefinedException;
use PHPUnit\Framework\TestCase;

use function Hypernode\Deploy\Deployer\getenvFallback;
use function Hypernode\Deploy\Deployer\noop;

class FunctionsTest extends TestCase
{
    private const ENV_VAR_1 = 'TEST_FALLBACK_VAR_1';
    private const ENV_VAR_2 = 'TEST_FALLBACK_VAR_2';
    private const ENV_VAR_3 = 'TEST_FALLBACK_VAR_3';

    protected function tearDown(): void
    {
        putenv(self::ENV_VAR_1);
        putenv(self::ENV_VAR_2);
        putenv(self::ENV_VAR_3);
    }

    public function testGetenvFallbackReturnsFirstMatchingVariable(): void
    {
        putenv(self::ENV_VAR_1 . '=first_value');
        putenv(self::ENV_VAR_2 . '=second_value');

        $result = getenvFallback([self::ENV_VAR_1, self::ENV_VAR_2]);

        $this->assertSame('first_value', $result);
    }

    public function testGetenvFallbackReturnsSecondWhenFirstNotSet(): void
    {
        putenv(self::ENV_VAR_1);
        putenv(self::ENV_VAR_2 . '=second_value');

        $result = getenvFallback([self::ENV_VAR_1, self::ENV_VAR_2]);

        $this->assertSame('second_value', $result);
    }

    public function testGetenvFallbackReturnsThirdWhenFirstTwoNotSet(): void
    {
        putenv(self::ENV_VAR_1);
        putenv(self::ENV_VAR_2);
        putenv(self::ENV_VAR_3 . '=third_value');

        $result = getenvFallback([self::ENV_VAR_1, self::ENV_VAR_2, self::ENV_VAR_3]);

        $this->assertSame('third_value', $result);
    }

    public function testGetenvFallbackThrowsExceptionWhenNoneSet(): void
    {
        putenv(self::ENV_VAR_1);
        putenv(self::ENV_VAR_2);

        $this->expectException(EnvironmentVariableNotDefinedException::class);
        $this->expectExceptionMessage(
            'None of the requested environment variables ' . self::ENV_VAR_1 . ', ' . self::ENV_VAR_2 . ' is defined'
        );

        getenvFallback([self::ENV_VAR_1, self::ENV_VAR_2]);
    }

    public function testGetenvFallbackWithEmptyArrayThrowsException(): void
    {
        $this->expectException(EnvironmentVariableNotDefinedException::class);
        $this->expectExceptionMessage('None of the requested environment variables  is defined');

        getenvFallback([]);
    }

    // Tests for noop() function

    public function testNoopReturnsCallable(): void
    {
        $result = noop();

        $this->assertInstanceOf(\Closure::class, $result);
    }

    public function testNoopReturnedClosureDoesNothing(): void
    {
        $closure = noop();

        // Should execute without error and return nothing
        $result = $closure();

        $this->assertNull($result);
    }
}
