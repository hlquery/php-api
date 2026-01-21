<?php
/*
 * hlquery PHP Client - Exceptions
 * 
 * Copyright (C) 2021-2026, Carlos F. Ferry <carlos.ferry@gmail.com>
 * 
 * This file is part of hlquery, released under the BSD License version 3.
 */

namespace Hlquery;

/*
 * Base exception class for hlquery client
 */
class HlqueryException extends \Exception {}

/*
 * Exception thrown when authentication fails
 */
class AuthenticationException extends HlqueryException {}

/*
 * Exception thrown when a request fails
 */
class RequestException extends HlqueryException {
    private $statusCode;
    private $responseBody;
    
    public function __construct($message, $statusCode = 0, $responseBody = null, $code = 0, \Exception $previous = null) {
        parent::__construct($message, $code, $previous);
        $this->statusCode = $statusCode;
        $this->responseBody = $responseBody;
    }
    
    public function getStatusCode() {
        return $this->statusCode;
    }
    
    public function getResponseBody() {
        return $this->responseBody;
    }
}

/*
 * Exception thrown when validation fails
 */
class ValidationException extends HlqueryException {}

/*
 * Exception thrown when a collection operation fails
 */
class CollectionException extends HlqueryException {}

/*
 * Exception thrown when a document operation fails
 */
class DocumentException extends HlqueryException {}

/*
 * Exception thrown when a search operation fails
 */
class SearchException extends HlqueryException {}
