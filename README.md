<div align="center">
  <img src="https://docs.hlquery.com/img/hlquery/2.png" alt="hlquery logo" width="200">
</div>

<div align="center">

**A modular PHP client library for hlquery with a familiar service-based API.**

[![Follow hlquery](https://img.shields.io/badge/Follow-%40hlquery-blue?logo=x&logoColor=white&labelColor=000000)](https://x.com/hlquery)
[![PHP build](https://img.shields.io/badge/PHP%20build-passing-brightgreen?logo=php&logoColor=white&labelColor=000000)](https://github.com/hlquery/php-api/actions/workflows/ci.yml)
[![php-api](https://img.shields.io/badge/GitHub-php--api-purple?logo=github&logoColor=white&labelColor=000000)](https://github.com/hlquery/php-api/)
[![hlquery](https://img.shields.io/badge/GitHub-hlquery-blue?logo=github&logoColor=white&labelColor=000000)](https://github.com/hlquery/hlquery/)
[![License](https://img.shields.io/badge/License-BSD%203--Clause-a35a0f?logo=open-source-initiative&logoColor=white&labelColor=000000)](https://opensource.org/licenses/BSD-3-Clause)

</div>

### What is the hlquery PHP API?

The hlquery PHP API is the official PHP client for [hlquery](https://github.com/hlquery/hlquery). It gives PHP applications a straightforward way to talk to the search engine through a small client instead of manually assembling `curl` calls and JSON payloads.

The library wraps hlquery's HTTP endpoints in a service-based API so you can create collections, index documents, run searches, manage lexical resources, and call custom module routes from normal PHP code.

### Why use it?

Use the PHP API when you want hlquery integration to feel like part of your application instead of a pile of handwritten REST calls. It reduces boilerplate, keeps authentication and request formatting consistent, and makes common operations easier to read and maintain.

### Installation

Requirements:

- PHP `>= 7.0`
- `ext-curl`
- `ext-json`

Composer:

```bash
$ composer require hlquery/php-client
```

Local checkout:

```php
require_once __DIR__ . '/lib/autoload.php';

use Hlquery\Client;
$client = new Client(getenv('HLQ_BASE_URL') ?: (getenv('HLQUERY_BASE_URL') ?: 'http://localhost:9200'));
```

Composer autoload:

```php
require_once __DIR__ . '/vendor/autoload.php';

use Hlquery\Client;

$client = new Client('http://localhost:9200');
```

### Authentication

```php
$client = new Client('http://localhost:9200', [
    'token' => 'your_token_here',
    'auth_method' => 'bearer',
]);

$client->setAuthToken('your_token_here', 'bearer');
$client->setAuthToken('your_api_key_here', 'api-key');
```

### Quick start

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

### Collections

List collections with pagination and iterate over the results:

```php
$response = $client->listCollections(0, 100);

if (!$response->isSuccess()) {
    throw new RuntimeException('Failed to list collections: ' . $response->getStatusCode());
}

$body = $response->getBody();
$collections = $body['collections'] ?? [];

foreach ($collections as $collection) {
    $name = is_array($collection) ? ($collection['name'] ?? '') : $collection;

    if ($name === '') {
        continue;
    }

    echo $name . PHP_EOL;
}
```

### SQL

```php
$sql = $client->sql();

$rows = $sql->raw('SHOW COLLECTIONS;');
$books = $sql->query(
    'books',
    'SELECT id, title FROM books ORDER BY title ASC LIMIT 3;'
);

print_r($rows->getBody());
print_r($books->getBody());
```

### Custom Module Routes

Use `executeRequest()` to call custom module routes directly:

```php
$moduleResponse = $client->executeRequest('GET', '/modules/<name>/<route>', null, [
    'q' => 'example query',
]);

print_r($moduleResponse->getBody());
```

### Contributing

We welcome contributions from the community! All contributions must be released under the BSD 3-Clause license.

### How to Contribute

- Check existing [PHP API issues](https://github.com/hlquery/php-api/issues) or open a new one
- Contribute PHP client changes to [hlquery/php-api](https://github.com/hlquery/php-api)
- Contribute shared server/API changes to [hlquery/hlquery](https://github.com/hlquery/hlquery)
- Test and report bugs against the PHP client
- Improve PHP-specific documentation and examples

### Community

- 📖 [Documentation](https://docs.hlquery.com)
- 🐦 [X (Twitter)](https://x.com/hlquery)
- 🟣 [PHP API GitHub](https://github.com/hlquery/php-api)
- 📦 [hlquery GitHub](https://github.com/hlquery/hlquery)

### License

The hlquery PHP API is licensed under the [BSD 3-Clause License](https://opensource.org/licenses/BSD-3-Clause).
