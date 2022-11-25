<?php

declare(strict_types=1);

namespace Hypernode\Deploy\Command;

use Hypernode\Deploy\Brancher\BrancherHypernodeManager;
use Hypernode\Deploy\ConfigurationLoader;
use Hypernode\Deploy\Report\ReportLoader;
use Hypernode\DeployConfiguration\BrancherServer;
use Hypernode\DeployConfiguration\Server;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

class Cleanup extends Command
{
    private ReportLoader $reportLoader;
    private ConfigurationLoader $configurationLoader;
    private BrancherHypernodeManager $brancherHypernodeManager;

    public function __construct(
        ReportLoader $reportLoader,
        ConfigurationLoader $configurationLoader,
        BrancherHypernodeManager $brancherHypernodeManager
    ) {
        parent::__construct();

        $this->reportLoader = $reportLoader;
        $this->configurationLoader = $configurationLoader;
        $this->brancherHypernodeManager = $brancherHypernodeManager;
    }

    protected function configure()
    {
        parent::configure();
        $this->setName('cleanup');
        $this->setDescription(
            'Clean up any acquired resources during the deployment, like brancher Hypernodes.'
        );
        $this->addArgument('stage', InputArgument::OPTIONAL, 'Stage to cleanup');
    }

    /**
     * @throws Throwable
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $report = $this->reportLoader->loadReport();

        if ($report !== null) {
            $this->brancherHypernodeManager->cancel(...$report->getBrancherHypernodes());
        }

        /** @var string $stageName */
        $stageName = $input->getArgument('stage');
        if ($stageName) {
            $config = $this->configurationLoader->load(
                $input->getOption('file') ?: 'deploy.php'
            );
            foreach ($config->getStages() as $stage) {
                if ($stage->getName() !== $stageName) {
                    continue;
                }
                foreach ($stage->getServers() as $server) {
                    if (!($server instanceof BrancherServer)) {
                        continue;
                    }
                    $labels = $server->getLabels();
                    $hypernode = $server->getOptions()[Server::OPTION_HN_PARENT_APP];
                    $brancherHypernodes = $this->brancherHypernodeManager->queryBrancherHypernodes(
                        $hypernode,
                        $labels
                    );
                    $this->brancherHypernodeManager->cancel(...$brancherHypernodes);
                }
            }
        }

        return 0;
    }
}
