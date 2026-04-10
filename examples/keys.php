<?php
/**
 * hlquery PHP Example - API Keys Management
 * 
 * This example demonstrates how to create, list, and delete API keys.
 */

require_once __DIR__ . '/../lib/autoload.php';

use Hlquery\Client;

// Replace with your hlquery master admin token
$adminToken = 'your_admin_token_here';
$baseUrl = 'http://localhost:9200';

try {
    $client = new Client($baseUrl);
    $client->setAuthToken($adminToken);

    echo "1. Creating a scoped search key for 'products' collection...\n";
    $createResp = $client->keys()->create([
        'description' => 'Public search key for products',
        'collections' => ['products'],
        'actions' => ['search'],
        'embedded_filters' => 'is_public:=true'
    ]);

    if ($createResp->getStatusCode() === 201) {
        $body = $createResp->getBody();
        $newKey = $body['key'];
        $keyId = $body['id'];
        echo "   ✓ Key created: $newKey\n";
        echo "   ✓ Key ID: $keyId\n";

        echo "\n2. Listing all API keys...\n";
        $listResp = $client->keys()->list();
        echo "   ✓ Found " . count($listResp->getBody()['keys']) . " keys\n";

        echo "\n3. Getting key details...\n";
        $getResp = $client->keys()->get($keyId);
        echo "   ✓ Key description: " . $getResp->getBody()['description'] . "\n";

        echo "\n4. Updating key permissions...\n";
        $client->keys()->update($keyId, [
            'actions' => ['search', 'create']
        ]);
        echo "   ✓ Permissions updated\n";

        echo "\n5. Deleting the key...\n";
        $client->keys()->delete($keyId);
        echo "   ✓ Key deleted\n";
    } else {
        echo "   ✗ Failed to create key: " . json_encode($createResp->getBody()) . "\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
