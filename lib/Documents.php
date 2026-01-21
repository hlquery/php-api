<?php
/*
 * hlquery PHP Client - Documents API
 * 
 * Copyright (C) 2021-2026, Carlos F. Ferry <carlos.ferry@gmail.com>
 * 
 * This file is part of hlquery, released under the BSD License version 3.
 */

namespace Hlquery;

use Hlquery\Utils\Validator;

/*
 * Documents API operations
 */
class Documents {
    private $request;
    
    public function __construct(Request $request) {
        $this->request = $request;
    }
    
    /*
     * List documents in a collection
     * 
     * @param string $collectionName
     * @param array $params Pagination parameters
     * @return Response
     */
    public function list($collectionName, $params = []) {
        Validator::validateCollectionName($collectionName);
        Validator::validateSearchParams($params);
        
        $offset = $params['offset'] ?? $params['from'] ?? 0;
        $limit = $params['limit'] ?? $params['size'] ?? 10;
        
        return $this->request->execute('GET', '/collections/' . urlencode($collectionName) . '/documents', null, [
            'offset' => $offset,
            'limit' => $limit
        ]);
    }
    
    /*
     * Get a single document by ID
     * 
     * @param string $collectionName
     * @param string $documentId
     * @return Response
     */
    public function get($collectionName, $documentId) {
        Validator::validateCollectionName($collectionName);
        Validator::validateDocumentId($documentId);
        
        return $this->request->execute('GET', '/collections/' . urlencode($collectionName) . '/documents/' . urlencode($documentId));
    }
    
    /*
     * Add a document
     * 
     * @param string $collectionName
     * @param array $document
     * @return Response
     */
    public function add($collectionName, $document) {
        Validator::validateCollectionName($collectionName);
        Validator::validateDocumentFields($document);
        
        return $this->request->execute('POST', '/collections/' . urlencode($collectionName) . '/documents', $document);
    }
    
    /*
     * Update a document
     * 
     * @param string $collectionName
     * @param string $documentId
     * @param array $document
     * @return Response
     */
    public function update($collectionName, $documentId, $document) {
        Validator::validateCollectionName($collectionName);
        Validator::validateDocumentId($documentId);
        Validator::validateDocumentFields($document);
        
        return $this->request->execute('PUT', '/collections/' . urlencode($collectionName) . '/documents/' . urlencode($documentId), $document);
    }
    
    /*
     * Delete a document
     * 
     * @param string $collectionName
     * @param string $documentId
     * @return Response
     */
    public function delete($collectionName, $documentId) {
        Validator::validateCollectionName($collectionName);
        Validator::validateDocumentId($documentId);
        
        return $this->request->execute('DELETE', '/collections/' . urlencode($collectionName) . '/documents/' . urlencode($documentId));
    }
    
    /*
     * Import documents (bulk)
     * 
     * @param string $collectionName
     * @param array $documents
     * @return Response
     */
    public function import($collectionName, $documents) {
        Validator::validateCollectionName($collectionName);
        
        // Validate each document in the array
        if (is_array($documents)) {
            foreach ($documents as $doc) {
                Validator::validateDocumentFields($doc);
            }
        }
        
        // API expects documents wrapped in 'documents' key
        $body = [
            'documents' => $documents
        ];
        
        return $this->request->execute('POST', '/collections/' . urlencode($collectionName) . '/documents/import', $body);
    }
    
    /*
     * Delete documents by filter
     * 
     * @param string $collectionName
     * @param string $filter
     * @return Response
     */
    public function deleteByFilter($collectionName, $filter) {
        Validator::validateCollectionName($collectionName);
        
        return $this->request->execute('DELETE', '/collections/' . urlencode($collectionName) . '/documents', null, [
            'filter_by' => $filter
        ]);
    }
}
