<?php

namespace Hypernode\Deploy\Command;

use Deployer\Deployer;
use Deployer\Command\TreeCommand;
use Symfony\Component\Console\Input\InputInterface as Input;
use Symfony\Component\Console\Output\OutputInterface as Output;
use Hypernode\Deploy\DeployRunner;
use Hypernode\Deploy\Exception\InvalidConfigurationException;
use Hypernode\Deploy\Exception\ValidationException;

class Tree extends TreeCommand
{
    /**
     * @var DeployRunner
     */
    private $deployRunner;

    public function __construct(
        Deployer $deployer,
        DeployRunner $deployRunner,
    ) {
        parent::__construct($deployer);
        $this->deployRunner = $deployRunner;
    }

    protected function execute(Input $input, Output $output): int
    {
        try {
            $this->deployRunner->prepare(true, true, $input->getArgument('task'), false);
        } catch (InvalidConfigurationException | ValidationException $e) {
            $output->write($e->getMessage());
            return 1;
        }
        $result = parent::execute($input, $output);

        return $result;
    }
}
