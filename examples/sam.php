<?php
/*
 * Basic SAM example for the hlquery PHP client.
 */

require_once __DIR__ . '/../lib/autoload.php';

use Hlquery\Client;

$base_url = getenv('HLQ_BASE_URL') ?: (getenv('HLQUERY_BASE_URL') ?: 'http://localhost:9200');
$token = $argv[1] ?? null;
$collection = $argv[2] ?? 'music';
$query = $argv[3] ?? 'queen of pop';

$client = new Client($base_url);

if ($token) {
     $client->setAuthToken($token, 'bearer');
}

$sam = $client->sam();

$status = $sam->status($collection);
echo "=== SAM STATUS ===\n";
print_r($status->getBody());
echo "\n";

$history = $sam->history($collection, 5);
echo "=== SAM HISTORY ===\n";
print_r($history->getBody());
echo "\n";

$search = $sam->search($collection, $query, ['limit' => 10]);
echo "=== SAM SEARCH ===\n";
print_r($search->getBody());
