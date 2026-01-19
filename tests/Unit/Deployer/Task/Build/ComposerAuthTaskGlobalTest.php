<?php

declare(strict_types=1);

namespace Hypernode\Deploy\Tests\Unit\Deployer\Task\Build;

use Hypernode\Deploy\Deployer\Task\Build\ComposerAuthTaskGlobal;
use Hypernode\DeployConfiguration\Exception\EnvironmentVariableNotDefinedException;
use JsonException;
use PHPUnit\Framework\TestCase;

class ComposerAuthTaskGlobalTest extends TestCase
{
    protected function tearDown(): void
    {
        putenv(ComposerAuthTaskGlobal::ENV_COMPOSER_AUTH);
    }

    public function testGetAuthContentReturnsDecodedBase64Content(): void
    {
        $jsonContent = '{"github-oauth":{"github.com":"token123"}}';
        $base64Content = base64_encode($jsonContent);
        putenv(ComposerAuthTaskGlobal::ENV_COMPOSER_AUTH . '=' . $base64Content);

        $task = new ComposerAuthTaskGlobal();
        $result = $task->getAuthContent();

        $this->assertSame($jsonContent, $result);
    }

    public function testGetAuthContentReturnsRawJsonWhenNotBase64Encoded(): void
    {
        $jsonContent = '{"http-basic":{"repo.example.com":{"username":"user","password":"pass"}}}';
        putenv(ComposerAuthTaskGlobal::ENV_COMPOSER_AUTH . '=' . $jsonContent);

        $task = new ComposerAuthTaskGlobal();
        $result = $task->getAuthContent();

        $this->assertSame($jsonContent, $result);
    }

    public function testGetAuthContentThrowsJsonExceptionForInvalidContent(): void
    {
        $invalidContent = 'this-is-not-valid-json-or-base64';
        putenv(ComposerAuthTaskGlobal::ENV_COMPOSER_AUTH . '=' . $invalidContent);

        $task = new ComposerAuthTaskGlobal();

        $this->expectException(JsonException::class);
        $task->getAuthContent();
    }

    public function testGetAuthContentThrowsExceptionWhenEnvVarNotSet(): void
    {
        putenv(ComposerAuthTaskGlobal::ENV_COMPOSER_AUTH);

        $task = new ComposerAuthTaskGlobal();

        $this->expectException(EnvironmentVariableNotDefinedException::class);
        $task->getAuthContent();
    }
}
