<?php

namespace Hypernode\Deploy\Deployer;

use Deployer\Exception\Exception;
use Deployer\Exception\RunException;
use Deployer\Exception\TimeoutException;
use Deployer\Task\Task;
use Hypernode\DeployConfiguration\Command\DeployCommand;
use Hypernode\DeployConfiguration\ServerRoleConfigurableInterface;
use Hypernode\DeployConfiguration\StageConfigurableInterface;

use Hypernode\DeployConfiguration\TaskConfigurationInterface;
use function Deployer\parse;
use function Deployer\run;
use function Deployer\task;
use function Deployer\within;
use function Deployer\writeln;

class TaskBuilder
{
    /**
     * @param TaskConfigurationInterface[] $commands
     *
     * @param string $namePrefix
     * @return string[]
     *
     * @psalm-return list<string>
     */
    public function buildAll(array $commands, string $namePrefix): array
    {
        $tasks = [];
        foreach ($commands as $command) {
            $name = $namePrefix . ':' . \count($tasks);

            try {
                $this->build($command, $name);
            } catch (\Exception $e) {
                return [];
            }
            $tasks[] = $name;
        }
        return $tasks;
    }

    /**
     * @param TaskConfigurationInterface $command
     * @param string $name
     * @return Task
     * @throws \Exception
     */
    private function build(TaskConfigurationInterface $command, string $name): Task
    {
        $task = task($name, function () use ($command) {
            $this->runCommandWithin($command);
        });

        if ($command instanceof StageConfigurableInterface && $command->getStage()) {
            $task->select("stage={$command->getStage()->getName()}");
        }

        if ($command instanceof ServerRoleConfigurableInterface && $command->getServerRoles()) {
            $roles = implode("&", $command->getServerRoles());
            $task->select("roles={$roles}");
        }

        return $task;
    }

    /**
     * @param TaskConfigurationInterface $command
     * @throws Exception
     * @throws RunException
     * @throws TimeoutException
     */
    private function runCommandWithin(TaskConfigurationInterface $command): void
    {
        $directory = $command->getWorkingDirectory();
        if ($directory === null && $command instanceof DeployCommand) {
            $directory = '{{release_path}}';
        }

        within($directory, function () use ($command) {
            $this->runCommand($command);
        });
    }

    /**
     * @param TaskConfigurationInterface $command
     * @throws Exception
     * @throws RunException
     * @throws TimeoutException
     */
    private function runCommand(TaskConfigurationInterface $command): void
    {
        $commandAction = $command->getCommand();
        if (\is_callable($commandAction)) {
            $commandAction();
        } else {
            writeln(sprintf('Running: "%s"', parse($commandAction)));
            run($commandAction, ['timeout' => 3600]);
        }
    }
}
