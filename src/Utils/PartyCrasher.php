<?php

require_once __DIR__ . '/DatabaseHandler.php';

class PartyCrasher
{
    private PDO $pdo;
    private DatabaseHandler $dbHandler;


    public function __construct()
    {
        $configPath = dirname(dirname(__DIR__)) . '/config/config.json';
        $this->dbHandler = new DatabaseHandler($configPath);
    }

    public function storeToDatabase(array $events): void
    {
        if (empty($events)) {
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
            // Log cURL errors and return null
            error_log('cURL error: ' . curl_error($ch));
            curl_close($ch);
            return null;
        }
        curl_close($ch);
        return $html;
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
                        $year = date('Y');
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

    // Main method to run the scraper
    public function run(): void
    {
        $all = [];
        for ($page = 1; $page <= 5; $page++) {
            $url = "https://www.kungahuset.se/mediecenter/officiella-program?page=$page";
            $html = $this->fetchHtml($url);

            if (!$html) {
                break; // Stop if no HTML is fetched
            }

            $items = $this->parseOfficialPrograms($html);
            if (empty($items)) {
                break; // Stop if no items are parsed
            }

            $all = array_merge($all, $items);
        }
        $this->storeToDatabase($all);

        // Output the data as JSON
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($all, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
}
