<?php
/*
 * Flush Example
 *
 * Demonstrates the flush operation:
 * 1. Create a fake collection
 * 2. Create a fake document
 * 3. Check collection count
 * 4. Flush all data
 * 5. Re-check collection count (should be 0)
 */

require_once __DIR__ . '/../lib/autoload.php';

use Hlquery\Client;

$client = new Client('http://localhost:9200');

echo str_repeat("=", 70) . "\n";
echo "FLUSH EXAMPLE\n";
echo str_repeat("=", 70) . "\n\n";

// Step 1: Create a fake collection
echo "Step 1: Creating a fake collection...\n";
$collection_name = 'flush_test_collection_' . time();

$schema = [
    'fields' => [
        ['name' => 'title', 'type' => 'string'],
        ['name' => 'content', 'type' => 'string'],
        ['name' => 'value', 'type' => 'int']
    ]
];

$create_result = $client->collections()->create($collection_name, $schema);
if ($create_result->isSuccess()) {
    echo "  ✓ Collection '$collection_name' created successfully\n";
} else {
    echo "  ✗ Failed to create collection: " . $create_result->getStatusCode() . "\n";
    $error_body = $create_result->getBody();
    if ($error_body) {
        echo "  Error: " . json_encode($error_body, JSON_PRETTY_PRINT) . "\n";
    }
    exit(1);
}

echo "\n";

// Step 2: Create a fake document
echo "Step 2: Creating a fake document...\n";
$doc = [
    'id' => 'flush_test_doc_' . time(),
    'title' => 'Flush Test Document',
    'content' => 'This is a test document for flush example',
    'value' => 42
];

$add_result = $client->documents()->add($collection_name, $doc);
if ($add_result->isSuccess()) {
    echo "  ✓ Document '{$doc['id']}' added successfully\n";
} else {
    echo "  ✗ Failed to add document: " . $add_result->getStatusCode() . "\n";
    $error_body = $add_result->getBody();
    if ($error_body) {
        echo "  Error: " . json_encode($error_body, JSON_PRETTY_PRINT) . "\n";
    }
}

echo "\n";

// Step 3: Check collection count before flush
echo "Step 3: Checking collection count before flush...\n";
$collections_before = $client->listCollections(0, 1000);
if ($collections_before->isSuccess()) {
    $body = $collections_before->getBody();
    $collections_list = isset($body['collections']) ? $body['collections'] : [];
    $count_before = count($collections_list);
    echo "  Collections before flush: $count_before\n";
    if ($count_before == 0) {
        echo "  ⚠ Warning: No collections found before flush\n";
    }
} else {
    echo "  ✗ Failed to list collections: " . $collections_before->getStatusCode() . "\n";
    $count_before = 0;
}

echo "\n";

// Step 4: Flush all data
echo "Step 4: Flushing all data...\n";
$flush_result = $client->flush();
if ($flush_result->isSuccess()) {
    $body = $flush_result->getBody();
    $collections_deleted = isset($body['collections_deleted']) ? $body['collections_deleted'] : 0;
    echo "  ✓ Flush completed successfully\n";
    echo "  Collections deleted: $collections_deleted\n";
    $message = isset($body['message']) ? $body['message'] : 'N/A';
    echo "  Message: $message\n";
} else {
    echo "  ✗ Flush failed: " . $flush_result->getStatusCode() . "\n";
    $error_body = $flush_result->getBody();
    if ($error_body) {
        echo "  Error: " . json_encode($error_body, JSON_PRETTY_PRINT) . "\n";
    }
    exit(1);
}

echo "\n";

// Step 5: Re-check collection count after flush
echo "Step 5: Checking collection count after flush...\n";
$collections_after = $client->listCollections(0, 1000);
if ($collections_after->isSuccess()) {
    $body = $collections_after->getBody();
    $collections_list = isset($body['collections']) ? $body['collections'] : [];
    $count_after = count($collections_list);
    echo "  Collections after flush: $count_after\n";
    
    if ($count_after == 0) {
        echo "  ✓ SUCCESS: All collections have been flushed\n";
    } else {
        echo "  ⚠ Warning: Expected 0 collections, but found $count_after\n";
    }
} else {
    echo "  ✗ Failed to list collections: " . $collections_after->getStatusCode() . "\n";
    $count_after = -1;
}

echo "\n";
echo str_repeat("=", 70) . "\n";
echo "FLUSH EXAMPLE COMPLETED\n";
echo str_repeat("=", 70) . "\n";
echo "Summary:\n";
echo "  Collections before flush: $count_before\n";
echo "  Collections after flush: $count_after\n";
echo "\n";
