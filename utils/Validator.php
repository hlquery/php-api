<?php
/*
 * hlquery PHP Client - Input Validation
 * 
 * Copyright (C) 2021-2026, Carlos F. Ferry <carlos.ferry@gmail.com>
 * 
 * This file is part of hlquery, released under the BSD License version 3.
 */

namespace Hlquery\Utils;

// ValidationException is in Hlquery namespace, not Hlquery\Utils
// We need to reference it with full namespace or import it
// Since autoloader should handle it, we'll use the full namespace

/*
 * Input validation utility class
 */
class Validator {
    private static function requireNonEmptyString($value, $fieldName) {
        if (!is_string($value)) {
            throw new \Hlquery\ValidationException($fieldName . " must be a non-empty string");
        }

        if (trim($value) === '') {
            throw new \Hlquery\ValidationException($fieldName . " must be a non-empty string");
        }
    }

    /*
     * Validate collection name
     * 
     * @param string $name
     * @throws ValidationException
     */
    public static function validateCollectionName($name) {
        self::requireNonEmptyString($name, 'Collection name');
        
        // Check name length (matches server validation: 1-64 characters)
        if (strlen($name) > 64) {
            throw new \Hlquery\ValidationException("Collection name must be between 1 and 64 characters");
        }
        
        // Collection names should be URL-safe
        if (preg_match('/[^a-zA-Z0-9_-]/', $name)) {
            throw new \Hlquery\ValidationException("Collection name contains invalid characters. Use only letters, numbers, underscores, and hyphens");
        }
        
        // Check if name starts with letter or underscore (matches server validation)
        $firstChar = $name[0];
        if (!ctype_alpha($firstChar) && $firstChar !== '_') {
            throw new \Hlquery\ValidationException("Collection name must start with a letter or underscore");
        }
    }
    
    /*
     * Validate document ID
     * 
     * @param string $id
     * @throws ValidationException
     */
    public static function validateDocumentId($id) {
        self::requireNonEmptyString($id, 'Document ID');
        
        // Matches server validation: 1-256 characters.
        if (strlen($id) > 256) {
            throw new \Hlquery\ValidationException("Document ID must be between 1 and 256 characters");
        }
        
        // Matches server validation: alphanumeric, underscores, hyphens, and dots.
        if (preg_match('/[^a-zA-Z0-9_.-]/', $id)) {
            throw new \Hlquery\ValidationException("Document ID contains invalid characters. Use only letters, numbers, underscores, hyphens, and dots");
        }
    }

    /*
     * Validate alias name
     *
     * @param string $name
     * @throws ValidationException
     */
    public static function validateAliasName($name) {
        self::requireNonEmptyString($name, 'Alias name');

        if (strlen($name) > 64) {
            throw new \Hlquery\ValidationException("Alias name must be between 1 and 64 characters");
        }

        if (preg_match('/[^a-zA-Z0-9_.-]/', $name)) {
            throw new \Hlquery\ValidationException("Alias name contains invalid characters. Use only letters, numbers, underscores, hyphens, and dots");
        }

        $firstChar = $name[0];
        if (!ctype_alpha($firstChar) && $firstChar !== '_') {
            throw new \Hlquery\ValidationException("Alias name must start with a letter or underscore");
        }
    }
    
    /*
     * Validate pagination parameters
     * 
     * @param int $offset
     * @param int $limit
     * @throws ValidationException
     */
    public static function validatePagination($offset, $limit) {
        if (!is_int($offset) || $offset < 0) {
            throw new \Hlquery\ValidationException("Offset must be a non-negative integer");
        }
        
        if (!is_int($limit) || $limit < 1) {
            throw new \Hlquery\ValidationException("Limit must be a positive integer");
        }
        
        if ($limit > 1000) {
            throw new \Hlquery\ValidationException("Limit cannot exceed 1000");
        }
    }
    
    /*
     * Validate search parameters
     * 
     * @param array $params
     * @throws ValidationException
     */
    public static function validateSearchParams($params) {
        if (!is_array($params)) {
            throw new \Hlquery\ValidationException("Search params must be an array");
        }

        if (isset($params['limit']) && (!is_int($params['limit']) || $params['limit'] < 1)) {
            throw new \Hlquery\ValidationException("Limit must be a positive integer");
        }
        
        if (isset($params['offset']) && (!is_int($params['offset']) || $params['offset'] < 0)) {
            throw new \Hlquery\ValidationException("Offset must be a non-negative integer");
        }
        
        if (isset($params['page']) && (!is_int($params['page']) || $params['page'] < 1)) {
            throw new \Hlquery\ValidationException("Page must be a positive integer");
        }
    }
    
    /*
     * Validate document field values for invalid characters
     * Commas are not allowed in string field values as they're reserved for internal parsing
     * 
     * @param array|object $document Document object to validate
     * @throws ValidationException If document contains invalid characters
     */
    public static function validateDocumentFields($document) {
        if (!$document || (!is_array($document) && !is_object($document))) {
            return; // Skip validation for non-objects/arrays (will be validated per-item)
        }
        
        // Convert object to array for easier iteration
        $docArray = is_object($document) ? (array)$document : $document;
        
        foreach ($docArray as $key => $value) {
            // Skip the 'id' field as it has its own validation
            if ($key === 'id') continue;
            
            // Check string values for commas - skip embedding/vector fields
            if (is_string($value) && strpos($value, ',') !== false) {
                // Allow commas in embedding/vector fields for vector search
                if ($key !== 'embedding' && !preg_match('/_vector$/', $key)) {
                    throw new \Hlquery\ValidationException(
                        "Field '{$key}' contains invalid character: comma (`,`). " .
                        "Commas are not allowed in field values. Use underscores (_) or spaces instead, or use arrays for multiple values."
                    );
                }
            }
            
            // Check array values - ensure they don't contain strings with commas
            if (is_array($value)) {
                foreach ($value as $item) {
                    if (is_string($item) && strpos($item, ',') !== false) {
                        throw new \Hlquery\ValidationException(
                            "Field '{$key}' contains invalid character: comma (`,`). " .
                            "Array items cannot contain commas. Use underscores (_) or spaces instead."
                        );
                    }
                }
            }
        }
    }
}
