<?php

declare(strict_types=1);

namespace Hypernode\Deploy;

use Deployer\Deployer;
use Hypernode\Deploy\Console\Output\OutputWatcher;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DeployerLoader
{
    private ?Deployer $deployer = null;

    public function getOrCreateInstance(OutputInterface $output): Deployer
    {
        if ($this->deployer) {
            return $this->deployer;
        }

        $console = new Application();
        $this->deployer = new Deployer($console);
        $this->deployer['output'] = new OutputWatcher($output);
        $this->deployer['input'] = new ArrayInput(
            [],
            new InputDefinition([
                new InputOption('limit'),
                new InputOption('profile'),
            ])
        );

        return $this->deployer;
    }
}
