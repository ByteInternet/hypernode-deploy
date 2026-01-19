<?php

namespace Hypernode\Deploy\Deployer\Task\Build;

use Hypernode\Deploy\Deployer\Task\TaskBase;
use Hypernode\DeployConfiguration\Configuration;

use function Hypernode\Deploy\Deployer\after;
use function Deployer\run;
use function Deployer\task;
use function Deployer\test;

class ComposerAuthTaskGlobal extends TaskBase
{
    public const ENV_COMPOSER_AUTH = 'DEPLOY_COMPOSER_AUTH';

    public function getAuthContent(): string
    {
        // Env var fetched to ensure key value is set
        $rawContents = \Hypernode\DeployConfiguration\getenv(self::ENV_COMPOSER_AUTH);
        $auth = base64_decode($rawContents, true);
        if ($auth === false) {
            // If not base64 encoded, we try to parse it as json. If that
            // fails as well, it will raise. Otherwise, we'll just set
            // $auth to the raw content.
            /** @psalm-suppress UnusedFunctionCall We only call json_decode for validation */
            json_decode($rawContents, true, flags: JSON_THROW_ON_ERROR);
            $auth = $rawContents;
        }
        return $auth;
    }

    public function configure(Configuration $config): void
    {
        task('deploy:vendors:auth', function () {
            if (test('[ ! -f auth.json ]') && \getenv(self::ENV_COMPOSER_AUTH)) {
                $auth = $this->getAuthContent();
                run(sprintf('echo %s > auth.json', escapeshellarg($auth)));
            }
        })->select("stage=build");
    }

    public function register(): void
    {
        after('build:compile:prepare', 'deploy:vendors:auth');
    }
}
