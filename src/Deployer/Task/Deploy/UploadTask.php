<?php

namespace Hypernode\Deploy\Deployer\Task\Deploy;

use Hypernode\Deploy\Deployer\RecipeLoader;
use Hypernode\Deploy\Deployer\Task\TaskBase;
use Hypernode\DeployConfiguration\Configuration;

use function Deployer\fail;
use function Deployer\task;

class UploadTask extends TaskBase
{
    /**
     * @var RecipeLoader
     */
    private $loader;

    public function __construct(RecipeLoader $loader)
    {
        $this->loader = $loader;
    }

    public function configure(Configuration $config): void
    {
        $this->loader->load('deploy/info.php');

        task('deploy:upload', [
            'prepare:ssh',
            'deploy:info',
            'deploy:prepare',
            'deploy:copy',
            'deploy:deploy',
        ]);

        fail('deploy', 'deploy:failed');
    }
}
