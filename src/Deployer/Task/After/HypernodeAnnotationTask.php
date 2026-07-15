<?php

namespace Hypernode\Deploy\Deployer\Task\After;

use Deployer\Task\Task;
use Hypernode\Deploy\Deployer\Task\ConfigurableTaskInterface;
use Hypernode\Deploy\Deployer\Task\TaskBase;
use Hypernode\DeployConfiguration\AfterDeployTask\HypernodeAnnotation;
use Hypernode\DeployConfiguration\TaskConfigurationInterface;

use function Deployer\set;

class HypernodeAnnotationTask extends TaskBase implements ConfigurableTaskInterface
{
    public function supports(TaskConfigurationInterface $config): bool
    {
        return $config instanceof HypernodeAnnotation;
    }

    /**
     * @param TaskConfigurationInterface|HypernodeAnnotation $taskConfig
     */
    public function configureWithTaskConfig(TaskConfigurationInterface $taskConfig): ?Task
    {
        set('hypernode_annotations', [
            'name' => $taskConfig->getName(),
            'description' => $taskConfig->getDescription(),
            'app' => $taskConfig->getApp(),
            'api_token' => $taskConfig->getApiToken(),
            'throw_on_error' => $taskConfig->getThrowOnError(),
        ]);

        return null;
    }
}
