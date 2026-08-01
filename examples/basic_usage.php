<?php
/*
 * Basic Usage Examples
 * 
 * Demonstrates basic operations with the hlquery PHP client
 */

require_once __DIR__ . '/../lib/autoload.php';

use Hlquery\Client;

$baseUrl = $argv[1] ?? (getenv('HLQ_BASE_URL') ?: (getenv('HLQUERY_BASE_URL') ?: 'http://localhost:9200'));
$token = $argv[2] ?? (getenv('HLQUERY_TOKEN') ?: null);

// Initialize client
$client = new Client($baseUrl);

if ($token) {
    $client->setAuthToken($token, 'bearer');
}

// Health check
$health = $client->health();
echo "Health Status: " . $health->getStatusCode() . "\n";
echo "Health Body: " . json_encode($health, JSON_PRETTY_PRINT) . "\n";

// List collections
$collectionNames = $client->collections()->names(0, 10);
echo "Found " . count($collectionNames) . " collections\n";

echo "Base URL: {$baseUrl}\n";
