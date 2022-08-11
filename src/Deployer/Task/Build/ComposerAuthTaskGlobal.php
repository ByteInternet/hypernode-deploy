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
    public function configure(Configuration $config): void
    {
        task('deploy:vendors:auth', function () {
            if (test('[ ! -f auth.json ]') && \getenv('DEPLOY_COMPOSER_AUTH')) {
                // Env var fetched to ensure key value is set
                $auth = \Hypernode\DeployConfiguration\getenv('DEPLOY_COMPOSER_AUTH');
                $auth = base64_decode($auth);
                run(sprintf('echo %s > auth.json', escapeshellarg($auth)));
            }
        })->select("stage=build");
    }

    public function register(): void
    {
        after('build:compile:prepare', 'deploy:vendors:auth');
    }
}
