<?php

namespace Hypernode\Deploy\Deployer\Task\Deploy;

use Hypernode\Api\HypernodeClientFactory;
use Hypernode\Deploy\Deployer\Task\TaskBase;
use Hypernode\DeployConfiguration\Configuration;
use Throwable;

use function Hypernode\Deploy\Deployer\after;
use function Deployer\run;
use function Deployer\get;
use function Deployer\currentHost;
use function Deployer\task;

class HypernodeAnnotation extends TaskBase
{
    public function configure(Configuration $config): void
    {
        task('deploy:hypernode-annotation', static function () {
            $defaultConfig = [
                'name' => get('release_name'),
                'description' => 'Hypernode Deploy',
                'app' => null,
                'api_token' => null,
                'throw_on_error' => false
            ];

            $userConfig = array_filter((array) get('hypernode_annotations'), fn($value) => $value !== null);
            $config = array_merge($defaultConfig, $userConfig);
            array_walk(
                $config,
                static function (&$value) use ($config) {
                    if (is_callable($value)) {
                        $value = $value($config);
                    }
                },
            );

            if (getenv('HYPERNODE_API_TOKEN')) {
                $config['api_token'] = getenv('HYPERNODE_API_TOKEN') ?: null;
            }

            if (empty($config['api_token'])) {
                $config['api_token'] = run('cat /etc/hypernode/hypernode_api_token');
            }

            if (empty($config['app'])) {
                $hostname = currentHost()->getHostname();
                if (preg_match('/([^.]+)\.hypernode\.io$/', $hostname, $matches)) {
                    $config['app'] = $matches[1];
                } else {
                    $config['app'] = null;
                }
            }

            if (empty($config['api_token'])) {
                if ($config['throw_on_error']) {
                    throw new \RuntimeException("Could not detect app or api token for Hypernode annotations request");
                }
            }

            $hypernodeClient = HypernodeClientFactory::create($config['api_token']);

            try {
                $hypernodeClient->insightsAnnotation->create([
                    'name' => $config['name'],
                    'x_axis' => time(),
                    'app' => $config['app'],
                    'metadata' => $config['description']
                ]);
            } catch (Throwable $e) {
                if ($config['throw_on_error']) {
                    throw $e;
                }
            }
        });

        after('deploy', 'deploy:hypernode-annotation');
    }
}
