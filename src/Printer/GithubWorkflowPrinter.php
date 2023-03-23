<?php

declare(strict_types=1);

namespace Hypernode\Deploy\Printer;

use Deployer\Component\ProcessRunner\Printer;
use Deployer\Host\Host;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class GithubWorkflowPrinter extends Printer
{
    public const WORKFLOW_COMMAND_PATTERN = '/^::[a-zA-Z0-9-].*::.*$/m';

    private OutputInterface $output;

    public function __construct(OutputInterface $output)
    {
        parent::__construct($output);
        $this->output = $output;
    }

    public function contentHasWorkflowCommand(string $content): bool
    {
        return (bool)preg_match(self::WORKFLOW_COMMAND_PATTERN, trim($content));
    }

    public function callback(Host $host, bool $forceOutput): callable
    {
        return function ($type, $buffer) use ($forceOutput, $host) {
            if (
                $this->output->isVerbose() || $forceOutput ||
                ($type == Process::OUT && $this->contentHasWorkflowCommand($buffer))
            ) {
                $this->printBuffer($type, $host, $buffer);
            }
        };
    }

    public function writeln(string $type, Host $host, string $line): void
    {
        if (empty($line)) {
            return;
        }

        if ($type == Process::OUT && $this->contentHasWorkflowCommand($line)) {
            $this->output->writeln($line);
            return;
        }

        parent::writeln($type, $host, $line);
    }
}
