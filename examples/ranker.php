<?php
/*
 * Ranker test script for hlquery PHP client
 *
 * Generates a configurable number of documents with synthetic "popularity"
 * and "hit log" signals, stores them in hlquery, and then executes a
 * search ordered by a compound `rank_signal` (log + linear mix).
 *
 * Usage examples:
 *   php ranker.php
 *   php ranker.php --collection=ranker_playground --count=1000 --batch=250
 */

require_once __DIR__ . '/../lib/autoload.php';

use Hlquery\Client;

$opts = getopt('', ['url::', 'collection::', 'count::', 'batch::', 'keep', 'skip-signal-fields']);
$baseUrl = $opts['url'] ?? getenv('HLQ_BASE_URL') ?? 'http://127.0.0.1:9200';
$collection = $opts['collection'] ?? 'ranker_test';
$docCount = max(1000, (int)($opts['count'] ?? 1000));
$batchSize = max(50, (int)($opts['batch'] ?? 250));
$cleanup = !isset($opts['keep']);
$storeSignals = !isset($opts['skip-signal-fields']);

function logLine($message) {
    echo '[ranker] ' . $message . "\n";
}

function computeRankSignal(int $popularity, int $hitLog): float
{
    return log($popularity + 1) * 1.15
         + log($hitLog + 1) * 0.95
         + sqrt($popularity) * 0.25
         + sqrt($hitLog) * 0.15;
}

$client = new Client($baseUrl);
logLine("Connecting to $baseUrl");

if ($cleanup) {
    logLine("Removing existing collection '$collection' (if present)");
    try {
        $response = $client->collections()->delete($collection);
        if (!$response->isSuccess() && $response->getStatusCode() !== 404) {
            logLine("Warning: could not delete collection: " . $response->getError());
        }
    } catch (\Throwable $e) {
        logLine("Warning: delete request failed: " . $e->getMessage());
    }
}

$schemaFields = [
    ['name' => 'title', 'type' => 'string'],
    ['name' => 'rank_signal', 'type' => 'float'],
];
if ($storeSignals) {
    $schemaFields[] = ['name' => 'popularity_score', 'type' => 'int'];
    $schemaFields[] = ['name' => 'hit_log', 'type' => 'int'];
}
$schema = [
    'name' => $collection,
    'fields' => $schemaFields,
];

logLine("Creating collection '$collection' with custom ranking fields");
$createResponse = $client->collections()->create($collection, $schema);
if (!$createResponse->isSuccess()) {
    logLine("Failed to create collection: " . $createResponse->getError());
    exit(1);
}

/**
 * Fetches collection field names from the API.
 */
function fetchCollectionFieldNames(Client $client, string $collection): array
{
    $response = $client->collections()->get($collection);
    if (!$response->isSuccess()) {
        return [];
    }
    $body = $response->getBody();
    $fields = $body['fields'] ?? [];
    $names = [];
    foreach ($fields as $field) {
        if (isset($field['name'])) {
            $names[] = $field['name'];
        }
    }
    return $names;
}

$fieldNames = fetchCollectionFieldNames($client, $collection);
$hasPopularityField = in_array('popularity_score', $fieldNames, true);
$hasHitLogField = in_array('hit_log', $fieldNames, true);
if ($storeSignals) {
    logLine("Signal fields enabled (popularity_score: " . ($hasPopularityField ? 'present' : 'missing') . ", hit_log: " . ($hasHitLogField ? 'present' : 'missing') . ")");
} else {
    logLine("Signal fields disabled by flag; only rank_signal will be written.");
}

$docStats = [];
$batchCount = 0;
$batchBuffer = [];

for ($i = 1; $i <= $docCount; $i++) {
    $popularity = random_int(0, 800);
    $hitLog = random_int(0, 400);
    $id = "doc$i";
    $rankSignal = computeRankSignal($popularity, $hitLog);

    $docStats[$id] = [
        'title' => "Document #$i",
        'popularity_score' => $popularity,
        'hit_log' => $hitLog,
        'rank_signal' => $rankSignal,
    ];

    $docPayload = [
        'id' => $id,
        'title' => $docStats[$id]['title'],
        'rank_signal' => $rankSignal,
    ];
    if ($storeSignals && $hasPopularityField) {
        $docPayload['popularity_score'] = $popularity;
    }
    if ($storeSignals && $hasHitLogField) {
        $docPayload['hit_log'] = $hitLog;
    }
    $batchBuffer[] = $docPayload;

    if (count($batchBuffer) >= $batchSize) {
        $response = $client->documents()->import($collection, $batchBuffer);
        if (!$response->isSuccess()) {
            logLine("Document import failed: " . $response->getError());
            exit(1);
        }
        $batchBuffer = [];
        $batchCount++;
        logLine("Imported batch #$batchCount ({$batchSize} docs)");
    }
}

if (!empty($batchBuffer)) {
    $response = $client->documents()->import($collection, $batchBuffer);
    if (!$response->isSuccess()) {
        logLine("Document import failed: " . $response->getError());
        exit(1);
    }
    logLine("Imported final batch (" . count($batchBuffer) . " docs)");
}

// Simulate incremental hits to update rank signal
$updates = min(500, $docCount);
logLine("Simulating $updates incremental hit updates");
$updateCount = 0;

for ($i = 0; $i < $updates; $i++) {
    $targetId = 'doc' . random_int(1, $docCount);
    $docStats[$targetId]['popularity_score'] += random_int(1, 6);
    $docStats[$targetId]['hit_log'] += random_int(0, 4);
    $docStats[$targetId]['rank_signal'] = computeRankSignal(
        $docStats[$targetId]['popularity_score'],
        $docStats[$targetId]['hit_log']
    );

    $updatePayload = [
        'id' => $targetId,
        'title' => $docStats[$targetId]['title'],
        'rank_signal' => $docStats[$targetId]['rank_signal'],
    ];
    if ($storeSignals && $hasPopularityField) {
        $updatePayload['popularity_score'] = $docStats[$targetId]['popularity_score'];
    }
    if ($storeSignals && $hasHitLogField) {
        $updatePayload['hit_log'] = $docStats[$targetId]['hit_log'];
    }

    $updateResponse = $client->documents()->update($collection, $targetId, $updatePayload);

    if (!$updateResponse->isSuccess()) {
        logLine("Update failed for $targetId: " . $updateResponse->getError());
        continue;
    }
    $updateCount++;
}
logLine("Applied $updateCount incremental updates");

// Prepare expectation: sort docs locally by rank signal
uasort($docStats, static function ($a, $b) {
    return $b['rank_signal'] <=> $a['rank_signal'];
});
$expectedTop = array_slice($docStats, 0, 5, true);

logLine("Querying hlquery, ordering by rank_signal:desc");
$searchResponse = $client->search($collection, [
    'q' => 'Document',
    'query_by' => 'title',
    'sort_by' => 'rank_signal:desc',
    'limit' => 10,
]);

if (!$searchResponse->isSuccess()) {
    logLine("Search failed: " . $searchResponse->getError());
    exit(1);
}

$body = $searchResponse->getBody();
$results = $body['documents'] ?? [];

logLine("Expected top documents (local scoring)");
foreach ($expectedTop as $id => $stat) {
    printf("  %s rank=%.4f pop=%d hits=%d\n", $id, $stat['rank_signal'], $stat['popularity_score'], $stat['hit_log']);
}

logLine("Search results returned by hlquery");
foreach ($results as $doc) {
    printf("  %s rank=%.4f pop=%d hits=%d\n",
        $doc['id'],
        $doc['rank_signal'] ?? 0.0,
        $doc['popularity_score'] ?? 0,
        $doc['hit_log'] ?? 0
    );
}

logLine("Ranker smoke test completed");
