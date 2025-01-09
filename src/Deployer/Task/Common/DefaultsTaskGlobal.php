<?php

declare(strict_types=1);

namespace Hypernode\Deploy\Deployer\Task\Common;

use Hypernode\Deploy\Deployer\Task\TaskBase;
use Hypernode\Deploy\Stdlib\CpuCoreInfo;
use Hypernode\Deploy\Stdlib\ReleaseInfo;
use Hypernode\DeployConfiguration\Configuration;

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

    public function __construct(CpuCoreInfo $cpuInfo, ReleaseInfo $releaseInfo)
    {
        $this->cpuInfo = $cpuInfo;
        $this->releaseInfo = $releaseInfo;
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

        set('commit_sha', function () {
            try {
                return $this->releaseInfo->getCommitSha();
            }
            catch (\Throwable $e) {
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
