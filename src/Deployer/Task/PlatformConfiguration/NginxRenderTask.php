<?php

namespace Hypernode\Deploy\Deployer\Task\PlatformConfiguration;

use Hypernode\Deploy\Deployer\Task\IncrementedTaskTrait;
use Deployer\Deployer;
use Deployer\Task\Task;
use Deployer\Task\Context;
use Hypernode\Deploy\Deployer\Task\ConfigurableTaskInterface;
use Hypernode\Deploy\Deployer\Task\TaskBase;
use Hypernode\DeployConfiguration\PlatformConfiguration\NginxConfiguration;
use Hypernode\DeployConfiguration\TaskConfigurationInterface;
use Twig\Environment;

use function Deployer\fail;
use function Deployer\get;
use function Deployer\run;
use function Deployer\set;
use function Deployer\task;

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
            foreach (glob(get('nginx/config_path') . '/**') as $nginx_file) {
                if (!is_file($nginx_file)) {
                    continue;
                }

                $renderedContent = $this->render($nginx_file, $variables);
                # Overwriting the template file shouldn't be a big deal right?
                file_put_contents($nginx_file, $renderedContent);
            }
        });
        fail('deploy:nginx:prepare', 'deploy:nginx:cleanup');

        return null;
    }
}
