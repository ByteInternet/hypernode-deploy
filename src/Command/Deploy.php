<?php

namespace Hypernode\Deploy\Command;

use Hypernode\Deploy\DeployRunner;
use Hypernode\Deploy\Report\ReportWriter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

class Deploy extends Command
{
    private DeployRunner $deployRunner;
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
        $this->addOption(
            'reuse-brancher',
            null,
            InputOption::VALUE_NONE,
            'Reuse the brancher Hypernode from the previous deploy. Only works when using addBrancherServer in your deploy configuration.'
        );
    }

    /**
     * @throws Throwable
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $result = $this->deployRunner->run(
            $output,
            $input->getArgument('stage'),
            DeployRunner::TASK_DEPLOY,
            false,
            true,
            $input->getOption('reuse-brancher')
        );

        if ($result === 0) {
            $this->reportWriter->write($this->deployRunner->getDeploymentReport());
        }

        return $result;
    }
}
