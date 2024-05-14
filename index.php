<?php

// to maintanace on rename public/.maintenance.php to public/maintenance.php
if (stream_resolve_include_path($maintenance = __DIR__ . '/public/maintenance.php')) {
	require $maintenance;
}

$container = require __DIR__ . '/app/bootstrap.php';
$container->getByType(\Nette\Application\Application::class)->run();
