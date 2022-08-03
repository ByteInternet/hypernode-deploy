<?php

namespace Hypernode\Deploy\Deployer\Task\PlatformConfiguration;

use Deployer\Task\Task;
use function Deployer\fail;
use function Deployer\get;
use function Deployer\set;
use function Deployer\task;
use function Deployer\upload;
use function Deployer\writeln;

use Hypernode\Deploy\Deployer\Task\ConfigurableTaskInterface;
use Hypernode\Deploy\Deployer\Task\IncrementedTaskTrait;
use Hypernode\DeployConfiguration\Configuration;
use Hypernode\DeployConfiguration\TaskConfigurationInterface;
use Hypernode\DeployConfiguration\PlatformConfiguration\NginxConfiguration;

class NginxUploadTask implements ConfigurableTaskInterface
{
    use IncrementedTaskTrait;

    protected function getIncrementalNamePrefix(): string
    {
        return 'deploy:configuration:nginx:upload:';
    }

    public function configureTask(TaskConfigurationInterface $config): void
    {
    }

    public function supports(TaskConfigurationInterface $config): bool
    {
        return $config instanceof NginxConfiguration;
    }

    /**
     * @param TaskConfigurationInterface|NginxConfiguration $config
     */
    public function build(TaskConfigurationInterface $config): ?Task
    {
        return null;
    }

    public function upload(NginxConfiguration $config): void
    {
        $sourceDir = rtrim($config->getSourceFolder(), '/');
        writeln("Uploading $sourceDir to {{nginx/config_path}}");

        $args = [
            '--archive',
            '--recursive',
            '--verbose',
            '--ignore-errors',
            '--copy-links',
            '--delete',
        ];
        $args = array_map('escapeshellarg', $args);
        upload($sourceDir . '/', '{{nginx/config_path}}/', ['options' => $args]);
    }

    public function configure(Configuration $config): void
    {
        set('nginx/config_path', function () {
            return '/tmp/nginx-config-' . get('hostname');
        });

        task('deploy:nginx:upload', function () use ($config) {
            // Upload all Nginx configs to the temp dir on the Hypernode
            foreach ($config->getPlatformConfigurations() as $platformConfiguration) {
                if ($platformConfiguration instanceof NginxConfiguration) {
                    $this->upload($platformConfiguration);
                }
            }
        });
        fail('deploy:nginx:upload', 'deploy:nginx:cleanup');
    }
}
