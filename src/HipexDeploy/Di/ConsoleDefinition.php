<?php
/**
 * @author Hipex <info@hipex.io>
 * @copyright (c) Hipex B.V. 2018
 */

namespace HipexDeploy\Di;

use function DI\autowire;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

class ConsoleDefinition
{
    /**
     * @return array
     */
    public static function getDefinition(): array
    {
        return [
            InputInterface::class => autowire(ArgvInput::class),
            OutputInterface::class => autowire(ConsoleOutput::class),
            LoggerInterface::class => autowire(ConsoleLogger::class),
        ];
    }
}
