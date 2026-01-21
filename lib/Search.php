<?php
/*
 * hlquery PHP Client - Search API
 * 
 * Copyright (C) 2021-2026, Carlos F. Ferry <carlos.ferry@gmail.com>
 * 
 * This file is part of hlquery, released under the BSD License version 3.
 */

namespace Hlquery;

use Hlquery\Utils\Validator;

/*
 * Search API operations
 */
class Search {
    private $request;
    private $collections;
    
    public function __construct(Request $request, Collections $collections) {
        $this->request = $request;
        $this->collections = $collections;
    }
    
    /*
     * Search documents
     * 
     * @param string $collectionName
     * @param array $params Search parameters
     * @return Response
     */
    public function search($collectionName, $params = []) {
        Validator::validateCollectionName($collectionName);
        Validator::validateSearchParams($params);
        
        $queryParams = [];
        
        // Handle structured query object (Elasticsearch-like)
        if (isset($params['query'])) {
            if (isset($params['query']['q'])) {
                $queryParams['q'] = $params['query']['q'];
            }
            if (isset($params['query']['query_by'])) {
                $queryParams['query_by'] = is_array($params['query']['query_by']) 
                    ? implode(',', $params['query']['query_by']) 
                    : $params['query']['query_by'];
            }
        }
        
        // Direct query parameters
        if (isset($params['q'])) {
            $queryParams['q'] = $params['q'];
        }
        
        // Fields to search in
        if (isset($params['query_by'])) {
            $queryParams['query_by'] = is_array($params['query_by']) 
                ? implode(',', $params['query_by']) 
                : $params['query_by'];
        } elseif (isset($params['q']) && $params['q'] !== '') {
            // Auto-detect searchable fields
            $collection = $this->collections->get($collectionName);
            if ($collection->getStatusCode() === 200) {
                $body = $collection->getBody();
                if (isset($body['searchable_fields']) && !empty($body['searchable_fields'])) {
                    $queryParams['query_by'] = implode(',', $body['searchable_fields']);
                }
            }
        }
        
        // Pagination
        if (isset($params['from'])) {
            $queryParams['offset'] = $params['from'];
        } elseif (isset($params['offset'])) {
            $queryParams['offset'] = $params['offset'];
        }
        
        if (isset($params['size'])) {
            $queryParams['limit'] = $params['size'];
        } elseif (isset($params['limit'])) {
            $queryParams['limit'] = $params['limit'];
        }
        
        // Page-based pagination
        if (isset($params['page'])) {
            $queryParams['page'] = $params['page'];
        }
        if (isset($params['per_page'])) {
            $queryParams['per_page'] = $params['per_page'];
        }
        
        // Filter
        if (isset($params['filter_by'])) {
            $queryParams['filter_by'] = $params['filter_by'];
        } elseif (isset($params['filter'])) {
            $queryParams['filter_by'] = is_array($params['filter']) 
                ? json_encode($params['filter']) 
                : $params['filter'];
        }
        
        // Sort
        if (isset($params['sort'])) {
            if (is_array($params['sort'])) {
                $sortFields = [];
                foreach ($params['sort'] as $sortItem) {
                    if (is_array($sortItem)) {
                        foreach ($sortItem as $field => $order) {
                            $sortFields[] = $order === 'desc' ? '-' . $field : $field;
                        }
                    } else {
                        $sortFields[] = $sortItem;
                    }
                }
                $queryParams['sort_by'] = implode(',', $sortFields);
            } else {
                $queryParams['sort_by'] = $params['sort'];
            }
        } elseif (isset($params['sort_by'])) {
            $queryParams['sort_by'] = is_array($params['sort_by'])
                ? implode(',', $params['sort_by'])
                : $params['sort_by'];
        }
        
        // Facets
        if (isset($params['facet_by'])) {
            $queryParams['facet_by'] = is_array($params['facet_by'])
                ? implode(',', $params['facet_by'])
                : $params['facet_by'];
        } elseif (isset($params['facets'])) {
            $queryParams['facet_by'] = is_array($params['facets'])
                ? implode(',', $params['facets'])
                : $params['facets'];
        }
        
        // Additional search parameters
        if (isset($params['typo_tolerance'])) {
            $queryParams['typo_tolerance'] = $params['typo_tolerance'];
        }
        
        if (isset($params['num_typos'])) {
            $queryParams['num_typos'] = $params['num_typos'];
        }
        
        // Highlighting parameters
        if (isset($params['highlight'])) {
            $queryParams['highlight'] = ($params['highlight'] === true || $params['highlight'] === 'true' || $params['highlight'] === 1) ? 'true' : 'false';
        }
        
        if (isset($params['highlight_fields'])) {
            $queryParams['highlight_fields'] = is_array($params['highlight_fields'])
                ? implode(',', $params['highlight_fields'])
                : $params['highlight_fields'];
        }
        
        if (isset($params['highlight_full_fields'])) {
            $queryParams['highlight_full_fields'] = is_array($params['highlight_full_fields'])
                ? implode(',', $params['highlight_full_fields'])
                : $params['highlight_full_fields'];
        }
        
        // Determine HTTP method
        $method = isset($params['body']) ? 'POST' : 'GET';
        $body = isset($params['body']) ? $params['body'] : null;
        
        return $this->request->execute($method, '/collections/' . urlencode($collectionName) . '/documents/search', $body, $queryParams);
    }
    
    /*
     * Multi-search across multiple collections
     * 
     * @param array $searches Array of search requests
     * @return Response
     */
    public function multiSearch($searches) {
        return $this->request->execute('POST', '/multi_search', ['searches' => $searches]);
    }
    
    /*
     * Vector search
     * 
     * @param string $collectionName
     * @param array $params Vector search parameters:
     *   - vector_query: Array of floats or JSON string
     *   - embedding: Array of floats or JSON string (alias for vector_query)
     *   - field_name: Field name to search in (default: 'embedding')
     *   - limit: Number of results (default: 10)
     *   - threshold: Similarity threshold (default: 0.0)
     *   - normalize: Normalize vectors (default: true)
     * @return Response
     */
    public function vectorSearch($collectionName, $params = []) {
        Validator::validateCollectionName($collectionName);
        
        $queryParams = [];
        
        // Handle vector query
        if (isset($params['vector_query'])) {
            if (is_array($params['vector_query'])) {
                $queryParams['vector_query'] = json_encode($params['vector_query']);
            } else {
                $queryParams['vector_query'] = $params['vector_query'];
            }
        } elseif (isset($params['embedding'])) {
            if (is_array($params['embedding'])) {
                $queryParams['vector_query'] = json_encode($params['embedding']);
            } else {
                $queryParams['vector_query'] = $params['embedding'];
            }
        }
        
        if (isset($params['field_name'])) {
            $queryParams['field_name'] = $params['field_name'];
        }
        
        if (isset($params['limit'])) {
            $queryParams['limit'] = $params['limit'];
        }
        
        if (isset($params['threshold'])) {
            $queryParams['threshold'] = $params['threshold'];
        }
        
        if (isset($params['normalize'])) {
            $queryParams['normalize'] = $params['normalize'] ? 'true' : 'false';
        }
        
        // Try vector_search endpoint first, fallback to search endpoint
        $path = '/collections/' . urlencode($collectionName) . '/vector_search';
        $method = isset($params['body']) ? 'POST' : 'GET';
        $body = isset($params['body']) ? $params['body'] : null;
        
        return $this->request->execute($method, $path, $body, $queryParams);
    }
}
