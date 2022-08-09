<?php

namespace Hypernode\Deploy\Deployer\Task\Build;

use Hypernode\Deploy\Deployer\Task\TaskInterface;
use Hypernode\DeployConfiguration\Configuration;

use function Deployer\run;
use function Deployer\set;
use function Deployer\task;

class PackageTask implements TaskInterface
{
    public function configure(Configuration $config): void
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
        })->select("stage=build");
    }

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
     * @param string[]|object[] $paths
     * @return string[]
     */
    private function prefixPath(array $paths): array
    {
        $result = [];

        foreach ($paths as $path) {
            if (\is_object($path)) {
                /** @psalm-suppress InvalidCast cast is ok here */
                $path = (string)$path;
            }
            $result[] = './' . ltrim($path, '/');
        }

        return $result;
    }
}
