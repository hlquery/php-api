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


# hlquery PHP API Client

Compact PHP client for hlquery. No framework required, no extra runtime dependencies beyond `curl` and `json`.

Included in the current client:

- Collections
- Documents
- Search
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

$client = new Client('http://localhost:9200');
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

### Reduce Text Example

If the `ai_search` module is enabled, use `executeRequest()` to summarize a stored document:

```php
$summary = $client->executeRequest('GET', '/modules/ai_search/talk', null, [
    'q' => 'summarize onboarding guide in docs',
    'run' => 'true',
]);

print_r($summary->getBody());
```

### Common Examples

### Create a collection

```php
$schema = [
    'searchable_fields' => ['title', 'description'],
    'filterable_fields' => ['category', 'in_stock'],
    'sortable_fields' => ['price', 'rating'],
];

$res = $client->collections()->create('products', $schema);
print_r($res->getBody());
```

### Add documents

```php
$client->documents()->add('products', [
    'id' => 'sku-1',
    'title' => 'Trail Running Shoes',
    'description' => 'Lightweight shoes for mixed terrain',
    'category' => 'footwear',
    'price' => 129,
    'rating' => 4.7,
    'in_stock' => true,
]);

$client->documents()->import('products', [
    [
        'id' => 'sku-2',
        'title' => 'Waterproof Jacket',
        'description' => 'Breathable shell for wet weather',
        'category' => 'outerwear',
        'price' => 189,
    ],
    [
        'id' => 'sku-3',
        'title' => 'Daypack 20L',
        'description' => 'Compact pack for short hikes',
        'category' => 'bags',
        'price' => 79,
    ],
]);
```

### Search without `query_by`

If `q` is set and `query_by` is omitted, the client tries to use the collection's `searchable_fields`.

```php
$results = $client->search('products', [
    'q' => 'waterproof jacket',
    'limit' => 10,
]);
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
$doc = $client->getDocument('products', 'sku-1');
print_r($doc->getBody());
```

### Update or delete a document

```php
$client->documents()->update('products', 'sku-1', [
    'price' => 119,
    'in_stock' => false,
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
