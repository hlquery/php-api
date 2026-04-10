<?php
/*
 * hlquery PHP API - Synonyms and Stopwords Test
 * 
 * This script tests all synonyms and stopwords API endpoints and verifies
 * that synonyms actually work in search (not just that the API accepts them).
 * 
 * What this script does:
 * 1. Tests all CRUD operations for synonyms (create, read, update, delete)
 * 2. Tests all CRUD operations for stopwords (add, list, delete)
 * 3. Verifies synonyms work in actual search by:
 *    - Creating a synonym (e.g., "car" -> ["automobile", "vehicle"])
 *    - Adding a document with the root term (e.g., "car")
 *    - Searching for a synonym term (e.g., "automobile")
 *    - Verifying the document is found (proves synonyms work!)
 * 
 * Usage: 
 *   php synstop.php [collection_name] [token]
 * 
 * Examples:
 *   php synstop.php                    # Use first available collection
 *   php synstop.php books               # Use "books" collection
 *   php synstop.php books my_token      # Use "books" with auth token
 * 
 * @author hlquery Team
 * @version 1.0
 */

require_once __DIR__ . '/../lib/autoload.php';

use Hlquery\Client;

// ----------------------------------------------------------------====================================
// CONFIGURATION
// ----------------------------------------------------------------====================================

// Base URL of your hlquery server (default: localhost:9200)
$baseUrl = 'http://localhost:9200';

// Parse command line arguments
// $argv[1] = collection name (optional)
// $argv[2] = authentication token (optional)
$testToken = isset($argv[2]) ? $argv[2] : null;
$collectionName = isset($argv[1]) ? $argv[1] : null;

// Initialize the hlquery client
// The client handles all HTTP requests to the hlquery API
$client = new Client($baseUrl, ['token' => $testToken]);

// ----------------------------------------------------------------====================================
// HELPER FUNCTIONS
// ----------------------------------------------------------------====================================

/*
 * Print test results in a formatted way
 * 
 * This function displays:
 * - Test name
 * - HTTP status code
 * - Response body (formatted JSON)
 * - Success/failure indicator
 * 
 * @param string $testName Name of the test being run
 * @param object $response Response object from the client
 */
function printResult($testName, $response) {
    echo str_repeat("=", 70) . "\n";
    echo "TEST: $testName\n";
    echo str_repeat("-", 70) . "\n";
    echo "Status Code: " . $response->getStatusCode() . "\n";
    echo "Response Body:\n";
    echo json_encode($response->getBody(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
    
    if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
        echo "✓ SUCCESS\n";
    } else {
        echo "✗ FAILED\n";
    }
    echo "\n";
}

/*
 * Get the first available collection from the server
 * 
 * This is used when no collection name is provided on the command line.
 * It fetches all collections and returns the first one found.
 * 
 * @param Client $client The hlquery client instance
 * @return string|null Collection name or null if none found
 */
function getFirstCollection($client) {
    try {
        // Fetch collections (offset=0, limit=1000 to get all)
        $collections = $client->listCollections(0, 1000);
        
        // Check if request was successful
        if ($collections->getStatusCode() === 200) {
            $body = $collections->getBody();
            
            // Extract collection name from response
            // Response format: {"collections": [{"name": "collection1"}, ...]}
            if (isset($body['collections']) && is_array($body['collections']) && count($body['collections']) > 0) {
                return $body['collections'][0]['name'];
            }
        }
    } catch (Exception $e) {
        // If anything goes wrong, just return null
        // The calling code will handle creating a test collection
    }
    return null;
}

// ----------------------------------------------------------------====================================
// MAIN SCRIPT START
// ----------------------------------------------------------------====================================

echo "=== hlquery Synonyms & Stopwords Test ===\n\n";

// ----------------------------------------------------------------====================================
// SETUP: Get or Create Test Collection
// ----------------------------------------------------------------====================================

// Use collection name from command line if provided, otherwise find/create one
$testCollection = $collectionName;

if (!$testCollection) {
    // No collection specified - try to find an existing one
    $testCollection = getFirstCollection($client);
    
    if (!$testCollection) {
        // No collections exist - create a test collection
        // This ensures the script can run even on a fresh server
        echo "Creating test collection...\n";
        
        // Create collection with basic fields for testing
        // Fields are: title (string) and content (string)
        // These are the standard fields used for text search
        $createResponse = $client->executeRequest('POST', '/collections', [
            'name' => 'test_synstop_' . time(),  // Unique name with timestamp
            'fields' => [
                ['name' => 'title', 'type' => 'string'],      // Document title field
                ['name' => 'content', 'type' => 'string']     // Document content field
            ]
        ]);
        
        // Check if collection was created successfully
        if ($createResponse->getStatusCode() === 200 || $createResponse->getStatusCode() === 201) {
            // Extract collection name from response
            $responseBody = json_decode($createResponse->getBody(), true);
            $testCollection = $responseBody['name'] ?? 'test_synstop_' . time();
            echo "Created collection: $testCollection\n\n";
        } else {
            echo "ERROR: Could not create test collection\n";
            echo "Status: " . $createResponse->getStatusCode() . "\n";
            exit(1);
        }
    } else {
        // Found an existing collection - use it
        echo "Using existing collection: $testCollection\n\n";
    }
} else {
    // Collection name was provided on command line - use it
    echo "Using specified collection: $testCollection\n\n";
}

// ----------------------------------------------------------------====================================
// SYNONYMS API TESTS
// ----------------------------------------------------------------====================================
// 
// Synonyms allow you to define equivalent terms for search.
// For example, if "car" and "automobile" are synonyms, searching for
// "automobile" will also find documents containing "car".
//
// API Endpoints:
// - GET  /collections/{name}/synonyms          - List synonyms for a collection
// - GET  /synonyms                            - List synonyms for all collections
// - POST /collections/{name}/synonyms/{id}    - Create/update a synonym
// - GET  /collections/{name}/synonyms/{id}    - Get a specific synonym
// - DELETE /collections/{name}/synonyms/{id}  - Delete a synonym
//
// ----------------------------------------------------------------====================================

echo str_repeat("#", 70) . "\n";
echo "# SYNONYMS API TESTS\n";
echo str_repeat("#", 70) . "\n\n";

try {
    // ------------------------------------------------------------------------
    // Test 1: List synonyms for this collection
    // ------------------------------------------------------------------------
    // GET /collections/{name}/synonyms
    // Returns all synonym groups defined for the collection
    echo "Test 1: List collection synonyms\n";
    $synonymsList = $client->executeRequest('GET', 
        '/collections/' . urlencode($testCollection) . '/synonyms');
    printResult("GET /collections/{name}/synonyms", $synonymsList);
    
    // ------------------------------------------------------------------------
    // Test 2: List synonyms for ALL collections
    // ------------------------------------------------------------------------
    // GET /synonyms
    // Returns synonyms grouped by collection (useful for overview)
    echo "Test 2: List all synonyms\n";
    $allSynonyms = $client->executeRequest('GET', '/synonyms');
    printResult("GET /synonyms (All Collections)", $allSynonyms);
    
    // ------------------------------------------------------------------------
    // Test 3: Create a new synonym group
    // ------------------------------------------------------------------------
    // POST /collections/{name}/synonyms/{id}
    // Creates a synonym group with:
    //   - root: The primary term (e.g., "car")
    //   - synonyms: Array of equivalent terms (e.g., ["automobile", "vehicle"])
    // 
    // When someone searches for "automobile", the system will also search
    // for "car" and "vehicle" (and vice versa).
    echo "Test 3: Create synonym\n";
    $synonymId = 'test_syn_' . time();  // Unique ID using timestamp
    $synonymData = [
        'root' => 'car',                                    // Primary term
        'synonyms' => ['automobile', 'vehicle', 'auto', 'motorcar']  // Equivalent terms
    ];
    $createSynonym = $client->executeRequest('POST', 
        '/collections/' . urlencode($testCollection) . '/synonyms/' . $synonymId, 
        $synonymData);
    printResult("POST /collections/{name}/synonyms/{id} (Create)", $createSynonym);
    
    // Only continue with remaining tests if synonym creation succeeded
    if ($createSynonym->getStatusCode() === 200 || $createSynonym->getStatusCode() === 201) {
        
        // --------------------------------------------------------------------
        // Test 4: Get a specific synonym by ID
        // --------------------------------------------------------------------
        // GET /collections/{name}/synonyms/{id}
        // Retrieves details about one specific synonym group
        echo "Test 4: Get specific synonym\n";
        $getSynonym = $client->executeRequest('GET', 
            '/collections/' . urlencode($testCollection) . '/synonyms/' . $synonymId);
        printResult("GET /collections/{name}/synonyms/{id}", $getSynonym);
        
        // --------------------------------------------------------------------
        // Test 5: Create a second synonym group
        // --------------------------------------------------------------------
        // This demonstrates that you can have multiple synonym groups
        // in the same collection
        echo "Test 5: Create second synonym\n";
        $synonymId2 = 'test_syn2_' . time();
        $synonymData2 = [
            'root' => 'book',                                    // Primary term
            'synonyms' => ['novel', 'tome', 'volume', 'publication']  // Equivalent terms
        ];
        $createSynonym2 = $client->executeRequest('POST', 
            '/collections/' . urlencode($testCollection) . '/synonyms/' . $synonymId2, 
            $synonymData2);
        printResult("POST /collections/{name}/synonyms/{id} (Create 2nd)", $createSynonym2);
        
        // --------------------------------------------------------------------
        // Test 6: Verify both synonyms exist in the list
        // --------------------------------------------------------------------
        // This confirms that both synonym groups were saved correctly
        echo "Test 6: List synonyms (verify both exist)\n";
        $synonymsList2 = $client->executeRequest('GET', 
            '/collections/' . urlencode($testCollection) . '/synonyms');
        printResult("GET /collections/{name}/synonyms (After creating 2)", $synonymsList2);
        
        // --------------------------------------------------------------------
        // Test 7: Delete a synonym
        // --------------------------------------------------------------------
        // DELETE /collections/{name}/synonyms/{id}
        // Removes a synonym group from the collection
        echo "Test 7: Delete first synonym\n";
        $deleteSynonym = $client->executeRequest('DELETE', 
            '/collections/' . urlencode($testCollection) . '/synonyms/' . $synonymId);
        printResult("DELETE /collections/{name}/synonyms/{id}", $deleteSynonym);
        
        // --------------------------------------------------------------------
        // Test 8: Verify the synonym was actually deleted
        // --------------------------------------------------------------------
        // Try to get the deleted synonym - should return 404 (Not Found)
        echo "Test 8: Verify deletion\n";
        $getDeleted = $client->executeRequest('GET', 
            '/collections/' . urlencode($testCollection) . '/synonyms/' . $synonymId);
        if ($getDeleted->getStatusCode() === 404) {
            echo "✓ SUCCESS: Synonym correctly deleted (404 returned)\n\n";
        } else {
            echo "✗ WARNING: Expected 404 but got " . $getDeleted->getStatusCode() . "\n\n";
        }
        
        // --------------------------------------------------------------------
        // Cleanup: Remove test data
        // --------------------------------------------------------------------
        // Delete the second synonym we created so we don't leave test data behind
        echo "Cleanup: Delete second synonym\n";
        $client->executeRequest('DELETE', 
            '/collections/' . urlencode($testCollection) . '/synonyms/' . $synonymId2);
        echo "✓ Cleaned up\n\n";
    } else {
        // Synonym creation failed - can't continue with remaining tests
        echo "✗ FAILED: Could not create synonym, skipping remaining synonym tests\n\n";
    }
    
} catch (Exception $e) {
    echo "✗ ERROR in synonyms API: " . $e->getMessage() . "\n\n";
}

// ----------------------------------------------------------------====================================
// STOPWORDS API TESTS
// ----------------------------------------------------------------====================================
//
// Stopwords are common words that are ignored during search to improve
// performance and relevance. For example, words like "the", "a", "and" are
// usually stopwords because they appear in almost every document and don't
// help distinguish between documents.
//
// API Endpoints:
// - GET    /collections/{name}/stopwords        - List stopwords for a collection
// - GET    /stopwords                           - List stopwords for all collections
// - POST   /collections/{name}/stopwords        - Add a stopword
// - DELETE /collections/{name}/stopwords/{word}  - Delete a stopword
//
// ----------------------------------------------------------------====================================

echo str_repeat("#", 70) . "\n";
echo "# STOPWORDS API TESTS\n";
echo str_repeat("#", 70) . "\n\n";

try {
    // ------------------------------------------------------------------------
    // Test 1: List stopwords for this collection
    // ------------------------------------------------------------------------
    // GET /collections/{name}/stopwords
    // Returns all stopwords defined for the collection
    echo "Test 1: List collection stopwords\n";
    $stopwordsList = $client->executeRequest('GET', 
        '/collections/' . urlencode($testCollection) . '/stopwords');
    printResult("GET /collections/{name}/stopwords", $stopwordsList);
    
    // ------------------------------------------------------------------------
    // Test 2: List stopwords for ALL collections
    // ------------------------------------------------------------------------
    // GET /stopwords
    // Returns stopwords grouped by collection (useful for overview)
    echo "Test 2: List all stopwords\n";
    $allStopwords = $client->executeRequest('GET', '/stopwords');
    printResult("GET /stopwords (All Collections)", $allStopwords);
    
    // ------------------------------------------------------------------------
    // Test 3: Add a stopword
    // ------------------------------------------------------------------------
    // POST /collections/{name}/stopwords
    // Adds a word to the stopwords list
    // Format: {"word": "the"}
    echo "Test 3: Add stopword\n";
    $stopwordData = ['word' => 'the'];  // "the" is a common stopword
    $addStopword = $client->executeRequest('POST', 
        '/collections/' . urlencode($testCollection) . '/stopwords', 
        $stopwordData);
    printResult("POST /collections/{name}/stopwords (Add 'the')", $addStopword);
    
    // ------------------------------------------------------------------------
    // Test 4: Add multiple stopwords
    // ------------------------------------------------------------------------
    // Demonstrates adding several stopwords in a loop
    // Common English stopwords: articles, conjunctions, prepositions
    echo "Test 4: Add more stopwords\n";
    $stopwordsToAdd = ['a', 'an', 'and', 'or', 'but'];
    foreach ($stopwordsToAdd as $word) {
        $addResponse = $client->executeRequest('POST', 
            '/collections/' . urlencode($testCollection) . '/stopwords', 
            ['word' => $word]);
        if ($addResponse->getStatusCode() === 200 || $addResponse->getStatusCode() === 201) {
            echo "  ✓ Added stopword: $word\n";
        } else {
            echo "  ✗ Failed to add stopword: $word\n";
        }
    }
    echo "\n";
    
    // ------------------------------------------------------------------------
    // Test 5: Verify all stopwords were added
    // ------------------------------------------------------------------------
    // List stopwords again and verify they're all present
    echo "Test 5: List stopwords (verify all added)\n";
    $stopwordsList2 = $client->executeRequest('GET', 
        '/collections/' . urlencode($testCollection) . '/stopwords');
    printResult("GET /collections/{name}/stopwords (After adding)", $stopwordsList2);
    
    // ------------------------------------------------------------------------
    // Test 6: Delete a stopword
    // ------------------------------------------------------------------------
    // DELETE /collections/{name}/stopwords/{word}
    // Removes a word from the stopwords list
    echo "Test 6: Delete stopword\n";
    $deleteStopword = $client->executeRequest('DELETE', 
        '/collections/' . urlencode($testCollection) . '/stopwords/the');
    printResult("DELETE /collections/{name}/stopwords/{word} (Delete 'the')", $deleteStopword);
    
    // ------------------------------------------------------------------------
    // Test 7: Verify the stopword was actually deleted
    // ------------------------------------------------------------------------
    // Check that "the" is no longer in the stopwords list
    echo "Test 7: Verify deletion\n";
    $stopwordsList3 = $client->executeRequest('GET', 
        '/collections/' . urlencode($testCollection) . '/stopwords');
    $body = $stopwordsList3->getBody();
    if (isset($body['stopwords']) && is_array($body['stopwords'])) {
        // Extract just the word strings from the stopwords array
        // Format: [{"word": "the", ...}, {"word": "a", ...}]
        $stopwordWords = [];
        foreach ($body['stopwords'] as $stop) {
            if (is_array($stop) && isset($stop['word'])) {
                $stopwordWords[] = $stop['word'];
            } elseif (is_string($stop)) {
                $stopwordWords[] = $stop;
            }
        }
        $hasThe = in_array('the', $stopwordWords);
        if (!$hasThe) {
            echo "✓ SUCCESS: Stopword 'the' correctly deleted\n\n";
        } else {
            echo "✗ WARNING: Stopword 'the' still present\n\n";
        }
    }
    
    // ------------------------------------------------------------------------
    // Cleanup: Remove test stopwords
    // ------------------------------------------------------------------------
    // Delete all the stopwords we added so we don't leave test data behind
    echo "Cleanup: Delete remaining test stopwords\n";
    foreach ($stopwordsToAdd as $word) {
        $client->executeRequest('DELETE', 
            '/collections/' . urlencode($testCollection) . '/stopwords/' . $word);
    }
    echo "✓ Cleaned up\n\n";
    
} catch (Exception $e) {
    echo "✗ ERROR in stopwords API: " . $e->getMessage() . "\n\n";
}

// ----------------------------------------------------------------====================================
// VERIFY SYNONYMS WORK IN SEARCH
// ----------------------------------------------------------------====================================
//
// This is the CRITICAL test - it verifies that synonyms actually work
// in search, not just that the API accepts them.
//
// How it works:
// 1. Create a synonym (e.g., "car" -> ["automobile", "vehicle"])
// 2. Add a document containing the root term (e.g., "car")
// 3. Search for a synonym term (e.g., "automobile")
// 4. Verify the document is found (proves synonyms work!)
//
// If this test fails, synonyms are stored but not applied during search.
//
// ----------------------------------------------------------------====================================

echo str_repeat("#", 70) . "\n";
echo "# VERIFY SYNONYMS WORK IN SEARCH\n";
echo str_repeat("#", 70) . "\n\n";

try {
    // ------------------------------------------------------------------------
    // Step 1: Create a test synonym
    // ------------------------------------------------------------------------
    // We use a unique root term to avoid conflicts with existing synonyms
    // This ensures the test is isolated and won't interfere with other data
    echo "Step 1: Creating test synonym\n";
    $testSynId = 'search_test_syn_' . time();
    $uniqueRoot = 'laptop_' . time(); // Unique term to avoid conflicts
    $synData = [
        'root' => $uniqueRoot,                              // Primary term
        'synonyms' => ['notebook', 'computer', 'pc', 'device']  // Equivalent terms
    ];
    $createSyn = $client->executeRequest('POST', 
        '/collections/' . urlencode($testCollection) . '/synonyms/' . $testSynId, 
        $synData);
    
    // Check if synonym creation succeeded
    $createBody = $createSyn->getBody();
    if ($createSyn->getStatusCode() !== 200 && $createSyn->getStatusCode() !== 201) {
        // Synonym creation failed - try to use an existing synonym instead
        echo "✗ Failed to create synonym for search test\n";
        echo "  Status: " . $createSyn->getStatusCode() . "\n";
        echo "  Response: " . json_encode($createBody, JSON_PRETTY_PRINT) . "\n";
        echo "  URL: /collections/" . urlencode($testCollection) . "/synonyms/" . $testSynId . "\n\n";
        
        // Fallback: Use an existing synonym from the collection
        echo "Attempting to use existing synonym from collection...\n";
        $existingSyns = $client->executeRequest('GET', 
            '/collections/' . urlencode($testCollection) . '/synonyms');
        $existingBody = $existingSyns->getBody();
        if (isset($existingBody['synonyms']) && count($existingBody['synonyms']) > 0) {
            // Use the first existing synonym for testing
            $existingSyn = $existingBody['synonyms'][0];
            $uniqueRoot = $existingSyn['root'] ?? 'car';
            $synonyms = $existingSyn['synonyms'] ?? ['automobile', 'vehicle'];
            $testSynId = $existingSyn['id'] ?? 'existing_syn';
            echo "Using existing synonym: '$uniqueRoot' -> " . json_encode($synonyms) . "\n\n";
        } else {
            // No synonyms available - can't test search functionality
            echo "No existing synonyms found. Skipping search test.\n\n";
            return;
        }
    } else {
        // Synonym created successfully
        echo "✓ Synonym created: '$uniqueRoot' -> ['notebook', 'computer', 'pc', 'device']\n\n";
        $synonyms = $synData['synonyms'];
    }
    
    // Continue with search test using the synonym we have
    if (isset($uniqueRoot) && isset($synonyms) && count($synonyms) > 0) {
        $synonymTerm = $synonyms[0]; // Use first synonym for search (e.g., "notebook")
        echo "✓ Synonym ready for testing\n\n";
        
        // --------------------------------------------------------------------
        // Step 2: Add a document containing the root term
        // --------------------------------------------------------------------
        // This document will contain "laptop_123456" (the root term)
        // We'll then search for "notebook" (a synonym) and should find this document
        echo "Step 2: Adding document with word '$uniqueRoot'\n";
        $docId = 'test_doc_syn_' . time();
        $docData = [
            'id' => $docId,
            'title' => "My favorite $uniqueRoot is great",
            'content' => "I love my $uniqueRoot. It is a fast $uniqueRoot."
        ];
        $addDoc = $client->executeRequest('POST', 
            '/collections/' . urlencode($testCollection) . '/documents', 
            $docData);
        
        if ($addDoc->getStatusCode() !== 200 && $addDoc->getStatusCode() !== 201) {
            echo "✗ Failed to add document\n";
            echo "  Status: " . $addDoc->getStatusCode() . "\n";
            echo "  Response: " . json_encode($addDoc->getBody(), JSON_PRETTY_PRINT) . "\n\n";
        } else {
            echo "✓ Document added with '$uniqueRoot' in content\n\n";
            
            // --------------------------------------------------------------------
            // Step 3a: Verify document is indexed
            // --------------------------------------------------------------------
            // Wait a moment for the document to be indexed, then verify it's
            // searchable by searching for the root term directly
            echo "Waiting 3 seconds for indexing...\n";
            sleep(3);
            
            echo "Step 3a: Verifying document is indexed (search for '$uniqueRoot' directly)\n";
            // Search for the root term - this should definitely work
            // query_by=title,content means search in both title and content fields
            $verifySearch = $client->executeRequest('GET', 
                '/collections/' . urlencode($testCollection) . '/search?q=' . urlencode($uniqueRoot) . '&query_by=title,content');
            $verifyBody = $verifySearch->getBody();
            $docIndexed = false;
            if (isset($verifyBody['hits']) && is_array($verifyBody['hits'])) {
                foreach ($verifyBody['hits'] as $hit) {
                    // Check both 'id' (legacy) and 'document']['id'] (current format)
                    $hitDocId = $hit['document']['id'] ?? $hit['id'] ?? null;
                    if ($hitDocId === $docId) {
                        $docIndexed = true;
                        break;
                    }
                }
            }
            if ($docIndexed) {
                echo "✓ Document is indexed (found when searching for '$uniqueRoot')\n\n";
            } else {
                echo "✗ WARNING: Document not found when searching for '$uniqueRoot' directly!\n";
                echo "  This means the document may not be indexed yet, or collection fields are wrong.\n";
                echo "  Hits returned: " . count($verifyBody['hits'] ?? []) . "\n";
                echo "  Continuing with synonym test anyway...\n\n";
            }
            
            // --------------------------------------------------------------------
            // Step 3b: THE CRITICAL TEST - Search for synonym term
            // --------------------------------------------------------------------
            // This is where we prove synonyms work:
            // - Document contains: "laptop_123456" (root term)
            // - Searching for: "notebook" (synonym)
            // - Expected: Document should be found because "notebook" is a synonym of "laptop_123456"
            echo "Step 3b: Searching for '$synonymTerm' (synonym of '$uniqueRoot')\n";
            echo "  Document contains: '$uniqueRoot'\n";
            echo "  Searching for: '$synonymTerm' (should find it via synonym)\n";
            // Search in both title and content fields
            $searchResponse = $client->executeRequest('GET', 
                '/collections/' . urlencode($testCollection) . '/search?q=' . urlencode($synonymTerm) . '&query_by=title,content');
            
            echo "Search Status: " . $searchResponse->getStatusCode() . "\n";
            $searchBody = $searchResponse->getBody();
            
            if (isset($searchBody['hits']) && is_array($searchBody['hits'])) {
                $found = false;
                // Look for our test document in the search results
                // API returns document in 'document' field, not directly in 'hit'
                foreach ($searchBody['hits'] as $hit) {
                    // Check both 'id' (legacy) and 'document']['id'] (current format)
                    $hitDocId = $hit['document']['id'] ?? $hit['id'] ?? null;
                    if ($hitDocId === $docId) {
                        $found = true;
                        break;
                    }
                }
                
                if ($found) {
                    echo "✓ SUCCESS! Found document when searching for '$synonymTerm'\n";
                    echo "  This proves synonyms are working - the document contains '$uniqueRoot',\n";
                    echo "  but searching for '$synonymTerm' (synonym) found it!\n\n";
                    echo "Search Results:\n";
                    echo "  Total hits: " . ($searchBody['found'] ?? count($searchBody['hits'])) . "\n";
                    echo "  Document found: " . $docId . "\n";
                    if (isset($searchBody['hits'][0]['score'])) {
                        echo "  Score: " . $searchBody['hits'][0]['score'] . "\n";
                    }
                    echo "  Document title: " . ($searchBody['hits'][0]['title'] ?? 'N/A') . "\n";
                } else {
                    echo "✗ FAILED! Document NOT found when searching for '$synonymTerm'\n";
                    echo "  Synonyms may not be working in search\n";
                    echo "  Expected to find document: " . $docId . "\n";
                    echo "  Total hits returned: " . count($searchBody['hits']) . "\n";
                    if (count($searchBody['hits']) > 0) {
                        $firstHit = $searchBody['hits'][0];
                        $firstDocId = $firstHit['document']['id'] ?? $firstHit['id'] ?? 'N/A';
                        $firstTitle = $firstHit['document']['title'] ?? $firstHit['title'] ?? 'N/A';
                        echo "  First hit ID: " . $firstDocId . "\n";
                        echo "  First hit title: " . $firstTitle . "\n";
                    }
                    echo "  Full search response:\n";
                    echo json_encode($searchBody, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
                }
            } else {
                echo "✗ Unexpected search response format\n";
                echo "Response: " . json_encode($searchBody, JSON_PRETTY_PRINT) . "\n";
            }
            echo "\n";
            
            // Also test searching for the root term directly (should definitely work)
            echo "Step 4: Searching for '$uniqueRoot' directly (should work)\n";
            $searchRoot = $client->executeRequest('GET', 
                '/collections/' . urlencode($testCollection) . '/search?q=' . urlencode($uniqueRoot) . '&query_by=title,content');
            $rootBody = $searchRoot->getBody();
            if (isset($rootBody['hits']) && is_array($rootBody['hits'])) {
                $foundRoot = false;
                foreach ($rootBody['hits'] as $hit) {
                    // Check both 'id' (legacy) and 'document']['id'] (current format)
                    $hitDocId = $hit['document']['id'] ?? $hit['id'] ?? null;
                    if ($hitDocId === $docId) {
                        $foundRoot = true;
                        break;
                    }
                }
                if ($foundRoot) {
                    echo "✓ Direct search for '$uniqueRoot' works (expected)\n";
                } else {
                    echo "✗ Direct search for '$uniqueRoot' failed (unexpected!)\n";
                }
            }
            echo "\n";
            
            // --------------------------------------------------------------------
            // Step 5: TEST SORTING/RELEVANCE ORDER - Multiple documents
            // --------------------------------------------------------------------
            // Create multiple documents with different synonym terms to test
            // if sorting/relevance order is consistent between root term and synonym searches
            echo "Step 5: Testing sorting/relevance order with multiple documents\n";
            echo "  Creating multiple documents with different frequencies of '$uniqueRoot' and '$synonymTerm'...\n";
            
            $testDocs = [];
            // Document 1: High frequency of root term
            $doc1Id = 'test_sort_doc1_' . time();
            $testDocs[] = [
                'id' => $doc1Id,
                'title' => "Document 1: $uniqueRoot $uniqueRoot $uniqueRoot",
                'content' => "$uniqueRoot $uniqueRoot $uniqueRoot $uniqueRoot $uniqueRoot"
            ];
            
            // Document 2: Medium frequency of root term
            $doc2Id = 'test_sort_doc2_' . time();
            $testDocs[] = [
                'id' => $doc2Id,
                'title' => "Document 2: $uniqueRoot $uniqueRoot",
                'content' => "$uniqueRoot $uniqueRoot $uniqueRoot"
            ];
            
            // Document 3: Low frequency of root term
            $doc3Id = 'test_sort_doc3_' . time();
            $testDocs[] = [
                'id' => $doc3Id,
                'title' => "Document 3: $uniqueRoot",
                'content' => "$uniqueRoot"
            ];
            
            // Add all test documents
            foreach ($testDocs as $doc) {
                $addDoc = $client->executeRequest('POST', 
                    '/collections/' . urlencode($testCollection) . '/documents', 
                    $doc);
                if ($addDoc->getStatusCode() === 200 || $addDoc->getStatusCode() === 201) {
                    echo "  ✓ Added: " . $doc['id'] . "\n";
                }
            }
            
            echo "  Waiting 3 seconds for indexing...\n";
            sleep(3);
            
            // Search for root term and get relevance order
            echo "\n  Searching for root term '$uniqueRoot'...\n";
            $rootSearch = $client->executeRequest('GET', 
                '/collections/' . urlencode($testCollection) . '/search?q=' . urlencode($uniqueRoot) . '&query_by=title,content');
            $rootResults = $rootSearch->getBody();
            
            // Search for synonym term and get relevance order
            echo "  Searching for synonym term '$synonymTerm'...\n";
            $synSearch = $client->executeRequest('GET', 
                '/collections/' . urlencode($testCollection) . '/search?q=' . urlencode($synonymTerm) . '&query_by=title,content');
            $synResults = $synSearch->getBody();
            
            // Extract document IDs and scores from both searches
            $rootOrder = [];
            $rootScores = [];
            if (isset($rootResults['hits']) && is_array($rootResults['hits'])) {
                foreach ($rootResults['hits'] as $idx => $hit) {
                    $hitDocId = $hit['document']['id'] ?? $hit['id'] ?? null;
                    $score = $hit['_text_match'] ?? $hit['text_match'] ?? $hit['score'] ?? 0;
                    if ($hitDocId && (strpos($hitDocId, 'test_sort_doc') === 0)) {
                        $rootOrder[] = $hitDocId;
                        $rootScores[$hitDocId] = $score;
                    }
                }
            }
            
            $synOrder = [];
            $synScores = [];
            if (isset($synResults['hits']) && is_array($synResults['hits'])) {
                foreach ($synResults['hits'] as $idx => $hit) {
                    $hitDocId = $hit['document']['id'] ?? $hit['id'] ?? null;
                    $score = $hit['_text_match'] ?? $hit['text_match'] ?? $hit['score'] ?? 0;
                    if ($hitDocId && (strpos($hitDocId, 'test_sort_doc') === 0)) {
                        $synOrder[] = $hitDocId;
                        $synScores[$hitDocId] = $score;
                    }
                }
            }
            
            // Compare results
            echo "\n  Results comparison:\n";
            echo "  Root term search ('$uniqueRoot'):\n";
            foreach ($rootOrder as $idx => $docId) {
                echo "    " . ($idx + 1) . ". $docId (score: " . ($rootScores[$docId] ?? 'N/A') . ")\n";
            }
            
            echo "  Synonym term search ('$synonymTerm'):\n";
            foreach ($synOrder as $idx => $docId) {
                echo "    " . ($idx + 1) . ". $docId (score: " . ($synScores[$docId] ?? 'N/A') . ")\n";
            }
            
            // Check if order matches (Elasticsearch behavior: order can differ based on term frequencies)
            $orderMatches = ($rootOrder === $synOrder);
            $allDocsFound = (count($rootOrder) === count($synOrder) && count($rootOrder) === count($testDocs));
            
            if ($allDocsFound) {
                echo "\n  ✓ All documents found in both searches\n";
                if ($orderMatches) {
                    echo "  ✓ Relevance order matches between root and synonym searches\n";
                    echo "    (Note: In Elasticsearch, order can differ based on term frequencies - this is correct behavior)\n";
                } else {
                    echo "  ⚠ Relevance order differs between root and synonym searches\n";
                    echo "    (This is expected Elasticsearch behavior - scores can differ based on term frequencies)\n";
                    echo "    Root order: " . implode(', ', $rootOrder) . "\n";
                    echo "    Synonym order: " . implode(', ', $synOrder) . "\n";
                }
            } else {
                echo "\n  ✗ WARNING: Not all documents found in both searches\n";
                echo "    Root search found: " . count($rootOrder) . " documents\n";
                echo "    Synonym search found: " . count($synOrder) . " documents\n";
                echo "    Expected: " . count($testDocs) . " documents\n";
            }
            
            // Cleanup: Delete test documents
            echo "\n  Cleanup: Deleting test documents...\n";
            foreach ($testDocs as $doc) {
                $client->executeRequest('DELETE', 
                    '/collections/' . urlencode($testCollection) . '/documents/' . $doc['id']);
            }
            echo "  ✓ Test documents deleted\n\n";
            
            // Cleanup: Delete original test document
            echo "Cleanup: Deleting test document\n";
            $client->executeRequest('DELETE', 
                '/collections/' . urlencode($testCollection) . '/documents/' . $docId);
            echo "✓ Test document deleted\n\n";
        }
        
        // Cleanup: Delete test synonym
        echo "Cleanup: Deleting test synonym\n";
        $client->executeRequest('DELETE', 
            '/collections/' . urlencode($testCollection) . '/synonyms/' . $testSynId);
        echo "✓ Test synonym deleted\n\n";
    }
    
} catch (Exception $e) {
    echo "✗ ERROR in search verification: " . $e->getMessage() . "\n\n";
}

// ----------------------------------------------------------------====================================
// SUMMARY
// ----------------------------------------------------------------====================================
echo str_repeat("#", 70) . "\n";
echo "# TESTING COMPLETE\n";
echo str_repeat("#", 70) . "\n\n";
echo "Collection used: $testCollection\n";
echo "All tests completed!\n\n";
