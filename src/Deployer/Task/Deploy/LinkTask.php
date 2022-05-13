<?php

namespace Hypernode\Deploy\Deployer\Task\Deploy;

use Hypernode\Deploy\Deployer\Task\TaskInterface;
use Hypernode\DeployConfiguration\Configuration;
use Hypernode\DeployConfiguration\ServerRole;

use function Deployer\run;
use function Deployer\task;
use function Deployer\test;

class LinkTask implements TaskInterface
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
        task('deploy:link', [
            'deploy:symlink',
            'deploy:public_link',
        ])->onRoles(ServerRole::APPLICATION);

        // Symlink public_html folder
        task('deploy:public_link', function () {
            if (!test('[ -z "$(ls -A {{domain_path}}/public_html)" ]')) {
                return;
            }

            run('rmdir {{domain_path}}/public_html');
            run('cd {{domain_path}} && ln -s application/current/{{public_folder}} public_html');
        })->onRoles(ServerRole::APPLICATION);
    }
}
