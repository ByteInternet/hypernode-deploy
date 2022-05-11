<?php

/**
 * @author Hypernode
 * @copyright Copyright (c) Hypernode
 */

namespace Hypernode\Deploy\Deployer\Task\Deploy;

use DI\Annotation\Inject;
use Hypernode\Deploy\Deployer\Task\Docker\ImageNameHelper;
use Hypernode\Deploy\Deployer\Task\RegisterAfterInterface;
use Hypernode\Deploy\Exception\CompatibilityException;
use Hypernode\DeployConfiguration\ClusterSharedFile;
use Hypernode\DeployConfiguration\ClusterSharedFolder;
use Hypernode\DeployConfiguration\Exception\EnvironmentVariableNotDefinedException;
use Hypernode\DeployConfiguration\PlatformService\RedisService;
use Hypernode\DeployConfiguration\Server;
use Hypernode\DeployConfiguration\ServerRole;
use Hypernode\DeployConfiguration\Stage;
use RuntimeException;
use function Deployer\after;
use function Deployer\get;
use function Deployer\input;
use function Deployer\task;
use Hypernode\Deploy\Deployer\Task\TaskInterface;
use Hypernode\DeployConfiguration\Configuration;
use function Deployer\upload;

class DaasManifestTask implements TaskInterface, RegisterAfterInterface
{
    /**
     * @var string
     */
    private $version;

    /**
     * @var ImageNameHelper
     */
    private $imageHelper;

    /**
     * @Inject({"version"="version"})
     * @param string          $version
     * @param ImageNameHelper $imageHelper
     */
    public function __construct(string $version, ImageNameHelper $imageHelper)
    {
        $this->version = $version;
        $this->imageHelper = $imageHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function configure(Configuration $config)
    {
        task('deploy:daas:manifest', function () use ($config) {
            file_put_contents('.hipex-daas.json', json_encode($this->getManifestData($config)));
            upload('.hipex-daas.json', '{{release_path}}');
        })->onRoles(ServerRole::APPLICATION_FIRST);
    }

    /**
     * {@inheritDoc}
     */
    public function registerAfter(): void
    {
        after('deploy:release', 'deploy:daas:manifest');
    }

    /**
     * @param Configuration $configuration
     * @return array
     * @throws EnvironmentVariableNotDefinedException|CompatibilityException
     */
    private function getManifestData(Configuration $configuration): array
    {
        $stage = $this->getCurrentStage($configuration);
        return [
            'deploy_version' => $this->version,
            'app_version' => $this->imageHelper->getVersion(),
            'shared' => $this->getSharedFilesAndFolders($configuration),
            'servers' => $this->getStageServers($stage),
            'daas' => $this->getDaasInfo($configuration),
            'redis' => $this->getRedisServices($configuration, $stage),
            'log_dir' => $configuration->getLogDir(),
        ];
    }

    /**
     * @param Configuration $configuration
     * @return array[]
     */
    private function getSharedFilesAndFolders(Configuration $configuration): array
    {
        $files = [];
        $clusterFiles = [];
        foreach ($configuration->getSharedFiles() as $file) {
            $files[] = $file->getFile();
            if ($file instanceof ClusterSharedFile) {
                $clusterFiles[] = $file->getFile();
            }
        }

        $folders = [];
        $clusterFolders = [];
        foreach ($configuration->getSharedFolders() as $file) {
            $folders[] = $file->getFolder();
            if ($file instanceof ClusterSharedFolder) {
                $clusterFolders[] = $file->getFolder();
            }
        }

        return [
            'files' => $files,
            'cluster_files' => $clusterFiles,
            'folders' => $folders,
            'cluster_folders' => $clusterFolders,
        ];
    }

    /**
     * @param Configuration $configuration
     * @return Stage
     */
    private function getCurrentStage(Configuration $configuration): Stage
    {
        foreach ($configuration->getStages() as $stage) {
            if ($stage->getName() === get('stage')) {
                return $stage;
            }
        }

        throw new RuntimeException('Could not determine current stage');
    }

    /**
     * @param Stage $stage
     * @return array
     */
    private function getStageServers(Stage $stage): array
    {
        $servers = [];
        foreach ($stage->getServers() as $server) {
            $servers[] = $this->getStageServer($server);
        }
        return $servers;
    }

    /**
     * @param Server $server
     * @return array
     */
    private function getStageServer(Server $server): array
    {
        return [
            'hostname' => $server->getHostname(),
            'roles' => $server->getRoles(),
            'options' => $server->getOptions(),
        ];
    }

    /**
     * @param Configuration $configuration
     * @return array|null
     */
    private function getDaasInfo(Configuration $configuration): ?array
    {
        try {
            return [
                'docker_image_php' => $this->imageHelper->getDockerImage($configuration, 'php'),
                'docker_image_nginx' => $this->imageHelper->getDockerImage($configuration, 'nginx'),
            ];
        } catch (EnvironmentVariableNotDefinedException $e) {
            // In this case DAAS is just not enabled
            return null;
        }
    }

    /**
     * @param Configuration $configuration
     * @param Stage         $stage
     * @return array
     * @throws CompatibilityException
     */
    private function getRedisServices(Configuration $configuration, Stage $stage): array
    {
        $result = [];
        foreach ($configuration->getPlatformServices() as $service) {
            if (!$service instanceof RedisService) {
                continue;
            }

            if (!method_exists($service, 'getPort')) {
                throw new CompatibilityException("2.3.0", 'hipex/deploy-configuration');
            }

            $serviceStage = $service->getStage();
            if ($serviceStage !== null && $serviceStage->getName() !== $stage->getName()) {
                continue;
            }

            $result[] = [
                'identifier' => $service->getIdentifier(),
                'master' => $service->getMasterServer(),
                'port' => $service->getPort(),
                'max_memory' => $service->getMaxMemory(),
                'server_roles' => $service->getServerRoles(),
            ];
        }
        return $result;
    }
}
