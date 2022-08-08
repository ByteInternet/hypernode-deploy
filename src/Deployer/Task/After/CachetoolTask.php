<?php

namespace Hypernode\Deploy\Deployer\Task\After;

use Hypernode\DeployConfiguration\ServerRole;
use Hypernode\Deploy\Deployer\Task\RegisterAfterInterface;
use Hypernode\Deploy\Deployer\Task\TaskInterface;
use Hypernode\DeployConfiguration\Configuration;

use function Deployer\after;
use function Deployer\desc;
use function Deployer\get;
use function Deployer\run;
use function Deployer\set;
use function Deployer\task;
use function Deployer\within;
use function Deployer\writeln;

class CachetoolTask implements TaskInterface, RegisterAfterInterface
{
    /**
     * @var string[]
     *
     * CacheTool 8.x works with PHP >=8.0
     * CacheTool 7.x works with PHP >=7.3
     * CacheTool 6.x works with PHP >=7.3
     * CacheTool 5.x works with PHP >=7.2
     * CacheTool 4.x works with PHP >=7.1
     */
    private $versionBinaryMapping = [
        8 => 'https://github.com/gordalina/cachetool/releases/download/8.4.0/cachetool.phar',
        7 => 'https://github.com/gordalina/cachetool/releases/download/7.1.0/cachetool.phar',
        6 => 'https://github.com/gordalina/cachetool/releases/download/6.6.0/cachetool.phar',
        5 => 'https://github.com/gordalina/cachetool/releases/download/5.1.3/cachetool.phar',
        4 => 'https://gordalina.github.io/cachetool/downloads/cachetool-4.1.1.phar',
    ];

    public function registerAfter(): void
    {
        after('deploy:symlink', 'cachetool:clear:opcache');
        after('cachetool:clear:opcache', 'cachetool:cleanup');
    }

    public function configure(Configuration $config): void
    {
        set('cachetool_binary', function () {
            set('cachetool_args', '');
            return "cachetool.phar";
        });

        set('cachetool', '127.0.0.1:9000');

        set('bin/cachetool', function () {
            $cachetoolBinary = get('cachetool_binary');

            within('{{release_path}}', function () {
                run('curl -L -o cachetool.phar ' . $this->getCachetoolUrl());
                $cachetoolBinary = '{{release_path}}/cachetool.phar';

                writeln(sprintf("Downloaded cachetool %s for PHP %d", $cachetoolBinary, $this->getPhpVersion()));
                return $cachetoolBinary;
            });
            return $cachetoolBinary;
        });

        set('cachetool_options', function () {
            $options = get('cachetool');
            $fullOptions = get('cachetool_args');

            if (strlen($fullOptions) > 0) {
                $options = "{$fullOptions}";
            } elseif (strlen($options) > 0) {
                $options = "--fcgi={$options}";
            }

            return $options;
        });


        desc('Clearing APC system cache');
        task('cachetool:clear:apc', function () {
            run("cd {{release_path}} && {{bin/php}} {{bin/cachetool}} apc:cache:clear system {{cachetool_options}}");
        });

        /**
         * Clear opcache cache
         */
        desc('Clearing OPcode cache');
        task('cachetool:clear:opcache', function () {
            run("cd {{release_path}} && {{bin/php}} {{bin/cachetool}} opcache:reset {{cachetool_options}}");
        });

        /**
         * Clear APCU cache
         */
        desc('Clearing APCu system cache');
        task('cachetool:clear:apcu', function () {
            run("cd {{release_path}} && {{bin/php}} {{bin/cachetool}} apcu:cache:clear {{cachetool_options}}");
        });


        $role = ServerRole::APPLICATION;
        task('cachetool:clear:opcache')
            ->select("roles=$role");

        task('cachetool:cleanup', function () {
            run('cd {{deploy_path}} && rm -f current/{{bin/cachetool}}');
        })->select("roles=$role");
    }

    protected function getPhpVersion(): float
    {
        return (float) run('{{bin/php}} -r "echo PHP_VERSION . \" - \" . PHP_VERSION_ID;"');
    }

    public function getCachetoolUrl(): string
    {
        $phpVersion = $this->getPhpVersion();
        if ($phpVersion >= 8.0) {
            return $this->versionBinaryMapping[8];
        }

        if ($phpVersion >= 7.3) {
            return $this->versionBinaryMapping[6];
        }

        if ($phpVersion >= 7.2) {
            return $this->versionBinaryMapping[5];
        }

        if ($phpVersion >= 7.1) {
            return $this->versionBinaryMapping[4];
        }

        return $this->versionBinaryMapping[8];
    }
}
