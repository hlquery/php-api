<?php
/*
 * Basic Usage Examples
 * 
 * Demonstrates basic operations with the hlquery PHP client
 */

require_once __DIR__ . '/../lib/autoload.php';

use Hlquery\Client;

// Initialize client
$client = new Client('http://localhost:9200');

// Health check
$health = $client->health();
echo "Health Status: " . $health->getStatusCode() . "\n";
echo "Health Body: " . json_encode($health->getBody(), JSON_PRETTY_PRINT) . "\n";

// List collections
$collections = $client->listCollections(0, 10);
if ($collections->isSuccess()) {
    $body = $collections->getBody();
    $count = isset($body['collections']) ? count($body['collections']) : 0;
    echo "Found $count collections\n";
}

// With authentication
$authenticatedClient = new Client('http://localhost:9200', [
    'token' => 'your_token_here'
]);

// Or set token dynamically
$client->setAuthToken('your_token_here', 'bearer');
