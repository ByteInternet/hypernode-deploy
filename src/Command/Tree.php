<?php

declare(strict_types=1);

namespace Hypernode\Deploy\Command;

use Deployer\Deployer;
use Deployer\Command\TreeCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface as Input;
use Symfony\Component\Console\Output\OutputInterface as Output;
use Hypernode\Deploy\DeployRunner;
use Hypernode\Deploy\Exception\InvalidConfigurationException;
use Hypernode\Deploy\Exception\ValidationException;

class Tree extends TreeCommand
{
    private const ARGUMENT_STAGE = 'stage';

    private Deployer $deployer;

    /**
     * @var DeployRunner
     */
    private $deployRunner;

    public function __construct(
        Deployer $deployer,
        DeployRunner $deployRunner,
    ) {
        parent::__construct($deployer);
        $this->deployer = $deployer;
        $this->deployRunner = $deployRunner;
    }

    protected function configure(): void
    {
        parent::configure();
        $this->addArgument(
            self::ARGUMENT_STAGE,
            InputArgument::OPTIONAL,
            'Only show tasks that are applicable to the given stage'
        );
    }

    protected function execute(Input $input, Output $output): int
    {
        $stage = $input->getArgument(self::ARGUMENT_STAGE);

        try {
            $this->deployRunner->prepare(true, false, $stage ?? '', false);
        } catch (InvalidConfigurationException | ValidationException $e) {
            $output->write($e->getMessage());
            return 1;
        }

        if ($stage !== null) {
            if (!$this->isConfiguredStage($stage)) {
                $output->writeln(
                    sprintf('<error>Stage "%s" is not defined in the deploy configuration.</error>', $stage)
                );
                return 1;
            }

            $this->disableTasksNotApplicableToStage($stage);
            $output->writeln(sprintf('Showing tasks for stage <info>%s</info>:', $stage));
        }

        return parent::execute($input, $output);
    }

    private function isConfiguredStage(string $stage): bool
    {
        $config = $this->deployRunner->getConfiguration();
        if ($config === null) {
            return false;
        }

        foreach ($config->getStages() as $configStage) {
            if ($configStage->getName() === $stage) {
                return true;
            }
        }

        return false;
    }

    private function disableTasksNotApplicableToStage(string $stage): void
    {
        foreach ($this->deployer->tasks->all() as $task) {
            if (!self::selectorMatchesStage($task->getSelector(), $stage)) {
                $task->disable();
            }
        }
    }

    /**
     * Check whether a Deployer task selector can match a stage.
     *
     * Non-stage conditions (e.g. roles) are ignored, since a task is applicable
     * to a stage as soon as one of its selectors or selector groups matches it.
     */
    public static function selectorMatchesStage(?array $selector, string $stage): bool
    {
        if (empty($selector)) {
            return true;
        }

        foreach ($selector as $conditions) {
            $matchesStage = true;

            foreach ($conditions as [$operator, $variable, $value]) {
                if ($variable !== 'stage') {
                    continue;
                }

                if (!self::compareStage($operator, $value, $stage)) {
                    $matchesStage = false;
                    break;
                }
            }

            if ($matchesStage) {
                return true;
            }
        }

        return false;
    }

    private static function compareStage(string $operator, string $expected, string $stage): bool
    {
        return match ($operator) {
            '=' => $expected === $stage,
            '!=' => $expected !== $stage,
            default => false,
        };
    }
}
