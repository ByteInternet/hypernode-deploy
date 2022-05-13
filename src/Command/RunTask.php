<?php

namespace Hypernode\Deploy\Command;

use Hypernode\Deploy\DeployRunner;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

class RunTask extends Command
{
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
        $this->addArgument('stage', InputArgument::REQUIRED, 'Stage');
        $this->addArgument('task', InputArgument::REQUIRED, 'Task to run');
    }

    /**
     * @throws Throwable
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->deployRunner->run($output, $input->getArgument('stage'), $input->getArgument('task'));
        return 0;
    }
}
