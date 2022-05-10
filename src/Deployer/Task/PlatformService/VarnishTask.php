<?php

/**
 * @author Hypernode
 * @copyright Copyright (c) Hypernode
 */

namespace Hypernode\Deploy\Deployer\Task\PlatformService;

use Hypernode\Deploy\Deployer\Task\ConfigValidationInterface;
use Hypernode\Deploy\Deployer\Task\IncrementedTaskTrait;
use Hypernode\Deploy\Exception\InvalidConfigurationException;
use function Deployer\upload;
use function Deployer\output;
use function Hypernode\Deploy\Deployer\after;
use function Deployer\run;
use function Deployer\task;
use Deployer\Task\Task;
use Hypernode\Deploy\Deployer\Task\ConfigurableTaskInterface;
use Hypernode\Deploy\Deployer\Task\RegisterAfterInterface;
use Hypernode\Deploy\Stdlib\PathInfo;
use Hypernode\DeployConfiguration\Configuration;
use Hypernode\DeployConfiguration\PlatformService\VarnishService;
use Hypernode\DeployConfiguration\TaskConfigurationInterface;
use Twig\Environment;

class VarnishTask implements ConfigurableTaskInterface, RegisterAfterInterface
{
    use IncrementedTaskTrait;

    /**
     * @var Environment
     */
    private $twig;

    /**
     * VarnishTask constructor.
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
        return 'deploy:configuration:supervisor:varnish:';
    }

    /**
     * Define deployer task using Hipex configuration
     *
     * @param VarnishService|TaskConfigurationInterface $config
     * @return Task|null
     */
    public function build(TaskConfigurationInterface $config): ?Task
    {
        $taskName = $this->getTaskName();
        return task($taskName, function () use ($config) {
            // Upload the VCL configuration file to the server
            upload($config->getConfigFile(), $this->getServerVclLocation());

            run(sprintf(
                'echo %s > ~/supervisor/supervisor.d/varnish.conf',
                escapeshellarg($this->buildSupervisorConfig($config))
            ));
        })->onRoles($config->getServerRoles());
    }

    /**
     * Use this method to register your task after another task
     * i.e. after('taska', 'taskb')
     */
    public function registerAfter(): void
    {
        foreach ($this->getRegisteredTasks() as $taskName) {
            after('deploy:configuration:supervisor', $taskName);
        }
    }

    /**
     * @param VarnishService $varnishServiceConfiguration
     * @return string
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    private function buildSupervisorConfig(VarnishService $varnishServiceConfiguration): string
    {
        return $this->twig->load('supervisor_program.conf.twig')->render(
            [
                'program_name' => 'varnish',
                'command' => $this->buildVarnishRunCommand($varnishServiceConfiguration),
                'directory' => PathInfo::getAbsoluteDomainPath(),
                'stdout_log' => PathInfo::getAbsoluteDomainPath() . '/var/log/varnish.log'
            ]
        );
    }

    /**
     * @param VarnishService $varnishServiceConfiguration
     * @return string
     */
    private function buildVarnishRunCommand(VarnishService $varnishServiceConfiguration): string
    {
        $args = implode(' ', array_merge([
            '-p feature=+esi_ignore_other_elements',
            '-p vcc_allow_inline_c=on',
            '-a :' . $varnishServiceConfiguration->getFrontendPort(),
            '-T :' . $varnishServiceConfiguration->getBackendPort(),
            '-f ' . $this->getServerVclLocation(),
            '-S ' . PathInfo::getAbsoluteDomainPath() . '/var/etc/varnish.secret',
            '-s malloc,' . $varnishServiceConfiguration->getMemory(),
            '-F',
            '-n ' . PathInfo::getAbsoluteDomainPath() . '/var/run',
        ], $varnishServiceConfiguration->getArguments()));

        return 'varnishd ' . $args;
    }

    /**
     * Configure using hipex configuration
     *
     * @param TaskConfigurationInterface|VarnishService $config
     * @throws InvalidConfigurationException
     */
    public function configureTask(TaskConfigurationInterface $config): void
    {
        // Check if VCL exists
        if (!file_exists($config->getConfigFile())) {
            throw new InvalidConfigurationException(sprintf(
                'Configuration file "%s" does not exist skipping Varnish setup',
                $config->getConfigFile()
            ));
        }
    }

    /**
     * @param TaskConfigurationInterface $service
     * @return bool
     */
    public function supports(TaskConfigurationInterface $service): bool
    {
        return $service instanceof VarnishService;
    }

    /**
     * Configure using hipex configuration
     *
     * @param Configuration $config
     */
    public function configure(Configuration $config)
    {
    }

    /**
     * Get the VCL location
     *
     * @return string
     */
    protected function getServerVclLocation(): string
    {
        return PathInfo::getAbsoluteDomainPath() . '/var/etc/varnish.vcl';
    }
}
