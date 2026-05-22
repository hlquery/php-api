<?php

declare(strict_types=1);

require_once __DIR__ . '/../lib/autoload.php';

class TestClient extends Hlquery\Client
{
    public $lastMethod;
    public $lastPath;
    public $lastPayload;
    public $lastQuery;

    public function executeRequest($method, $path, $payload = null, array $query = [])
    {
        $this->lastMethod = $method;
        $this->lastPath = $path;
        $this->lastPayload = $payload;
        $this->lastQuery = $query;
        return new Hlquery\Response(200, [], ['ok' => true], '{"ok":true}', '');
    }
}

function assertTrue($condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function assertSame($expected, $actual, string $message): void
{
    if ($expected !== $actual) {
        throw new RuntimeException($message . ' Expected: ' . var_export($expected, true) . ' Actual: ' . var_export($actual, true));
    }
}

function run(string $name, callable $fn): void
{
    try {
        $fn();
        fwrite(STDOUT, "[ok] $name\n");
    } catch (Throwable $e) {
        fwrite(STDERR, "[fail] $name: " . $e->getMessage() . "\n");
        exit(1);
    }
}

run('Client validates and normalizes base_url', function (): void {
    $client = new Hlquery\Client('http://localhost:9200/', ['timeout' => 5]);
    $ref = new ReflectionObject($client);
    $prop = $ref->getProperty('base_url');
    $prop->setAccessible(true);
    assertSame('http://localhost:9200', $prop->getValue($client), 'Client should strip trailing slash from base_url.');
});

run('Client rejects invalid base_url', function (): void {
    $threw = false;
    try {
        new Hlquery\Client('not-a-url');
    } catch (Hlquery\ValidationException $e) {
        $threw = true;
    }
    assertTrue($threw, 'Expected ValidationException for invalid base_url.');
});

run('Auth token validation', function (): void {
    $client = new Hlquery\Client('http://localhost:9200');
    $threw = false;
    try {
        $client->setAuthToken('');
    } catch (Hlquery\ValidationException $e) {
        $threw = true;
    }
    assertTrue($threw, 'Expected ValidationException for empty token.');
});

run('Config utilities', function (): void {
    $defaults = Hlquery\Utils\Config::mergeDefaults(['timeout' => 45]);
    assertSame('http://localhost:9200', $defaults['base_url'], 'Default base URL should be set');
    assertSame(45, $defaults['timeout'], 'Timeout override should be preserved');
    assertTrue(Hlquery\Utils\Config::isValidUrl('http://localhost:9200'), 'HTTP URL should be valid');
    assertTrue(!Hlquery\Utils\Config::isValidUrl('not-a-url'), 'Invalid URL should be rejected');
    assertSame('http://localhost:9200', Hlquery\Utils\Config::normalizeUrl('http://localhost:9200/'), 'Trailing slash should be removed');
});

run('Auth header generation', function (): void {
    assertTrue(Hlquery\Utils\Auth::isValidToken('secret'), 'Non-empty token should be valid');
    assertSame(md5('secret'), Hlquery\Utils\Auth::generateToken('secret'), 'Token hash should match md5');
    assertSame(
        ['header' => 'Authorization', 'value' => 'Bearer secret'],
        Hlquery\Utils\Auth::getAuthHeader('secret'),
        'Bearer header should be generated'
    );
    assertSame(
        ['header' => 'X-API-Key', 'value' => 'secret'],
        Hlquery\Utils\Auth::getAuthHeader('secret', 'api-key'),
        'API key header should be generated'
    );
});

run('Validator accepts valid inputs', function (): void {
    Hlquery\Utils\Validator::validateCollectionName('books');
    Hlquery\Utils\Validator::validateDocumentId('doc-1');
    Hlquery\Utils\Validator::validateAliasName('alias_1');
    Hlquery\Utils\Validator::validatePagination(0, 10);
    Hlquery\Utils\Validator::validateSearchParams(['limit' => 10, 'offset' => 0]);
    assertTrue(true, 'Validator should not throw for valid inputs');
});

run('Validator rejects invalid inputs', function (): void {
    $threw = false;
    try {
        Hlquery\Utils\Validator::validateCollectionName('123bad');
    } catch (Hlquery\ValidationException $e) {
        $threw = true;
    }
    assertTrue($threw, 'Invalid collection names should raise ValidationException');

    $threw = false;
    try {
        Hlquery\Utils\Validator::validateDocumentFields(['title' => 'bad,value']);
    } catch (Hlquery\ValidationException $e) {
        $threw = true;
    }
    assertTrue($threw, 'Comma-containing field values should be rejected');
});

run('Validator pagination limits', function (): void {
    $threw = false;
    try {
        Hlquery\Utils\Validator::validatePagination(0, 1001);
    } catch (Hlquery\ValidationException $e) {
        $threw = true;
    }
    assertTrue($threw, 'Limit > 1000 should be rejected');
});

run('Collections list validates pagination', function (): void {
    $client = new TestClient('http://localhost:9200');
    $threw = false;
    try {
        $client->collections()->list(-1, 10);
    } catch (Hlquery\ValidationException $e) {
        $threw = true;
    }
    assertTrue($threw, 'Collections::list should reject negative offset');
});

run('Documents add validates optional id', function (): void {
    $client = new TestClient('http://localhost:9200');
    $threw = false;
    try {
        $client->documents()->add('books', ['id' => 'bad/id', 'title' => 'x']);
    } catch (Hlquery\ValidationException $e) {
        $threw = true;
    }
    assertTrue($threw, 'Documents::add should validate document id when provided');
});

run('Validator allows commas in embedding fields', function (): void {
    // embedding and *_vector are allowed to contain commas
    Hlquery\Utils\Validator::validateDocumentFields(['embedding' => '1,2,3']);
    Hlquery\Utils\Validator::validateDocumentFields(['title_vector' => '1,2,3']);
    assertTrue(true, 'Comma should be allowed in embedding/vector fields');
});

run('HttpClient setAuthToken updates internal fields', function (): void {
    $http = new Hlquery\HttpClient('http://localhost:9200');
    $http->setAuthToken('tok', 'api-key');
    $ref = new ReflectionObject($http);
    $tokenProp = $ref->getProperty('token');
    $tokenProp->setAccessible(true);
    $methodProp = $ref->getProperty('auth_method');
    $methodProp->setAccessible(true);
    assertSame('tok', $tokenProp->getValue($http), 'HttpClient token should be updated');
    assertSame('api-key', $methodProp->getValue($http), 'HttpClient auth_method should be updated');
});

run('Validator alias name rules', function (): void {
    Hlquery\Utils\Validator::validateAliasName('alias_1');

    $threw = false;
    try {
        Hlquery\Utils\Validator::validateAliasName('123bad');
    } catch (Hlquery\ValidationException $e) {
        $threw = true;
    }
    assertTrue($threw, 'Alias name must start with a letter or underscore');

    $threw = false;
    try {
        Hlquery\Utils\Validator::validateAliasName(str_repeat('a', 65));
    } catch (Hlquery\ValidationException $e) {
        $threw = true;
    }
    assertTrue($threw, 'Alias name length > 64 should be rejected');
});

run('Validator validateSearchParams type checks', function (): void {
    $threw = false;
    try {
        Hlquery\Utils\Validator::validateSearchParams('nope');
    } catch (Hlquery\ValidationException $e) {
        $threw = true;
    }
    assertTrue($threw, 'Search params must be an array');

    $threw = false;
    try {
        Hlquery\Utils\Validator::validateSearchParams(['limit' => 0]);
    } catch (Hlquery\ValidationException $e) {
        $threw = true;
    }
    assertTrue($threw, 'limit < 1 should be rejected');
});

run('Validator rejects commas inside array items', function (): void {
    $threw = false;
    try {
        Hlquery\Utils\Validator::validateDocumentFields(['tags' => ['ok', 'bad,value']]);
    } catch (Hlquery\ValidationException $e) {
        $threw = true;
    }
    assertTrue($threw, 'Array items containing commas should be rejected');
});

run('Documents import validates each document', function (): void {
    $client = new TestClient('http://localhost:9200');
    $threw = false;
    try {
        $client->documents()->import('books', [['title' => 'ok'], ['title' => 'bad,value']]);
    } catch (Hlquery\ValidationException $e) {
        $threw = true;
    }
    assertTrue($threw, 'Documents::import should validate document fields for each item');
});

run('SAM status/history query shaping', function (): void {
    $client = new TestClient('http://localhost:9200');
    $client->sam()->status(null, ['verbose' => 1]);
    assertSame('/sam/status', $client->lastPath, 'SAM::status path mismatch');
    assertSame(['verbose' => 1], $client->lastQuery, 'SAM::status should pass-through params when collection is null');

    $client->sam()->history('books', 3, ['since' => '2020-01-01']);
    assertSame('/sam/history', $client->lastPath, 'SAM::history path mismatch');
    assertSame(['limit' => 3, 'since' => '2020-01-01', 'collection' => 'books'], $client->lastQuery, 'SAM::history query mismatch');
});

run('HttpClient URL building', function (): void {
    $http = new Hlquery\HttpClient('http://localhost:9200');
    $ref = new ReflectionObject($http);
    $method = $ref->getMethod('buildUrl');
    $method->setAccessible(true);
    $url = $method->invoke($http, '/search', ['q' => 'test', 'limit' => 10]);
    assertSame('http://localhost:9200/search?q=test&limit=10', $url, 'Query string should be appended');
});

run('Collections service request shape', function (): void {
    $client = new TestClient('http://localhost:9200');
    $client->collections()->list(5, 20);
    assertSame('GET', $client->lastMethod, 'Collections::list should use GET');
    assertSame('/collections', $client->lastPath, 'Collections::list path mismatch');
    assertSame(['offset' => 5, 'limit' => 20], $client->lastQuery, 'Collections::list query mismatch');

    $client->collections()->getFields('books');
    assertSame('GET', $client->lastMethod, 'Collections::getFields should use GET');
    assertSame('/collections/books/fields', $client->lastPath, 'Collections::getFields path mismatch');
});

run('Documents service validates and encodes IDs', function (): void {
    $client = new TestClient('http://localhost:9200');
    $client->documents()->get('books', 'doc-1');
    assertSame('GET', $client->lastMethod, 'Documents::get should use GET');
    assertSame('/collections/books/documents/doc-1', $client->lastPath, 'Documents::get path mismatch');

    $client->documents()->search('books', ['q' => 'hello', 'offset' => 0, 'limit' => 10]);
    assertSame('GET', $client->lastMethod, 'Documents::search should use GET');
    assertSame('/collections/books/documents/search', $client->lastPath, 'Documents::search path mismatch');

    $threw = false;
    try {
        $client->documents()->get('123bad', 'doc-1');
    } catch (Hlquery\ValidationException $e) {
        $threw = true;
    }
    assertTrue($threw, 'Documents::get should validate collection name');
});

run('SQL service payload shape', function (): void {
    $client = new TestClient('http://localhost:9200');
    $client->sql()->query('books', 'select * from books where id=:id', ['params' => ['id' => '1']]);
    assertSame('POST', $client->lastMethod, 'SQL::query should use POST');
    assertSame('/sql', $client->lastPath, 'SQL::query path mismatch');
    assertTrue(is_array($client->lastPayload), 'SQL::query should send array payload');
    assertSame('books', $client->lastPayload['collection'], 'SQL::query should include collection');
    assertSame('select * from books where id=:id', $client->lastPayload['q'], 'SQL::query should include q');
    assertSame(['id' => '1'], $client->lastPayload['params'], 'SQL::query should merge extra params');

    $client->sql()->execute('select 1');
    assertSame('POST', $client->lastMethod, 'SQL::execute should use POST');
    assertSame('/sql', $client->lastPath, 'SQL::execute path mismatch');
    assertSame('select 1', $client->lastPayload['q'], 'SQL::execute should include q');
});

run('Keys service request shape', function (): void {
    $client = new TestClient('http://localhost:9200');
    $client->keys()->list(10, 5);
    assertSame('GET', $client->lastMethod, 'Keys::list should use GET');
    assertSame('/keys', $client->lastPath, 'Keys::list path mismatch');
    assertSame(['offset' => 10, 'limit' => 5], $client->lastQuery, 'Keys::list query mismatch');

    $client->keys()->get('abc/123');
    assertSame('GET', $client->lastMethod, 'Keys::get should use GET');
    assertSame('/keys/abc%2F123', $client->lastPath, 'Keys::get should rawurlencode key id');

    $client->keys()->create(['name' => 'k']);
    assertSame('POST', $client->lastMethod, 'Keys::create should use POST');
    assertSame('/keys', $client->lastPath, 'Keys::create path mismatch');
    assertSame(['name' => 'k'], $client->lastPayload, 'Keys::create payload mismatch');

    $client->keys()->update('id', ['role' => 'rw']);
    assertSame('PUT', $client->lastMethod, 'Keys::update should use PUT');
    assertSame('/keys/id', $client->lastPath, 'Keys::update path mismatch');
    assertSame(['role' => 'rw'], $client->lastPayload, 'Keys::update payload mismatch');

    $client->keys()->delete('id');
    assertSame('DELETE', $client->lastMethod, 'Keys::delete should use DELETE');
    assertSame('/keys/id', $client->lastPath, 'Keys::delete path mismatch');
});

run('SAM service request shape', function (): void {
    $client = new TestClient('http://localhost:9200');
    $client->sam()->search('books', 'hello', ['limit' => 3]);
    assertSame('GET', $client->lastMethod, 'SAM::search should use GET');
    assertSame('/sam/search', $client->lastPath, 'SAM::search path mismatch');
    assertSame(['collection' => 'books', 'q' => 'hello', 'limit' => 3], $client->lastQuery, 'SAM::search query mismatch');

    $client->sam()->status('books');
    assertSame('GET', $client->lastMethod, 'SAM::status should use GET');
    assertSame('/sam/status', $client->lastPath, 'SAM::status path mismatch');
    assertSame(['collection' => 'books'], $client->lastQuery, 'SAM::status should include collection when provided');

    $client->sam()->history(null, 7);
    assertSame('GET', $client->lastMethod, 'SAM::history should use GET');
    assertSame('/sam/history', $client->lastPath, 'SAM::history path mismatch');
    assertSame(['limit' => 7], $client->lastQuery, 'SAM::history should include limit');

    $threw = false;
    try {
        $client->sam()->search('123bad', 'hello');
    } catch (Hlquery\ValidationException $e) {
        $threw = true;
    }
    assertTrue($threw, 'SAM::search should validate collection name');
});

run('Client convenience methods delegate', function (): void {
    $client = new TestClient('http://localhost:9200');
    $client->health();
    assertSame('GET', $client->lastMethod, 'Client::health should use GET');
    assertSame('/health', $client->lastPath, 'Client::health path mismatch');

    $client->info();
    assertSame('GET', $client->lastMethod, 'Client::info should use GET');
    assertSame('/', $client->lastPath, 'Client::info path mismatch');

    $client->stats();
    assertSame('GET', $client->lastMethod, 'Client::stats should use GET');
    assertSame('/stats', $client->lastPath, 'Client::stats path mismatch');

    $client->multiSearch(['queries' => []]);
    assertSame('POST', $client->lastMethod, 'Client::multiSearch should use POST');
    assertSame('/multi_search', $client->lastPath, 'Client::multiSearch path mismatch');
    assertSame(['queries' => []], $client->lastPayload, 'Client::multiSearch payload mismatch');
});

run('Extended client service accessors', function (): void {
    $client = new TestClient('http://localhost:9200');
    assertTrue($client->synonyms() instanceof Hlquery\Synonyms, 'Client::synonyms should return Synonyms service');
    assertTrue($client->stopwords() instanceof Hlquery\Stopwords, 'Client::stopwords should return Stopwords service');
    assertTrue($client->overrides() instanceof Hlquery\Overrides, 'Client::overrides should return Overrides service');
    assertTrue($client->aliases() instanceof Hlquery\Aliases, 'Client::aliases should return Aliases service');
    assertTrue($client->links() instanceof Hlquery\Links, 'Client::links should return Links service');
    assertTrue($client->users() instanceof Hlquery\Users, 'Client::users should return Users service');
    assertTrue($client->modules() instanceof Hlquery\Modules, 'Client::modules should return Modules service');
    assertTrue($client->analytics() instanceof Hlquery\Analytics, 'Client::analytics should return Analytics service');
});

run('Extended route wrappers shape requests', function (): void {
    $client = new TestClient('http://localhost:9200');

    $client->ready();
    assertSame('GET', $client->lastMethod, 'Client::ready should use GET');
    assertSame('/ready', $client->lastPath, 'Client::ready path mismatch');

    $client->status();
    assertSame('/status', $client->lastPath, 'Client::status path mismatch');

    $client->query();
    assertSame('/query', $client->lastPath, 'Client::query path mismatch');

    $client->bootStatus();
    assertSame('/boot-status', $client->lastPath, 'Client::bootStatus path mismatch');

    $client->consistency();
    assertSame('/consistency', $client->lastPath, 'Client::consistency path mismatch');

    $client->metricsJson();
    assertSame('/metrics.json', $client->lastPath, 'Client::metricsJson path mismatch');

    $client->rocksdbUnderscore();
    assertSame('/_rocksdb', $client->lastPath, 'Client::rocksdbUnderscore path mismatch');

    $client->collections()->language('books');
    assertSame('/collections/books/lang', $client->lastPath, 'Collections::language path mismatch');

    $client->collections()->distributed(['ping' => 1]);
    assertSame('/collections/distributed', $client->lastPath, 'Collections::distributed path mismatch');
    assertSame(['ping' => 1], $client->lastQuery, 'Collections::distributed query mismatch');

    $client->collections()->vectorSearch('books', ['vector' => [1, 2]], 'POST');
    assertSame('POST', $client->lastMethod, 'Collections::vectorSearch POST should use POST');
    assertSame('/collections/books/vector_search', $client->lastPath, 'Collections::vectorSearch path mismatch');

    $client->collections()->searchAlias('books', ['vector' => [1, 2]]);
    assertSame('GET', $client->lastMethod, 'Collections::searchAlias should use GET by default');
    assertSame('/collections/books/search', $client->lastPath, 'Collections::searchAlias path mismatch');

    $client->documents()->updateByQuery('books', ['filter' => 'x']);
    assertSame('/collections/books/documents/_update_by_query', $client->lastPath, 'Documents::updateByQuery path mismatch');

    $client->documents()->deleteByQuery('books', ['filter' => 'x']);
    assertSame('/collections/books/documents/_delete_by_query', $client->lastPath, 'Documents::deleteByQuery path mismatch');

    $client->documents()->context('books', 'doc-1');
    assertSame('/collections/books/documents/doc-1/context', $client->lastPath, 'Documents::context path mismatch');

    $client->sam()->addDocumentLabel('books', 'doc-1', 'queen of pop');
    assertSame('POST', $client->lastMethod, 'SAM::addDocumentLabel should use POST');
    assertSame('/sam/label/add/books/doc-1', $client->lastPath, 'SAM::addDocumentLabel path mismatch');
    assertSame(['label' => 'queen of pop'], $client->lastPayload, 'SAM::addDocumentLabel payload mismatch');
});

run('Resource services shape requests', function (): void {
    $client = new TestClient('http://localhost:9200');

    $client->synonyms()->upsert('books', 'car', ['synonyms' => ['auto']]);
    assertSame('PUT', $client->lastMethod, 'Synonyms::upsert should use PUT');
    assertSame('/collections/books/synonyms/car', $client->lastPath, 'Synonyms::upsert path mismatch');

    $client->stopwords()->create('books', ['word' => 'the']);
    assertSame('POST', $client->lastMethod, 'Stopwords::create should use POST');
    assertSame('/collections/books/stopwords', $client->lastPath, 'Stopwords::create path mismatch');

    $client->overrides()->get('books', 'ovr-1');
    assertSame('GET', $client->lastMethod, 'Overrides::get should use GET');
    assertSame('/collections/books/overrides/ovr-1', $client->lastPath, 'Overrides::get path mismatch');

    $client->aliases()->upsert('alias_1', ['collection' => 'books']);
    assertSame('PUT', $client->lastMethod, 'Aliases::upsert should use PUT');
    assertSame('/aliases/alias_1', $client->lastPath, 'Aliases::upsert path mismatch');

    $client->links()->connect('127.0.0.1:9201');
    assertSame('POST', $client->lastMethod, 'Links::connect should use POST');
    assertSame('/links/connect', $client->lastPath, 'Links::connect path mismatch');
    assertSame(['endpoint' => '127.0.0.1:9201'], $client->lastPayload, 'Links::connect payload mismatch');

    $client->users()->update('u1', ['role' => 'admin']);
    assertSame('PUT', $client->lastMethod, 'Users::update should use PUT');
    assertSame('/users/u1', $client->lastPath, 'Users::update path mismatch');

    $client->modules()->syntax('ranker');
    assertSame('GET', $client->lastMethod, 'Modules::syntax should use GET');
    assertSame('/modules/ranker/syntax', $client->lastPath, 'Modules::syntax path mismatch');

    $client->analytics()->click(['collection' => 'books']);
    assertSame('POST', $client->lastMethod, 'Analytics::click should use POST');
    assertSame('/analytics/click', $client->lastPath, 'Analytics::click path mismatch');

    $client->modules()->loadWithPayload(['module' => 'ranker']);
    assertSame('POST', $client->lastMethod, 'Modules::loadWithPayload should use POST');
    assertSame('/loadmodule', $client->lastPath, 'Modules::loadWithPayload path mismatch');

    $client->sql()->queryGet('books', 'select * from books');
    assertSame('GET', $client->lastMethod, 'SQL::queryGet should use GET');
    assertSame('/sql', $client->lastPath, 'SQL::queryGet path mismatch');
    assertSame(['collection' => 'books', 'q' => 'select * from books'], $client->lastQuery, 'SQL::queryGet query mismatch');

    $client->multiSearchGet(['q' => 'x']);
    assertSame('GET', $client->lastMethod, 'Client::multiSearchGet should use GET');
    assertSame('/multi_search', $client->lastPath, 'Client::multiSearchGet path mismatch');
    assertSame(['q' => 'x'], $client->lastQuery, 'Client::multiSearchGet query mismatch');
});

run('Response getMessage behavior', function (): void {
    $r1 = new Hlquery\Response(500, [], ['message' => 'bad'], '{"message":"bad"}', '');
    assertSame('bad', $r1->getMessage(), 'message should be used when present');

    $r2 = new Hlquery\Response(500, [], ['error' => 'nope'], '{"error":"nope"}', '');
    assertSame('nope', $r2->getMessage(), 'error should be used when message missing');

    $r3 = new Hlquery\Response(500, [], ['error' => 'nope'], '{"error":"nope"}', 'curl failed');
    assertSame('curl failed', $r3->getMessage(), 'error string should override body');

    $r4 = new Hlquery\Response(204, [], null, '', '');
    assertTrue($r4->isSuccess(), '2xx should be success');
});

fwrite(STDOUT, "PHP tests passed.\n");
