<?php

namespace Hlquery;

use Hlquery\Utils\Validator;

class Documents {
    private $request;

    public function __construct(Request $request) {
        $this->request = $request;
    }

    public function list($collectionName, $params = []) {
        Validator::validateCollectionName($collectionName);
        Validator::validateSearchParams($params);

        $offset = $params['offset'] ?? ($params['from'] ?? 0);
        $limit = $params['limit'] ?? ($params['size'] ?? 10);

        return $this->request->execute(
            'GET',
            '/collections/' . rawurlencode($collectionName) . '/documents',
            null,
            ['offset' => $offset, 'limit' => $limit]
        );
    }

    public function get($collectionName, $documentId) {
        Validator::validateCollectionName($collectionName);
        Validator::validateDocumentId($documentId);
        return $this->request->execute('GET', '/collections/' . rawurlencode($collectionName) . '/documents/' . rawurlencode($documentId));
    }

    public function add($collectionName, $document) {
        Validator::validateCollectionName($collectionName);
        Validator::validateDocumentFields($document);
        return $this->request->execute('POST', '/collections/' . rawurlencode($collectionName) . '/documents', $document);
    }

    public function update($collectionName, $documentId, $document) {
        Validator::validateCollectionName($collectionName);
        Validator::validateDocumentId($documentId);
        Validator::validateDocumentFields($document);
        return $this->request->execute('PUT', '/collections/' . rawurlencode($collectionName) . '/documents/' . rawurlencode($documentId), $document);
    }

    public function delete($collectionName, $documentId) {
        Validator::validateCollectionName($collectionName);
        Validator::validateDocumentId($documentId);
        return $this->request->execute('DELETE', '/collections/' . rawurlencode($collectionName) . '/documents/' . rawurlencode($documentId));
    }

    public function import($collectionName, $documents) {
        Validator::validateCollectionName($collectionName);
        if (is_array($documents)) {
            foreach ($documents as $document) {
                Validator::validateDocumentFields($document);
            }
        }
        return $this->request->execute(
            'POST',
            '/collections/' . rawurlencode($collectionName) . '/documents/import',
            ['documents' => $documents]
        );
    }

    public function deleteByFilter($collectionName, $filter) {
        Validator::validateCollectionName($collectionName);
        return $this->request->execute(
            'DELETE',
            '/collections/' . rawurlencode($collectionName) . '/documents',
            null,
            ['filter_by' => $filter]
        );
    }

    public function facetCounts($collectionName, $params = []) {
        Validator::validateCollectionName($collectionName);
        Validator::validateSearchParams($params);
        $method = array_key_exists('body', $params) ? 'POST' : 'GET';
        $body = $method === 'POST' ? $params['body'] : null;
        $query = $method === 'GET' ? $params : [];
        return $this->request->execute($method, '/collections/' . rawurlencode($collectionName) . '/documents/facet_counts', $body, $query);
    }

    public function export($collectionName, $params = []) {
        Validator::validateCollectionName($collectionName);
        Validator::validateSearchParams($params);
        $method = array_key_exists('body', $params) ? 'POST' : 'GET';
        $body = $method === 'POST' ? $params['body'] : null;
        $query = $method === 'GET' ? $params : [];
        return $this->request->execute($method, '/collections/' . rawurlencode($collectionName) . '/documents/export', $body, $query);
    }
}
