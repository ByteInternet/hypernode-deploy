<?php

namespace Hypernode\Deploy;

use Exception;
use Symfony\Component\Process\Exception\RuntimeException;
use Symfony\Component\Process\Process;

\define('WORKING_DIR', getcwd());
\define('APPLICATION_ROOT', \dirname(__DIR__));

// Allows to specify a custom autoload path to avoid overriding Hipex deploy packages
// when the same packages are used by your application and Hipex Deploy.
if (getenv('DEPLOY_AUTOLOAD_PATH') !== false) {
    $customAutoLoadPath = getenv('DEPLOY_AUTOLOAD_PATH');

    if (file_exists($customAutoLoadPath)) {
        require_once $customAutoLoadPath;
    }
}


/** @var \Composer\Autoload\ClassLoader $loader */
$loader = require_once APPLICATION_ROOT . '/vendor/autoload.php';
if (!array_key_exists('Deployer\\', $loader->getPrefixesPsr4())) {
    $loader->setPsr4('Deployer\\', APPLICATION_ROOT . '/vendor/deployer/deployer/src');
    require_once APPLICATION_ROOT . '/vendor/deployer/deployer/src/functions.php';
    require_once APPLICATION_ROOT . '/vendor/deployer/deployer/src/Support/helpers.php';
}

$calledBinary = basename($argv[0]);
if ($calledBinary === 'hipex-deploy') {
    fwrite(STDERR, "\n\e[33mDEPRECATED: Command 'hipex-deploy' was called, please call 'hypernode-deploy'. This fallback will be removed in future versions!\e[39m\n\n");
}

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

$app = new Bootstrap();
try {
    exit($app->run());
} catch (Exception $e) {
    echo 'Build failed ' . $e->getMessage();
    echo $e;
    exit($e->getCode() ?? 1);
}
