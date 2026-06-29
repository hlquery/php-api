<?php

declare(strict_types=1);

namespace Hlquery {
    class ValidationException extends \Exception {}
}

namespace {
    require_once __DIR__ . '/../utils/Config.php';
    require_once __DIR__ . '/../utils/Auth.php';
    require_once __DIR__ . '/../utils/Validator.php';
    require_once __DIR__ . '/../lib/Response.php';
    require_once __DIR__ . '/../lib/Client.php';
    require_once __DIR__ . '/../lib/Service.php';
    require_once __DIR__ . '/../lib/Collections.php';
    require_once __DIR__ . '/../lib/Modules.php';
    require_once __DIR__ . '/../lib/Synonyms.php';
    require_once __DIR__ . '/../lib/Stopwords.php';
    require_once __DIR__ . '/../lib/Overrides.php';
    require_once __DIR__ . '/../lib/Presets.php';

    use Hlquery\Utils\Auth;
    use Hlquery\Utils\Config;
    use Hlquery\Utils\Validator;

    class RecordingClient extends \Hlquery\Client {
        public $last_request = null;

        public function __construct() {}

        public function executeRequest($method, $path, $payload = null, array $query = []) {
            $this->last_request = [
                'method' => $method,
                'path' => $path,
                'payload' => $payload,
                'query' => $query,
            ];

            return $this->last_request;
        }
    }

    function assertTrue($condition, string $message): void {
        if (!$condition) {
            throw new \RuntimeException($message);
        }
    }

    function assertSame($expected, $actual, string $message): void {
        if ($expected !== $actual) {
            throw new \RuntimeException($message . ' Expected: ' . var_export($expected, true) . ' Actual: ' . var_export($actual, true));
        }
    }

    $defaults = Config::mergeDefaults(['timeout' => 45]);
    assertSame('http://localhost:9200', $defaults['base_url'], 'Default base URL should be set');
    assertSame(45, $defaults['timeout'], 'Timeout override should be preserved');
    assertTrue(Config::isValidUrl('http://localhost:9200'), 'HTTP URL should be valid');
    assertTrue(!Config::isValidUrl('not-a-url'), 'Invalid URL should be rejected');
    assertSame('http://localhost:9200', Config::normalizeUrl('http://localhost:9200/'), 'Trailing slash should be removed');

    assertTrue(Auth::isValidToken('secret'), 'Non-empty token should be valid');
    assertSame(md5('secret'), Auth::generateToken('secret'), 'Token hash should match md5');
    assertSame(
        ['header' => 'Authorization', 'value' => 'Bearer secret'],
        Auth::getAuthHeader('secret'),
        'Bearer header should be generated'
    );
    assertSame(
        ['header' => 'X-API-Key', 'value' => 'secret'],
        Auth::getAuthHeader('secret', 'api-key'),
        'API key header should be generated'
    );
    assertSame(
        ['header' => 'X-TYPESENSE-API-KEY', 'value' => 'secret'],
        Auth::getAuthHeader('secret', 'typesense'),
        'Typesense-compatible API key header should be generated'
    );

    Validator::validateCollectionName('books');
    Validator::validateDocumentId('doc-1');
    Validator::validateAliasName('alias_1');
    Validator::validatePagination(0, 10);
    Validator::validateSearchParams(['limit' => 10, 'offset' => 0]);

    $threw = false;
    try {
        Validator::validateCollectionName('123bad');
    } catch (\Hlquery\ValidationException $e) {
        $threw = true;
    }
    assertTrue($threw, 'Invalid collection names should raise ValidationException');

    Validator::validateDocumentFields(['title' => 'valid,value']);

    $threw = false;
    try {
        Validator::validateDocumentFields(['' => 'value']);
    } catch (\Hlquery\ValidationException $e) {
        $threw = true;
    }
    assertTrue($threw, 'Empty document field names should be rejected');

    $client = new RecordingClient();

    $client->cache();
    assertSame(
        ['method' => 'GET', 'path' => '/cache', 'payload' => null, 'query' => []],
        $client->last_request,
        'Client should expose the cache route'
    );

    $collections = new \Hlquery\Collections($client);
    $collections->getFields('books');
    assertSame(
        ['method' => 'GET', 'path' => '/collections/books', 'payload' => null, 'query' => []],
        $client->last_request,
        'Collection fields helper should use the collection metadata route'
    );
    $collections->update('books', ['fields' => [['name' => 'title', 'type' => 'string']]]);
    assertSame(
        ['method' => 'POST', 'path' => '/collections/books/update', 'payload' => ['fields' => [['name' => 'title', 'type' => 'string']]], 'query' => []],
        $client->last_request,
        'Collection updates should use the supported update route'
    );

    $modules = new \Hlquery\Modules($client);
    $modules->request('GET', 'demo module/search route', null, ['q' => 'phone']);
    assertSame(
        ['method' => 'GET', 'path' => '/modules/demo%20module/search%20route', 'payload' => null, 'query' => ['q' => 'phone']],
        $client->last_request,
        'Module request paths should URL-encode each path segment'
    );

    $synonyms = new \Hlquery\Synonyms($client);
    $synonyms->listSynonymSets(['sort_by' => 'id']);
    assertSame(
        ['method' => 'GET', 'path' => '/synonym_sets', 'payload' => null, 'query' => ['sort_by' => 'id']],
        $client->last_request,
        'Synonym set listing should use the compatibility route'
    );
    $synonyms->updateInGlobalSynonymSet('smart phone', ['synonyms' => ['phone']]);
    assertSame(
        ['method' => 'PUT', 'path' => '/synonym_sets/global/items/smart%20phone', 'payload' => ['synonyms' => ['phone']], 'query' => []],
        $client->last_request,
        'Global synonym set updates should URL-encode term ids'
    );

    $stopwords = new \Hlquery\Stopwords($client);
    $stopwords->listStopwordSets(['sort_by' => 'word']);
    assertSame(
        ['method' => 'GET', 'path' => '/stopword_sets', 'payload' => null, 'query' => ['sort_by' => 'word']],
        $client->last_request,
        'Stopword set listing should use the compatibility route'
    );
    $stopwords->deleteFromGlobalStopwordSet('the word');
    assertSame(
        ['method' => 'DELETE', 'path' => '/stopword_sets/global/items/the%20word', 'payload' => null, 'query' => []],
        $client->last_request,
        'Stopword set item deletes should URL-encode item ids'
    );

    $overrides = new \Hlquery\Overrides($client);
    $overrides->createCuration('books', 'launch promo', ['rule' => ['query' => 'launch']]);
    assertSame(
        ['method' => 'POST', 'path' => '/collections/books/curations/launch%20promo', 'payload' => ['rule' => ['query' => 'launch']], 'query' => []],
        $client->last_request,
        'Curation helpers should use the curation route alias'
    );

    $presets = new \Hlquery\Presets($client);
    $presets->upsert('popular books', ['q' => '*', 'query_by' => 'title']);
    assertSame(
        ['method' => 'PUT', 'path' => '/presets/popular%20books', 'payload' => ['q' => '*', 'query_by' => 'title'], 'query' => []],
        $client->last_request,
        'Preset helpers should URL-encode preset names'
    );

    assertTrue(isset($client->synonymSets), 'Client should expose synonymSets as a service alias');
    assertTrue(isset($client->stopwordSets), 'Client should expose stopwordSets as a service alias');
    assertTrue(isset($client->curations), 'Client should expose curations as a service alias');
    assertTrue(isset($client->presets), 'Client should expose presets as a service alias');

    fwrite(STDOUT, "PHP offline smoke tests passed.\n");
}
