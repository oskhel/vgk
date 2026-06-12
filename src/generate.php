<?php

require_once __DIR__ . '/Utils/PartyCrasher.php';

$outputPath = dirname(__DIR__) . '/data.json';

// Load existing data keyed by id
$existing = [];
if (file_exists($outputPath)) {
    $stored = json_decode(file_get_contents($outputPath), true) ?? [];
    foreach ($stored as $event) {
        $existing[$event['id']] = $event;
    }
}

// Merge freshly scraped events (overwrite existing by id to pick up edits)
$partyCrasher = new PartyCrasher(false);
$scraped = $partyCrasher->fetchAll();
foreach ($scraped as $event) {
    $existing[$event['id']] = $event;
}

// Drop events older than 1 year
$cutoff = date('Y-m-d', strtotime('-1 year'));
$merged = array_filter($existing, function($event) use ($cutoff) {
    return !empty($event['date']) && $event['date'] >= $cutoff;
});

// Sort by date ascending
usort($merged, function($a, $b) { return strcmp($a['date'], $b['date']); });

file_put_contents($outputPath, json_encode(array_values($merged), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

echo "Wrote " . count($merged) . " events to data.json\n";
