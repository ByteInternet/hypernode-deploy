<?php
/**
 * @author Hipex <info@hipex.io>
 * @copyright (c) Hipex B.V. 2018
 */

namespace HipexDeploy\Command;

use HipexDeploy\DeployRunner;
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

    /**
     * Deploy constructor.
     *
     * @param DeployRunner $deployRunner
     */
    public function __construct(DeployRunner $deployRunner)
    {
        $this->deployRunner = $deployRunner;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();
        $this->setName('build');
        $this->setDescription('Build application and package');
    }

    /**
     * {@inheritdoc}
     * @throws Throwable
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->deployRunner->run($output, 'build', 'build');
    }
}
