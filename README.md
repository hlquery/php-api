<div align="center">
  <img src="https://docs.hlquery.com/img/hlquery/2.png" alt="hlquery logo" width="200">
</div>


# hlquery PHP API Client

A modular PHP client library for hlquery, designed with a familiar and intuitive API structure.

## Features

-  **Modular Architecture**: Clean separation of concerns with organized classes
-  **Intuitive API**: Familiar and easy-to-use structure
-  **Authentication Support**: Bearer token and X-API-Key authentication
-  **Flexible Parameters**: Support for multiple parameter formats
-  **Auto-detection**: Automatically detects searchable fields when not specified
-  **Type-safe Responses**: Response objects with helper methods
-  **Comprehensive Validation**: Input validation for all operations
-  **No Dependencies**: Uses PHP's built-in `curl` and `json` extensions
-  **Professional Structure**: Well-organized and modular architecture

## Installation

No dependencies required - uses PHP's built-in `curl` and `json` extensions.

Simply include the client:

```php
require_once 'HlqueryClient.php';
```

## Quick Start

### Basic Usage

```php
require_once 'HlqueryClient.php';

use Hlquery\Client;

// Initialize client
$client = new Client('http://localhost:9200');

// Health check
$health = $client->health();
echo "Status: " . $health->getStatusCode() . "\n";

// List collections
$collections = $client->listCollections(0, 10);
if ($collections->isSuccess()) {
    $body = $collections->getBody();
    echo "Found " . count($body['collections'] ?? []) . " collections\n";
}
```

### With Authentication

```php
// Method 1: Set token in constructor
$client = new Client('http://localhost:9200', [
    'token' => 'your_token_here',
    'auth_method' => 'bearer'  // or 'api-key'
]);

// Method 2: Set token dynamically
$client = new Client('http://localhost:9200');
$client->setAuthToken('your_token_here', 'bearer');

// Method 3: Use X-API-Key
$client->setAuthToken('your_token_here', 'api-key');
```

## Architecture

### Core Classes

#### `Hlquery\Client`
Main client class that provides access to all API operations.

#### `Hlquery\Request`
Handles HTTP requests, authentication, and error handling.

#### `Hlquery\Response`
Response wrapper with helper methods:
- `getStatusCode()` - Get HTTP status code
- `getBody()` - Get response body
- `isSuccess()` - Check if request was successful
- `isError()` - Check if request failed
- `getError()` - Get error message
- `toArray()` - Convert to array format (for backward compatibility)

#### `Hlquery\Collections`
Collection management operations (list, get, create, delete, update).

#### `Hlquery\Documents`
Document CRUD operations (list, get, add, update, delete, import).

#### `Hlquery\Search`
Search operations with flexible parameter formats.

### Utility Classes

#### `Hlquery\Utils\Auth`
Authentication utilities (token generation, validation).

#### `Hlquery\Utils\Config`
Configuration management (defaults, URL validation).

#### `Hlquery\Utils\Validator`
Input validation for all operations.

### Exceptions

- `Hlquery\HlqueryException` - Base exception
- `Hlquery\AuthenticationException` - Authentication errors
- `Hlquery\RequestException` - Request errors
- `Hlquery\ValidationException` - Validation errors
- `Hlquery\CollectionException` - Collection operation errors
- `Hlquery\DocumentException` - Document operation errors
- `Hlquery\SearchException` - Search operation errors

## API Methods

### System APIs

#### `health()`
Check server health status.

```php
$health = $client->health();
if ($health->isSuccess()) {
    $body = $health->getBody();
    echo "Status: " . $body['status'] . "\n";
}
```

#### `stats()`
Get server statistics.

```php
$stats = $client->stats();
```

#### `info()`
Get server information.

```php
$info = $client->info();
```

### Collections API

#### Using the Collections API Object

```php
$collections = $client->collections();

// List collections
$result = $collections->list(0, 10);

// Get collection
$result = $collections->get('my_collection');

// Create collection
$result = $collections->create('new_collection', $schema);

// Delete collection
$result = $collections->delete('collection_name');

// Get formatted fields
$result = $collections->getFields('my_collection');
```

#### Convenience Methods

```php
// List collections
$collections = $client->listCollections(0, 10);

// Get collection details
$collection = $client->getCollection('my_collection');

// Get collection fields (formatted)
$fields = $client->getCollectionFields('my_collection');
```

### Documents API

#### Using the Documents API Object

```php
$documents = $client->documents();

// List documents
$result = $documents->list('collection', ['offset' => 0, 'limit' => 10]);

// Get document
$result = $documents->get('collection', 'doc_id');

// Add document
$result = $documents->add('collection', $document);

// Update document
$result = $documents->update('collection', 'doc_id', $document);

// Delete document
$result = $documents->delete('collection', 'doc_id');

// Bulk import
$result = $documents->import('collection', [$doc1, $doc2, $doc3]);
```

#### Field Value Character Restrictions

**Important**: String field values have character restrictions:

**❌ Invalid Characters** (not allowed):
- Commas (`,`) - Reserved for internal parsing

** Valid Characters** (allowed):
- Letters, numbers, underscores (`_`), hyphens (`-`), spaces, periods, and most punctuation (except commas)

**Examples:**

 **Valid:**
```php
$doc = [
    'id' => 'doc1',
    'tags' => 'tag1_tag2_tag3',        //  Use underscores
    'cast' => 'Actor1_Actor2',          //  Use underscores
    'genre' => 'Action_Drama'            //  Use underscores
];

// Or use arrays for multiple values:
$doc2 = [
    'id' => 'doc2',
    'tags' => ['tag1', 'tag2', 'tag3']  //  Arrays are fine
];
```

❌ **Invalid:**
```php
$doc = [
    'id' => 'doc1',
    'tags' => 'tag1,tag2,tag3',         // ❌ Commas not allowed
    'cast' => 'Actor1, Actor2',         // ❌ Commas not allowed
    'genre' => 'Action,Drama'           // ❌ Commas not allowed
];
```

**Workarounds:**
- Use underscores (`_`) or spaces instead of commas
- Use arrays for multiple values: `tags: ['tag1', 'tag2', 'tag3']`
- Use separate fields if you need comma-separated data

#### Convenience Methods

```php
// List documents
$docs = $client->listDocuments('collection', ['offset' => 0, 'limit' => 10]);

// Get document
$doc = $client->getDocument('collection', 'doc_id');
```

### Search API

#### Using the Search API Object

```php
$search = $client->searchApi();

// Simple search
$results = $search->search('collection', [
    'q' => 'search query',
    'query_by' => 'title,content',
    'limit' => 10
]);

// Multi-search
$results = $search->multiSearch([
    ['collection' => 'col1', 'q' => 'query1'],
    ['collection' => 'col2', 'q' => 'query2']
]);
```

#### Convenience Method

```php
// Search documents
$results = $client->search('collection', [
    'q' => 'search query',
    'query_by' => 'title,content',
    'limit' => 10
]);
```

### Search Parameters

The `search()` method accepts flexible parameters:

#### Query Parameters
- `q` - Query string (supports field clauses, `OR`, `NOT`, phrases, and wildcards)
  - Examples: `'q' => 'title:laptop'`, `'q' => 'title:laptop OR title:notebook'`, `'q' => 'title:laptop NOT title:refurbished'`, `'q' => '"wireless keyboard"'`, `'q' => 'laptop*'`
- `query_by` - Fields to search in (string or array)
- `query` - Structured query object

#### Pagination
- `from` / `offset` - Starting offset
- `size` / `limit` - Number of results
- `page` - Page number (alternative to offset)
- `per_page` - Results per page

#### Filtering & Sorting
- `filter_by` - Filter conditions (string), for example `'price:>100&&category:electronics'`
- `filter` - Filter object
- `sort_by` - Sort fields (string or array)
- `sort` - Sort specification

#### Faceting
- `facet_by` - Facet fields (string or array)
- `facets` - Facet specification

### API Aliases

#### `indices($params = [])`
Alias for `listCollections()`.

```php
$collections = $client->indices(['offset' => 0, 'limit' => 10]);
```

#### `get($params)`
Alias for `getCollection()` or `getDocument()`.

```php
// Get document
$doc = $client->get(['index' => 'collection', 'id' => 'doc_id']);

// Get collection
$collection = $client->get(['index' => 'collection']);
```

#### `cat($type = 'indices', $params = [])`
Cat API for listing collections and system information.

```php
$indices = $client->cat('indices', ['limit' => 10]);
```

## Response Handling

All methods return a `Response` object:

```php
$response = $client->health();

// Check status
if ($response->isSuccess()) {
    // Handle success
    $body = $response->getBody();
}

// Or check status code
if ($response->getStatusCode() === 200) {
    // Handle success
}

// Get error
if ($response->isError()) {
    $error = $response->getError();
    echo "Error: " . $error . "\n";
}

// Convert to array (for backward compatibility)
$array = $response->toArray();
// Returns: ['status' => 200, 'body' => [...]]
```

## Error Handling

The client throws exceptions for errors:

```php
use Hlquery\Client;
use Hlquery\RequestException;
use Hlquery\AuthenticationException;
use Hlquery\ValidationException;

try {
    $result = $client->search('collection', ['q' => 'test']);
    
    if ($result->isError()) {
        // Handle HTTP error
        echo "Error: " . $result->getError() . "\n";
    }
} catch (RequestException $e) {
    // Handle request errors
    echo "Request failed: " . $e->getMessage() . "\n";
    echo "Status: " . $e->getStatusCode() . "\n";
} catch (AuthenticationException $e) {
    // Handle authentication errors
    echo "Auth failed: " . $e->getMessage() . "\n";
} catch (ValidationException $e) {
    // Handle validation errors
    echo "Validation failed: " . $e->getMessage() . "\n";
} catch (\Exception $e) {
    // Handle other errors
    echo "Error: " . $e->getMessage() . "\n";
}
```

## Examples

### Complete Example

See `example.php` for a complete example demonstrating:
- Health checks
- Authentication (with and without token)
- Listing collections
- Getting collection fields
- Listing documents with pagination
- Multiple search methods
- Dynamic authentication

Run the example:

```bash
# Without authentication
php example.php

# With authentication
php example.php your_token_here
```

### Organized Examples

Check the `examples/` directory for organized examples:
- `basic_usage.php` - Basic operations
- `search.php` - Search patterns
- `collections.php` - Collection management
- `documents.php` - Document CRUD


## Backward Compatibility

The old `HlqueryClient` class is still available as an alias:

```php
// Old way (still works)
$client = new HlqueryClient('http://localhost:9200');

// New way (recommended)
use Hlquery\Client;
$client = new Client('http://localhost:9200');
```

Both work identically, but the new namespace-based approach is recommended for new code.

## Requirements

- PHP >= 7.0
- `ext-curl` - For HTTP requests
- `ext-json` - For JSON encoding/decoding

## Composer Installation

This package can be installed via Composer:

```bash
composer require hlquery/php-client
```

Or add to your `composer.json`:

```json
{
    "require": {
        "hlquery/php-client": "*"
    }
}
```
### Ranking Helpers

Use `Hlquery\Ranker` to compute a composite `rank_signal` (log + linear mix) that combines `popularity_score` and `hit_log`, and to attach a ranking field to your query.

```php
use Hlquery\Ranker;

$signal = Ranker::computeRankSignal($popularityScore, $hitLog);
$params = ['q' => 'query'];
Ranker::attachRankSort($params); // sort_by=rank_signal:desc
```
