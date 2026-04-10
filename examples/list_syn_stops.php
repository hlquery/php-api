<?php
/*
 * hlquery PHP API - List All Synonyms and Stopwords
 * 
 * This script provides an organized overview of all synonyms and stopwords
 * across all collections in your hlquery server.
 * 
 * What it does:
 * - Fetches all collections from the server
 * - Retrieves all synonyms and stopwords for each collection
 * - Displays them in an organized, easy-to-read format
 * - Provides summary statistics
 * 
 * Usage: 
 *   php list_syn_stops.php [token]
 * 
 * Examples:
 *   php list_syn_stops.php              # List all synonyms and stopwords
 *   php list_syn_stops.php my_token     # List with authentication token
 * 
 * Output format:
 * - Collections are listed alphabetically
 * - Each collection shows:
 *   * All synonym groups (root term + synonyms list)
 *   * All stopwords (displayed in columns)
 * - Summary statistics at the end
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
// $argv[1] = authentication token (optional)
$testToken = isset($argv[1]) ? $argv[1] : null;

// Initialize the hlquery client
$client = new Client($baseUrl, ['token' => $testToken]);

// ----------------------------------------------------------------====================================
// MAIN SCRIPT
// ----------------------------------------------------------------====================================

echo "=== hlquery Synonyms & Stopwords List ===\n\n";

try {
    // ------------------------------------------------------------------------
    // Step 1: Fetch all collections
    // ------------------------------------------------------------------------
    // We need to know which collections exist before we can list their
    // synonyms and stopwords
    echo "Fetching collections...\n";
    $collectionsResponse = $client->listCollections(0, 1000);  // Get up to 1000 collections
    
    if ($collectionsResponse->getStatusCode() !== 200) {
        echo "✗ Failed to fetch collections\n";
        exit(1);
    }
    
    // Extract collections array from response
    // Response format: {"collections": [{"name": "collection1"}, ...]}
    $collectionsBody = $collectionsResponse->getBody();
    $collections = $collectionsBody['collections'] ?? [];
    
    if (empty($collections)) {
        echo "No collections found.\n";
        exit(0);
    }
    
    echo "Found " . count($collections) . " collection(s)\n\n";
    
    // ------------------------------------------------------------------------
    // Step 2: Fetch all synonyms across all collections
    // ------------------------------------------------------------------------
    // GET /synonyms returns synonyms for all collections in one request
    echo "Fetching all synonyms...\n";
    $allSynonymsResponse = $client->executeRequest('GET', '/synonyms');
    $allSynonyms = [];
    if ($allSynonymsResponse->getStatusCode() === 200) {
        $allSynonymsBody = $allSynonymsResponse->getBody();
        // Response format: {"collections": [{"collection": "name", "synonyms": [...]}, ...]}
        $allSynonyms = $allSynonymsBody['collections'] ?? [];
    }
    
    // ------------------------------------------------------------------------
    // Step 3: Fetch all stopwords across all collections
    // ------------------------------------------------------------------------
    // GET /stopwords returns stopwords for all collections in one request
    echo "Fetching all stopwords...\n";
    $allStopwordsResponse = $client->executeRequest('GET', '/stopwords');
    $allStopwords = [];
    if ($allStopwordsResponse->getStatusCode() === 200) {
        $allStopwordsBody = $allStopwordsResponse->getBody();
        // Response format: {"collections": [{"collection": "name", "stopwords": [...]}, ...]}
        $allStopwords = $allStopwordsBody['collections'] ?? [];
    }
    
    echo "\n";
    echo str_repeat("=", 80) . "\n";
    echo "SYNONYMS & STOPWORDS BY COLLECTION\n";
    echo str_repeat("=", 80) . "\n\n";
    
    // Initialize counters for summary statistics
    $totalSynonyms = 0;
    $totalStopwords = 0;
    $collectionsWithSynonyms = 0;
    $collectionsWithStopwords = 0;
    
    // ------------------------------------------------------------------------
    // Step 4: Organize data by collection for easy lookup
    // ------------------------------------------------------------------------
    // Convert the API response into a collection-name -> data mapping
    // This makes it easier to display data per collection
    
    // Build synonyms lookup: collection_name -> array of synonym groups
    $synonymsByCollection = [];
    foreach ($allSynonyms as $synData) {
        $collName = $synData['collection'] ?? '';
        if ($collName) {
            $synonymsByCollection[$collName] = $synData['synonyms'] ?? [];
        }
    }
    
    // Build stopwords lookup: collection_name -> array of stopwords
    $stopwordsByCollection = [];
    foreach ($allStopwords as $stopData) {
        $collName = $stopData['collection'] ?? '';
        if ($collName) {
            $stopwordsByCollection[$collName] = $stopData['stopwords'] ?? [];
        }
    }
    
    // ------------------------------------------------------------------------
    // Step 5: Display synonyms and stopwords for each collection
    // ------------------------------------------------------------------------
    // Iterate through all collections and display their synonyms/stopwords
    foreach ($collections as $collection) {
        // Extract collection name (handle both array and string formats)
        $collName = is_array($collection) ? ($collection['name'] ?? '') : $collection;
        if (empty($collName)) continue;
        
        // Get synonyms and stopwords for this collection
        $synonyms = $synonymsByCollection[$collName] ?? [];
        $stopwords = $stopwordsByCollection[$collName] ?? [];
        
        // Only show collections that have synonyms or stopwords
        // (Skip empty collections to keep output clean)
        if (empty($synonyms) && empty($stopwords)) {
            continue;
        }
        
        // Display collection header
        echo str_repeat("-", 80) . "\n";
        echo "Collection: " . strtoupper($collName) . "\n";
        echo str_repeat("-", 80) . "\n\n";
        
        // --------------------------------------------------------------------
        // Display Synonyms Section
        // --------------------------------------------------------------------
        if (!empty($synonyms)) {
            $collectionsWithSynonyms++;
            $synCount = count($synonyms);
            $totalSynonyms += $synCount;
            
            echo "  SYNONYMS (" . $synCount . "):\n";
            echo "  " . str_repeat("-", 78) . "\n";
            
            // Display each synonym group
            // Format: ID, Root term, Synonyms list, Creation date
            foreach ($synonyms as $index => $syn) {
                // Extract synonym data
                $synId = $syn['id'] ?? ($syn['root'] ?? 'N/A');      // Synonym group ID
                $root = $syn['root'] ?? 'N/A';                       // Primary/root term
                $synList = $syn['synonyms'] ?? [];                   // Array of equivalent terms
                
                // Display synonym group information
                echo "  " . ($index + 1) . ". ID: " . $synId . "\n";
                echo "     Root: \"" . $root . "\"\n";
                echo "     Synonyms: ";
                
                // Format synonyms list as comma-separated quoted strings
                if (!empty($synList)) {
                    $synStr = implode(', ', array_map(function($s) {
                        return '"' . $s . '"';
                    }, $synList));
                    echo $synStr . "\n";
                } else {
                    echo "(none)\n";
                }
                
                // Show creation timestamp if available
                if (isset($syn['created_at'])) {
                    echo "     Created: " . $syn['created_at'] . "\n";
                }
                echo "\n";
            }
        } else {
            echo "  SYNONYMS: (none)\n\n";
        }
        
        // --------------------------------------------------------------------
        // Display Stopwords Section
        // --------------------------------------------------------------------
        if (!empty($stopwords)) {
            $collectionsWithStopwords++;
            $stopCount = count($stopwords);
            $totalStopwords += $stopCount;
            
            echo "  STOPWORDS (" . $stopCount . "):\n";
            echo "  " . str_repeat("-", 78) . "\n";
            
            // Extract just the word strings from stopwords array
            // Stopwords can be in format: [{"word": "the", ...}] or ["the", "a", ...]
            $words = [];
            foreach ($stopwords as $stop) {
                if (is_array($stop)) {
                    $word = $stop['word'] ?? '';
                } else {
                    $word = $stop;
                }
                if ($word) {
                    $words[] = $word;
                }
            }
            
            // Display stopwords in a compact column format (4 columns)
            // This makes it easier to scan through many stopwords
            $cols = 4;
            $rows = ceil(count($words) / $cols);
            
            for ($row = 0; $row < $rows; $row++) {
                echo "     ";
                for ($col = 0; $col < $cols; $col++) {
                    $idx = $row * $cols + $col;
                    if ($idx < count($words)) {
                        // Format: "word" with fixed width for alignment
                        printf("%-18s", '"' . $words[$idx] . '"');
                    }
                }
                echo "\n";
            }
            echo "\n";
        } else {
            echo "  STOPWORDS: (none)\n\n";
        }
        
        echo "\n";
    }
    
    // ------------------------------------------------------------------------
    // Step 6: Display Summary Statistics
    // ------------------------------------------------------------------------
    echo str_repeat("=", 80) . "\n";
    echo "SUMMARY\n";
    echo str_repeat("=", 80) . "\n\n";
    
    // Overall statistics
    echo "Total Collections: " . count($collections) . "\n";
    echo "Collections with Synonyms: " . $collectionsWithSynonyms . "\n";
    echo "Collections with Stopwords: " . $collectionsWithStopwords . "\n";
    echo "Total Synonyms: " . $totalSynonyms . "\n";
    echo "Total Stopwords: " . $totalStopwords . "\n";
    
    // ------------------------------------------------------------------------
    // Step 7: List collections without synonyms or stopwords
    // ------------------------------------------------------------------------
    // This helps identify collections that might benefit from synonyms/stopwords
    $collectionsWithout = [];
    foreach ($collections as $collection) {
        $collName = is_array($collection) ? ($collection['name'] ?? '') : $collection;
        if (empty($collName)) continue;
        
        // Check if this collection has synonyms or stopwords
        $hasSyn = !empty($synonymsByCollection[$collName] ?? []);
        $hasStop = !empty($stopwordsByCollection[$collName] ?? []);
        
        // If it has neither, add to the list
        if (!$hasSyn && !$hasStop) {
            $collectionsWithout[] = $collName;
        }
    }
    
    // Display collections without synonyms/stopwords (if any)
    if (!empty($collectionsWithout)) {
        echo "\nCollections without synonyms or stopwords (" . count($collectionsWithout) . "):\n";
        echo "  " . implode(", ", $collectionsWithout) . "\n";
        echo "  (These collections don't have any synonyms or stopwords configured)\n";
    }
    
    echo "\n";
    
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
