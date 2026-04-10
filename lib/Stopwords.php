<?php

namespace Hlquery;

use Hlquery\Utils\Validator;

class Stopwords {
    private $request;

    public function __construct(Request $request) {
        $this->request = $request;
    }

    private function validateWord($word, $fieldName = 'word') {
        if (!is_string($word) || trim($word) === '') {
            throw new ValidationException($fieldName . ' must be a non-empty string');
        }
    }

    public function list($collectionName) { Validator::validateCollectionName($collectionName); return $this->request->execute('GET', '/collections/' . rawurlencode($collectionName) . '/stopwords'); }
    public function create($collectionName, $params) { Validator::validateCollectionName($collectionName); return $this->request->execute('POST', '/collections/' . rawurlencode($collectionName) . '/stopwords', $params); }
    public function delete($collectionName, $word) { Validator::validateCollectionName($collectionName); $this->validateWord($word); return $this->request->execute('DELETE', '/collections/' . rawurlencode($collectionName) . '/stopwords/' . rawurlencode($word)); }
    public function listAll() { return $this->request->execute('GET', '/stopwords'); }
    public function listGlobal() { return $this->request->execute('GET', '/stopwords/global'); }
    public function createGlobal($params) { return $this->request->execute('POST', '/stopwords/global', $params); }
    public function deleteGlobal($word) { $this->validateWord($word); return $this->request->execute('DELETE', '/stopwords/global/' . rawurlencode($word)); }
}
