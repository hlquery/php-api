<?php
/*
 * Documents Examples
 * 
 * Demonstrates document CRUD operations
 */

require_once __DIR__ . '/../lib/autoload.php';

use Hlquery\Client;

$client = new Client('http://localhost:9200');

$collection = 'collection';

// List documents
$docs = $client->documents()->list($collection, [
    'offset' => 0,
    'limit' => 10
]);
echo "Documents: " . json_encode($docs->getBody(), JSON_PRETTY_PRINT) . "\n";

// Get document
$doc = $client->documents()->get($collection, 'doc_id');
echo "Document: " . json_encode($doc->getBody(), JSON_PRETTY_PRINT) . "\n";

// Add document
$newDoc = [
    'id' => 'doc_1',
    'title' => 'New Document',
    'content' => 'Document content'
];
$addResult = $client->documents()->add($collection, $newDoc);
echo "Add result: " . json_encode($addResult->getBody(), JSON_PRETTY_PRINT) . "\n";

// Update document
$updatedDoc = [
    'title' => 'Updated Document',
    'content' => 'Updated content'
];
$updateResult = $client->documents()->update($collection, 'doc_id', $updatedDoc);
echo "Update result: " . json_encode($updateResult->getBody(), JSON_PRETTY_PRINT) . "\n";

// Delete document
$deleteResult = $client->documents()->delete($collection, 'doc_id');
echo "Delete result: " . json_encode($deleteResult->getBody(), JSON_PRETTY_PRINT) . "\n";

// Bulk import
$bulkDocs = [
    ['id' => 'doc1', 'title' => 'Doc 1'],
    ['id' => 'doc2', 'title' => 'Doc 2'],
    ['id' => 'doc3', 'title' => 'Doc 3']
];
$importResult = $client->documents()->import($collection, $bulkDocs);
echo "Import result: " . json_encode($importResult->getBody(), JSON_PRETTY_PRINT) . "\n";
