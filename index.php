<?php
require_once 'src/Utils/DatabaseHandler.php';

$dbHandler = new DatabaseHandler('config/config.json');
$pdo = $dbHandler->getPdo();

$stmt = $pdo->query("SELECT * FROM royal_events ORDER BY date ASC");
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

$currentDate = date('Y-m-d');
$currentDate = '2025-05-18';
$currentEvent = null;
$upcomingEvents = [];

$monthMap = [
    '01' => 'januari', '02' => 'februari', '03' => 'mars', '04' => 'april',
    '05' => 'maj', '06' => 'juni', '07' => 'juli', '08' => 'augusti',
    '09' => 'september', '10' => 'oktober', '11' => 'november', '12' => 'december',
];

// Format date month according to swedish months in $monthMap. Remove year and keep only month and day
foreach ($events as &$event) {
    if (isset($event['date'])) {
        $dateParts = explode('-', $event['date']);

        if (count($dateParts) === 3) {
            $month = $monthMap[$dateParts[1]];
            $day = str_pad($dateParts[2], 2, '0', STR_PAD_LEFT);
            $event['formatted_date'] = "$day $month";
        }
    }
}

foreach ($events as $event) {
    if ($event['date'] === $currentDate) {
        $currentEvent = $event;
    } elseif ($event['date'] > $currentDate) {
        $upcomingEvents[] = $event;
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Royal Events</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Roboto:wght@400;500&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: "source sans pro", sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f8f5f0;
            color: #333;
        }
        .hero {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background: linear-gradient(135deg, #002147, #004080);
            color: #f8c471;
            text-align: center;
            padding: 20px;
            border-bottom: 5px solid #d4af37;
        }
        .hero h1 {
            font-family: 'Playfair Display', serif;
            font-size: 4em;
            margin: 0;
            font-weight: 700;
            padding-bottom: 0.3em;
        }
        .hero p {
            font-size: 1.5em;
            margin: 10px 0;
            font-weight: 400;
        }
        .hero .no-event {
            font-size: 2em;
            font-weight: 500;
            margin-top: 20px;
        }
        .hero .title {
            font-family: 'Playfair Display', serif;
        }
        .hero .participant {
            font-size: 1.2em;
            font-weight: 200;
            margin: 1em;
        }
        .hero .location {
            font-size: 1.2em;
            font-weight: 400;
            margin: 0;
        }
        .hero .date {
            font-size: 1.2em;
            font-weight: 400;
            margin: 0;
        }

        .calendar-container {
            margin: 20px auto;
            width: 90%;
            max-width: 800px;
            font-family: 'Roboto', sans-serif;
        }

        .calendar-title {
            text-align: center;
            font-family: 'Playfair Display', serif;
            font-size: 2em;
            margin-bottom: 20px;
            color: #002147;
        }

        .calendar-list {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .calendar-item {
            padding: 20px;
            border: 2px solid #d4af37;
            border-radius: 8px;
            background-color: #fff;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .calendar-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 10px rgba(0, 0, 0, 0.15);
        }

        .calendar-date {
            font-weight: 700;
            font-size: 1.2em;
            color: #002147;
            margin-bottom: 10px;
        }

        .event {
            background-color: #f8c471;
            color: #002147;
            padding: 10px;
            border-radius: 5px;
            font-size: 1em;
            margin-bottom: 5px;
            transition: background-color 0.2s, color 0.2s;
        }

        .event:hover {
            background-color: #d4af37;
            color: #fff;
        }
    </style>
</head>
<body>
<div class="hero" style="position: relative;">
    <?php if ($currentEvent): ?>
        <h1>Dagens kungliga uppdrag</h1>
        <p class="title"><strong><?= htmlspecialchars($currentEvent['title']) ?></strong></p>
        <?php if (!empty($currentEvent['participant'])): ?>
            <p class="participant"><?= htmlspecialchars($currentEvent['participant']) ?></p>
        <?php endif; ?>
        <?php if (!empty($currentEvent['location'])): ?>
            <p class="location"><?= htmlspecialchars($currentEvent['location']) ?></p>
        <?php endif; ?>
        <?php if (!empty($currentEvent['formatted_date'])): ?>
            <p class="date"><?= htmlspecialchars($currentEvent['formatted_date']) ?></p>
        <?php endif; ?>
    <?php else: ?>
        <h1>No Royal Events Today</h1>
        <p class="no-event">Check back tomorrow for updates!</p>
    <?php endif; ?>
    <p style="position: absolute;bottom: 35px;font-size: 0.8em;font-weight: 400;color: #f8c471;">Skrolla ned för att se vad som kommer härnäst</p>
</div>

<div class="calendar-container">
    <h2 class="calendar-title">Kommande kungliga uppdrag</h2>
    <div class="calendar-list">
        <?php
        foreach ($upcomingEvents as $event) {
            $day = $event['date'];
            $weekday = strftime('%A', strtotime($day)); // Get weekday in Swedish
            $dayNumber = date('j', strtotime($day));

            echo '<div class="calendar-item">';
            echo '<div class="calendar-date">';
            echo '<strong>' . ucfirst($weekday) . ' ' . $dayNumber . '</strong>';
            echo '</div>';

            echo '<div class="event">' . htmlspecialchars($event['title']) . '</div>';
            echo '</div>';
        }
        ?>
    </div>
</div>

</body>
</html>