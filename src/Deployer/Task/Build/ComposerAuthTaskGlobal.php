<?php

/**
 * @author Hypernode
 * @copyright Copyright (c) Hypernode
 */

namespace Hypernode\Deploy\Deployer\Task\Build;

use Hypernode\DeployConfiguration\ServerRole;
use function Hypernode\Deploy\Deployer\after;
use function Deployer\run;
use function Deployer\task;
use function Deployer\test;
use Hypernode\Deploy\Deployer\RecipeLoader;

use Hypernode\Deploy\Deployer\Task\RegisterAfterInterface;
use Hypernode\Deploy\Deployer\Task\TaskInterface;
use Hypernode\DeployConfiguration\Configuration;

class ComposerAuthTaskGlobal implements TaskInterface, RegisterAfterInterface
{
    /**
     * @var RecipeLoader
     */
    private $loader;

    /**
     * DeployTask constructor.
     *
     * @param RecipeLoader $loader
     */
    public function __construct(RecipeLoader $loader)
    {
        $this->loader = $loader;
    }

    /**
     * @param Configuration $config
     */
    public function configure(Configuration $config)
    {
        task('deploy:vendors:auth', function () {
            if (test('[ ! -f auth.json ]') && \getenv('DEPLOY_COMPOSER_AUTH')) {
                // Env var fetched to ensure key value is set
                $auth = \Hypernode\DeployConfiguration\getenv('DEPLOY_COMPOSER_AUTH');
                $auth = base64_decode($auth);
                run(sprintf('echo %s > auth.json', escapeshellarg($auth)));
            }
        })->onStage('build');
    }

    /**
     * Use this method to register your task after another task
     * i.e. after('taska', 'taskb')
     */
    public function registerAfter(): void
    {
        after('build:compile:prepare', 'deploy:vendors:auth');
    }
}
