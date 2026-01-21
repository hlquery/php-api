<?php
/*
 * hlquery PHP Client - Collections API
 * 
 * Copyright (C) 2021-2026, Carlos F. Ferry <carlos.ferry@gmail.com>
 * 
 * This file is part of hlquery, released under the BSD License version 3.
 */

namespace Hlquery;

use Hlquery\Utils\Validator;

/*
 * Collections API operations
 */
class Collections {
    private $request;
    
    public function __construct(Request $request) {
        $this->request = $request;
    }
    
    /*
     * List all collections
     * 
     * @param int $offset Pagination offset
     * @param int $limit Number of results
     * @return Response
     */
    public function list($offset = 0, $limit = 10) {
        Validator::validatePagination($offset, $limit);
        return $this->request->execute('GET', '/collections', null, [
            'offset' => $offset,
            'limit' => $limit
        ]);
    }
    
    /*
     * Get collection details
     * 
     * @param string $name Collection name
     * @return Response
     */
    public function get($name) {
        Validator::validateCollectionName($name);
        return $this->request->execute('GET', '/collections/' . urlencode($name));
    }
    
    /*
     * Create a new collection
     * 
     * @param string $name Collection name
     * @param array $schema Collection schema
     * @return Response
     */
    public function create($name, $schema) {
        Validator::validateCollectionName($name);
        
        // Build request body - API expects 'name' and 'fields' directly (not nested in 'schema')
        $body = [
            'name' => $name
        ];
        
        // Extract fields from schema array
        if (is_array($schema)) {
            // If schema has 'fields' key, use it
            if (isset($schema['fields']) && is_array($schema['fields'])) {
                $body['fields'] = $schema['fields'];
            } elseif (isset($schema['searchable_fields']) && is_array($schema['searchable_fields'])) {
                // Support searchable_fields as alternative
                $body['searchable_fields'] = $schema['searchable_fields'];
            }
        }
        
        return $this->request->execute('POST', '/collections', $body);
    }
    
    /*
     * Delete a collection
     * 
     * @param string $name Collection name
     * @return Response
     */
    public function delete($name) {
        Validator::validateCollectionName($name);
        return $this->request->execute('DELETE', '/collections/' . urlencode($name));
    }
    
    /*
     * Update collection schema
     * 
     * @param string $name Collection name
     * @param array $schema Updated schema
     * @return Response
     */
    public function update($name, $schema) {
        Validator::validateCollectionName($name);
        return $this->request->execute('POST', '/collections/' . urlencode($name) . '/update', $schema);
    }
    
    /*
     * Get collection fields (formatted)
     * 
     * @param string $name Collection name
     * @return Response
     */
    public function getFields($name) {
        $response = $this->get($name);
        
        if ($response->getStatusCode() !== 200) {
            return $response;
        }
        
        $body = $response->getBody();
        $allFields = [];
        $fieldTypes = [];
        
        // Collect searchable fields
        if (isset($body['searchable_fields'])) {
            foreach ($body['searchable_fields'] as $field) {
                if (!in_array($field, $allFields)) {
                    $allFields[] = $field;
                }
                $fieldTypes[$field] = isset($fieldTypes[$field]) ? $fieldTypes[$field] : [];
                $fieldTypes[$field][] = 'searchable';
            }
        }
        
        // Collect filterable fields
        if (isset($body['filterable_fields'])) {
            foreach ($body['filterable_fields'] as $field) {
                if (!in_array($field, $allFields)) {
                    $allFields[] = $field;
                }
                $fieldTypes[$field] = isset($fieldTypes[$field]) ? $fieldTypes[$field] : [];
                $fieldTypes[$field][] = 'filterable';
            }
        }
        
        // Collect sortable fields
        if (isset($body['sortable_fields'])) {
            foreach ($body['sortable_fields'] as $field) {
                if (!in_array($field, $allFields)) {
                    $allFields[] = $field;
                }
                $fieldTypes[$field] = isset($fieldTypes[$field]) ? $fieldTypes[$field] : [];
                $fieldTypes[$field][] = 'sortable';
            }
        }
        
        // Format fields
        $fields = [];
        foreach ($allFields as $field) {
            $fields[] = [
                'name' => $field,
                'type' => implode(', ', $fieldTypes[$field])
            ];
        }
        
        return new Response(200, [
            'collection' => $name,
            'fields' => $fields,
            'field_count' => count($fields),
            'searchable_fields' => $body['searchable_fields'] ?? [],
            'filterable_fields' => $body['filterable_fields'] ?? [],
            'sortable_fields' => $body['sortable_fields'] ?? []
        ]);
    }
}
