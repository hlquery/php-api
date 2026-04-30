<div align="center">
  <img src="https://docs.hlquery.com/img/hlquery/2.png" alt="hlquery logo" width="200">
</div>

<div align="center">

**A modular PHP client library for hlquery, designed with a familiar and intuitive API structure.**

[![Follow hlquery](https://img.shields.io/badge/Follow-%40hlquery-blue?logo=x&logoColor=white)](https://x.com/hlquery)
[![Commit Activity](https://img.shields.io/github/commit-activity/m/hlquery/hlquery)](https://github.com/hlquery/php-api/pulse)
[![hlquery](https://img.shields.io/badge/GitHub-hlquery-181717?logo=github&logoColor=white)](https://github.com/hlquery/hlquery/stargazers)
[![License](https://img.shields.io/badge/License-BSD%203--Clause-blue.svg)](https://opensource.org/licenses/BSD-3-Clause)

</div>


Compact PHP client for hlquery. No framework required, no extra runtime dependencies beyond `curl` and `json`.

Included in the current client:

- Collections
- Documents
- Search
- SQL
- Keys
- Aliases
- Overrides
- Synonyms
- Stopwords
- System helpers, including `etc()`

### Install

Requirements:

- PHP `>= 7.0`
- `ext-curl`
- `ext-json`

Local usage:

```php
require_once __DIR__ . '/lib/autoload.php';

use Hlquery\Client;

$client = new Client(getenv('HLQ_BASE_URL') ?: (getenv('HLQUERY_BASE_URL') ?: 'http://localhost:9200'));
```

Composer:

```bash
composer require hlquery/php-client
```

```php
require_once __DIR__ . '/vendor/autoload.php';

use Hlquery\Client;

$client = new Client('http://localhost:9200');
```

Auth:

```php
$client = new Client('http://localhost:9200', [
    'token' => 'your_token_here',
    'auth_method' => 'bearer', // or 'api-key'
]);

// or later
$client->setAuthToken('your_token_here', 'bearer');
```

### Quick Start

```php
require_once __DIR__ . '/lib/autoload.php';

use Hlquery\Client;

$client = new Client('http://localhost:9200');

$health = $client->health();
if ($health->isSuccess()) {
    $body = $health->getBody();
    echo "status: " . ($body['status'] ?? 'ok') . PHP_EOL;
}

$collections = $client->listCollections(0, 10);
print_r($collections->getBody());
```

### Example API Responses

Captured from a local `http://localhost:9200` server.

`$client->health()->getBody()`:

```json
{
  "server": "hlquery",
  "status": "ok",
  "version": "1.0"
}
```

`$client->search('readme_demo', ['q' => 'search', 'query_by' => 'title,content', 'limit' => 10])->getBody()`:

```json
{
  "hits": [
    {
      "document": {
        "id": "doc-2",
        "title": "Search Engineering Notes"
      },
      "highlights": {
        "title": "<em>Search</em> Engineering Notes"
      }
    }
  ],
  "found": 1
}
```

### Reduce Text Example

Use `executeRequest()` to call custom module routes directly:

```php
$moduleResponse = $client->executeRequest('GET', '/modules/<name>/<route>', null, [
    'q' => 'example query',
]);

print_r($moduleResponse->getBody());
```

### Common Examples

### Create a collection

This client uses a service-based API similar to Typesense. The main difference is that hlquery expects the collection name as the first argument, instead of inside the schema array.

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use Hlquery\Client;

$client = new Client('http://localhost:9200');

$collections = $client->collections();

$schema = [
    'fields' => [
        ['name' => 'id', 'type' => 'string'],
        ['name' => 'title', 'type' => 'string'],
        ['name' => 'author', 'type' => 'string'],
        ['name' => 'year', 'type' => 'int32'],
    ],
];

$response = $collections->create('books', $schema);

print_r($response->getBody());
```

### Add documents

```php
$documents = $client->documents();

$documents->add('books', [
    'id' => '1',
    'title' => 'The Hobbit',
    'author' => 'J.R.R. Tolkien',
    'year' => 1937,
]);

$documents->import('books', [
    [
        'id' => '2',
        'title' => 'Dune',
        'author' => 'Frank Herbert',
        'year' => 1965,
    ],
    [
        'id' => '3',
        'title' => 'Neuromancer',
        'author' => 'William Gibson',
        'year' => 1984,
    ],
]);
```

### Search without `query_by`

If `q` is set and `query_by` is omitted, the client tries to use the collection's `searchable_fields`.

```php
$results = $client->search('books', [
    'q' => 'tolkien',
    'limit' => 10,
]);
```

### SQL

Use the SQL service object for a nested API style:

```php
$sql = $client->sql();
```

Basic SQL example:

```php
$sql = $client->sql();

$results = $sql->query('products', 'SELECT id, title FROM products ORDER BY title ASC LIMIT 3;');

if ($results->isSuccess()) {
    $body = $results->getBody();
    print_r($body['rows'] ?? []);
}
```

Collection-bound SQL `SELECT`:

```php
$sql = $client->sql();

$results = $sql->query(
    'products',
    'SELECT id, title, price FROM products WHERE price >= 100 ORDER BY price DESC LIMIT 5;'
);
```

Top-level SQL execution:

```php
$sql = $client->sql();

$rows = $sql->raw('SHOW COLLECTIONS;');

$insert = $sql->execute(
    "INSERT INTO products (id, title, price) VALUES ('sku-9', 'Camp Stove', 89);"
);
```

### Vector Search Notes

For vector search, the important part is usually not the raw embedding array in the example, but the search knobs around it.

- `field_name` must match the vector field stored in your collection.
- `topk` controls how many nearest matches you ask for back.
- `threshold` can cut off weak matches early.
- `nprobe` is the main recall/speed tradeoff on IVF-style indexes.

Briefly: a higher `nprobe` checks more partitions, which usually improves recall but costs more CPU and latency. Start small, then raise it only if you are missing obvious neighbors. If you are tuning quality, `nprobe` is one of the first parameters worth testing.

### Read a document

```php
$doc = $client->getDocument('books', '1');
print_r($doc->getBody());
```

### Update or delete a document

```php
$client->documents()->update('books', '1', [
    'year' => 1938,
]);

$client->documents()->delete('products', 'sku-3');
```

### Synonyms

```php
$client->synonyms()->create('products', 'shoe_terms', [
    'root' => 'shoe',
    'synonyms' => ['sneaker', 'trainer'],
]);

$synonyms = $client->synonyms()->list('products');
print_r($synonyms->getBody());
```

Global synonyms are also supported:

```php
$client->synonyms()->createGlobal('global_shoe_terms', [
    'root' => 'shoe',
    'synonyms' => ['sneaker', 'trainer'],
]);
```

