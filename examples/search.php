<?php
/*
 * Search Examples
 * 
 * Demonstrates various search patterns with the hlquery PHP client
 */

require_once __DIR__ . '/../lib/autoload.php';

use Hlquery\Client;

$baseUrl = $argv[1] ?? (getenv('HLQ_BASE_URL') ?: (getenv('HLQUERY_BASE_URL') ?: 'http://localhost:9200'));
$token = $argv[2] ?? (getenv('HLQUERY_TOKEN') ?: null);
$collection = $argv[3] ?? 'php_search_example_' . getmypid();
$client = new Client($baseUrl);

if ($token) {
    $client->setAuthToken($token, 'bearer');
}

$client->collections()->delete($collection);
$client->collections()->create($collection, [
    'fields' => [
        ['name' => 'title', 'type' => 'string'],
        ['name' => 'content', 'type' => 'string'],
        ['name' => 'category', 'type' => 'string'],
        ['name' => 'price', 'type' => 'float'],
        ['name' => 'embedding', 'type' => 'float[]'],
    ],
]);
$client->documents()->import($collection, [
    ['id' => 'prod_laptop_001', 'title' => 'Laptop Computer', 'content' => 'Portable work machine', 'category' => 'electronics', 'price' => 1299.99, 'embedding' => [0.1, 0.2, 0.3, 0.4, 0.5]],
    ['id' => 'prod_keyboard_001', 'title' => 'Wireless Keyboard', 'content' => 'Compact Bluetooth keyboard', 'category' => 'electronics', 'price' => 49.99, 'embedding' => [0.2, 0.1, 0.4, 0.3, 0.5]],
    ['id' => 'prod_notebook_001', 'title' => 'Paper Notebook', 'content' => 'Plain paper writing notebook', 'category' => 'office', 'price' => 9.99, 'embedding' => [0.5, 0.4, 0.3, 0.2, 0.1]],
]);

// Simple search
$results = $client->search($collection, [
    'q' => 'search query',
    'query_by' => 'title,content',
    'limit' => 10
]);
echo "Search results: " . json_encode($results->getBody(), JSON_PRETTY_PRINT) . "\n";

// Search with filters
$filteredResults = $client->search($collection, [
    'q' => 'query',
    'query_by' => 'title',
    'filter_by' => 'category:electronics',
    'sort_by' => 'price:asc',
    'limit' => 20
]);
echo "Filtered results: " . json_encode($filteredResults->getBody(), JSON_PRETTY_PRINT) . "\n";

// Supported query semantics
// Field-specific search
$fieldResults = $client->search($collection, [
    'q' => 'title:laptop',
    'query_by' => 'title,content',
    'limit' => 10
]);
echo "Field results: " . json_encode($fieldResults->getBody(), JSON_PRETTY_PRINT) . "\n";

// Boolean OR query
$orResults = $client->search($collection, [
    'q' => 'title:laptop OR title:notebook',
    'query_by' => 'title,content',
    'limit' => 10
]);
echo "Boolean OR results: " . json_encode($orResults->getBody(), JSON_PRETTY_PRINT) . "\n";

// Boolean NOT query
$notResults = $client->search($collection, [
    'q' => 'title:laptop NOT title:refurbished',
    'query_by' => 'title,content',
    'limit' => 10
]);
echo "Boolean NOT results: " . json_encode($notResults->getBody(), JSON_PRETTY_PRINT) . "\n";

// Phrase search
$phraseResults = $client->search($collection, [
    'q' => '"wireless keyboard"',
    'query_by' => 'title',
    'limit' => 10
]);
echo "Phrase results: " . json_encode($phraseResults->getBody(), JSON_PRETTY_PRINT) . "\n";

// Wildcard search
$wildcardResults = $client->search($collection, [
    'q' => 'laptop*',
    'query_by' => 'title,content',
    'limit' => 10
]);
echo "Wildcard results: " . json_encode($wildcardResults->getBody(), JSON_PRETTY_PRINT) . "\n";

// query_by restriction
$restrictedResults = $client->search($collection, [
    'q' => 'laptop',
    'query_by' => 'title',
    'limit' => 10
]);
echo "query_by restricted results: " . json_encode($restrictedResults->getBody(), JSON_PRETTY_PRINT) . "\n";

// Filter operators belong in filter_by
$combinedResults = $client->search($collection, [
    'q' => '*',
    'query_by' => 'title,content',
    'filter_by' => 'price:>100&&category:electronics',
    'limit' => 10
]);
echo "Filtered results: " . json_encode($combinedResults->getBody(), JSON_PRETTY_PRINT) . "\n";

// Vector search
// Important knobs:
// - field_name: the vector field stored in the collection
// - topk: how many nearest matches to return
// - threshold: minimum similarity / distance gate, depending on server config
// - nprobe: higher usually improves recall, but increases latency
//
// In practice, nprobe is one of the first params to tune when vector results
// feel too weak or too narrow.
$vectorResults = $client->vectorSearch($collection, [
    'body' => [
        'vector' => [0.1, 0.2, 0.3, 0.4, 0.5],
        'field_name' => 'embedding',
        'topk' => 10,
        'threshold' => 0.5,
        'include_distance' => true,
        'query_params' => [
            'ef' => 64,
            'nprobe' => 4,
            'is_linear' => true
        ]
    ]
]);
echo "Vector results: " . json_encode($vectorResults->getBody(), JSON_PRETTY_PRINT) . "\n";

// Multi-search
$multiResults = $client->searchApi()->multiSearch([
    ['collection' => $collection, 'q' => 'laptop', 'query_by' => 'title'],
    ['collection' => $collection, 'q' => 'keyboard', 'query_by' => 'content']
]);
echo "Multi-search results: " . json_encode($multiResults->getBody(), JSON_PRETTY_PRINT) . "\n";

$client->collections()->delete($collection);
