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
            font-family: 'Roboto', sans-serif;
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
        .calendar-container {
            margin: 20px auto;
            width: 90%;
            max-width: 1200px;
        }
        .calendar-title {
            text-align: center;
            font-family: 'Playfair Display', serif;
            font-size: 2em;
            margin-bottom: 20px;
            color: #002147;
        }
        .calendar {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 10px;
        }
        .calendar-header {
            font-weight: 700;
            text-align: center;
            padding: 10px;
            background-color: #d4af37;
            color: #fff;
            border-radius: 5px;
        }
        .calendar-day {
            background-color: #fff;
            border: 2px solid #d4af37;
            border-radius: 5px;
            padding: 10px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .calendar-day strong {
            display: block;
            font-size: 1.2em;
            margin-bottom: 5px;
            color: #002147;
        }
        .event {
            background-color: #f8c471;
            color: #002147;
            margin: 5px 0;
            padding: 5px;
            border-radius: 5px;
            font-size: 0.9em;
            text-align: left;
        }
        .event:hover {
            background-color: #d4af37;
            color: #fff;
        }
        @media (max-width: 768px) {
            .calendar {
                grid-template-columns: repeat(2, 1fr);
            }
            .calendar-header {
                font-size: 0.9em;
            }
        }
    </style>
</head>
<body>
<div class="hero" style="position: relative;">
    <?php if ($currentEvent): ?>
        <h1>Dagens kungliga uppdrag</h1>
        <p><strong><?= htmlspecialchars($currentEvent['title']) ?></strong></p>
        <?php if (!empty($currentEvent['participant'])): ?>
            <p>Deltagare: <?= htmlspecialchars($currentEvent['participant']) ?></p>
        <?php endif; ?>
        <?php if (!empty($currentEvent['location'])): ?>
            <p>Plats: <?= htmlspecialchars($currentEvent['location']) ?></p>
        <?php endif; ?>
        <?php if (!empty($currentEvent['date'])): ?>
            <p>Datum: <?= htmlspecialchars($currentEvent['date']) ?></p>
        <?php endif; ?>
    <?php else: ?>
        <h1>No Royal Events Today</h1>
        <p class="no-event">Check back tomorrow for updates!</p>
    <?php endif; ?>
    <p style="position: absolute;bottom: 35px;font-size: 0.8em;font-weight: 400;color: #f8c471;">Skrolla ned för att se vad som kommer härnäst</p>
</div>

<div class="calendar-container">
    <h2 class="calendar-title">Kommande kungliga uppdrag</h2>
    <div class="calendar">
        <div class="calendar-header">Måndag</div>
        <div class="calendar-header">Tisday</div>
        <div class="calendar-header">Onsdag</div>
        <div class="calendar-header">Torsdag</div>
        <div class="calendar-header">Fredag</div>
        <div class="calendar-header">Lördag</div>
        <div class="calendar-header">Söndag</div>
        <?php
        $startOfMonth = strtotime(date('Y-m-01'));
        $endOfMonth = strtotime(date('Y-m-t'));
        $currentDay = $startOfMonth;

        // Adjust day of the week to start on Monday
        $dayOfWeek = (date('N', $startOfMonth) % 7); // 'N' gives 1 (Monday) to 7 (Sunday)

        for ($i = 0; $i < $dayOfWeek; $i++) {
            echo '<div class="calendar-day"></div>';
        }

        while ($currentDay <= $endOfMonth) {
            $day = date('Y-m-d', $currentDay);
            echo '<div class="calendar-day">';
            echo '<strong>' . date('j', $currentDay) . '</strong>';
            foreach ($upcomingEvents as $event) {
                if ($event['date'] === $day) {
                    echo '<div class="event">' . htmlspecialchars($event['title']) . '</div>';
                }
            }
            echo '</div>';

            $currentDay = strtotime('+1 day', $currentDay);
        }

        $remainingDays = (7 - date('N', $endOfMonth)) % 7;
        for ($i = 0; $i < $remainingDays; $i++) {
            echo '<div class="calendar-day"></div>';
        }
        ?>
    </div>
</div>
</body>
</html>