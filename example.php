<?php
/*
 * hlquery PHP API Comprehensive Example
 * 
 * This example tests ALL routes found in the HTTP server:
 * - Health, stats, metrics, status, root
 * - Collections (list, create, get, delete, update)
 * - Documents (list, get, add, update, delete, import, delete by filter)
 * - Search (regular search, vector search, multi-search)
 * - Synonyms, stopwords, overrides, aliases
 * 
 * Usage: php example.php [command] [token]
 *   Commands:
 *     cols   - Run collections API examples
 *     docs   - Run documents API examples
 *     open   - List and open collections (interactive)
 *     status - Show server health and status information
 *     help   - Show this help message
 *     all    - Run all examples (default)
 */

require_once __DIR__ . '/lib/autoload.php';

use Hlquery\Client;

// Configuration
$baseUrl = getenv('HLQ_BASE_URL') ?: (getenv('HLQUERY_BASE_URL') ?: 'http://localhost:9200');

// Parse command line arguments
$command = 'all';
$testToken = null;
$offset = 0;
$limit = 1000;
$collectionName = null;

if (isset($argv[1])) {
    $firstArg = $argv[1];
    if (in_array($firstArg, ['cols', 'docs', 'open', 'status', 'help', 'all', 'demo'])) {
        $command = $firstArg;
        // Parse pagination for cols command: cols [offset] [limit] [token]
        if ($command === 'cols' && isset($argv[2]) && is_numeric($argv[2])) {
            $offset = intval($argv[2]);
            if (isset($argv[3]) && is_numeric($argv[3])) {
                $limit = intval($argv[3]);
                $testToken = isset($argv[4]) ? $argv[4] : null;
            } else {
                $testToken = isset($argv[3]) ? $argv[3] : null;
            }
        } elseif ($command === 'docs' && isset($argv[2])) {
            // For docs command: docs [collection_name] [token]
            $collectionName = $argv[2];
            $testToken = isset($argv[3]) ? $argv[3] : null;
        } else {
            // Look for token in remaining args (skip numeric args)
            for ($i = 2; $i < count($argv); $i++) {
                if (!is_numeric($argv[$i])) {
                    $testToken = $argv[$i];
                    break;
                }
            }
        }
    } else {
        // First arg is token, use 'all' command
        $testToken = $firstArg;
    }
}

// Show help if requested
if ($command === 'help') {
    echo "=== hlquery PHP API Example ===\n\n";
    echo "Usage: php example.php [command] [args...] [token]\n\n";
    echo "Commands:\n";
    echo "  cols   - List collections (with pagination)\n";
    echo "          Usage: cols [offset] [limit] [token]\n";
    echo "          Example: cols 0 200 (list first 200 collections)\n";
    echo "  docs   - Run documents API examples\n";
    echo "          Usage: docs [collection_name] [token]\n";
    echo "          Example: docs my_collection\n";
    echo "  open   - List and open collections (interactive)\n";
    echo "  status - Show server health and status information\n";
    echo "  demo   - Quick demo: stats, ping, create collection, add doc, list\n";
    echo "  help   - Show this help message\n";
    echo "  all    - Run all examples (default)\n\n";
    echo "Authentication:\n";
    echo "  Token is optional. Only provide if server requires authentication.\n";
    echo "  Example: php example.php cols 0 200 my_token\n";
    echo "  Example: php example.php docs my_collection my_token\n\n";
    echo "Examples:\n";
    echo "  php example.php\n";
    echo "  php example.php cols\n";
    echo "  php example.php cols 0 200\n";
    echo "  php example.php docs my_collection\n";
    echo "  php example.php status\n";
    exit(0);
}

echo "=== hlquery PHP API Example ===\n";
echo "Command: $command\n\n";

// Create client
$client = new Client($baseUrl);

// Optional: Set authentication token if provided
if ($testToken) {
    $client->setAuthToken($testToken, 'bearer');
    echo "Using authentication token: " . substr($testToken, 0, 8) . "...\n\n";
}

// ----------------------------------------------------------------====================================
// QUICK DEMO: Stats, Ping, Create Collection, Add Doc, List
// ----------------------------------------------------------------====================================
if ($command === 'all' || $command === 'demo') {
echo "\n" . str_repeat("#", 70) . "\n";
echo "# QUICK DEMO: Stats, Ping, Create Collection, Add Doc, List\n";
echo str_repeat("#", 70) . "\n\n";

try {
    // 1. Run stats
    echo "1. Getting stats...\n";
    $stats_resp = $client->stats();
    if ($stats_resp->isSuccess()) {
        $stats_body = $stats_resp->getBody();
        echo "   ✓ Stats retrieved successfully\n";
        if (is_array($stats_body)) {
            echo "   Stats: " . json_encode($stats_body, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
        }
    } else {
        echo "   ✗ Failed to get stats: " . $stats_resp->getStatusCode() . "\n";
    }
    echo "\n";
    
    // 2. Run ping (health check)
    echo "2. Running ping (health check)...\n";
    $health_resp = $client->health();
    if ($health_resp->isSuccess()) {
        $health_body = $health_resp->getBody();
        echo "   ✓ Health check successful\n";
        if (is_array($health_body)) {
            $status = $health_body['status'] ?? 'unknown';
            echo "   Status: $status\n";
            if (isset($health_body['version'])) {
                echo "   Version: " . $health_body['version'] . "\n";
            }
        }
    } else {
        echo "   ✗ Health check failed: " . $health_resp->getStatusCode() . "\n";
    }
    echo "\n";
    
    // 3. Create collection "random_php"
    $collection_name = 'random_php';
    echo "3. Creating collection '$collection_name'...\n";
    
    // Check if collection already exists and delete it first
    $existing_collections = $client->collections()->list(0, 1000);
    if ($existing_collections->isSuccess()) {
        $existing_body = $existing_collections->getBody();
        $collections = isset($existing_body['collections']) ? $existing_body['collections'] : [];
        foreach ($collections as $col) {
            $col_name = is_array($col) ? ($col['name'] ?? $col) : $col;
            if ($col_name === $collection_name) {
                echo "   Collection already exists, deleting it first...\n";
                $client->collections()->delete($collection_name);
                break;
            }
        }
    }
    
    $schema = [
        'fields' => [
            ['name' => 'title', 'type' => 'string'],
            ['name' => 'content', 'type' => 'string'],
            ['name' => 'value', 'type' => 'int']
        ]
    ];
    $create_resp = $client->collections()->create($collection_name, $schema);
    if ($create_resp->isSuccess()) {
        echo "   ✓ Collection '$collection_name' created successfully\n";
    } else {
        echo "   ✗ Failed to create collection: " . $create_resp->getStatusCode() . "\n";
        $error_body = $create_resp->getBody();
        if ($error_body) {
            echo "   Error: " . json_encode($error_body, JSON_PRETTY_PRINT) . "\n";
        }
    }
    echo "\n";
    
    // 4. Add a document
    echo "4. Adding a document to '$collection_name'...\n";
    $new_doc = [
        'id' => 'doc_' . time(),
        'title' => 'Random PHP Document',
        'content' => 'This is a test document created by the PHP example script',
        'value' => 42
    ];
    $add_resp = $client->documents()->add($collection_name, $new_doc);
    if ($add_resp->isSuccess()) {
        echo "   ✓ Document added successfully\n";
        echo "   Document ID: " . $new_doc['id'] . "\n";
    } else {
        echo "   ✗ Failed to add document: " . $add_resp->getStatusCode() . "\n";
        $error_body = $add_resp->getBody();
        if ($error_body) {
            echo "   Error: " . json_encode($error_body, JSON_PRETTY_PRINT) . "\n";
        }
    }
    echo "\n";
    
    // 5. List documents
    echo "5. Listing documents in '$collection_name'...\n";
    $list_resp = $client->documents()->list($collection_name, [
        'offset' => 0,
        'limit' => 100
    ]);
    if ($list_resp->isSuccess()) {
        $list_body = $list_resp->getBody();
        $documents = [];
        if (isset($list_body['documents']) && is_array($list_body['documents'])) {
            $documents = $list_body['documents'];
        } elseif (is_array($list_body)) {
            $documents = $list_body;
        }
        
        echo "   ✓ Found " . count($documents) . " document(s)\n";
        foreach ($documents as $idx => $doc) {
            echo "   Document #" . ($idx + 1) . ":\n";
            if (is_array($doc)) {
                foreach ($doc as $key => $value) {
                    if (is_array($value) || is_object($value)) {
                        echo "     $key: " . json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
                    } else {
                        echo "     $key: $value\n";
                    }
                }
            } else {
                echo "     " . json_encode($doc, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
            }
            echo "\n";
        }
    } else {
        echo "   ✗ Failed to list documents: " . $list_resp->getStatusCode() . "\n";
        $error_body = $list_resp->getBody();
        if ($error_body) {
            echo "   Error: " . json_encode($error_body, JSON_PRETTY_PRINT) . "\n";
        }
    }
    echo "\n";
    
} catch (Exception $e) {
    echo "Error in quick demo: " . $e->getMessage() . "\n\n";
}

echo str_repeat("=", 70) . "\n";
echo "Quick demo completed!\n";
echo str_repeat("=", 70) . "\n\n";

// Exit if this was just the demo command
if ($command === 'demo') {
    exit(0);
}
}

// Helper function to print results
function printResult($title, $response, $printBody = true) {
    echo str_repeat("=", 70) . "\n";
    echo "TEST: $title\n";
    echo str_repeat("-", 70) . "\n";
    
    if ($response instanceof \Hlquery\Response) {
        $status = $response->getStatusCode();
        $body = $response->getBody();
        $headers = $response->getHeaders();
        
        echo "Status Code: $status\n";
        
        if ($printBody && $body) {
            echo "Response Body:\n";
            if (is_array($body)) {
                echo json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
            } else {
                echo $body . "\n";
            }
        }
        
        if ($status >= 200 && $status < 300) {
            echo "✓ SUCCESS\n";
        } else {
            echo "✗ FAILED\n";
        }
    } else {
        echo "Invalid response type\n";
    }
    echo "\n";
}

// Helper to get first collection name
function getFirstCollection($client) {
    $collections = $client->listCollections(0, 1);
    if ($collections->getStatusCode() === 200) {
        $body = $collections->getBody();
        if (isset($body['collections']) && !empty($body['collections'])) {
            $first = $body['collections'][0];
            return is_array($first) ? ($first['name'] ?? $first) : $first;
        }
    }
    return null;
}

// ----------------------------------------------------------------====================================
// STATUS COMMAND
// ----------------------------------------------------------------====================================
if ($command === 'status') {
    echo "\n" . str_repeat("#", 70) . "\n";
    echo "# SERVER STATUS\n";
    echo str_repeat("#", 70) . "\n\n";
    
    try {
        // GET /health
        printResult("GET /health", $client->health());
        
        // GET /stats
        printResult("GET /stats", $client->stats());
        
        // GET /etc (protocol codes)
        $etc = $client->executeRequest('GET', '/etc');
        printResult("GET /etc (Protocol Codes)", $etc);
        
        // GET /status
        $status = $client->executeRequest('GET', '/status');
        printResult("GET /status", $status);
        
        // GET / (Root Info) - show concise version
        $info = $client->info();
        echo str_repeat("=", 70) . "\n";
        echo "TEST: GET / (Root Info)\n";
        echo str_repeat("-", 70) . "\n";
        if ($info instanceof \Hlquery\Response) {
            $statusCode = $info->getStatusCode();
            $body = $info->getBody();
            echo "Status Code: $statusCode\n";
            if ($statusCode >= 200 && $statusCode < 300 && is_array($body)) {
                echo "Name: " . ($body['name'] ?? 'N/A') . "\n";
                echo "Version: " . ($body['version'] ?? 'N/A') . "\n";
                echo "Description: " . ($body['description'] ?? 'N/A') . "\n";
                echo "✓ SUCCESS\n";
            } else {
                echo "Response Body:\n";
                echo json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
                echo "✓ SUCCESS\n";
            }
        } else {
            echo "Invalid response\n";
        }
        echo "\n";
    } catch (Exception $e) {
        echo "Error getting status: " . $e->getMessage() . "\n\n";
    }
    exit(0);
}

// ----------------------------------------------------------------====================================
// System APIs (only for 'all' command, skip for 'demo')
// ----------------------------------------------------------------====================================
if ($command === 'all') {
    echo "\n" . str_repeat("#", 70) . "\n";
    echo "# System APIs\n";
    echo str_repeat("#", 70) . "\n\n";

    try {
        // GET /health
        printResult("GET /health", $client->health());
        
        // GET /stats
        printResult("GET /stats", $client->stats());
        
        // GET /etc (protocol codes)
        $etc = $client->executeRequest('GET', '/etc');
        printResult("GET /etc (Protocol Codes)", $etc);
        
        // GET /metrics (Prometheus-compatible)
        $metrics = $client->executeRequest('GET', '/metrics');
        printResult("GET /metrics", $metrics);
        
        // GET /status
        $status = $client->executeRequest('GET', '/status');
        printResult("GET /status", $status);
        
        // GET /
        printResult("GET / (Root)", $client->info());
        
    } catch (Exception $e) {
        echo "Error in system APIs: " . $e->getMessage() . "\n\n";
    }
}

// ----------------------------------------------------------------====================================
// COLLECTIONS API (skip for 'demo' command)
// ----------------------------------------------------------------====================================
if ($command === 'all' || $command === 'cols' || $command === 'open') {
    echo "\n" . str_repeat("#", 70) . "\n";
    echo "# COLLECTIONS API\n";
    echo str_repeat("#", 70) . "\n\n";

    try {
        // GET /collections with pagination
        $collections = $client->listCollections($offset, $limit);
        
        if ($command === 'cols') {
            // Simple list display for cols command
            if ($collections->getStatusCode() === 200) {
                $body = $collections->getBody();
                if (isset($body['collections'])) {
                    $colsList = $body['collections'];
                    $total = count($colsList);
                    echo "Collections (showing $total, offset: $offset, limit: $limit):\n\n";
                    foreach ($colsList as $col) {
                        $name = is_array($col) ? ($col['name'] ?? $col) : $col;
                        echo "  $name\n";
                    }
                    echo "\n";
                } else {
                    echo "No collections found.\n\n";
                }
            } else {
                echo "Error: " . $collections->getStatusCode() . "\n";
                $body = $collections->getBody();
                if (is_array($body) && isset($body['message'])) {
                    echo "Message: " . $body['message'] . "\n";
                }
                echo "\n";
            }
        } else {
            // Full display for 'all' and 'open' commands
            printResult("GET /collections (List)", $collections);
            
            // Get first collection for other tests
            $firstCollection = getFirstCollection($client);
            
            if ($firstCollection && $command === 'all') {
                echo "Using collection: $firstCollection\n\n";
                
                // GET /collections/{name}
                printResult("GET /collections/{name}", $client->getCollection($firstCollection));
                
                // GET /collections/{name} (fields formatted)
                printResult("GET /collections/{name}/fields (formatted)", $client->getCollectionFields($firstCollection));
                
                // Test creating a temporary collection
                $testCollectionName = 'test_collection_' . time();
                $testSchema = [
                    'fields' => [
                        ['name' => 'title', 'type' => 'string'],
                        ['name' => 'content', 'type' => 'string'],
                        ['name' => 'embedding', 'type' => 'float[]']
                    ]
                ];
                
                // POST /collections
                $createResult = $client->collections()->create($testCollectionName, $testSchema);
                printResult("POST /collections (Create)", $createResult);
                
                if ($createResult->getStatusCode() === 200 || $createResult->getStatusCode() === 201) {
                    // POST /collections/{name}/update
                    $updateSchema = [
                        'fields' => [
                            ['name' => 'title', 'type' => 'string'],
                            ['name' => 'content', 'type' => 'string'],
                            ['name' => 'embedding', 'type' => 'float[]'],
                            ['name' => 'tags', 'type' => 'string[]']
                        ]
                    ];
                    $updateResult = $client->collections()->update($testCollectionName, $updateSchema);
                    printResult("POST /collections/{name}/update", $updateResult);
                    
                    // DELETE /collections/{name} (cleanup)
                    $deleteResult = $client->collections()->delete($testCollectionName);
                    printResult("DELETE /collections/{name}", $deleteResult);
                }
            } elseif (!$firstCollection) {
                echo "No collections found - skipping collection-specific tests\n\n";
            }
        }
        
        // For 'open' command, list all collections
        if ($command === 'open') {
            $collections = $client->listCollections(0, 1000);
            if ($collections->getStatusCode() === 200) {
                $body = $collections->getBody();
                if (isset($body['collections'])) {
                    echo "\nAvailable Collections:\n";
                    echo str_repeat("-", 70) . "\n";
                    foreach ($body['collections'] as $col) {
                        $name = is_array($col) ? ($col['name'] ?? $col) : $col;
                        echo "  $name\n";
                    }
                    echo "\n";
                }
            }
        }
        
    } catch (Exception $e) {
        echo "Error in collections API: " . $e->getMessage() . "\n\n";
    }
}

// ----------------------------------------------------------------====================================
// DOCUMENTS API (skip for 'demo' command)
// ----------------------------------------------------------------====================================
if ($command === 'all' || $command === 'docs' || $command === 'open') {
    echo "\n" . str_repeat("#", 70) . "\n";
    echo "# DOCUMENTS API\n";
    echo str_repeat("#", 70) . "\n\n";

    // Use provided collection name or get first collection
    $testCollection = $collectionName ?: getFirstCollection($client);
    if (!$testCollection) {
        if ($command === 'docs' && !$collectionName) {
            echo "Error: No collection name provided and no collections found.\n";
            echo "Usage: php example.php docs [collection_name] [token]\n\n";
        } else {
            echo "No collections available - skipping document tests\n\n";
        }
    } else {
        if ($command === 'docs' && $collectionName) {
            echo "Using collection: $testCollection\n\n";
        }
        try {
            // GET /collections/{name}/documents
            $documents = $client->listDocuments($testCollection, ['offset' => 0, 'limit' => $limit]);
            
            if ($command === 'docs') {
                // Simple list display for docs command
                if ($documents->getStatusCode() === 200) {
                    $body = $documents->getBody();
                    if (isset($body['documents'])) {
                        $docsList = $body['documents'];
                        $total = count($docsList);
                        echo "Documents in '$testCollection' (showing $total, limit: $limit):\n\n";
                        foreach ($docsList as $doc) {
                            $docId = is_array($doc) ? ($doc['id'] ?? $doc) : $doc;
                            echo "  $docId\n";
                        }
                        echo "\n";
                    } else {
                        echo "No documents found.\n\n";
                    }
                } else {
                    echo "Error: " . $documents->getStatusCode() . "\n";
                    $body = $documents->getBody();
                    if (is_array($body) && isset($body['message'])) {
                        echo "Message: " . $body['message'] . "\n";
                    }
                    echo "\n";
                }
            } else {
                // Full display for 'all' command
                printResult("GET /collections/{name}/documents (List)", $documents);
            }
            
            // Get a document ID if available (for 'all' command)
            $testDocId = null;
            if ($command === 'all') {
                $documents = $client->listDocuments($testCollection, ['offset' => 0, 'limit' => 1]);
                if ($documents->getStatusCode() === 200) {
                    $body = $documents->getBody();
                    if (isset($body['documents']) && !empty($body['documents'])) {
                        $doc = $body['documents'][0];
                        $testDocId = is_array($doc) ? ($doc['id'] ?? null) : null;
                    }
                }
            }
        
        if ($testDocId) {
            // GET /collections/{name}/documents/{id}
            printResult("GET /collections/{name}/documents/{id}", 
                $client->getDocument($testCollection, $testDocId));
        }
        
        // POST /collections/{name}/documents (Add)
        $newDoc = [
            'id' => 'test_doc_' . time(),
            'title' => 'Test Document',
            'content' => 'This is a test document for API testing',
            'embedding' => [0.1, 0.2, 0.3, 0.4, 0.5] // Sample vector
        ];
        $addResult = $client->documents()->add($testCollection, $newDoc);
        printResult("POST /collections/{name}/documents (Add)", $addResult);
        
        if ($addResult->getStatusCode() === 200 || $addResult->getStatusCode() === 201) {
            $addedDocId = $newDoc['id'];
            
            // PUT /collections/{name}/documents/{id}
            $updatedDoc = [
                'title' => 'Updated Test Document',
                'content' => 'This document has been updated'
            ];
            $updateResult = $client->documents()->update($testCollection, $addedDocId, $updatedDoc);
            printResult("PUT /collections/{name}/documents/{id} (Update)", $updateResult);
            
            // POST /collections/{name}/documents/import
            $bulkDocs = [
                ['id' => 'bulk_1', 'title' => 'Bulk Doc 1', 'content' => 'Content 1'],
                ['id' => 'bulk_2', 'title' => 'Bulk Doc 2', 'content' => 'Content 2'],
                ['id' => 'bulk_3', 'title' => 'Bulk Doc 3', 'content' => 'Content 3']
            ];
            $importResult = $client->documents()->import($testCollection, $bulkDocs);
            printResult("POST /collections/{name}/documents/import (Bulk Import)", $importResult);
            
            // DELETE /collections/{name}/documents/{id}
            $deleteResult = $client->documents()->delete($testCollection, $addedDocId);
            printResult("DELETE /collections/{name}/documents/{id}", $deleteResult);
            
            // DELETE /collections/{name}/documents (by filter)
            $deleteByFilterResult = $client->documents()->deleteByFilter($testCollection, [
                'filter_by' => 'title:Bulk*'
            ]);
            printResult("DELETE /collections/{name}/documents (by filter)", $deleteByFilterResult);
        }
        
        } catch (Exception $e) {
            echo "Error in documents API: " . $e->getMessage() . "\n\n";
        }
    }
}

// ----------------------------------------------------------------====================================
// SEARCH API (only for 'all' command)
// ----------------------------------------------------------------====================================
if ($command === 'all') {
    echo "\n" . str_repeat("#", 70) . "\n";
    echo "# SEARCH API\n";
    echo str_repeat("#", 70) . "\n\n";

    $searchCollection = getFirstCollection($client);
    if (!$searchCollection) {
        echo "No collections available - skipping search tests\n\n";
    } else {
        try {
        // GET/POST /collections/{name}/documents/search (Regular search)
        $searchParams = [
            'q' => 'test',
            'query_by' => 'title,content',
            'limit' => 5
        ];
        printResult("GET /collections/{name}/documents/search (Regular Search)", 
            $client->search($searchCollection, $searchParams));
        
        // Sorting examples
        echo "\n--- Sorting Examples ---\n\n";
        
        // Sort by relevance (default)
        $searchRelevance = [
            'q' => 'test',
            'query_by' => 'title,content',
            'sort_by' => '_text_match:desc',
            'limit' => 5
        ];
        printResult("Search sorted by Relevance (Best Match)", 
            $client->search($searchCollection, $searchRelevance));
        
        // Sort by title alphabetically
        $searchTitle = [
            'q' => 'test',
            'query_by' => 'title,content',
            'sort_by' => 'title:asc',
            'limit' => 5
        ];
        printResult("Search sorted by Title (A-Z)", 
            $client->search($searchCollection, $searchTitle));
        
        // Sort by document ID
        $searchId = [
            'q' => 'test',
            'query_by' => 'title,content',
            'sort_by' => 'id:asc',
            'limit' => 5
        ];
        printResult("Search sorted by Document ID (A-Z)", 
            $client->search($searchCollection, $searchId));
        
        // Sort by date (if available)
        $searchDate = [
            'q' => 'test',
            'query_by' => 'title,content',
            'sort_by' => 'created_at:desc',
            'limit' => 5
        ];
        printResult("Search sorted by Date (Newest First)", 
            $client->search($searchCollection, $searchDate));
        
        // POST /collections/{name}/documents/search (POST method)
        $searchParamsPost = [
            'q' => 'document',
            'query_by' => 'title,content',
            'limit' => 3,
            'sort_by' => 'title:asc'
        ];
        $searchPost = $client->searchApi()->search($searchCollection, [
            'body' => $searchParamsPost,
            'q' => 'document',
            'query_by' => 'title,content',
            'limit' => 3
        ]);
        printResult("POST /collections/{name}/documents/search", $searchPost);
        
        // POST /collections/{name}/vector_search (Vector search body)
        $vectorQuery = [0.1, 0.2, 0.3, 0.4, 0.5, 0.6, 0.7, 0.8, 0.9, 1.0]; // Sample 10D vector
        $vectorParams = [
            'body' => [
                'vector' => $vectorQuery,
                'field_name' => 'embedding',
                'topk' => 5,
                'threshold' => 0.0,
                'normalize' => true,
                'include_vector' => false
            ]
        ];
        printResult("POST /collections/{name}/vector_search (Vector Search)", 
            $client->vectorSearch($searchCollection, $vectorParams));
        
        // POST /multi_search
        $multiSearchParams = [
            'searches' => [
                [
                    'collection' => $searchCollection,
                    'q' => 'test',
                    'query_by' => 'title'
                ],
                [
                    'collection' => $searchCollection,
                    'q' => 'document',
                    'query_by' => 'content'
                ]
            ]
        ];
        printResult("POST /multi_search", 
            $client->searchApi()->multiSearch($multiSearchParams['searches']));
        
        // POST /collections/{name}/documents/facet_counts
        $facetParams = [
            'facet_by' => 'title',
            'q' => '*',
            'limit' => 0
        ];
        $facetResult = $client->executeRequest('POST', 
            '/collections/' . urlencode($searchCollection) . '/documents/facet_counts', 
            $facetParams);
        printResult("POST /collections/{name}/documents/facet_counts", $facetResult);
        
        // POST /collections/{name}/documents/export
        $exportParams = [
            'filter_by' => '',
            'limit' => 10
        ];
        $exportResult = $client->executeRequest('POST', 
            '/collections/' . urlencode($searchCollection) . '/documents/export', 
            $exportParams);
        printResult("POST /collections/{name}/documents/export", $exportResult, false);
        
        } catch (Exception $e) {
            echo "Error in search API: " . $e->getMessage() . "\n\n";
        }
    }
}

// ----------------------------------------------------------------====================================
// SYNONYMS API (only for 'all' command)
// ----------------------------------------------------------------====================================
if ($command === 'all') {
    echo "\n" . str_repeat("#", 70) . "\n";
    echo "# SYNONYMS API\n";
    echo str_repeat("#", 70) . "\n\n";

    $synonymCollection = getFirstCollection($client);
    if (!$synonymCollection) {
        echo "No collections available - skipping synonym tests\n\n";
    } else {
        try {
        // GET /collections/{name}/synonyms
        $synonymsList = $client->executeRequest('GET', 
            '/collections/' . urlencode($synonymCollection) . '/synonyms');
        printResult("GET /collections/{name}/synonyms", $synonymsList);
        
        // GET /synonyms (all collections)
        $allSynonyms = $client->executeRequest('GET', '/synonyms');
        printResult("GET /synonyms (All Collections)", $allSynonyms);
        
        // POST /collections/{name}/synonyms/{id}
        $synonymData = [
            'root' => 'car',
            'synonyms' => ['automobile', 'vehicle', 'auto']
        ];
        $createSynonym = $client->executeRequest('POST', 
            '/collections/' . urlencode($synonymCollection) . '/synonyms/test_synonym_1', 
            $synonymData);
        printResult("POST /collections/{name}/synonyms/{id} (Create)", $createSynonym);
        
        if ($createSynonym->getStatusCode() === 200 || $createSynonym->getStatusCode() === 201) {
            // GET /collections/{name}/synonyms/{id}
            $getSynonym = $client->executeRequest('GET', 
                '/collections/' . urlencode($synonymCollection) . '/synonyms/test_synonym_1');
            printResult("GET /collections/{name}/synonyms/{id}", $getSynonym);
            
            // DELETE /collections/{name}/synonyms/{id}
            $deleteSynonym = $client->executeRequest('DELETE', 
                '/collections/' . urlencode($synonymCollection) . '/synonyms/test_synonym_1');
            printResult("DELETE /collections/{name}/synonyms/{id}", $deleteSynonym);
        }
        
        } catch (Exception $e) {
            echo "Error in synonyms API: " . $e->getMessage() . "\n\n";
        }
    }
}

// ----------------------------------------------------------------====================================
// STOPWORDS API (only for 'all' command)
// ----------------------------------------------------------------====================================
if ($command === 'all') {
    echo "\n" . str_repeat("#", 70) . "\n";
    echo "# STOPWORDS API\n";
    echo str_repeat("#", 70) . "\n\n";

    $stopwordCollection = getFirstCollection($client);
    if (!$stopwordCollection) {
        echo "No collections available - skipping stopword tests\n\n";
    } else {
        try {
        // GET /collections/{name}/stopwords
        $stopwordsList = $client->executeRequest('GET', 
            '/collections/' . urlencode($stopwordCollection) . '/stopwords');
        printResult("GET /collections/{name}/stopwords", $stopwordsList);
        
        // GET /stopwords (all collections)
        $allStopwords = $client->executeRequest('GET', '/stopwords');
        printResult("GET /stopwords (All Collections)", $allStopwords);
        
        // POST /collections/{name}/stopwords
        $stopwordData = ['word' => 'the'];
        $createStopword = $client->executeRequest('POST', 
            '/collections/' . urlencode($stopwordCollection) . '/stopwords', 
            $stopwordData);
        printResult("POST /collections/{name}/stopwords (Create)", $createStopword);
        
        if ($createStopword->getStatusCode() === 200 || $createStopword->getStatusCode() === 201) {
            // DELETE /collections/{name}/stopwords/{word}
            $deleteStopword = $client->executeRequest('DELETE', 
                '/collections/' . urlencode($stopwordCollection) . '/stopwords/the');
            printResult("DELETE /collections/{name}/stopwords/{word}", $deleteStopword);
        }
        
        } catch (Exception $e) {
            echo "Error in stopwords API: " . $e->getMessage() . "\n\n";
        }
    }
}

// ----------------------------------------------------------------====================================
// OVERRIDES API (only for 'all' command)
// ----------------------------------------------------------------====================================
if ($command === 'all') {
    echo "\n" . str_repeat("#", 70) . "\n";
    echo "# OVERRIDES API\n";
    echo str_repeat("#", 70) . "\n\n";

    $overrideCollection = getFirstCollection($client);
    if (!$overrideCollection) {
        echo "No collections available - skipping override tests\n\n";
    } else {
        try {
        // GET /collections/{name}/overrides
        $overridesList = $client->executeRequest('GET', 
            '/collections/' . urlencode($overrideCollection) . '/overrides');
        printResult("GET /collections/{name}/overrides", $overridesList);
        
        // POST /collections/{name}/overrides/{id}
        $overrideData = [
            'rule' => [
                'query' => 'test query',
                'match' => 'exact'
            ],
            'includes' => [
                ['id' => 'doc1', 'position' => 1]
            ]
        ];
        $createOverride = $client->executeRequest('POST', 
            '/collections/' . urlencode($overrideCollection) . '/overrides/test_override_1', 
            $overrideData);
        printResult("POST /collections/{name}/overrides/{id} (Create)", $createOverride);
        
        if ($createOverride->getStatusCode() === 200 || $createOverride->getStatusCode() === 201) {
            // GET /collections/{name}/overrides/{id}
            $getOverride = $client->executeRequest('GET', 
                '/collections/' . urlencode($overrideCollection) . '/overrides/test_override_1');
            printResult("GET /collections/{name}/overrides/{id}", $getOverride);
            
            // DELETE /collections/{name}/overrides/{id}
            $deleteOverride = $client->executeRequest('DELETE', 
                '/collections/' . urlencode($overrideCollection) . '/overrides/test_override_1');
            printResult("DELETE /collections/{name}/overrides/{id}", $deleteOverride);
        }
        
        } catch (Exception $e) {
            echo "Error in overrides API: " . $e->getMessage() . "\n\n";
        }
    }
}

// ----------------------------------------------------------------====================================
// ALIASES API (only for 'all' command)
// ----------------------------------------------------------------====================================
if ($command === 'all') {
    echo "\n" . str_repeat("#", 70) . "\n";
    echo "# ALIASES API\n";
    echo str_repeat("#", 70) . "\n\n";

    try {
    // GET /aliases
    $aliasesList = $client->executeRequest('GET', '/aliases');
    printResult("GET /aliases", $aliasesList);
    
    $aliasCollection = getFirstCollection($client);
    if ($aliasCollection) {
        // POST /aliases/{name}
        $aliasName = 'test_alias_' . time();
        $aliasData = ['collection_name' => $aliasCollection];
        $createAlias = $client->executeRequest('POST', '/aliases/' . $aliasName, $aliasData);
        printResult("POST /aliases/{name} (Create)", $createAlias);
        
        if ($createAlias->getStatusCode() === 200 || $createAlias->getStatusCode() === 201) {
            // GET /aliases/{name}
            $getAlias = $client->executeRequest('GET', '/aliases/' . $aliasName);
            printResult("GET /aliases/{name}", $getAlias);
            
            // DELETE /aliases/{name}
            $deleteAlias = $client->executeRequest('DELETE', '/aliases/' . $aliasName);
            printResult("DELETE /aliases/{name}", $deleteAlias);
        }
    }
    
    } catch (Exception $e) {
        echo "Error in aliases API: " . $e->getMessage() . "\n\n";
    }
}

// ----------------------------------------------------------------====================================
// SUMMARY (only show for 'all' command or when no specific command provided)
// ----------------------------------------------------------------====================================
if ($command === 'all') {
    echo "\n" . str_repeat("#", 70) . "\n";
    echo "# TESTING COMPLETE\n";
    echo str_repeat("#", 70) . "\n\n";

    echo "Routes have been tested. Check the output above for results.\n";
    echo "Note: Some tests may fail if:\n";
    echo "  - Authentication is required but no token was provided\n";
    echo "  - Collections don't exist\n";
    echo "  - Required data is missing\n";
    echo "\n";
    echo "Usage: php example.php [command] [args...] [token]\n";
    echo "  Commands:\n";
    echo "    cols   - List collections (with pagination)\n";
    echo "             Usage: cols [offset] [limit] [token]\n";
    echo "             Example: cols 0 200\n";
    echo "    docs   - Run documents API examples\n";
    echo "             Usage: docs [collection_name] [token]\n";
    echo "             Example: docs my_collection\n";
    echo "    open   - List and open collections\n";
    echo "    status - Show server health and status information\n";
    echo "    demo   - Quick demo: stats, ping, create collection, add doc, list\n";
    echo "    help   - Show help message\n";
    echo "    all    - Run all examples (default)\n";
    echo "\n";
}
