<?php
/**
 * @author Hipex <info@hipex.io>
 * @copyright (c) Hipex B.V. 2018
 */

declare(strict_types=1);


namespace HipexDeploy\Deployer\Task\Common;

use function Deployer\set;

use HipexDeploy\Deployer\Task\TaskInterface;
use HipexDeploy\Stdlib\CpuCoreInfo;
use HipexDeploy\Stdlib\ReleaseInfo;
use HipexDeployConfiguration\Configuration;

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
        set('cpu_cores', function() {
            return $this->cpuInfo->count();
        });

        set('release_message', function() {
            return $this->releaseInfo->getMessage();
        });

        set('commit_sha', function() {
            return $this->releaseInfo->getCommitSha();
        });

        set('configured/bin/php', $config->getPhpVersion());
        set('configured/public_folder', $config->getPublicFolder());
    }
}
