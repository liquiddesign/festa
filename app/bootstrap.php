<?php

$loader = @include __DIR__ . '/../vendor/autoload.php';

if (!$loader) {
    \trigger_error('Vendor folder not found, please run "composer install"', \E_USER_ERROR);
}

$configurator = new Nette\Configurator();
$configurator->setDebugMode(false);
$configurator->setDebugMode((new \Nette\DI\Config\Loader())->load(__DIR__ . '/config/config.neon')['parameters']['lqd_ip']); // load admin IP
$configurator->enableTracy(__DIR__ . '/../temp/log');
$configurator->setTempDirectory(__DIR__ . '/../temp');
$configurator->addConfig(__DIR__ . '/config/config.neon');

if (\is_file(__DIR__ . '/config/config.custom.neon')) {
    $configurator->addConfig(__DIR__ . '/config/config.custom.neon');
}

if ($configurator->isDebugMode()) {
    $configurator->addConfig(__DIR__ . '/config/config.debug.neon');
}

if (\is_file(__DIR__ . '/config/config.production.neon')) {
    $configurator->addConfig(__DIR__ . '/config/config.production.neon');
} else {
    if (\is_file(__DIR__ . '/config/config.local.neon')) {
        $configurator->addConfig(__DIR__ . '/config/config.local.neon');
    }
}

$container = $configurator->createContainer();

$container->parameters['baseDir'] = \dirname(__FILE__, 2);
$baseDirOffset = \strlen($container->parameters['baseDir']);

$container->parameters['wwwUrl'] = \substr($container->getByType(\Nette\Http\Request::class)->getUrl()->scriptPath, 0, -1);
$container->parameters['userUrl'] = $container->parameters['wwwUrl'] . \substr($container->parameters['userDir'], $baseDirOffset);
$container->parameters['pubUrl'] = $container->parameters['wwwUrl'] . \substr($container->parameters['pubDir'], $baseDirOffset);

if ($container->parameters['productionMode']) {
    $cache = new \Nette\Caching\Cache($container->getService('cache.storage'), 'lqd');
    $container->parameters['ts'] = $cache->load('timestamp', static function () use ($cache) {
        $time = \time();
        $cache->save('timestamp', $time);

        return $time;
    });
} else {
    $container->parameters['ts'] = \time();
}

$container->parameters['composer'] = $loader;

return $container;
