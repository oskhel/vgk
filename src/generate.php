<?php

require_once __DIR__ . '/Utils/PartyCrasher.php';

$partyCrasher = new PartyCrasher(false); // No DB needed for static generation
$events = $partyCrasher->fetchAll();

$outputPath = dirname(__DIR__) . '/data.json';
file_put_contents($outputPath, json_encode($events, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

echo "Wrote " . count($events) . " events to data.json\n";
