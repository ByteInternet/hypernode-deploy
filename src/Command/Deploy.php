<?php

namespace Hypernode\Deploy\Command;

use Hypernode\Deploy\DeployRunner;
use Hypernode\Deploy\Report\ReportWriter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

class Deploy extends Command
{
    /**
     * @var DeployRunner
     */
    private $deployRunner;
    private ReportWriter $reportWriter;

    public function __construct(DeployRunner $deployRunner, ReportWriter $reportWriter)
    {
        parent::__construct();
        $this->deployRunner = $deployRunner;
        $this->reportWriter = $reportWriter;
    }

    protected function configure()
    {
        parent::configure();
        $this->setName('deploy');
        $this->setDescription('Deploy application.');
        $this->addArgument('stage', InputArgument::REQUIRED, 'Stage deploy to');
    }

    /**
     * @throws Throwable
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $result = $this->deployRunner->run($output, $input->getArgument('stage'), DeployRunner::TASK_DEPLOY, false, true);

        if ($result === 0) {
            $this->reportWriter->write($this->deployRunner->getDeploymentReport());
        }

        return $result;
    }
}
