<?php

namespace Hypernode\Deploy\Command;

use Hypernode\Deploy\DeployRunner;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

class RunTask extends Command
{
    private const ARGUMENT_STAGE = 'stage';
    private const ARGUMENT_TASK = 'task';
    private const OPTION_CONFIGURE_BUILD_STAGE = 'configure-build-stage';
    private const OPTION_CONFIGURE_SERVERS = 'configure-servers';

    /**
     * @var DeployRunner
     */
    private $deployRunner;

    public function __construct(DeployRunner $deployRunner)
    {
        parent::__construct();
        $this->deployRunner = $deployRunner;
    }

    protected function configure()
    {
        parent::configure();
        $this->setName('run-task');
        $this->setDescription('Run a seperate deployer task');
        $this->addArgument(self::ARGUMENT_STAGE, InputArgument::REQUIRED, 'Stage');
        $this->addArgument(self::ARGUMENT_TASK, InputArgument::REQUIRED, 'Task to run');
        $this->addOption(self::OPTION_CONFIGURE_BUILD_STAGE, 'b', InputOption::VALUE_NONE, 'Configure build stage before running task');
        $this->addOption(self::OPTION_CONFIGURE_SERVERS, 's', InputOption::VALUE_NONE, 'Configure servers before running task');
    }

    /**
     * @throws Throwable
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        return $this->deployRunner->run(
            $output,
            $input->getArgument(self::ARGUMENT_STAGE),
            $input->getArgument(self::ARGUMENT_TASK),
            $input->getOption(self::OPTION_CONFIGURE_BUILD_STAGE),
            $input->getOption(self::OPTION_CONFIGURE_SERVERS),
        );
    }
}
