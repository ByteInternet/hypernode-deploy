<?php

namespace Hypernode\Deploy\Deployer\Task\Deploy;

use Hypernode\Deploy\Deployer\Task\TaskBase;
use Hypernode\DeployConfiguration\Configuration;

use function Deployer\run;
use function Deployer\task;
use function Deployer\upload;

class CopyTask extends TaskBase
{
    public function configure(Configuration $config): void
    {
        task('deploy:copy:code', function () use ($config) {
            $packageFilepath = $config->getBuildArchiveFile();
            $packageFilename = pathinfo($packageFilepath, PATHINFO_BASENAME);

            upload($packageFilepath, '{{release_path}}');
            run('cd {{release_path}} && tar -xf ' . $packageFilename);
            run('cd {{release_path}} && rm -f ' . $packageFilename);
        });

        task('deploy:copy', [
            'deploy:copy:code',
            'deploy:shared',
        ]);
    }
}
