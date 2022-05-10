<?php
/**
 * @author Hipex <info@hipex.io>
 * @copyright (c) Hipex B.V. 2018
 */

namespace HipexDeploy;

use Deployer\Console\Application as ConsoleApplication;
use DI\Container;
use DI\ContainerBuilder;
use Exception;
use HipexDeploy\Stdlib\ClassFinder;
use InvalidArgumentException;
use PackageVersions\Versions;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use function Sentry\init as sentryInit;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class Application
{
    /**
     * Run application
     *
     * @throws Exception
     */
    public function run()
    {
        sentryInit([
            'dsn' => 'https://85e2b396851b4fcc9cc5df626b974687@sentry.hipex.cloud/10',
            'release' => $this->getVersion()
        ]);

        $container = $this->createDiContainer();
        $application = new ConsoleApplication();
        $application->setName('Hipex Deploy');
        $application->setName(implode(PHP_EOL, [
            ' _   _ _                  ______           _',
            '| | | (_)                 |  _  \         | |',
            '| |_| |_ _ __   _____  __ | | | |___ _ __ | | ___  _   _',
            '|  _  | | \'_ \ / _ \ \/ / | | | / _ \ \'_ \| |/ _ \| | | |',
            '| | | | | |_) |  __/>  <  | |/ /  __/ |_) | | (_) | |_| |',
            '\_| |_/_| .__/ \___/_/\_\ |___/ \___| .__/|_|\___/ \__, |',
            '        | |                         | |             __/ |',
            '        |_|                         |_|            |___/',
            '',
            sprintf('Deployer version: %s', Versions::getVersion('deployer/deployer')),
            sprintf('Deployer Recipe version: %s', Versions::getVersion('deployer/recipes')),
            ''
        ]));
        $application->setVersion('Version: ' . $this->getVersion());
        $this->addCommands($container, $application);
        $this->registerTwigLoader($container);

        $application->run(
            $container->get(InputInterface::class),
            $container->get(OutputInterface::class)
        );
    }

    /**
     * @return Container
     * @throws InvalidArgumentException
     * @throws Exception
     */
    private function createDiContainer(): Container
    {
        $builder = new ContainerBuilder();
        $builder->useAutowiring(true);
        $builder->useAnnotations(true);
        $builder->addDefinitions(Di\ConsoleDefinition::getDefinition());
        $builder->addDefinitions([
           'version' => $this->getVersion(),
        ]);

        return $builder->build();
    }

    /**
     * @param Container $container
     */
    private function registerTwigLoader(Container $container)
    {
        $loader = new FilesystemLoader(__DIR__ . '/Resource/template');
        $twig = new Environment($loader);
        $container->set(Environment::class, $twig);
    }

    /**
     * @return string
     */
    private function getVersion(): string
    {
        return '@git_version@ @build_datetime@';
    }

    /**
     * @param ContainerInterface $container
     * @param ConsoleApplication $application
     * @throws InvalidArgumentException
     */
    private function addCommands(ContainerInterface $container, ConsoleApplication $application)
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
}
