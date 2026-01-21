<?php
/*
 * hlquery PHP Client - Main Client Class
 * Elasticsearch-like API client for hlquery
 * 
 * Copyright (C) 2021-2026, Carlos F. Ferry <carlos.ferry@gmail.com>
 * 
 * This file is part of hlquery, released under the BSD License version 3.
 */

namespace Hlquery;

use Hlquery\Utils\Config;

/*
 * Main hlquery client class
 */
class Client {
    private $request;
    private $collections;
    private $documents;
    private $search;
    
    /*
     * Constructor
     * 
     * @param string $baseUrl Base URL of hlquery server
     * @param array $options Additional options:
     *   - timeout: Request timeout in seconds (default: 30)
     *   - token: Authentication token
     *   - auth_method: 'bearer' or 'api-key' (default: 'bearer')
     */
    public function __construct($baseUrl = null, $options = []) {
        $options = Config::mergeDefaults($options);
        
        $baseUrl = $baseUrl ?? $options['base_url'];
        $baseUrl = Config::normalizeUrl($baseUrl);
        
        if (!Config::isValidUrl($baseUrl)) {
            throw new \InvalidArgumentException("Invalid base URL: " . $baseUrl);
        }
        
        $this->request = new Request(
            $baseUrl,
            $options['timeout'],
            $options['token'] ?? null,
            $options['auth_method'] ?? 'bearer'
        );
        
        $this->collections = new Collections($this->request);
        $this->documents = new Documents($this->request);
        $this->search = new Search($this->request, $this->collections);
    }
    
    /*
     * Set authentication token
     * 
     * @param string $token
     * @param string $method 'bearer' or 'api-key'
     * @return $this
     */
    public function setAuthToken($token, $method = 'bearer') {
        $this->request->setAuthToken($token, $method);
        return $this;
    }
    
    /*
     * Clear authentication
     * 
     * @return $this
     */
    public function clearAuth() {
        $this->request->clearAuth();
        return $this;
    }
    
    // ============================================================================
    // CLUSTER & NODE APIs
    // ============================================================================
    
    /*
     * Health check
     * 
     * @return Response
     */
    public function health() {
        return $this->request->execute('GET', '/health');
    }
    
    /*
     * Get server stats
     * 
     * @return Response
     */
    public function stats() {
        return $this->request->execute('GET', '/stats');
    }
    
    /*
     * Get protocol codes for API communication
     * Returns HTTP status codes and protocol information
     * 
     * @return Response
     */
    public function etc() {
        return $this->request->execute('GET', '/etc');
    }
    
    /*
     * Get server info
     * 
     * @return Response
     */
    public function info() {
        return $this->request->execute('GET', '/');
    }
    
    /*
     * Get cluster health status
     * 
     * @return Response
     */
    public function clusterHealth() {
        return $this->request->execute('GET', '/cluster/health');
    }
    
    /*
     * Get cluster statistics
     * 
     * @return Response
     */
    public function clusterStats() {
        return $this->request->execute('GET', '/cluster/stats');
    }
    
    /*
     * Get list of nodes in the cluster
     * 
     * @return Response
     */
    public function clusterNodes() {
        return $this->request->execute('GET', '/cluster/nodes');
    }
    
    // ============================================================================
    // COLLECTIONS API
    // ============================================================================
    
    /*
     * Get collections API instance
     * 
     * @return Collections
     */
    public function collections() {
        return $this->collections;
    }
    
    /*
     * List all collections
     * 
     * @param int $offset
     * @param int $limit
     * @return Response
     */
    public function listCollections($offset = 0, $limit = 10) {
        return $this->collections->list($offset, $limit);
    }
    
    /*
     * Get collection details
     * 
     * @param string $name
     * @return Response
     */
    public function getCollection($name) {
        return $this->collections->get($name);
    }
    
    /*
     * Get collection fields (formatted)
     * 
     * @param string $name
     * @return Response
     */
    public function getCollectionFields($name) {
        return $this->collections->getFields($name);
    }
    
    // ============================================================================
    // DOCUMENTS API
    // ============================================================================
    
    /*
     * Get documents API instance
     * 
     * @return Documents
     */
    public function documents() {
        return $this->documents;
    }
    
    /*
     * List documents in a collection
     * 
     * @param string $collectionName
     * @param array $params
     * @return Response
     */
    public function listDocuments($collectionName, $params = []) {
        return $this->documents->list($collectionName, $params);
    }
    
    /*
     * Get a document by ID
     * 
     * @param string $collectionName
     * @param string $documentId
     * @return Response
     */
    public function getDocument($collectionName, $documentId) {
        return $this->documents->get($collectionName, $documentId);
    }
    
    // ============================================================================
    // SEARCH API
    // ============================================================================
    
    /*
     * Get search API instance
     * 
     * @return Search
     */
    public function searchApi() {
        return $this->search;
    }
    
    /*
     * Search documents
     * 
     * @param string $collectionName
     * @param array $params
     * @return Response
     */
    public function search($collectionName, $params = []) {
        return $this->search->search($collectionName, $params);
    }
    
    /*
     * Vector search
     * 
     * @param string $collectionName
     * @param array $params
     * @return Response
     */
    public function vectorSearch($collectionName, $params = []) {
        return $this->search->vectorSearch($collectionName, $params);
    }
    
    /*
     * Execute arbitrary HTTP request
     * 
     * @param string $method HTTP method
     * @param string $path API path
     * @param array|string|null $body Request body
     * @param array $queryParams Query parameters
     * @return Response
     */
    public function executeRequest($method, $path, $body = null, $queryParams = []) {
        return $this->request->execute($method, $path, $body, $queryParams);
    }
    
    // ============================================================================
    // ELASTICSEARCH-LIKE ALIASES
    // ============================================================================
    
    /*
     * Alias for listCollections() - Elasticsearch compatibility
     * 
     * @param array $params
     * @return Response
     */
    public function indices($params = []) {
        return $this->listCollections(
            $params['offset'] ?? 0,
            $params['limit'] ?? 10
        );
    }
    
    /*
     * Alias for getCollection() or getDocument() - Elasticsearch compatibility
     * 
     * @param array $params
     * @return Response
     */
    public function get($params) {
        if (isset($params['index']) && isset($params['id'])) {
            return $this->getDocument($params['index'], $params['id']);
        } elseif (isset($params['index'])) {
            return $this->getCollection($params['index']);
        }
        throw new \InvalidArgumentException("Invalid parameters for get()");
    }
    
    /*
     * Alias for listCollections() - Elasticsearch cat API
     * 
     * @param string $type
     * @param array $params
     * @return Response
     */
    public function cat($type = 'indices', $params = []) {
        if ($type === 'indices') {
            return $this->indices($params);
        }
        throw new \InvalidArgumentException("Unsupported cat type: " . $type);
    }
}
