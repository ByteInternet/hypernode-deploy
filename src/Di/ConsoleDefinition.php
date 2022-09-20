<?php

declare(strict_types=1);

namespace Hypernode\Deploy\Di;

use Hypernode\Deploy\Console\Output\ConsoleLogger;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

use function DI\autowire;

class ConsoleDefinition
{
    public static function getDefinition(): array
    {
        return [
            InputInterface::class => autowire(ArgvInput::class),
            OutputInterface::class => autowire(ConsoleOutput::class),
            LoggerInterface::class => autowire(ConsoleLogger::class),
        ];
    }
}
