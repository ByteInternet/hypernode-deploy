<?php

/**
 * @author Hypernode
 * @copyright Copyright (c) Hypernode
 */

namespace Hypernode\Deploy\Command;

use Hypernode\Deploy\DeployRunner;
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
        $this->setName('deploy');
        $this->setDescription('Deploy application.');
        $this->addArgument('stage', InputArgument::REQUIRED, 'Stage deploy to');
    }

    /**
     * {@inheritdoc}
     * @throws Throwable
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->deployRunner->run($output, $input->getArgument('stage'), 'deploy');
    }
}