<?php

namespace Hypernode\Deploy\Deployer\Task\Deploy;

use Hypernode\Deploy\Deployer\Task\TaskBase;
use Hypernode\DeployConfiguration\Configuration;

use function Deployer\run;
use function Deployer\task;
use function Deployer\test;

class LinkTask extends TaskBase
{
    public function configure(Configuration $config): void
    {
        task('deploy:link', [
            'deploy:symlink',
            'deploy:public_link',
        ]);

        // Symlink public_html folder
        task('deploy:public_link', function () {
            if (test('[ -e /data/web/public ]')) {
                // If the current public directory is not empty we do nothing
                if (!test('[ -z "$(ls -A /data/web/public)" ]')) {
                    return;
                } else {
                    run('rmdir /data/web/public');
                    run('ln -s {{release_path}}/{{public_folder}} /data/web/public');
                }
            } else {
                run('ln -s {{current_path}}/{{public_folder}} /data/web/public');
            }
        });
    }
}
