<?php
/*
 * Vector Search Example
 * 
 * Demonstrates vector search capabilities with multiple collections:
 * - Creates 4 collections with vector fields
 * - Inserts 10 documents per collection with random embeddings
 * - Performs vector searches to find relationships
 * - Shows similarity-based document retrieval
 * 
 * Usage: php examples/vector.php [token]
 */

require_once __DIR__ . '/../lib/autoload.php';

use Hlquery\Client;

// Configuration
$baseUrl = 'http://localhost:9200';
$testToken = isset($argv[1]) ? $argv[1] : null;

// Helper function to generate random vector
function generateRandomVector($dimensions = 128) {
    $vector = [];
    for ($i = 0; $i < $dimensions; $i++) {
        $vector[] = (mt_rand() / mt_getrandmax()) * 2 - 1; // Random value between -1 and 1
    }
    // Normalize the vector
    $magnitude = sqrt(array_sum(array_map(function($val) { return $val * $val; }, $vector)));
    return array_map(function($val) use ($magnitude) { return $val / $magnitude; }, $vector);
}

// Helper function to generate related vectors (similar to a base vector)
function generateRelatedVector($baseVector, $similarity = 0.8) {
    $related = array_map(function($val) use ($similarity) { return $val * $similarity; }, $baseVector);
    $random = generateRandomVector(count($baseVector));
    $orthogonal = array_map(function($val) use ($similarity) { return $val * (1 - $similarity); }, $random);
    return array_map(function($val, $idx) use ($orthogonal) { return $val + $orthogonal[$idx]; }, $related, array_keys($related));
}

// Helper function to print results
function printResult($title, $response, $printBody = true) {
    echo str_repeat("=", 80) . "\n";
    echo "TEST: $title\n";
    echo str_repeat("-", 80) . "\n";
    
    if ($response) {
        $status = $response->getStatusCode();
        $body = $response->getBody();
        
        echo "Status Code: $status\n";
        
        if ($printBody && $body) {
            echo "Response Body:\n";
            if (is_array($body) || is_object($body)) {
                echo json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
            } else {
                echo "$body\n";
            }
        }
        
        if ($status >= 200 && $status < 300) {
            echo "✓ SUCCESS\n";
        } else {
            echo "✗ FAILED\n";
        }
    } else {
        echo "Invalid response\n";
    }
    echo "\n";
}

// Helper function to print vector query
function printVectorQuery($collectionName, $vectorQuery, $params = []) {
    echo "\n" . str_repeat("=", 80) . "\n";
    echo "VECTOR QUERY\n";
    echo str_repeat("=", 80) . "\n";
    echo "Collection: $collectionName\n";
    echo "Vector Dimensions: " . count($vectorQuery) . "\n";
    $first10 = array_slice($vectorQuery, 0, 10);
    $first10Str = implode(', ', array_map(function($v) { return number_format($v, 4); }, $first10));
    echo "Vector (first 10 values): [$first10Str...]\n";
    echo "Limit: " . ($params['limit'] ?? 10) . "\n";
    echo "Threshold: " . ($params['threshold'] ?? 0.0) . "\n";
    echo "Normalize: " . (isset($params['normalize']) ? ($params['normalize'] ? 'true' : 'false') : 'true') . "\n";
    if (isset($params['field_name'])) {
        echo "Field Name: " . $params['field_name'] . "\n";
    }
    echo str_repeat("=", 80) . "\n\n";
}

// Main execution
echo "=== hlquery Vector Search Example ===\n\n";

// Create client
$client = new Client($baseUrl);
if ($testToken) {
    $client->setAuthToken($testToken, 'bearer');
    echo "Using authentication token: " . substr($testToken, 0, 8) . "...\n\n";
}

// Collection names
$collections = ['products', 'articles', 'images', 'documents'];

// Collection schemas with vector fields
$schemas = [
    'products' => [
        'fields' => [
            ['name' => 'name', 'type' => 'string'],
            ['name' => 'description', 'type' => 'string'],
            ['name' => 'embedding', 'type' => 'float[]']
        ]
    ],
    'articles' => [
        'fields' => [
            ['name' => 'title', 'type' => 'string'],
            ['name' => 'content', 'type' => 'string'],
            ['name' => 'embedding', 'type' => 'float[]']
        ]
    ],
    'images' => [
        'fields' => [
            ['name' => 'filename', 'type' => 'string'],
            ['name' => 'caption', 'type' => 'string'],
            ['name' => 'embedding', 'type' => 'float[]']
        ]
    ],
    'documents' => [
        'fields' => [
            ['name' => 'title', 'type' => 'string'],
            ['name' => 'text', 'type' => 'string'],
            ['name' => 'embedding', 'type' => 'float[]']
        ]
    ]
];

// Generate base vectors for each collection (for creating related documents)
$baseVectors = [];
foreach ($collections as $col) {
    $baseVectors[$col] = generateRandomVector(128);
}

// ----------------------------------------------------------------====================================
// CREATE COLLECTIONS
// ----------------------------------------------------------------====================================
echo "\n" . str_repeat("#", 80) . "\n";
echo "# CREATING COLLECTIONS\n";
echo str_repeat("#", 80) . "\n\n";

foreach ($collections as $collectionName) {
    try {
        // Check if collection exists, delete if it does
        $existing = $client->getCollection($collectionName);
        if ($existing->getStatusCode() === 200) {
            echo "Collection $collectionName exists, deleting...\n";
            $client->collections()->delete($collectionName);
        }
    } catch (Exception $e) {
        // Collection doesn't exist, that's fine
    }
    
    // Create collection
    $createResult = $client->collections()->create($collectionName, $schemas[$collectionName]);
    if ($createResult->isSuccess()) {
        echo "✓ Created collection: $collectionName\n";
    } else {
        echo "✗ Failed to create collection: $collectionName\n";
        $error = $createResult->getBody();
        echo "  Error: " . json_encode($error) . "\n";
    }
}
echo "\n";

// ----------------------------------------------------------------====================================
// INSERT DOCUMENTS
// ----------------------------------------------------------------====================================
echo "\n" . str_repeat("#", 80) . "\n";
echo "# INSERTING DOCUMENTS\n";
echo str_repeat("#", 80) . "\n\n";

$allDocuments = [];

foreach ($collections as $collectionName) {
    echo "Inserting documents into $collectionName...\n";
    $documents = [];
    $baseVector = $baseVectors[$collectionName];
    
    for ($i = 1; $i <= 10; $i++) {
        // Create documents with varying similarity to base vector
        $similarity = 0.5 + ($i / 10) * 0.4; // Range from 0.5 to 0.9
        $embedding = generateRelatedVector($baseVector, $similarity);
        
        $doc = null;
        switch ($collectionName) {
            case 'products':
                $doc = [
                    'id' => "product_$i",
                    'name' => "Product $i",
                    'description' => "This is product number $i with similarity " . number_format($similarity, 2),
                    'embedding' => $embedding
                ];
                break;
            case 'articles':
                $doc = [
                    'id' => "article_$i",
                    'title' => "Article $i",
                    'content' => "This is article number $i with similarity " . number_format($similarity, 2),
                    'embedding' => $embedding
                ];
                break;
            case 'images':
                $doc = [
                    'id' => "image_$i",
                    'filename' => "image_$i.jpg",
                    'caption' => "Image $i with similarity " . number_format($similarity, 2),
                    'embedding' => $embedding
                ];
                break;
            case 'documents':
                $doc = [
                    'id' => "doc_$i",
                    'title' => "Document $i",
                    'text' => "This is document number $i with similarity " . number_format($similarity, 2),
                    'embedding' => $embedding
                ];
                break;
        }
        
        if ($doc) {
            $documents[] = $doc;
        }
    }
    
    // Bulk import
    $importResult = $client->documents()->import($collectionName, $documents);
    if ($importResult->isSuccess()) {
        echo "✓ Inserted " . count($documents) . " documents into $collectionName\n";
        $allDocuments[$collectionName] = $documents;
    } else {
        echo "✗ Failed to insert documents into $collectionName\n";
        $error = $importResult->getBody();
        echo "  Error: " . json_encode($error) . "\n";
    }
}
echo "\n";

// Wait a bit for indexing
echo "Waiting for documents to be indexed...\n";
sleep(2);
echo "\n";

// ----------------------------------------------------------------====================================
// VECTOR SEARCH - Find Similar Documents
// ----------------------------------------------------------------====================================
echo "\n" . str_repeat("#", 80) . "\n";
echo "# VECTOR SEARCH - Finding Relationships\n";
echo str_repeat("#", 80) . "\n\n";

// Test 1: Search in products collection using base vector
echo "TEST 1: Search in products collection using base vector\n\n";
$productsQuery = $baseVectors['products'];
printVectorQuery('products', $productsQuery, ['limit' => 5, 'threshold' => 0.0, 'normalize' => true]);

$productsSearch = $client->vectorSearch('products', [
    'vector_query' => $productsQuery,
    'limit' => 5,
    'threshold' => 0.0,
    'normalize' => true
]);

if ($productsSearch->isSuccess()) {
    $body = $productsSearch->getBody();
    echo "RESULTS:\n";
    if (isset($body['hits']) && !empty($body['hits'])) {
        foreach ($body['hits'] as $index => $hit) {
            $doc = $hit['document'] ?? $hit;
            $docId = $doc['id'] ?? ($hit['id'] ?? 'N/A');
            $name = $doc['name'] ?? 'N/A';
            $similarity = isset($hit['similarity_score']) ? number_format($hit['similarity_score'], 4) : 'N/A';
            
            echo "\n  " . ($index + 1) . ". Document ID: $docId\n";
            echo "     Name: $name\n";
            echo "     Similarity Score: $similarity\n";
            
            if (isset($doc['embedding']) && is_array($doc['embedding']) && !empty($doc['embedding'])) {
                $emb = array_slice($doc['embedding'], 0, 5);
                $embStr = implode(', ', array_map(function($v) { return number_format($v, 4); }, $emb));
                echo "     Embedding (first 5): [$embStr...]\n";
            }
        }
    } else {
        echo "  No results found\n";
    }
    echo "\nTotal found: " . ($body['found'] ?? 0) . "\n";
} else {
    echo "✗ Search failed: " . json_encode($productsSearch->getBody()) . "\n";
}
echo "\n";

// Test 2: Search in articles collection using a related vector
echo "TEST 2: Search in articles collection using related vector\n\n";
$articlesQuery = generateRelatedVector($baseVectors['articles'], 0.85);
printVectorQuery('articles', $articlesQuery, ['limit' => 5, 'threshold' => 0.5, 'normalize' => true]);

$articlesSearch = $client->vectorSearch('articles', [
    'vector_query' => $articlesQuery,
    'limit' => 5,
    'threshold' => 0.5,
    'normalize' => true
]);

if ($articlesSearch->isSuccess()) {
    $body = $articlesSearch->getBody();
    echo "RESULTS:\n";
    if (isset($body['hits']) && !empty($body['hits'])) {
        foreach ($body['hits'] as $index => $hit) {
            $doc = $hit['document'] ?? $hit;
            $docId = $doc['id'] ?? ($hit['id'] ?? 'N/A');
            $title = $doc['title'] ?? 'N/A';
            $similarity = isset($hit['similarity_score']) ? number_format($hit['similarity_score'], 4) : 'N/A';
            
            echo "\n  " . ($index + 1) . ". Document ID: $docId\n";
            echo "     Title: $title\n";
            echo "     Similarity Score: $similarity\n";
        }
    } else {
        echo "  No results found (threshold too high)\n";
    }
    echo "\nTotal found: " . ($body['found'] ?? 0) . "\n";
} else {
    echo "✗ Search failed: " . json_encode($articlesSearch->getBody()) . "\n";
}
echo "\n";

// Test 3: Cross-collection search - find similar documents across collections
echo "TEST 3: Cross-collection search - Find similar documents in images collection\n\n";
$imagesQuery = $baseVectors['images'];
printVectorQuery('images', $imagesQuery, ['limit' => 10, 'threshold' => 0.0, 'normalize' => true]);

$imagesSearch = $client->vectorSearch('images', [
    'vector_query' => $imagesQuery,
    'limit' => 10,
    'threshold' => 0.0,
    'normalize' => true
]);

if ($imagesSearch->isSuccess()) {
    $body = $imagesSearch->getBody();
    echo "RESULTS:\n";
    if (isset($body['hits']) && !empty($body['hits'])) {
        foreach ($body['hits'] as $index => $hit) {
            $doc = $hit['document'] ?? $hit;
            $docId = $doc['id'] ?? ($hit['id'] ?? 'N/A');
            $filename = $doc['filename'] ?? 'N/A';
            $caption = $doc['caption'] ?? 'N/A';
            $similarity = isset($hit['similarity_score']) ? number_format($hit['similarity_score'], 4) : 'N/A';
            
            echo "\n  " . ($index + 1) . ". Document ID: $docId\n";
            echo "     Filename: $filename\n";
            echo "     Caption: $caption\n";
            echo "     Similarity Score: $similarity\n";
        }
    } else {
        echo "  No results found\n";
    }
    echo "\nTotal found: " . ($body['found'] ?? 0) . "\n";
} else {
    echo "✗ Search failed: " . json_encode($imagesSearch->getBody()) . "\n";
}
echo "\n";

// Test 4: Search with high threshold to find only very similar documents
echo "TEST 4: High threshold search - Find only very similar documents\n\n";
$documentsQuery = $baseVectors['documents'];
printVectorQuery('documents', $documentsQuery, ['limit' => 5, 'threshold' => 0.7, 'normalize' => true]);

$documentsSearch = $client->vectorSearch('documents', [
    'vector_query' => $documentsQuery,
    'limit' => 5,
    'threshold' => 0.7,
    'normalize' => true
]);

if ($documentsSearch->isSuccess()) {
    $body = $documentsSearch->getBody();
    echo "RESULTS:\n";
    if (isset($body['hits']) && !empty($body['hits'])) {
        foreach ($body['hits'] as $index => $hit) {
            $doc = $hit['document'] ?? $hit;
            $docId = $doc['id'] ?? ($hit['id'] ?? 'N/A');
            $title = $doc['title'] ?? 'N/A';
            $similarity = isset($hit['similarity_score']) ? number_format($hit['similarity_score'], 4) : 'N/A';
            
            echo "\n  " . ($index + 1) . ". Document ID: $docId\n";
            echo "     Title: $title\n";
            echo "     Similarity Score: $similarity\n";
        }
    } else {
        echo "  No results found (threshold too high - no documents with similarity > 0.7)\n";
    }
    echo "\nTotal found: " . ($body['found'] ?? 0) . "\n";
} else {
    echo "✗ Search failed: " . json_encode($documentsSearch->getBody()) . "\n";
}
echo "\n";

// Test 5: Find relationships between collections
echo "TEST 5: Find relationships - Search products using articles base vector\n\n";
$crossQuery = $baseVectors['articles'];
printVectorQuery('products', $crossQuery, [
    'limit' => 3,
    'threshold' => 0.0,
    'normalize' => true,
    'note' => 'Using articles base vector to search products (cross-collection similarity)'
]);

$crossSearch = $client->vectorSearch('products', [
    'vector_query' => $crossQuery,
    'limit' => 3,
    'threshold' => 0.0,
    'normalize' => true
]);

if ($crossSearch->isSuccess()) {
    $body = $crossSearch->getBody();
    echo "RESULTS (Cross-collection similarity):\n";
    if (isset($body['hits']) && !empty($body['hits'])) {
        foreach ($body['hits'] as $index => $hit) {
            $doc = $hit['document'] ?? $hit;
            $docId = $doc['id'] ?? ($hit['id'] ?? 'N/A');
            $name = $doc['name'] ?? 'N/A';
            $similarity = isset($hit['similarity_score']) ? number_format($hit['similarity_score'], 4) : 'N/A';
            
            echo "\n  " . ($index + 1) . ". Document ID: $docId\n";
            echo "     Name: $name\n";
            echo "     Similarity Score: $similarity\n";
            echo "     Note: Lower scores indicate less similarity across collections\n";
        }
    } else {
        echo "  No results found\n";
    }
    echo "\nTotal found: " . ($body['found'] ?? 0) . "\n";
} else {
    echo "✗ Search failed: " . json_encode($crossSearch->getBody()) . "\n";
}
echo "\n";

// ----------------------------------------------------------------====================================
// SUMMARY
// ----------------------------------------------------------------====================================
echo "\n" . str_repeat("#", 80) . "\n";
echo "# SUMMARY\n";
echo str_repeat("#", 80) . "\n\n";

echo "Created Collections:\n";
foreach ($collections as $col) {
    echo "  - $col\n";
}
echo "\n";

echo "Documents Inserted:\n";
foreach ($collections as $col) {
    $count = isset($allDocuments[$col]) ? count($allDocuments[$col]) : 0;
    echo "  - $col: $count documents\n";
}
echo "\n";

echo "Vector Search Tests Performed:\n";
echo "  1. Products collection search (base vector, threshold 0.0)\n";
echo "  2. Articles collection search (related vector, threshold 0.5)\n";
echo "  3. Images collection search (base vector, all results)\n";
echo "  4. Documents collection search (base vector, high threshold 0.7)\n";
echo "  5. Cross-collection search (articles vector → products collection)\n";
echo "\n";

echo "Key Observations:\n";
echo "  - Documents with higher similarity scores are more related\n";
echo "  - Threshold parameter filters results by minimum similarity\n";
echo "  - Vector normalization ensures consistent similarity calculations\n";
echo "  - Cross-collection searches can find relationships across different data types\n";
echo "\n";

echo "Usage: php examples/vector.php [token]\n";
echo "\n";
