<?php

namespace Hypernode\Deploy\Deployer\Task\Docker;

use Hypernode\DeployConfiguration\Exception\EnvironmentVariableNotDefinedException;
use Hypernode\Deploy\Deployer\Task\TaskInterface;
use Hypernode\DeployConfiguration\Configuration;

use function Deployer\run;
use function Deployer\task;
use function Deployer\writeln;

class PushTask implements TaskInterface
{
    /**
     * @var ImageNameHelper
     */
    private $imageHelper;

    /**
     * @param ImageNameHelper $imageHelper
     */
    public function __construct(ImageNameHelper $imageHelper)
    {
        $this->imageHelper = $imageHelper;
    }

    /**
     * @param Configuration $config
     *
     * @return void
     */
    public function configure(Configuration $config)
    {
        task('docker:push', function () use ($config) {
            $this->registryLogin($config);
            $this->push($this->imageHelper->getDockerImage($config, 'php'));
            $this->push($this->imageHelper->getDockerImage($config, 'nginx'));
        })->onStage('build');
    }

    private function push(string $tag): void
    {
        $tag = escapeshellarg($tag);
        run("docker push {$tag}", ['timeout' => 3600]);
    }

    /**
     * @param Configuration $config
     * @throws EnvironmentVariableNotDefinedException
     */
    private function registryLogin(Configuration $config): void
    {
        $username = $config->getDockerRegistryUsername() ?: getenv('CI_REGISTRY_USER');
        $password = $config->getDockerRegistryPassword() ?: getenv('CI_REGISTRY_PASSWORD');

        if (!$username || !$password) {
            writeln('----------------------------------------------------------');
            writeln('No docker registry username / password set, login skipped');
            writeln('----------------------------------------------------------');
            return;
        }

        $registry = escapeshellarg($this->imageHelper->getImageRegistry($config));

        run("docker login ${registry} --username \$USERNAME --password \$PASSWORD", [
            'env' => [
                'USERNAME' => $username,
                'PASSWORD' => $password,
            ]
        ]);
    }
}
