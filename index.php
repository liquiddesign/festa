<?php

// to maintanace on rename public/.maintenance.php to public/maintenance.php
if (stream_resolve_include_path($maintenance = __DIR__ . '/public/maintenance.php')) {
	require $maintenance;
}

$container = require __DIR__ . '/app/bootstrap.php';

// QR Code redirect
$httpRequest = $container->getByType(\Nette\Http\IRequest::class);
$relativeUrl = \trim(\substr($httpRequest->getUrl()->getPath(), \strlen($httpRequest->getUrl()->getBasePath())), '/');
if ($relativeUrl !== '' && \strpos($relativeUrl, 'admin') !== 0) {
	$qrCode = $container->getService('repository.custom.qrcode')
		->many()->where('sourceUrl = :rel OR sourceUrl LIKE :full', ['rel' => $relativeUrl, 'full' => '%/' . $relativeUrl])->first();
	if ($qrCode) {
		$container->getByType(\Nette\Http\IResponse::class)->redirect($qrCode->targetUrl, 301);
		exit;
	}
}

$container->getByType(\Nette\Application\Application::class)->run();
