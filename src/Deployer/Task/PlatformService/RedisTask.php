<?php

namespace Hypernode\Deploy\Deployer\Task\PlatformService;

use Hypernode\Deploy\Deployer\Task\IncrementedTaskTrait;
use Deployer\Exception\Exception;
use Deployer\Task\Context;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Deployer\Task\Task;
use Hypernode\Deploy\Deployer\Task\ConfigurableTaskInterface;
use Hypernode\Deploy\Deployer\Task\RegisterAfterInterface;
use Hypernode\Deploy\Stdlib\PathInfo;
use Hypernode\DeployConfiguration\Configuration;
use Hypernode\DeployConfiguration\PlatformService\RedisService;
use Hypernode\DeployConfiguration\TaskConfigurationInterface;
use Twig\Environment;

use function Hypernode\Deploy\Deployer\after;
use function Deployer\run;
use function Deployer\task;

class RedisTask implements ConfigurableTaskInterface, RegisterAfterInterface
{
    use IncrementedTaskTrait;

    protected const WRAPPER_TASK_NAME = 'deploy:configuration:supervisor:redis';

    /**
     * @var Environment
     */
    private $twig;

    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }

    protected function getIncrementalNamePrefix(): string
    {
        return 'deploy:service:redis:';
    }

    /**
     * @param RedisService|TaskConfigurationInterface $config
     */
    public function build(TaskConfigurationInterface $config): ?Task
    {
        $taskName = $this->getTaskName($config->getIdentifier());

        $task = task($taskName, function () use ($config) {
            run('mkdir -p ' . $this->getRedisRunPath($config));
            run(sprintf('echo %s > ' . $this->getRedisConfigurationFilePath($config), escapeshellarg($this->buildRedisConfig($config))));
            run(sprintf('echo %s > ~/supervisor/supervisor.d/redis.' . $config->getIdentifier() . '.conf', escapeshellarg($this->buildSupervisorConfig($config))));
        })->onRoles($config->getServerRoles());

        after(self::WRAPPER_TASK_NAME, $taskName);

        return $task;
    }

    public function registerAfter(): void
    {
        after('deploy:configuration:supervisor', self::WRAPPER_TASK_NAME);
    }

    /**
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    private function buildSupervisorConfig(RedisService $config): string
    {
        return $this->twig->load('supervisor_program.conf.twig')->render(
            [
                'program_name' => 'redis-' . $config->getIdentifier(),
                'command' => 'redis-server ' . $this->getRedisConfigurationFilePath($config),
                'directory' => PathInfo::getAbsoluteDomainPath(),
                'stdout_log' => PathInfo::getAbsoluteDomainPath() . '/var/log/redis.' . $config->getIdentifier() . '.log'
            ]
        );
    }

    /**
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws Exception
     */
    private function buildRedisConfig(RedisService $config): string
    {
        $templateParams = [
            'max_memory' => $config->getMaxMemory(),
            'directory' => $this->getRedisRunPath($config),
            'pid_file' => $this->getRedisRunPath($config) . '/pid',
            'port' => $config->getPort(),
            'extra_settings' => $config->getExtraSettings(),
        ];

        $snapshotFrequency = $config->getSnapshotSaveFrequency();
        if ($snapshotFrequency && $snapshotFrequency > 0) {
            $templateParams['snapshot_frequency'] = $snapshotFrequency;
        }

        $templateParams['unix_socket'] = sprintf(
            '%s/var/run/%s.sock',
            PathInfo::getAbsoluteDomainPath(),
            'redis.' . $config->getIdentifier()
        );

        if ($config->getMasterServer() && $config->getMasterServer() !== Context::get()->getHost()->getRealHostname()) {
            $templateParams['master'] = $config->getMasterServer();
        }

        return $this->twig->load('redis.conf.twig')->render($templateParams);
    }

    /**
     * @param RedisService $config
     * @return string
     */
    private function getRedisConfigurationFilePath(RedisService $config): string
    {
        return PathInfo::getAbsoluteDomainPath() . '/var/etc/redis.' . $config->getIdentifier() . '.conf';
    }

    private function getRedisRunPath(RedisService $config): string
    {
        $fullIdentifier = 'redis.' . $config->getIdentifier();
        return PathInfo::getAbsoluteDomainPath() . '/var/run/' . $fullIdentifier;
    }

    public function configureTask(TaskConfigurationInterface $config): void
    {
    }

    public function supports(TaskConfigurationInterface $config): bool
    {
        return $config instanceof RedisService;
    }

    public function configure(Configuration $config): void
    {
        task(self::WRAPPER_TASK_NAME, static function () {});
    }
}
