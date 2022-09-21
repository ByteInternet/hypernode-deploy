<?php

namespace Hypernode\Deploy\Command;

use Hypernode\Deploy\DeployRunner;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

class ComposerAuth extends Command
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
        $this->setName('composer-auth');
        $this->setDescription('Setup composer authentication file');
    }

    /**
     * @throws Throwable
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        return $this->deployRunner->run($output, 'build', 'deploy:vendors:auth', false, false);
    }
}
