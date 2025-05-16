<?php

class DatabaseHandler
{
    private PDO $pdo;

    public function __construct(string $configPath)
    {
        if (!file_exists($configPath)) {
            die("Configuration file not found: $configPath");
        }

        $config = json_decode(file_get_contents($configPath), true);
        $dbConfig = $config['database'];

        $host = $dbConfig['host'];
        $dbname = $dbConfig['dbname'];
        $username = $dbConfig['username'];
        $password = $dbConfig['password'];

        try {
            $this->pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die("Database connection error: " . $e->getMessage());
        }
    }

    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    public function checkEventExists(string $id): bool
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM royal_events WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetchColumn() > 0;
    }

    public function insertEvent(array $event): void
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO royal_events (id, title, participant, location, date)
            VALUES (:id, :title, :participant, :location, :date)
        ");
        $stmt->execute([
            ':id' => $event['id'],
            ':title' => $event['title'],
            ':participant' => $event['participant'],
            ':location' => $event['location'],
            ':date' => $event['date'] ?: null,
        ]);
    }

    public function updateEvent(array $event): void
    {
        $stmt = $this->pdo->prepare("
            UPDATE royal_events
            SET title = :title, participant = :participant, location = :location, date = :date
            WHERE id = :id
        ");
        $stmt->execute([
            ':id' => $event['id'],
            ':title' => $event['title'],
            ':participant' => $event['participant'],
            ':location' => $event['location'],
            ':date' => $event['date'] ?: null,
        ]);
    }
}