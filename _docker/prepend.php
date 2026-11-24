<?php
// Dev instance v Coolify (Dockerfile.dev). Načítá se přes auto_prepend_file před index.php.

if (PHP_SAPI === 'cli') {
    return;
}

// TLS končí na Traefiku a dovnitř přijde prosté HTTP. Bez tohohle by Nette generovalo
// http:// odkazy na https:// stránce (stejný trik dělá bootstrap.php pro DDEV).
if (isset($_SERVER['HTTP_X_FORWARDED_PROTO'])
    && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https') {
    $_SERVER['HTTPS'] = 'on';
    $_SERVER['SERVER_PORT'] = 443;
}

// bootstrap.php zapíná debug mód jen pro `lqd_ip` (kancelář). Na VPN instanci
// přichází požadavky z Traefiku a Nette 2.4 přidá 127.0.0.1 do whitelistu jen bez
// X-Forwarded-For — proto hlavičky zahodit a adresu připíchnout na localhost.
// APP_DEBUG=0 vrátí produkční chování (chybové stránky místo Tracy).
if (getenv('APP_DEBUG') !== '0') {
    unset($_SERVER['HTTP_X_FORWARDED_FOR'], $_SERVER['HTTP_X_REAL_IP'], $_SERVER['HTTP_FORWARDED']);
    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
}
