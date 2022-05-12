<?php

namespace Hypernode\Deploy\Deployer\Task\Build;

use Hypernode\DeployConfiguration\ServerRole;
use function Deployer\run;
use function Deployer\set;
use function Deployer\task;

use Hypernode\Deploy\Deployer\Task\TaskInterface;
use Hypernode\DeployConfiguration\Configuration;

class PackageTask implements TaskInterface
{
    /**
     * {@inheritdoc}
     */
    public function configure(Configuration $config)
    {
        set('tar/exclude', '{{configured/tar/exclude}}');
        set('tar/filepath', $config->getBuildArchiveFile());

        set('tar/working-directory', function () {
            return escapeshellarg(getcwd());
        });

        set('configured/tar/exclude', function () use ($config) {
            return $this->getTarExclude($config);
        });

        task('build:package', function () {
            run('tar {{tar/exclude}} --directory={{tar/working-directory}} -czf {{tar/filepath}} .', ['timeout' => 3600]);
            run('ls -alh {{tar/filepath}}');
        })->onStage('build');
    }

    /**
     * @param Configuration $config
     * @return string
     */
    private function getTarExclude(Configuration $config): string
    {
        $excludes = array_merge(
            [
                './build',
                './auth.json',
            ],
            $this->prefixPath($config->getSharedFiles()),
            $this->prefixPath($config->getSharedFolders()),
            $config->getDeployExclude()
        );

        $excludeStr = [];
        foreach ($excludes as $exclude) {
            $excludeStr[] = '--exclude=' . escapeshellarg($exclude);
        }

        return implode(' ', $excludeStr);
    }

    /**
     * @param string[] $paths
     * @return array
     */
    private function prefixPath(array $paths): array
    {
        return array_map(static function (string $path): string {
            return './' . ltrim($path, '/');
        }, $paths);
    }
}
