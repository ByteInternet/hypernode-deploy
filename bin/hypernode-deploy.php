<?php

namespace Hypernode\Deploy;

use Exception;
use Symfony\Component\Process\Exception\RuntimeException;
use Symfony\Component\Process\Process;

\define('WORKING_DIR', getcwd());
\define('APPLICATION_ROOT', \dirname(__DIR__));

$customAutoLoadPath = WORKING_DIR . '/vendor/autoload.php';
// Allows to specify a custom autoload path to avoid overriding Hipex deploy packages
// when the same packages are used by your application and Hipex Deploy.
if (getenv('DEPLOY_AUTOLOAD_PATH') !== false) {
    $customAutoLoadPath = getenv('DEPLOY_AUTOLOAD_PATH');
}

if (file_exists($customAutoLoadPath)) {
    require_once $customAutoLoadPath;
}

require_once APPLICATION_ROOT . '/vendor/autoload.php';

if (!getenv('SSH_AUTH_SOCK')) {

    $process = Process::fromShellCommandline(sprintf(
        'eval "$(ssh-agent -s) " && %s %s',
        PHP_BINARY,
        implode(' ', array_map('escapeshellarg', $argv))
    ));

    try {
        $process->setTty(true);
    } catch (RuntimeException $e) {
        // This is expected in some situation and does not impose problems
    }

    $process->setTimeout(0);

    $process->run(function ($type, $buffer) {
        if ($type === Process::ERR) {
            fwrite(STDERR, $buffer);
        } else {
            fwrite(STDOUT, $buffer);
        }
    });

    exit($process->getExitCode());
}

$app = new Application();
try {
    exit($app->run());
} catch (Exception $e) {
    echo 'Build failed ' . $e->getMessage();
    echo $e;
    exit($e->getCode() ?? 1);
}
