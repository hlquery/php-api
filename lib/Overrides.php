<?php

namespace Hlquery;

use Hlquery\Utils\Validator;

class Overrides {
    private $request;

    public function __construct(Request $request) {
        $this->request = $request;
    }

    public function list($collectionName) { Validator::validateCollectionName($collectionName); return $this->request->execute('GET', '/collections/' . rawurlencode($collectionName) . '/overrides'); }
    public function get($collectionName, $id) { Validator::validateCollectionName($collectionName); Validator::validateDocumentId($id); return $this->request->execute('GET', '/collections/' . rawurlencode($collectionName) . '/overrides/' . rawurlencode($id)); }
    public function create($collectionName, $id, $override) { Validator::validateCollectionName($collectionName); Validator::validateDocumentId($id); return $this->request->execute('POST', '/collections/' . rawurlencode($collectionName) . '/overrides/' . rawurlencode($id), $override); }
    public function update($collectionName, $id, $override) { Validator::validateCollectionName($collectionName); Validator::validateDocumentId($id); return $this->request->execute('PUT', '/collections/' . rawurlencode($collectionName) . '/overrides/' . rawurlencode($id), $override); }
    public function upsert($collectionName, $id, $override) { return $this->create($collectionName, $id, $override); }
    public function delete($collectionName, $id) { Validator::validateCollectionName($collectionName); Validator::validateDocumentId($id); return $this->request->execute('DELETE', '/collections/' . rawurlencode($collectionName) . '/overrides/' . rawurlencode($id)); }
}
