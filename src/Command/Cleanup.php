<?php

declare(strict_types=1);

namespace Hypernode\Deploy\Command;

use Hypernode\Deploy\Ephemeral\EphemeralHypernodeManager;
use Hypernode\Deploy\Report\ReportLoader;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

class Cleanup extends Command
{
    private ReportLoader $reportLoader;
    private EphemeralHypernodeManager $ephemeralHypernodeManager;

    public function __construct(ReportLoader $reportLoader, EphemeralHypernodeManager $ephemeralHypernodeManager)
    {
        parent::__construct();

        $this->reportLoader = $reportLoader;
        $this->ephemeralHypernodeManager = $ephemeralHypernodeManager;
    }

    protected function configure()
    {
        parent::configure();
        $this->setName('cleanup');
        $this->setDescription(
            'Clean up any acquired resources during the deployment, like ephemeral Hypernodes.'
        );
    }

    /**
     * @throws Throwable
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $report = $this->reportLoader->loadReport();

        if ($report === null) {
            $output->writeln('No report found, skipping cleanup.');
            return 0;
        }

        $this->ephemeralHypernodeManager->cancel(...$report->getEphemeralHypernodes());

        return 0;
    }
}
