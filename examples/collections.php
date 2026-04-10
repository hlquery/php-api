<?php
/*
 * Collections Examples
 * 
 * Demonstrates collection management operations
 */

require_once __DIR__ . '/../lib/autoload.php';

use Hlquery\Client;

$client = new Client('http://localhost:9200');

// List collections
$collections = $client->collections()->list(0, 10);
echo "Collections: " . json_encode($collections->getBody(), JSON_PRETTY_PRINT) . "\n";

// Get collection details
if ($collections->isSuccess()) {
    $body = $collections->getBody();
    if (isset($body['collections']) && !empty($body['collections'])) {
        $first_collection = $body['collections'][0];
        $collection_name = is_array($first_collection) ? ($first_collection['name'] ?? $first_collection) : $first_collection;
        
        // Get collection
        $collection = $client->collections()->get($collection_name);
        echo "Collection details: " . json_encode($collection->getBody(), JSON_PRETTY_PRINT) . "\n";
        
        // Get formatted fields
        $fields = $client->collections()->getFields($collection_name);
        echo "Collection fields: " . json_encode($fields->getBody(), JSON_PRETTY_PRINT) . "\n";
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
$createResult = $client->collections()->create('new_collection', $schema);
echo "Create result: " . json_encode($createResult->getBody(), JSON_PRETTY_PRINT) . "\n";

// Delete collection
$deleteResult = $client->collections()->delete('collection_name');
echo "Delete result: " . json_encode($deleteResult->getBody(), JSON_PRETTY_PRINT) . "\n";
