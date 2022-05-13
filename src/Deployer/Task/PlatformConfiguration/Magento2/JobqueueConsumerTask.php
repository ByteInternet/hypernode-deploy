<?php

namespace Hypernode\Deploy\Deployer\Task\PlatformConfiguration\Magento2;

use Deployer\Task\Task;
use Hypernode\Deploy\Deployer\Task\ConfigurableTaskInterface;
use Hypernode\Deploy\Deployer\Task\IncrementedTaskTrait;
use Hypernode\Deploy\Deployer\Task\RegisterAfterInterface;
use Hypernode\Deploy\Stdlib\PathInfo;
use Hypernode\DeployConfiguration\Configuration;
use Hypernode\DeployConfiguration\PlatformConfiguration\Magento2\JobQueueConsumer;
use Hypernode\DeployConfiguration\TaskConfigurationInterface;
use Twig\Environment;

use function Deployer\get;
use function Deployer\run;
use function Deployer\task;
use function Hypernode\Deploy\Deployer\before;

class JobqueueConsumerTask implements ConfigurableTaskInterface, RegisterAfterInterface
{
    use IncrementedTaskTrait;

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
        return 'deploy:configuration:supervisor:m2-jobqueue:';
    }

    public function configureTask(TaskConfigurationInterface $config): void
    {
    }

    /**
     * @param JobQueueConsumer|TaskConfigurationInterface $config
     */
    public function build(TaskConfigurationInterface $config): ?Task
    {
        $taskName = $this->getTaskName();
        return task(
            $taskName,
            function () use ($config) {
                run(sprintf('echo %s > {{supervisor/config_path}}/jobqueue.' . $config->getConsumer() . '.conf', escapeshellarg($this->buildSupervisorConfig($config))));
            }
        )->onRoles($config->getServerRoles());
    }

    public function supports(TaskConfigurationInterface $config): bool
    {
        return $config instanceof JobQueueConsumer;
    }

    /**
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    private function buildSupervisorConfig(JobQueueConsumer $config): string
    {
        return $this->twig->load('supervisor_program.conf.twig')->render(
            [
                'program_name' => 'jobqueue-consumer-' . $config->getConsumer(),
                'command' => $this->getSupervisorCommand($config),
                'directory' => get('release_path/magento', get('release_path')),
                'stdout_log' => PathInfo::getAbsoluteDomainPath() . '/var/log/jobqueue.' .  $config->getConsumer() . '.log',
                'numprocs' => $config->getWorkers()
            ]
        );
    }

    private function getSupervisorCommand(JobQueueConsumer $config): string
    {
        return implode(' ', [
            '{{bin/php}} bin/magento',
            sprintf('queue:consumers:start %s', $config->getConsumer()),
            sprintf('--max-messages=%s', $config->getMaxMessages()),
        ]);
    }

    public function registerAfter(): void
    {
        foreach ($this->getRegisteredTasks() as $taskName) {
            before('deploy:supervisor:upload', $taskName);
        }
    }

    public function configure(Configuration $config): void
    {
    }
}
