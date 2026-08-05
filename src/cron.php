<?php

require_once __DIR__ . '/Utils/PartyCrasher.php';

// When invoked over HTTP (as opposed to the CLI cron job), require a shared
// secret to prevent anyone who reaches this endpoint from triggering scrapes/DB writes.
if (php_sapi_name() !== 'cli') {
    $configPath = dirname(__DIR__) . '/config/config.json';
    $config = file_exists($configPath) ? json_decode(file_get_contents($configPath), true) : [];
    $expectedSecret = $config['cron_secret'] ?? '';

    $providedSecret = $_GET['secret'] ?? ($_SERVER['HTTP_X_CRON_SECRET'] ?? '');

    if ($expectedSecret === '' || !hash_equals($expectedSecret, (string)$providedSecret)) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'Forbidden']);
        exit;
    }
}

// Instantiate and run the PartyCrasher class
$partyCrasher = new PartyCrasher();
$partyCrasher->run();