<?php
/*
 * Documents Examples
 * 
 * Demonstrates document CRUD operations
 */

require_once __DIR__ . '/../lib/autoload.php';

use Hlquery\Client;

$baseUrl = $argv[1] ?? (getenv('HLQ_BASE_URL') ?: (getenv('HLQUERY_BASE_URL') ?: 'http://localhost:9200'));
$token = $argv[2] ?? (getenv('HLQUERY_TOKEN') ?: null);
$client = new Client($baseUrl);

if ($token) {
    $client->setAuthToken($token, 'bearer');
}

$collection = 'php_documents_example_' . getmypid();

$client->collections()->delete($collection);
$client->collections()->create($collection, [
    'fields' => [
        ['name' => 'title', 'type' => 'string'],
        ['name' => 'content', 'type' => 'string'],
    ],
]);

// Add document
$newDoc = [
    'id' => 'doc_1',
    'title' => 'New Document',
    'content' => 'Document content'
];
$addResult = $client->documents()->add($collection, $newDoc);
echo "Add result: " . json_encode($addResult->getBody(), JSON_PRETTY_PRINT) . "\n";

// List documents
$docs = $client->documents()->list($collection, [
    'offset' => 0,
    'limit' => 10
]);
echo "Documents: " . json_encode($docs->getBody(), JSON_PRETTY_PRINT) . "\n";

// Get document
$doc = $client->documents()->get($collection, 'doc_1');
echo "Document: " . json_encode($doc->getBody(), JSON_PRETTY_PRINT) . "\n";

// Update document
$updatedDoc = [
    'title' => 'Updated Document',
    'content' => 'Updated content'
];
$updateResult = $client->documents()->update($collection, 'doc_1', $updatedDoc);
echo "Update result: " . json_encode($updateResult->getBody(), JSON_PRETTY_PRINT) . "\n";

// Delete document
$deleteResult = $client->documents()->delete($collection, 'doc_1');
echo "Delete result: " . json_encode($deleteResult->getBody(), JSON_PRETTY_PRINT) . "\n";

// Bulk import
$bulkDocs = [
    ['id' => 'doc1', 'title' => 'Doc 1'],
    ['id' => 'doc2', 'title' => 'Doc 2'],
    ['id' => 'doc3', 'title' => 'Doc 3']
];
$importResult = $client->documents()->import($collection, $bulkDocs);
echo "Import result: " . json_encode($importResult->getBody(), JSON_PRETTY_PRINT) . "\n";

$cleanup = $client->collections()->delete($collection);
echo "Cleanup result: " . json_encode($cleanup->getBody(), JSON_PRETTY_PRINT) . "\n";
