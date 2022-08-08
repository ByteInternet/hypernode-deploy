<?php

namespace Hypernode\Deploy;

use Composer\InstalledVersions;
use DI\Container;
use DI\ContainerBuilder;
use Exception;
use Hypernode\Deploy\Stdlib\ClassFinder;
use InvalidArgumentException;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class Bootstrap
{
    private const APP_LOGO = <<<NAME
        __  __                                      __        ____             __
       / / / /_  ______  ___  _________  ____  ____/ /__     / __ \___  ____  / /___  __  __
      / /_/ / / / / __ \/ _ \/ ___/ __ \/ __ \/ __  / _ \   / / / / _ \/ __ \/ / __ \/ / / /
     / __  / /_/ / /_/ /  __/ /  / / / / /_/ / /_/ /  __/  / /_/ /  __/ /_/ / / /_/ / /_/ /
    /_/ /_/\__, / .___/\___/_/  /_/ /_/\____/\__,_/\___/  /_____/\___/ .___/_/\____/\__, /
          /____/_/                                                  /_/            /____/

    Deployer version: %s

    NAME;

    /**
     * Run application
     *
     * @throws Exception
     */
    public function run(): int
    {
        $container = $this->initContainer();
        $application = $this->initApplication($container);

        return $application->run(
            $container->get(InputInterface::class),
            $container->get(OutputInterface::class)
        );
    }

    /**
     * @throws InvalidArgumentException
     * @throws Exception
     */
    private function initContainer(): Container
    {
        $builder = new ContainerBuilder();
        $builder->useAutowiring(true);
        $builder->useAnnotations(true);
        $builder->addDefinitions(Di\ConsoleDefinition::getDefinition());
        $builder->addDefinitions([
            'version' => $this->getVersion(),
        ]);

        $container = $builder->build();

        $this->registerTwigLoader($container);

        return $container;
    }

    private function initApplication(Container $container): Application
    {
        $application = new Application();
        $application->getDefinition()->addOption(
            new InputOption(
                '--file',
                '-f',
                InputOption::VALUE_OPTIONAL,
                'Specify configuration file',
                'deploy.php'
            )
        );
        $application->setName(
            sprintf(
                self::APP_LOGO,
                InstalledVersions::getVersion('deployer/deployer')
            )
        );
        $application->setVersion('Version: ' . $this->getVersion());
        $this->addCommandsToApplication($container, $application);

        return $application;
    }

    private function registerTwigLoader(Container $container): void
    {
        $loader = new FilesystemLoader(__DIR__ . '/Resource/template');
        $twig = new Environment($loader);
        $container->set(Environment::class, $twig);
    }

    /**
     * @throws InvalidArgumentException
     */
    private function addCommandsToApplication(ContainerInterface $container, Application $application): void
    {
        $finder = new ClassFinder(__NAMESPACE__ . '\\Command');
        $finder->in(__DIR__ . DIRECTORY_SEPARATOR . 'Command');
        $finder->subclassOff(Command::class);

        foreach ($finder as $class) {
            /** @var Command $command */
            $command = $container->get($class);
            $application->add($command);
        }
    }

    private function getVersion(): string
    {
        return '@git_version@ @build_datetime@';
    }
}
