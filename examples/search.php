<?php
/*
 * Search Examples
 * 
 * Demonstrates various search patterns with the hlquery PHP client
 */

require_once __DIR__ . '/../lib/autoload.php';

use Hlquery\Client;

$client = new Client('http://localhost:9200');
$collection = 'collection_name';

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
    ['collection' => 'col1', 'q' => 'query1', 'query_by' => 'title'],
    ['collection' => 'col2', 'q' => 'query2', 'query_by' => 'content']
]);
echo "Multi-search results: " . json_encode($multiResults->getBody(), JSON_PRETTY_PRINT) . "\n";
