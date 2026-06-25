<?php

declare(strict_types=1);

namespace Hlquery {
    class ValidationException extends \Exception {}
}

namespace {
    require_once __DIR__ . '/../utils/Config.php';
    require_once __DIR__ . '/../utils/Auth.php';
    require_once __DIR__ . '/../utils/Validator.php';

    use Hlquery\Utils\Auth;
    use Hlquery\Utils\Config;
    use Hlquery\Utils\Validator;

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

    fwrite(STDOUT, "PHP offline smoke tests passed.\n");
}
