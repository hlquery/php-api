<?php
/*
 * Collections Examples
 * 
 * Demonstrates collection management operations
 */

require_once __DIR__ . '/../lib/autoload.php';

use Hlquery\Client;

$baseUrl = $argv[1] ?? (getenv('HLQ_BASE_URL') ?: (getenv('HLQUERY_BASE_URL') ?: 'http://localhost:9200'));
$token = $argv[2] ?? (getenv('HLQUERY_TOKEN') ?: null);
$client = new Client($baseUrl);

if ($token) {
    $client->setAuthToken($token, 'bearer');
}

$exampleCollection = 'php_collections_example_' . getmypid();

// List collections
$collections = $client->collections()->list(0, 10);
echo "Collections: " . json_encode($collections, JSON_PRETTY_PRINT) . "\n";

// Get collection details
if ($collections->isSuccess()) {
    if (!empty($collections['collections'])) {
        $first_collection = $collections['collections'][0];
        $collection_name = is_array($first_collection) ? ($first_collection['name'] ?? $first_collection) : $first_collection;
        
        // Get collection
        $collection = $client->collections()->get($collection_name);
        echo "Collection details: " . json_encode($collection, JSON_PRETTY_PRINT) . "\n";
        
        // Get formatted fields
        $fields = $client->collections()->getFields($collection_name);
        echo "Collection fields: " . json_encode($fields, JSON_PRETTY_PRINT) . "\n";
    }
}

// Create collection
$schema = [
    'fields' => [
        ['name' => 'title', 'type' => 'string'],
        ['name' => 'content', 'type' => 'string'],
        ['name' => 'embedding', 'type' => 'float[]']
    ]
];
$createResult = $client->collections()->create($exampleCollection, $schema);
echo "Create result: " . json_encode($createResult, JSON_PRETTY_PRINT) . "\n";

// Delete collection
$deleteResult = $client->collections()->delete($exampleCollection);
echo "Delete result: " . json_encode($deleteResult, JSON_PRETTY_PRINT) . "\n";
