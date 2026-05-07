<div align="center">
  <img src="https://docs.hlquery.com/img/hlquery/2.png" alt="hlquery logo" width="200">
</div>

<div align="center">

**A modular PHP client library for hlquery, designed with a familiar and intuitive API structure.**

[![Follow hlquery](https://img.shields.io/badge/Follow-%40hlquery-blue?logo=x&logoColor=white)](https://x.com/hlquery)
[![Commit Activity](https://img.shields.io/github/commit-activity/m/hlquery/hlquery)](https://github.com/hlquery/php-api/pulse)
[![GitHub](https://img.shields.io/badge/GitHub-php--api-181717?logo=github&logoColor=white)](https://github.com/hlquery/php-api/stargazers)
[![hlquery](https://img.shields.io/badge/GitHub-hlquery-blue?logo=github&logoColor=white)](https://github.com/hlquery/hlquery/stargazers)
[![License](https://img.shields.io/badge/License-BSD%203--Clause-blue.svg)](https://opensource.org/licenses/BSD-3-Clause)

</div>

### What is the hlquery PHP API?

The hlquery PHP API is the official PHP client for hlquery. It gives PHP applications a straightforward way to talk to the search engine through a small client instead of manually assembling `curl` calls and JSON payloads.

The library wraps hlquery's HTTP endpoints in a service-based API so you can create collections, index documents, run searches, manage lexical resources, query SAM, and call custom module routes from normal PHP code.

### Why use it?

Use the PHP API when you want hlquery integration to feel like part of your application instead of a pile of hand-written REST calls. It reduces boilerplate, keeps authentication and request formatting consistent, and makes common operations easier to read and maintain.

### Why choose it over raw HTTP?

Choose the PHP client over raw HTTP when you want less boilerplate around search, indexing, and admin operations, one consistent client for auth, params, headers, and response parsing, and direct access to collections, documents, SQL, overrides, synonyms, stopwords, and SAM. It also works in plain PHP with no framework requirement.

### Install

Requirements:

- PHP `>= 7.0`
- `ext-curl`
- `ext-json`

Composer:

```bash
composer require hlquery/php-client
```

Local usage:

```php
require_once __DIR__ . '/lib/autoload.php';

use Hlquery\Client;

$client = new Client(getenv('HLQ_BASE_URL') ?: (getenv('HLQUERY_BASE_URL') ?: 'http://localhost:9200'));
```

Composer usage:

```php
require_once __DIR__ . '/vendor/autoload.php';

use Hlquery\Client;

$client = new Client('http://localhost:9200');
```

### Auth

```php
$client = new Client('http://localhost:9200', [
    'token' => 'your_token_here',
    'auth_method' => 'bearer',
]);

$client->setAuthToken('your_token_here', 'bearer');
$client->setAuthToken('your_api_key_here', 'api-key');
```

### Quick Start

```php
require_once __DIR__ . '/vendor/autoload.php';

use Hlquery\Client;

$client = new Client('http://localhost:9200');

$health = $client->health();
if ($health->isSuccess()) {
    echo "status: " . (($health->getBody()['status'] ?? 'ok')) . PHP_EOL;
}

$collections = $client->listCollections(0, 10);
print_r($collections->getBody());
```

### SAM

Use the SAM service for search, background status, and recent query history:

SAM is separate from vector search. It performs term and intent-style lookup, not vector similarity search.

```php
$sam = $client->sam();

$status = $sam->status('music');
$history = $sam->history('music', 5);
$results = $sam->search('music', 'queen of pop', [
    'limit' => 10,
]);

print_r($status->getBody());
print_r($history->getBody());
print_r($results->getBody());
```

### SQL

```php
$sql = $client->sql();

$rows = $sql->query('SHOW COLLECTIONS;');
$books = $sql->search(
    'books',
    'SELECT id, title FROM books ORDER BY title ASC LIMIT 3;'
);

print_r($rows->getBody());
print_r($books->getBody());
```

### Reduce Text Example

Use `executeRequest()` to call custom module routes directly:

```php
$moduleResponse = $client->executeRequest('GET', '/modules/<name>/<route>', null, [
    'q' => 'example query',
]);

print_r($moduleResponse->getBody());
```

### Notes

- Base URL defaults to `http://localhost:9200`.
- The client stays framework-agnostic.
- See `etc/api/php/example.php` and the language-specific examples in this repo for more complete flows.
