<?php

namespace Hypernode\Deploy\Deployer;

use Deployer\Task\Task;
use Hypernode\DeployConfiguration\Command\Command;
use Hypernode\DeployConfiguration\Command\DeployCommand;
use Hypernode\DeployConfiguration\ServerRoleConfigurableInterface;
use Hypernode\DeployConfiguration\StageConfigurableInterface;

use function Deployer\parse;
use function Deployer\run;
use function Deployer\task;
use function Deployer\within;
use function Deployer\writeln;

class TaskBuilder
{
    /**
     * @param Command[] $commands
     *
     * @return string[]
     *
     * @psalm-return list<string>
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

    private function runCommandWithin(Command $command): void
    {
        $directory = $command->getWorkingDirectory();
        if ($directory === null && $command instanceof DeployCommand) {
            $directory = '{{release_path}}';
        }

        within($directory, function () use ($command) {
            $this->runCommand($command);
        });
    }

    private function runCommand(Command $command): void
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
