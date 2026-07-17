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
     * @param TaskConfigurationInterface|HypernodeAnnotation $config
     */
    public function configureWithTaskConfig(TaskConfigurationInterface $config): ?Task
    {
        set('hypernode_annotations', [
            'name' => $config->getName(),
            'description' => $config->getDescription(),
            'app' => $config->getApp(),
            'api_token' => $config->getApiToken(),
            'throw_on_error' => $config->getThrowOnError(),
        ]);

        return null;
    }
}
