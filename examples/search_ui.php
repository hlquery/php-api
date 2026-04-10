<?php
/*
 * Search script for hlquery art collection
 * Usage: php search_ui.php "your search query"
 * Or: Access via web: search_ui.php?q=your+search+query
 */

// Configuration
$API_BASE_URL = 'http://localhost:9200';
$COLLECTION_NAME = 'bench_collection_0';

/*
 * Safely truncate HTML text without cutting tags in the middle
 * @param string $html The HTML string to truncate
 * @param int $maxLength Maximum length of the truncated string
 * @return string Truncated HTML with complete tags
 */
function truncateHtmlSafely($html, $maxLength = 200) {
    if (strlen($html) <= $maxLength) {
        return $html;
    }
    
    // Truncate to max length
    $truncated = substr($html, 0, $maxLength);
    
    // Find the last complete tag before the truncation point
    $lastTagEnd = strrpos($truncated, '>');
    $lastTagStart = strrpos($truncated, '<');
    
    // If we cut in the middle of a tag (there's a < without a > after it)
    if ($lastTagStart !== false && ($lastTagEnd === false || $lastTagStart > $lastTagEnd)) {
        // Find the start of this incomplete tag and truncate before it
        $truncated = substr($html, 0, $lastTagStart);
    }
    
    // Count open and closed <em> tags
    $openEmCount = substr_count($truncated, '<em>');
    $closeEmCount = substr_count($truncated, '</em>');
    $unclosedEm = $openEmCount - $closeEmCount;
    
    // If we have unclosed <em> tags, try to find and include the closing tag(s)
    if ($unclosedEm > 0) {
        $remaining = substr($html, strlen($truncated));
        $pos = 0;
        $found = 0;
        while ($found < $unclosedEm && ($pos = strpos($remaining, '</em>', $pos)) !== false) {
            $found++;
            $pos += 5; // Move past '</em>'
        }
        if ($found > 0) {
            // Include all necessary closing tags
            $lastClosePos = strrpos(substr($remaining, 0, $pos), '</em>');
            if ($lastClosePos !== false) {
                $truncated = substr($html, 0, strlen($truncated) + $lastClosePos + 5);
            }
        } else {
            // Can't find closing tag, remove any incomplete <em> tag at the end
            $truncated = preg_replace('/<em>[^<]*$/', '', $truncated);
        }
    }
    
    return $truncated;
}

// Get search query from command line or GET parameter
$query = '';
$debugMode = false;
if (php_sapi_name() === 'cli') {
    // Command line mode
    if (isset($argv[1])) {
        $query = $argv[1];
        $debugMode = isset($argv[2]) && $argv[2] === '--debug';
        
        // Note: If you want to search for an exact phrase with quotes, you need to escape them:
        // php search.php '"exact phrase"'  or  php search.php "\"exact phrase\""
        // The outer quotes are shell quotes, inner quotes are part of the search query
    } else {
        echo "Usage: php search_ui.php \"your search query\" [--debug]\n";
        echo "Example: php search_ui.php \"data\"\n";
        echo "Example with debug: php search_ui.php \"data\" --debug\n";
        echo "Example with phrase search: php search_ui.php '\"exact phrase\"'  or  php search_ui.php \"\\\"exact phrase\\\"\"\n";
        exit(1);
    }
} else {
    // Web mode
    $query = isset($_GET['q']) ? $_GET['q'] : '';
    $debugMode = isset($_GET['debug']);
    if (empty($query)) {
        echo "<h1>Search Art Collection</h1>";
        echo "<form method='GET'>";
        echo "<input type='text' name='q' placeholder='Enter search query...' style='padding: 10px; width: 300px;' />";
        echo "<label><input type='checkbox' name='debug' value='1' /> Debug mode</label><br><br>";
        echo "<button type='submit' style='padding: 10px 20px;'>Search</button>";
        echo "</form>";
        exit;
    }
}

// Validate query
if (empty(trim($query))) {
    die("Error: Search query cannot be empty\n");
}

// Build search URL
$encodedCollection = urlencode($COLLECTION_NAME);
$encodedQuery = urlencode($query);
$searchUrl = "$API_BASE_URL/collections/$encodedCollection/documents/search";

// Prepare search parameters
$params = [
    'q' => $query,
    'query_by' => '*',  // Search all fields
    'limit' => 10,
    'highlight' => true,
    'include_created_at' => true,
    'sort_by' => '_text_match:desc'  // Sort by relevance
];

// DEBUG: Show request details
if ($debugMode) {
    echo "\n=== DEBUG: Request Details ===\n";
    echo "URL: $searchUrl\n";
    echo "Method: POST\n";
    echo "Parameters:\n";
    print_r($params);
    echo "JSON Params: " . json_encode($params, JSON_PRETTY_PRINT) . "\n";
    echo "=== END DEBUG ===\n\n";
}

// Make POST request
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $searchUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

// Handle errors
if ($error) {
    die("Error: cURL error: $error\n");
}

if ($httpCode !== 200) {
    echo "DEBUG: HTTP Status Code: $httpCode\n";
    echo "DEBUG: Raw Response:\n$response\n\n";
    die("Error: HTTP $httpCode\nResponse: $response\n");
}

// DEBUG: Show raw JSON response
if ($debugMode) {
    echo "\n=== DEBUG: Raw JSON Response ===\n";
    echo "Response Length: " . strlen($response) . " bytes\n";
    echo "Response:\n";
    // Pretty print JSON if possible
    $prettyJson = json_encode(json_decode($response), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($prettyJson !== false && $prettyJson !== 'null') {
        echo $prettyJson;
    } else {
        echo $response;
    }
    echo "\n=== END DEBUG ===\n\n";
}

// Parse JSON response
$data = json_decode($response, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo "DEBUG: JSON Parse Error: " . json_last_error_msg() . "\n";
    echo "DEBUG: Raw Response (first 500 chars):\n" . substr($response, 0, 500) . "\n\n";
    die("Error: Failed to parse JSON response: " . json_last_error_msg() . "\n");
}

    // DEBUG: Show parsed data structure
    if ($debugMode) {
        echo "\n=== DEBUG: Parsed Data Structure ===\n";
        echo "Keys in response: " . implode(', ', array_keys($data)) . "\n";
        if (isset($data['hits'])) {
            echo "Number of hits: " . count($data['hits']) . "\n";
            echo "Found: " . ($data['found'] ?? 'N/A') . "\n";
            if (count($data['hits']) > 0) {
                echo "\nFirst hit structure:\n";
                print_r($data['hits'][0]);
                echo "\nFirst hit document keys: " . (isset($data['hits'][0]['document']) ? implode(', ', array_keys($data['hits'][0]['document'])) : 'N/A') . "\n";
                if (isset($data['hits'][0]['document']['timestamp'])) {
                    echo "First hit has timestamp: " . $data['hits'][0]['document']['timestamp'] . "\n";
                }
                if (isset($data['hits'][0]['document']['created_at'])) {
                    echo "First hit has created_at: " . $data['hits'][0]['document']['created_at'] . "\n";
                }
            }
        }
        echo "\n=== END DEBUG ===\n\n";
    }

// Display results
if (php_sapi_name() === 'cli') {
    // CLI output
    // Show query with quotes preserved if they were in the original query
    $displayQuery = $query;
    // Detect if this is a phrase search (starts and ends with quotes)
    $isPhraseSearch = (strlen($query) >= 2 && $query[0] === '"' && $query[strlen($query)-1] === '"');
    if ($isPhraseSearch) {
        echo "\n=== Search Results for exact phrase: $displayQuery ===\n\n";
    } else {
        echo "\n=== Search Results for: \"$displayQuery\" ===\n\n";
    }
    
    if (isset($data['hits']) && is_array($data['hits'])) {
        $found = $data['found'] ?? count($data['hits']);
        $time = $data['search_time_ms'] ?? 0;
        
        echo "Found: $found results (in " . ($time / 1000) . "s)\n\n";
        
        foreach ($data['hits'] as $index => $hit) {
            $doc = $hit['document'] ?? $hit;
            $score = $hit['_text_match'] ?? $hit['text_match'] ?? 0;
            $id = $doc['id'] ?? 'N/A';
            $title = $doc['title'] ?? $doc['name'] ?? $id;
            
            echo "--- Result " . ($index + 1) . " (Score: " . number_format($score, 2) . ") ---\n";
            echo "ID: $id\n";
            echo "Title: $title\n";
            
            // Show highlights if available
            if (isset($hit['highlights']) && is_array($hit['highlights'])) {
                foreach ($hit['highlights'] as $field => $highlight) {
                    if ($field !== 'title' && $field !== 'name' && $field !== 'id') {
                        $text = is_array($highlight) ? implode(' ... ', $highlight) : $highlight;
                        // Preserve <em> tags as returned by API (don't strip them)
                        // Only remove potentially dangerous tags, keep <em> and </em>
                        $text = strip_tags($text, '<em>');
                        // Show full content - no truncation
                        echo "$field: " . $text . "\n";
                    }
                }
            }
            
            // Fallback: Show content field if no highlights
            if (!isset($hit['highlights']) || empty($hit['highlights'])) {
                if (isset($doc['content'])) {
                    echo "content: " . substr($doc['content'], 0, 200) . "...\n";
                }
            }
            
            // Show created_at if available (from document or hit level)
            $createdAt = $doc['created_at'] ?? $hit['created_at'] ?? null;
            if ($createdAt) {
                echo "Created: " . $createdAt . "\n";
            } else {
                // DEBUG: Show if created_at was requested but not returned
                if ($debugMode) {
                    echo "DEBUG: created_at not found. Document keys: " . implode(', ', array_keys($doc)) . "\n";
                    echo "DEBUG: Hit keys: " . implode(', ', array_keys($hit)) . "\n";
                    if (isset($doc['timestamp'])) {
                        echo "DEBUG: timestamp field exists: " . $doc['timestamp'] . "\n";
                    }
                }
                echo "Created: (not available)\n";
            }
            
            echo "\n";
        }
    } else {
        echo "No results found.\n";
    }
} else {
    // Web output
    header('Content-Type: text/html; charset=utf-8');
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Search Results - Art Collection</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
            .container { max-width: 900px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
            h1 { color: #1a0dab; margin-bottom: 10px; }
            .search-form { margin-bottom: 20px; }
            .search-form input { padding: 10px; width: 400px; font-size: 16px; border: 1px solid #ddd; border-radius: 4px; }
            .search-form button { padding: 10px 20px; font-size: 16px; background: #1a0dab; color: white; border: none; border-radius: 4px; cursor: pointer; }
            .search-form button:hover { background: #1557b0; }
            .results-info { color: #70757a; margin-bottom: 20px; }
            .result { margin-bottom: 30px; padding-bottom: 20px; border-bottom: 1px solid #e2e8f0; }
            .result-title { color: #1a0dab; font-size: 20px; font-weight: 400; margin-bottom: 4px; text-decoration: none; }
            .result-title:hover { text-decoration: underline; }
            .result-id { color: #006621; font-size: 14px; margin-bottom: 3px; }
            .result-snippet { color: #545454; font-size: 14px; line-height: 1.58; margin-top: 3px; }
            .result-score { color: #70757a; font-size: 12px; margin-top: 5px; }
            .result-date { color: #70757a; font-size: 12px; }
            em { font-weight: bold; background: yellow; }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>Search Art Collection</h1>
            
            <div class="search-form">
                <form method="GET">
                    <input type="text" name="q" value="<?php echo htmlspecialchars($query); ?>" placeholder="Enter search query..." />
                    <button type="submit">Search</button>
                </form>
            </div>
            
            <?php
            if (isset($data['hits']) && is_array($data['hits'])) {
                $found = $data['found'] ?? count($data['hits']);
                $time = $data['search_time_ms'] ?? 0;
                
                echo "<div class='results-info'>";
                echo "About $found results (" . ($time / 1000) . " seconds)";
                echo "</div>";
                
                foreach ($data['hits'] as $hit) {
                    $doc = $hit['document'] ?? $hit;
                    $score = $hit['_text_match'] ?? $hit['text_match'] ?? 0;
                    $id = $doc['id'] ?? 'N/A';
                    $title = $doc['title'] ?? $doc['name'] ?? $id;
                    $createdAt = $doc['created_at'] ?? null;
                    
                    echo "<div class='result'>";
                    echo "<a href='#' class='result-title'>$title</a>";
                    echo "<div class='result-id'>$id</div>";
                    
                    // Show highlights if available
                    if (isset($hit['highlights']) && is_array($hit['highlights'])) {
                        foreach ($hit['highlights'] as $field => $highlight) {
                            if ($field !== 'title' && $field !== 'name' && $field !== 'id') {
                                $text = is_array($highlight) ? implode(' ... ', $highlight) : $highlight;
                                // Preserve <em> tags for web output (CSS will style them)
                                echo "<div class='result-snippet'>$text</div>";
                            }
                        }
                    }
                    
                    // Fallback: Show content field if no highlights
                    if (!isset($hit['highlights']) || empty($hit['highlights'])) {
                        if (isset($doc['content'])) {
                            echo "<div class='result-snippet'>" . htmlspecialchars($doc['content']) . "</div>";
                        }
                    }
                    
                    echo "<div class='result-score'>Relevance Score: " . number_format($score, 2) . "</div>";
                    if ($createdAt) {
                        echo "<div class='result-date'>Created: $createdAt</div>";
                    }
                    echo "</div>";
                }
            } else {
                echo "<p>No results found for \"$query\"</p>";
            }
            ?>
        </div>
    </body>
    </html>
    <?php
}
?>
