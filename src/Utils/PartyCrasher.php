<?php

require_once __DIR__ . '/DatabaseHandler.php';

class PartyCrasher
{
    private ?DatabaseHandler $dbHandler;

    public function __construct(bool $withDatabase = true)
    {
        if ($withDatabase) {
            $configPath = dirname(dirname(__DIR__)) . '/config/config.json';
            $this->dbHandler = new DatabaseHandler($configPath);
        } else {
            $this->dbHandler = null;
        }
    }

    public function storeToDatabase(array $events): void
    {
        if (empty($events) || $this->dbHandler === null) {
            return;
        }

        foreach ($events as $event) {
            if (empty($event['id'])) {
                continue;
            }

            if ($this->dbHandler->checkEventExists($event['id'])) {
                $this->dbHandler->updateEvent($event);
            } else {
                $this->dbHandler->insertEvent($event);
            }
        }
    }

    // Fetch HTML content from a given URL
    public function fetchHtml(string $url): ?string
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; PHP scraper)',
        ]);
        $html = curl_exec($ch);
        if (curl_errno($ch)) {
            error_log('cURL error: ' . curl_error($ch));
            curl_close($ch);
            return null;
        }
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            error_log("fetchHtml: unexpected HTTP status $httpCode for URL: $url");
            return null;
        }

        if (empty($html)) {
            error_log("fetchHtml: empty response body for URL: $url");
            return null;
        }

        return $html;
    }

    // Validate that the fetched HTML contains the expected event structure
    public function validateHtml(string $html): bool
    {
        // Must contain at least one lp-article element — the structural marker for events
        if (strpos($html, 'lp-article') === false) {
            error_log('validateHtml: no lp-article elements found — page structure may have changed');
            return false;
        }

        // Must contain the official programs CSS class used for event data fields
        if (strpos($html, 'kh-official-programs') === false) {
            error_log('validateHtml: kh-official-programs class not found — page structure may have changed');
            return false;
        }

        return true;
    }

    // Validate a single parsed event has the minimum required fields
    public function validateEvent(array $event): bool
    {
        if (empty($event['id'])) {
            return false;
        }

        if (empty($event['title']) && empty($event['participant'])) {
            return false;
        }

        if (!empty($event['date']) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $event['date'])) {
            error_log("validateEvent: malformed date '{$event['date']}' for event id '{$event['id']}'");
            return false;
        }

        return true;
    }

    // Parse official programs from the HTML content
    public function parseOfficialPrograms(string $html): array
    {
        $dom = new DOMDocument();
        libxml_use_internal_errors(true); // Suppress HTML parsing warnings
        $dom->loadHTML($html);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);
        // Query for specific nodes containing program data
        $nodes = $xpath->query(
            "//div[contains(concat(' ', normalize-space(@class), ' '),' lp-article ') and @id]"
        );

        $output = [];
        $seenHashes = []; // To avoid duplicate entries
        $monthMap = [
            'januari' => '01', 'februari' => '02', 'mars' => '03', 'april' => '04',
            'maj' => '05', 'juni' => '06', 'juli' => '07', 'augusti' => '08',
            'september' => '09', 'oktober' => '10', 'november' => '11', 'december' => '12',
        ];

        foreach ($nodes as $node) {
            $id = $node->getAttribute('id');
            // Extract title
            $h2 = $xpath->query(".//h2[contains(@class,'subheading')]", $node)->item(0);
            $title = $h2 ? trim($h2->textContent) : '';

            // Extract participant
            $participantNode = $xpath->query(".//div[contains(@class,'kh-official-programs__item--participant')]//span", $node)->item(0);
            $participant = $participantNode ? trim($participantNode->textContent) : '';

            // Extract location
            $locationNode = $xpath->query(".//div[contains(@class,'kh-official-programs__item--location')]//p", $node)->item(0);
            $location = $locationNode ? trim(preg_replace('/\s+/', ' ', $locationNode->textContent)) : '';

            // Extract and format date
            $dateNode = $xpath->query(
                ".//div[contains(concat(' ', normalize-space(@class), ' '),' kh-official-programs__item--date ')]",
                $node
            )->item(0);

            $isoDate = '';
            if ($dateNode) {
                $texts = [];
                foreach ($dateNode->getElementsByTagName('span') as $span) {
                    $texts[] = trim($span->textContent);
                }

                if (count($texts) === 3) {
                    $day = $texts[1];
                    $month = strtolower($texts[2]);

                    if (isset($monthMap[$month])) {
                        $monthNum = $monthMap[$month];
                        $year = ($monthNum < (int)date('m')) ? date('Y') + 1 : date('Y');
                        $dateStr = "$year-$monthNum-" . str_pad($day, 2, '0', STR_PAD_LEFT);
                        $dt = DateTime::createFromFormat('Y-m-d', $dateStr);

                        if ($dt) {
                            $isoDate = $dt->format('Y-m-d');
                        }
                    }
                }
            }

            // Skip empty entries
            if ($title === '' && $participant === '' && $location === '') {
                continue;
            }

            // Skip entries that fail validation
            if (!$this->validateEvent(['id' => $id, 'title' => $title, 'participant' => $participant, 'location' => $location, 'date' => $isoDate])) {
                continue;
            }

            // Avoid duplicate entries using a hash
            $hash = md5($title . '|' . $participant . '|' . $location . '|' . $isoDate);
            if (isset($seenHashes[$hash])) {
                continue;
            }
            $seenHashes[$hash] = true;

            // Add parsed data to the output array
            $output[] = [
                'id' => $id,
                'title' => $title,
                'participant' => $participant,
                'location' => $location,
                'date' => $isoDate,
            ];
        }

        return $output;
    }

    // Fetch and parse all events across pages without persisting
    public function fetchAll(): array
    {
        $all = [];
        for ($page = 1; $page <= 5; $page++) {
            $url = "https://www.kungahuset.se/mediecenter/officiella-program?page=$page";
            $html = $this->fetchHtml($url);

            if (!$html || !$this->validateHtml($html)) {
                break;
            }

            $items = $this->parseOfficialPrograms($html);
            if (empty($items)) {
                break;
            }

            $all = array_merge($all, $items);
        }
        return $all;
    }

    // Main method to run the scraper (DB mode)
    public function run(): void
    {
        $all = $this->fetchAll();
        $this->storeToDatabase($all);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($all, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
}
