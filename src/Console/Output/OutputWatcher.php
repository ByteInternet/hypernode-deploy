<?php

declare(strict_types=1);

namespace Hypernode\Deploy\Console\Output;

use Symfony\Component\Console\Formatter\OutputFormatterInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\Output;

class OutputWatcher extends Output implements OutputInterface
{
    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var bool
     */
    private $wasWritten = false;

    /**
     * @param OutputInterface $output
     */
    public function __construct(OutputInterface $output)
    {
        parent::__construct();
        $this->output = $output;
    }

    protected function doWrite(string $message, bool $newline): void
    {
        if ($newline) {
            $this->output->writeln($message);
        } else {
            $this->output->write($message);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function write($messages, $newline = false, int $options = self::OUTPUT_NORMAL): void
    {
        $this->wasWritten = true;
        $this->output->write($messages, $newline, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function writeln($messages, int $options = self::OUTPUT_NORMAL): void
    {
        $this->write($messages, true, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function setVerbosity($level): void
    {
        $this->output->setVerbosity($level);
    }

    /**
     * {@inheritdoc}
     */
    public function getVerbosity(): int
    {
        return $this->output->getVerbosity();
    }

    /**
     * {@inheritdoc}
     */
    public function setDecorated($decorated): void
    {
        $this->output->setDecorated($decorated);
    }

    /**
     * {@inheritdoc}
     */
    public function isDecorated(): bool
    {
        return $this->output->isDecorated();
    }

    /**
     * {@inheritdoc}
     */
    public function setFormatter(OutputFormatterInterface $formatter): void
    {
        $this->output->setFormatter($formatter);
    }

    /**
     * {@inheritdoc}
     */
    public function getFormatter(): OutputFormatterInterface
    {
        return $this->output->getFormatter();
    }

    /**
     * @param boolean $wasWritten
     */
    public function setWasWritten(bool $wasWritten): void
    {
        $this->wasWritten = $wasWritten;
    }

    /**
     * @return boolean
     */
    public function getWasWritten()
    {
        return $this->wasWritten;
    }

    /**
     * {@inheritdoc}
     */
    public function isQuiet(): bool
    {
        return self::VERBOSITY_QUIET === $this->getVerbosity();
    }

    /**
     * {@inheritdoc}
     */
    public function isVerbose(): bool
    {
        return self::VERBOSITY_VERBOSE <= $this->getVerbosity();
    }

    /**
     * {@inheritdoc}
     */
    public function isVeryVerbose(): bool
    {
        return self::VERBOSITY_VERY_VERBOSE <= $this->getVerbosity();
    }

    /**
     * {@inheritdoc}
     */
    public function isDebug(): bool
    {
        return self::VERBOSITY_DEBUG <= $this->getVerbosity();
    }
}
