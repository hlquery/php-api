<div align="center">
  <img src="https://docs.hlquery.com/img/hlquery/2.png" alt="hlquery logo" width="200">
</div>

<div align="center">

**A modular PHP client library for hlquery, designed with a familiar and intuitive API structure.**

[![Twitter Follow](https://img.shields.io/twitter/url/https/x.com/hlquery.svg?style=social&label=Follow%20%40hlquery)](https://x.com/hlquery)
[![Linux Build](https://github.com/hlquery/php-api/workflows/Linux%20build/badge.svg)](https://github.com/hlquery/php-api/actions)
[![macOS Build](https://github.com/hlquery/php-api/workflows/macOS%20Build/badge.svg)](https://github.com/hlquery/php-api/actions)
[![Commit Activity](https://img.shields.io/github/commit-activity/m/hlquery/php-api)](https://github.com/hlquery/php-api/pulse)
[![GitHub stars](https://img.shields.io/github/stars/hlquery/php-api?style=social)](https://github.com/hlquery/php-api/stargazers)
[![License](https://img.shields.io/badge/License-BSD%203--Clause-blue.svg)](https://opensource.org/licenses/BSD-3-Clause)

[Documentation](https://docs.hlquery.com) • [hlquery](https://github.com/hlquery/hlquery) • [Discord](https://discord.hlquery.com)

</div>


### hlquery PHP API Client

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

### Multi-search

```php
$results = $client->searchApi()->multiSearch([
    [
        'collection' => 'products',
        'q' => 'running',
        'query_by' => 'title,description',
    ],
    [
        'collection' => 'products',
        'q' => 'backpack',
        'query_by' => 'title,description',
    ],
]);
```

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
