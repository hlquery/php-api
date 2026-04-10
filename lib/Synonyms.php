<?php

namespace Hlquery;

use Hlquery\Utils\Validator;

class Synonyms {
    private $request;

    public function __construct(Request $request) {
        $this->request = $request;
    }

    public function list($collectionName) { Validator::validateCollectionName($collectionName); return $this->request->execute('GET', '/collections/' . rawurlencode($collectionName) . '/synonyms'); }
    public function get($collectionName, $id) { Validator::validateCollectionName($collectionName); Validator::validateDocumentId($id); return $this->request->execute('GET', '/collections/' . rawurlencode($collectionName) . '/synonyms/' . rawurlencode($id)); }
    public function create($collectionName, $id, $synonym) { Validator::validateCollectionName($collectionName); Validator::validateDocumentId($id); return $this->request->execute('POST', '/collections/' . rawurlencode($collectionName) . '/synonyms/' . rawurlencode($id), $synonym); }
    public function update($collectionName, $id, $synonym) { Validator::validateCollectionName($collectionName); Validator::validateDocumentId($id); return $this->request->execute('PUT', '/collections/' . rawurlencode($collectionName) . '/synonyms/' . rawurlencode($id), $synonym); }
    public function upsert($collectionName, $id, $synonym) { return $this->create($collectionName, $id, $synonym); }
    public function delete($collectionName, $id) { Validator::validateCollectionName($collectionName); Validator::validateDocumentId($id); return $this->request->execute('DELETE', '/collections/' . rawurlencode($collectionName) . '/synonyms/' . rawurlencode($id)); }
    public function listAll() { return $this->request->execute('GET', '/synonyms'); }
    public function listGlobal() { return $this->request->execute('GET', '/synonyms/global'); }
    public function getGlobal($id) { Validator::validateDocumentId($id); return $this->request->execute('GET', '/synonyms/global/' . rawurlencode($id)); }
    public function createGlobal($id, $synonym) { Validator::validateDocumentId($id); return $this->request->execute('POST', '/synonyms/global/' . rawurlencode($id), $synonym); }
    public function updateGlobal($id, $synonym) { Validator::validateDocumentId($id); return $this->request->execute('PUT', '/synonyms/global/' . rawurlencode($id), $synonym); }
    public function upsertGlobal($id, $synonym) { return $this->createGlobal($id, $synonym); }
    public function deleteGlobal($id) { Validator::validateDocumentId($id); return $this->request->execute('DELETE', '/synonyms/global/' . rawurlencode($id)); }
}
