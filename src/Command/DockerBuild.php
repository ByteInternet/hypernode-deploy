<?php

namespace Hypernode\Deploy\Command;

use Hypernode\Deploy\DeployRunner;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

class DockerBuild extends Command
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

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();
        $this->setName('docker:build');
        $this->setDescription('Build docker image based on deploy file.');
    }

    /**
     * {@inheritdoc}
     * @throws Throwable
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->deployRunner->run($output, 'build', 'docker:build');
    }
}
