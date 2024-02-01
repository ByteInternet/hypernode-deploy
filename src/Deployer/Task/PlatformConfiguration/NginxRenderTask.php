<?php

namespace Hypernode\Deploy\Deployer\Task\PlatformConfiguration;

use Deployer\Deployer;
use Deployer\Task\Context;
use Deployer\Task\Task;
use Hypernode\Deploy\Deployer\Task\ConfigurableTaskInterface;
use Hypernode\Deploy\Deployer\Task\IncrementedTaskTrait;
use Hypernode\Deploy\Deployer\Task\TaskBase;
use Hypernode\DeployConfiguration\PlatformConfiguration\NginxConfiguration;
use Hypernode\DeployConfiguration\TaskConfigurationInterface;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Twig\Environment;

use function Deployer\fail;
use function Deployer\get;
use function Deployer\task;
use function Deployer\writeln;

class NginxRenderTask extends TaskBase implements ConfigurableTaskInterface
{
    use IncrementedTaskTrait;

    /**
     * @var Environment
     */
    private $twig;

    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }

    protected function getIncrementalNamePrefix(): string
    {
        return 'deploy:configuration:nginx:render:';
    }

    public function supports(TaskConfigurationInterface $config): bool
    {
        return $config instanceof NginxConfiguration;
    }

    private function getAllDeployerVars(): array
    {
        if (!Context::has()) {
            return Deployer::get()->config->ownValues();
        } else {
            return Context::get()->getConfig()->ownValues();
        }
    }

    /**
     * Render variables in the given file
     * @param string $file Template file to render
     * @param array $variables Variables to render
     * @return string Rendered file
     */
    private function render(string $file, array $variables): string
    {
        // Get all present Deployer variables
        $template = $this->twig->load($file);
        return $template->render($variables);
    }

    public function configureWithTaskConfig(TaskConfigurationInterface $config): ?Task
    {
        task('deploy:nginx:render', function () {
            $variables = $this->getAllDeployerVars();
            // Render every file in nginx/config_path using twig
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(get('nginx/config_path')));
            foreach ($iterator as $nginx_file) {
                if (!$nginx_file->isFile()) {
                    continue;
                }

                $renderedContent = $this->render($nginx_file->getPathname(), $variables);
                writeln('Rendered contents for ' . $nginx_file->getPathname() . ': ' . $renderedContent);

                # Overwriting the template file shouldn't be a big deal right?
                file_put_contents($nginx_file->getPathname(), $renderedContent);
            }
        });
        fail('deploy:nginx:prepare', 'deploy:nginx:cleanup');

        return null;
    }
}
