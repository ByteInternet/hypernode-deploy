<?php

namespace Hypernode\Deploy\Stdlib;

use function Deployer\get;
use function Deployer\has;
use function Deployer\run;
use function Deployer\set;

class PathInfo
{
    public static function getAbsoluteDomainPath(): string
    {
        if (!has('deploy_path/realpath')) {
            $realpath = run('realpath {{deploy_path}}');
            set('deploy_path/realpath', $realpath);
        }
        return get('deploy_path/realpath');
    }
}
