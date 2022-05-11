<?php

/**
 * @author Hypernode
 * @copyright Copyright (c) Hypernode
 */

declare(strict_types=1);


namespace Hypernode\Deploy\Deployer\Task\Common;

use function Deployer\set;

use Hypernode\Deploy\Deployer\Task\TaskInterface;
use Hypernode\Deploy\Stdlib\CpuCoreInfo;
use Hypernode\Deploy\Stdlib\ReleaseInfo;
use Hypernode\DeployConfiguration\Configuration;

class DefaultsTaskGlobal implements TaskInterface
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
     * DefaultsTask constructor.
     *
     * @param CpuCoreInfo $cpuInfo
     * @param ReleaseInfo $releaseInfo
     */
    public function __construct(CpuCoreInfo $cpuInfo, ReleaseInfo $releaseInfo)
    {
        $this->cpuInfo = $cpuInfo;
        $this->releaseInfo = $releaseInfo;
    }

    /**
     * {@inheritdoc}
     */
    public function configure(Configuration $config)
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
            return $this->releaseInfo->getCommitSha();
        });

        set('configured/bin/php', $config->getPhpVersion());
        set('configured/public_folder', $config->getPublicFolder());
    }
}
