<?php

namespace Hypernode\Deploy\Deployer;

use Deployer\Exception\Exception;
use Deployer\Exception\RunException;
use Deployer\Exception\TimeoutException;
use Deployer\Task\Task;
use Hypernode\DeployConfiguration\Command\Command;
use Hypernode\DeployConfiguration\Command\DeployCommand;
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

            /** @var Command $command */
            $this->build($command, $name);

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

        return $task;
    }

    /**
     * @param Command $command
     * @throws Exception
     * @throws RunException
     * @throws TimeoutException
     */
    private function runCommandWithin(Command $command): void
    {
        $directory = $command->getWorkingDirectory();
        if ($directory === null && $command instanceof DeployCommand) {
            $directory = '{{release_path}}';
        }

        within($directory ?: '', function () use ($command) {
            $this->runCommand($command);
        });
    }

    /**
     * @param Command $command
     * @throws Exception
     * @throws RunException
     * @throws TimeoutException
     */
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
