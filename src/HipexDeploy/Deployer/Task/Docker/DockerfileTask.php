<?php
/**
 * @author Hipex <info@hipex.io>
 * @copyright (c) Hipex B.V. 2018
 */

namespace HipexDeploy\Deployer\Task\Docker;

use HipexDeployConfiguration\Exception\ConfigurationException;
use HipexDeployConfiguration\PlatformConfiguration\NginxConfiguration;
use Twig\Environment;
use function Deployer\task;
use HipexDeploy\Deployer\Task\TaskInterface;
use HipexDeployConfiguration\Configuration;
use function Deployer\write;

class DockerfileTask implements TaskInterface
{
    private const DEFAULT_NGINX_IMAGE = 'registry.hipex.cloud/hipex-services/docker-image-nginx';
    private const DEFAULT_PHP_IMAGE = 'registry.hipex.cloud/hipex-services/docker-image-php';

    /**
     * @var Environment
     */
    private $twig;

    /**
     * @param Environment $twig
     */
    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }

    /**
     * Configure using hipex configuration
     *
     * @param Configuration $config
     * @throws ConfigurationException
     */
    public function configure(Configuration $config)
    {
        if (!method_exists($config, 'getDockerBaseImagePhp')) {
            throw new ConfigurationException('Deploy configuration version used is not supported. Please update "hipex/deploy-configuration" package');
        }

        task('docker:dockerfile', function() use ($config) {
            file_put_contents('build/Dockerfile.php', $this->twig->render('Dockerfile.php.twig', [
                'base_image' => $this->getBaseImagePhp($config),
                'volumes' => $this->getVolumes($config),
                'public_folder' => $config->getPublicFolder(),
            ]));

            file_put_contents('build/Dockerfile.nginx', $this->twig->render('Dockerfile.nginx.twig', [
                'base_image' => $this->getBaseImageNginx($config),
                'volumes' => $this->getVolumes($config),
                'config_directory' => $this->getNginxConfigDirectory($config),
                'public_folder' => $config->getPublicFolder(),
            ]));
        })->onStage('build');
    }

    /**
     * @param Configuration $config
     * @return string
     * @throws ConfigurationException
     */
    private function getBaseImagePhp(Configuration $config): string
    {
        $image = $config->getDockerBaseImagePhp();
        if ($image) {
            return $image;
        }

        write(
            "<error>" .
            "!!WARNING!!\n" .
            "Building docker images without fixed base image is strongly discouraged,\n" .
            "we suggest to use Configuration::setDockerBaseImagePhp() method to configure one\n" .
            "of the hipex `" . self::DEFAULT_PHP_IMAGE . "/X.X-fpm` tags." .
            "</error>\n"
        );
        switch ($config->getPhpVersion()) {
            case 'php70':
                return self::DEFAULT_PHP_IMAGE . '/7.0-fpm';
            case 'php71':
                return self::DEFAULT_PHP_IMAGE . '/7.1-fpm';
            case 'php72':
                return self::DEFAULT_PHP_IMAGE . '/7.2-fpm';
            case 'php73':
                return self::DEFAULT_PHP_IMAGE . '/7.3-fpm';
            case 'php74':
                return self::DEFAULT_PHP_IMAGE . '/7.4-fpm';
            default:
                throw new ConfigurationException('Php version not supported for docker build, please use at least PHP 7.0');
        }
    }

    /**
     * @param Configuration $config
     * @return array
     */
    private function getVolumes(Configuration $config): array
    {
        $result = [];
        $result[$config->getLogDir()] = $config->getLogDir();

        foreach ($config->getSharedFolders() as $folder) {
            $folder = (string) $folder;
            $result[$folder] = $folder;
        }

        foreach ($config->getWritableFolders() as $folder) {
            $result[$folder] = $folder;
        }
        return $result;
    }

    /**
     * @param Configuration $config
     * @return string
     */
    private function getBaseImageNginx(Configuration $config): string
    {
        $image = $config->getDockerBaseImageNginx();
        if ($image) {
            return $image;
        }

        write(
            "<error>" .
            "!!WARNING!!\n" .
            "Building docker images without fixed base image is strongly discouraged,\n" .
            "we suggest to use Configuration::setDockerBaseImageNginx() method to configure one\n" .
            "of the hipex `" . self::DEFAULT_NGINX_IMAGE . "` tags." .
            "</error>\n"
        );

        return self::DEFAULT_NGINX_IMAGE;
    }

    /**
     * @param Configuration $config
     * @return string|null
     */
    private function getNginxConfigDirectory(Configuration $config): ?string
    {
        foreach ($config->getPlatformConfigurations() as $configuration) {
            if (!$configuration instanceof NginxConfiguration) {
                continue;
            }

            return trim($configuration->getSourceFolder(), '/');
        }
        return null;
    }
}
