<?php

namespace Hypernode\Deploy\Deployer;

use function Deployer\parse;
use function Deployer\run;
use function Deployer\task;
use Deployer\Task\Task;
use function Deployer\within;
use function Deployer\writeln;
use Hypernode\DeployConfiguration\Command\Command;
use Hypernode\DeployConfiguration\Command\DeployCommand;
use Hypernode\DeployConfiguration\ServerRoleConfigurableInterface;
use Hypernode\DeployConfiguration\StageConfigurableInterface;

class TaskBuilder
{
    /**
     * @param Command[] $commands
     * @param string $namePrefix
     * @return Task[]
     */
    public function buildAll(array $commands, string $namePrefix): array
    {
        $tasks = [];
        foreach ($commands as $command) {
            $name = $namePrefix . ':' . \count($tasks);

            $this->build($command, $name);
            $tasks[] = $name;
        }
        return $tasks;
    }

    /**
     * @param Command $command
     * @param string $name
     * @return Task
     */
    private function build(Command $command, string $name): Task
    {
        $task = task($name, function () use ($command) {
            $this->runCommandWithin($command);
        });

        if ($command instanceof StageConfigurableInterface && $command->getStage()) {
            $task->onStage($command->getStage()->getName());
        }

        if ($command instanceof ServerRoleConfigurableInterface && $command->getServerRoles()) {
            $task->onRoles($command->getServerRoles());
        }

        return $task;
    }

    /**
     * @param Command $command
     */
    private function runCommandWithin(Command $command)
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
     * @param Command $command
     */
    private function runCommand(Command $command)
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
