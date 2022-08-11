<?php

namespace Hypernode\Deploy\Deployer\Task;

use Deployer\Task\Task;
use Hypernode\Deploy\Exception\InvalidConfigurationException;
use Hypernode\DeployConfiguration\TaskConfigurationInterface;

interface ConfigurableTaskInterface extends TaskInterface
{
    /**
     * Define deployer task using Hipex configuration
     *
     * @param TaskConfigurationInterface $config
     * @return Task|null
     */
    public function configureWithTaskConfig(TaskConfigurationInterface $config): ?Task;

    /**
     * @param TaskConfigurationInterface $config
     * @return bool
     */
    public function supports(TaskConfigurationInterface $config): bool;
}
