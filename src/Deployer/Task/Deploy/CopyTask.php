<?php

namespace Hypernode\Deploy\Deployer\Task\Deploy;

use Hypernode\Deploy\Deployer\Task\TaskInterface;
use Hypernode\DeployConfiguration\Configuration;
use Hypernode\DeployConfiguration\ServerRole;

use function Deployer\run;
use function Deployer\task;
use function Deployer\upload;

class CopyTask implements TaskInterface
{
    /**
     * Configure using hipex configuration
     *
     * @param Configuration $config
     *
     * @return void
     */
    public function configure(Configuration $config)
    {
        task('deploy:copy:code', function () use ($config) {
            $packageFilepath = $config->getBuildArchiveFile();
            $packageFilename = pathinfo($packageFilepath, PATHINFO_BASENAME);

            upload($packageFilepath, '{{release_path}}');
            run('cd {{release_path}} && tar -xf ' . $packageFilename);
            run('cd {{release_path}} && rm -f ' . $packageFilename);
        })->onRoles(ServerRole::APPLICATION);

        task('deploy:copy', [
            'deploy:copy:code',
            'deploy:shared',
        ])->onRoles(ServerRole::APPLICATION);
    }
}
