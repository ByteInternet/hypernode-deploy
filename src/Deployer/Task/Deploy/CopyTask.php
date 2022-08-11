<?php

namespace Hypernode\Deploy\Deployer\Task\Deploy;

use Hypernode\Deploy\Deployer\Task\TaskBase;
use Hypernode\DeployConfiguration\Configuration;
use Hypernode\DeployConfiguration\ServerRole;

use function Deployer\run;
use function Deployer\task;
use function Deployer\upload;

class CopyTask extends TaskBase
{
    public function configure(Configuration $config): void
    {
        $role = ServerRole::APPLICATION;
        task('deploy:copy:code', function () use ($config) {
            $packageFilepath = $config->getBuildArchiveFile();
            $packageFilename = pathinfo($packageFilepath, PATHINFO_BASENAME);

            upload($packageFilepath, '{{release_path}}');
            run('cd {{release_path}} && tar -xf ' . $packageFilename);
            run('cd {{release_path}} && rm -f ' . $packageFilename);
        })->select("roles=$role");

        task('deploy:copy', [
            'deploy:copy:code',
            'deploy:shared',
        ])->select("roles=$role");
    }
}
