<?php

require_once __DIR__ . '/Utils/PartyCrasher.php';

$outputPath = dirname(__DIR__) . '/data.json';

// Max number of events to keep in data.json
$maxUpcoming = 100; // nearest upcoming events (incl. today)
$maxPast = 50;      // most recent past events

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

// Keep only events that have a date
$merged = array_filter($existing, function($event) {
    return !empty($event['date']);
});
$merged = array_values($merged);

// Keep the nearest upcoming events and the most recent past events
$today = date('Y-m-d');
$upcoming = [];
$past = [];
foreach ($merged as $event) {
    if ($event['date'] >= $today) {
        $upcoming[] = $event;
    } else {
        $past[] = $event;
    }
}
usort($upcoming, function($a, $b) { return strcmp($a['date'], $b['date']); });
usort($past, function($a, $b) { return strcmp($b['date'], $a['date']); });
$merged = array_merge(array_slice($upcoming, 0, $maxUpcoming), array_slice($past, 0, $maxPast));

// Sort by date ascending
usort($merged, function($a, $b) { return strcmp($a['date'], $b['date']); });

file_put_contents($outputPath, json_encode(array_values($merged), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

echo "Wrote " . count($merged) . " events to data.json\n";
