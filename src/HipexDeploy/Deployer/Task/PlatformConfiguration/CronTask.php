<?php
/**
 * @author Hipex <info@hipex.io>
 * @copyright (c) Hipex B.V. 2020
 */

namespace HipexDeploy\Deployer\Task\PlatformConfiguration;

use HipexDeploy\Deployer\Task\IncrementedTaskTrait;
use function Deployer\run;
use function Deployer\set;
use function Deployer\task;
use Deployer\Task\Context;
use Deployer\Task\Task;
use function Deployer\upload;
use function Deployer\within;
use function HipexDeploy\Deployer\before;
use HipexDeploy\Deployer\Task\ConfigurableTaskInterface;
use HipexDeploy\Deployer\Task\RegisterAfterInterface;
use HipexDeployConfiguration\Configuration;
use HipexDeployConfiguration\PlatformConfiguration\CronConfiguration;
use HipexDeployConfiguration\ServerRole;
use HipexDeployConfiguration\TaskConfigurationInterface;

class CronTask implements ConfigurableTaskInterface, RegisterAfterInterface
{

    use IncrementedTaskTrait;

    /**
     * @return string
     */
    protected function getIncrementalNamePrefix(): string
    {
        return 'deploy:configuration:crontab:';
    }

    /**
     * @param TaskConfigurationInterface|CronConfiguration $config
     */
    public function configureTask(TaskConfigurationInterface $config): void
    {
        set($this->getSourceFileName($config), $config->getSourceFile());
    }

    /**
     * @param TaskConfigurationInterface $config
     * @return bool
     */
    public function supports(TaskConfigurationInterface $config): bool
    {
        return $config instanceof CronConfiguration;
    }

    /**
     * Define deployer task using Hipex configuration
     *
     * @param TaskConfigurationInterface $config
     * @return Task|null
     */
    public function build(TaskConfigurationInterface $config): ?Task
    {
        $cronSourceFile = $this->getSourceFileName($config);
        return task($this->getTaskName(), function() use ($cronSourceFile) {
            upload('{{' . $cronSourceFile . '}}', '/tmp/crontab.source');
            within('{{release_path}}', function() use ($cronSourceFile) {
                set('cron/tmp-file', '/tmp/crontab.' . md5(rand()));
                try {
                    $host = Context::get()->getHost();
                    $path = sprintf('/home/%s/.bin:/usr/local/bin:/usr/bin:/usr/local/sbin:/usr/sbin', $host->getUser());
                    run('echo "PATH=' . $path . '" > {{cron/tmp-file}}');
                    run('echo "PHPBIN={{bin/php}}" >> {{cron/tmp-file}}');
                    $absoluteReleasePath = run('pwd');
                    run('echo "APPLICATION_ROOT=' . $absoluteReleasePath . '" >> {{cron/tmp-file}}');
                    run('echo "" >> {{cron/tmp-file}}');
                    run('cat /tmp/crontab.source >> {{cron/tmp-file}}');
                    run('echo "" >> {{cron/tmp-file}}');
                    run('crontab {{cron/tmp-file}}');
                } finally {
                    run('[ -e /tmp/crontab.source ] && rm /tmp/crontab.source');
                    run('[ -e {{cron/tmp-file}} ] && rm {{cron/tmp-file}}');
                }
            });
        })->onRoles(ServerRole::APPLICATION);
    }

    /**
     * Use this method to register your task after another task
     * i.e. after('taska', 'taskb')
     */
    public function registerAfter(): void
    {
        foreach ($this->getRegisteredTasks() as $taskName) {
            before('deploy:symlink', $taskName);
        }
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
     * @param TaskConfigurationInterface $config
     * @return string
     */
    protected function getSourceFileName(TaskConfigurationInterface $config): string
    {
        return 'cron/source-file-' . spl_object_hash($config);
    }
}
