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

    /**
     * JobqueueConsumerTask constructor.
     * @param Environment $twig
     */
    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }

    /**
     * @return string
     */
    protected function getIncrementalNamePrefix(): string
    {
        return 'deploy:configuration:supervisor:m2-jobqueue:';
    }

    /**
     * Configure deployer using Hipex configuration
     *
     * @param TaskConfigurationInterface $config
     *
     * @return void
     */
    public function configureTask(TaskConfigurationInterface $config)
    {
    }

    /**
     * Define deployer task using Hipex configuration
     *
     * @param JobQueueConsumer|TaskConfigurationInterface $config
     * @return Task|null
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

    /**
     * @param TaskConfigurationInterface $config
     * @return bool
     */
    public function supports(TaskConfigurationInterface $config): bool
    {
        return $config instanceof JobQueueConsumer;
    }

    /**
     * @param JobQueueConsumer $config
     * @return string
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

    /**
     * @param JobQueueConsumer $config
     * @return string
     */
    private function getSupervisorCommand(JobQueueConsumer $config)
    {
        return implode(' ', [
            '{{bin/php}} bin/magento',
            sprintf('queue:consumers:start %s', $config->getConsumer()),
            sprintf('--max-messages=%s', $config->getMaxMessages()),
        ]);
    }

    /**
     * Use this method to register your task after another task
     * i.e. after('taska', 'taskb')
     */
    public function registerAfter(): void
    {
        foreach ($this->getRegisteredTasks() as $taskName) {
            before('deploy:supervisor:upload', $taskName);
        }
    }

    /**
     * Configure using hipex configuration
     *
     * @param Configuration $config
     *
     * @return void
     */
    public function configure(Configuration $config)
    {
    }
}
