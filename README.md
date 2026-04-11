<div align="center">
  <img src="https://docs.hlquery.com/img/hlquery/2.png" alt="hlquery logo" width="200">
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

## Install

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

## Quick Start

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

## Common Examples

### 1. Create a collection

```php
$schema = [
    'searchable_fields' => ['title', 'description'],
    'filterable_fields' => ['category', 'in_stock'],
    'sortable_fields' => ['price', 'rating'],
];

$res = $client->collections()->create('products', $schema);
print_r($res->getBody());
```

### 2. Add documents

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

### 3. Search

```php
$results = $client->search('products', [
    'q' => 'trail shoes',
    'query_by' => ['title', 'description'],
    'filter_by' => 'price:>100&&in_stock:true',
    'sort_by' => 'rating:desc',
    'limit' => 5,
]);

print_r($results->getBody());
```

### 4. Search without `query_by`

If `q` is set and `query_by` is omitted, the client tries to use the collection's `searchable_fields`.

```php
$results = $client->search('products', [
    'q' => 'waterproof jacket',
    'limit' => 10,
]);
```

### 5. Multi-search

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

### 6. Vector search

```php
$results = $client->vectorSearch('products', [
    'field_name' => 'embedding',
    'vector_query' => [0.12, 0.98, 0.44, 0.31],
    'limit' => 3,
    'include_distance' => true,
]);
```

### 7. Read a document

```php
$doc = $client->getDocument('products', 'sku-1');
print_r($doc->getBody());
```

### 8. Update or delete a document

```php
$client->documents()->update('products', 'sku-1', [
    'price' => 119,
    'in_stock' => false,
]);

$client->documents()->delete('products', 'sku-3');
```

### 9. Facet counts

```php
$facets = $client->documents()->facetCounts('products', [
    'q' => '*',
    'facet_by' => ['category'],
]);

print_r($facets->getBody());
```

### 10. Export documents

```php
$export = $client->documents()->export('products', [
    'filter_by' => 'category:footwear',
]);

print_r($export->getBody());
```

### 11. System and `etc` endpoints

```php
$health = $client->health();
$stats = $client->stats();
$etc = $client->etc();
$metrics = $client->metrics();
$status = $client->status();

print_r($etc->getBody());
```

Other system helpers available on `Client`:

- `ping()`
- `info()`
- `startup()`
- `bootStatus()`
- `connections()`
- `rocksdb()`
- `rocksdbInternal()`
- `docTotal()`
- `integrity()`
- `consistency()`
- `selfCheck()`
- `storageStatus()`

### 12. Global search

```php
$results = $client->searchApi()->globalSearch([
    'q' => 'running shoes',
    'query_by' => 'title,description',
    'limit' => 10,
]);
```

### 13. API keys

```php
$key = $client->keys()->create([
    'description' => 'Search-only key for products',
    'collections' => ['products'],
    'actions' => ['search'],
]);

$allKeys = $client->keys()->list();
print_r($allKeys->getBody());
```

### 14. Aliases

```php
$client->aliases()->create('products_live', [
    'collection_name' => 'products',
]);

$alias = $client->aliases()->get('products_live');
print_r($alias->getBody());
```

### 15. Synonyms

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

### 16. Stopwords

```php
$client->stopwords()->create('products', [
    'stopwords' => ['the', 'and', 'of'],
]);

$stopwords = $client->stopwords()->list('products');
print_r($stopwords->getBody());
```

Global stopwords are also supported:

```php
$client->stopwords()->createGlobal([
    'stopwords' => ['the', 'and', 'of'],
]);
```

### 17. Overrides

```php
$client->overrides()->create('products', 'promo_boost', [
    'rule' => [
        'query' => 'trail shoes',
        'match' => 'exact',
    ],
    'includes' => [
        ['id' => 'sku-1', 'position' => 1],
    ],
]);

$override = $client->overrides()->get('products', 'promo_boost');
print_r($override->getBody());
```

### 18. Raw requests

Use `executeRequest()` if you need an endpoint that does not have a wrapper yet.

```php
$res = $client->executeRequest('GET', '/synonyms');
print_r($res->getBody());
```

## Response Object

All calls return `Hlquery\Response`.

```php
$response = $client->health();

if ($response->isSuccess()) {
    print_r($response->getBody());
} else {
    echo $response->getError() . PHP_EOL;
}

echo $response->getStatusCode() . PHP_EOL;
print_r($response->getHeaders());
print_r($response->toArray());
```

Helpers:

- `getStatusCode()`
- `getBody()`
- `getHeaders()`
- `isSuccess()`
- `isError()`
- `getError()`
- `toArray()`

## Error Handling

```php
use Hlquery\AuthenticationException;
use Hlquery\RequestException;
use Hlquery\ValidationException;

try {
    $res = $client->search('products', ['q' => 'boots']);
    print_r($res->getBody());
} catch (ValidationException $e) {
    echo "validation error: " . $e->getMessage() . PHP_EOL;
} catch (AuthenticationException $e) {
    echo "auth error: " . $e->getMessage() . PHP_EOL;
} catch (RequestException $e) {
    echo "request error: " . $e->getMessage() . PHP_EOL;
}
```

## Notes

- `search()` uses `/collections/{name}/search`.
- `searchLegacy()` is available for older `/documents/search` callers.
- `query_by` can be a string or array.
- `facet_by`, `sort_by`, and highlight fields also accept strings or arrays.
- For string field values, avoid comma-delimited packed values. Prefer arrays or another separator.

Example:

```php
[
    'tags' => ['trail', 'lightweight', 'men'],
]
```

## Useful Entry Points

Object-style:

```php
$client->collections();
$client->documents();
$client->searchApi();
$client->keys();
$client->aliases();
$client->overrides();
$client->synonyms();
$client->stopwords();
```

Convenience methods:

```php
$client->listCollections();
$client->getCollection('products');
$client->getCollectionFields('products');
$client->listDocuments('products', ['limit' => 20]);
$client->getDocument('products', 'sku-1');
$client->search('products', ['q' => 'trail']);
$client->vectorSearch('products', ['field_name' => 'embedding']);
$client->etc();
$client->metricsJson();
```

## Examples

Run the bundled example:

```bash
php example.php
php example.php status
php example.php demo
```

More examples live in [`examples/`](./examples) and the larger CLI demo is [`example.php`](./example.php).

Current example files:

- `examples/basic_usage.php`
- `examples/collections.php`
- `examples/documents.php`
- `examples/search.php`
- `examples/vector.php`
- `examples/keys.php`
- `examples/synstop.php`
- `examples/list_syn_stops.php`
- `examples/ranker.php`
