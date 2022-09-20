<?php

namespace Hypernode\Deploy\Command;

use Hypernode\Deploy\DeployRunner;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

class Build extends Command
{
    /**
     * @var DeployRunner
     */
    private $deployRunner;

    public function __construct(DeployRunner $deployRunner)
    {
        $this->deployRunner = $deployRunner;
        parent::__construct();
    }

    protected function configure()
    {
        parent::configure();
        $this->setName('build');
        $this->setDescription('Build application and package');
    }

    /**
     * @throws Throwable
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        return $this->deployRunner->run($output, 'build', DeployRunner::TASK_BUILD);
    }
}
