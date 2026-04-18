<?php
/*
 * SQL Examples
 *
 * Usage:
 *   php examples/sql.php [base_url] [token]
 */

require_once __DIR__ . '/../lib/autoload.php';

use Hlquery\Client;

$baseUrl = $argv[1] ?? 'http://localhost:9200';
$token = $argv[2] ?? null;
$collection = 'php_sql_example_' . getmypid();

$client = new Client($baseUrl);

if ($token) {
    $client->setAuthToken($token, 'bearer');
}

function printResponse($label, $response)
{
    echo $label . " (" . $response->getStatusCode() . ")\n";
    echo json_encode($response->getBody(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n\n";
}

$client->collections()->delete($collection);

$create = $client->collections()->create($collection, [
    'fields' => [
        ['name' => 'title', 'type' => 'string'],
        ['name' => 'category', 'type' => 'string'],
        ['name' => 'score', 'type' => 'float'],
        ['name' => 'active', 'type' => 'bool'],
    ],
]);
printResponse('Create collection', $create);

$client->documents()->import($collection, [
    ['id' => 'doc_1', 'title' => 'Algebra basics', 'category' => 'math', 'score' => 10.0, 'active' => true],
    ['id' => 'doc_2', 'title' => 'Geometry proofs', 'category' => 'math', 'score' => 20.0, 'active' => true],
    ['id' => 'doc_3', 'title' => 'Cooking basics', 'category' => 'food', 'score' => 30.0, 'active' => false],
]);

$select = $client->sqlSearch(
    $collection,
    "SELECT id, title, score FROM {$collection} WHERE title LIKE '%basics%' ORDER BY score DESC;"
);
printResponse('Collection SQL SELECT', $select);

$aggregate = $client->sql(
    "SELECT category, COUNT(*) AS total_docs, AVG(score) AS avg_score FROM {$collection} GROUP BY category ORDER BY total_docs DESC, category ASC;"
);
printResponse('Top-level SQL aggregate', $aggregate);

$insert = $client->execSql(
    "INSERT INTO {$collection} (id, title, category, score, active) VALUES ('doc_4', 'Inserted via SQL', 'ops', 40.0, true);"
);
printResponse('Top-level SQL INSERT', $insert);

$showCollections = $client->sql('SHOW COLLECTIONS;');
printResponse('SHOW COLLECTIONS', $showCollections);

$cleanup = $client->collections()->delete($collection);
printResponse('Cleanup collection', $cleanup);
