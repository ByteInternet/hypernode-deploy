<?php

declare(strict_types=1);

namespace Hypernode\Deploy\Deployer\Task\Common;

use Hypernode\Deploy\Deployer\Task\TaskBase;
use Hypernode\Deploy\Stdlib\CpuCoreInfo;
use Hypernode\Deploy\Stdlib\ReleaseInfo;
use Hypernode\DeployConfiguration\Configuration;
use Hypernode\Deploy\Stdlib\TargetFinder;

use function Deployer\set;

class DefaultsTaskGlobal extends TaskBase
{
    /**
     * @var CpuCoreInfo
     */
    private $cpuInfo;

    /**
     * @var ReleaseInfo
     */
    private $releaseInfo;

    /**
     * @var TargetFinder
     */
    private $targetFinder;

    public function __construct(CpuCoreInfo $cpuInfo, ReleaseInfo $releaseInfo, TargetFinder $targetFinder)
    {
        $this->cpuInfo = $cpuInfo;
        $this->releaseInfo = $releaseInfo;
        $this->targetFinder = $targetFinder;
    }

    public function configure(Configuration $config): void
    {
        set('bin/php', '{{configured/bin/php}}');
        set('public_folder', '{{configured/public_folder}}');
        set('cpu_cores', function () {
            return $this->cpuInfo->count();
        });

        set('release_message', function () {
            return $this->releaseInfo->getMessage();
        });

        set('target', function () {
            return $this->targetFinder->getTarget();
        });

        set('commit_sha', function () {
            try {
                return $this->releaseInfo->getCommitSha();
            } catch (\Throwable $e) {
                return '';
            }
        });

        if (str_starts_with($config->getPhpVersion(), 'php')) {
            set('configured/bin/php', $config->getPhpVersion());
        } else {
            set('configured/bin/php', 'php' . $config->getPhpVersion());
        }
        set('configured/public_folder', $config->getPublicFolder());
    }
}
