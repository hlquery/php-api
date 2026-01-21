<?php
/*
 * hlquery PHP Client - Response Handler
 * 
 * Copyright (C) 2021-2026, Carlos F. Ferry <carlos.ferry@gmail.com>
 * 
 * This file is part of hlquery, released under the BSD License version 3.
 */

namespace Hlquery;

/*
 * Response wrapper for API responses
 */
class Response {
    private $statusCode;
    private $body;
    private $headers;
    
    public function __construct($statusCode, $body, $headers = []) {
        $this->statusCode = $statusCode;
        $this->body = $body;
        $this->headers = $headers;
    }
    
    public function getStatusCode() {
        return $this->statusCode;
    }
    
    public function getBody() {
        return $this->body;
    }
    
    public function getHeaders() {
        return $this->headers;
    }
    
    public function isSuccess() {
        return $this->statusCode >= 200 && $this->statusCode < 300;
    }
    
    public function isError() {
        return $this->statusCode >= 400;
    }
    
    public function getError() {
        if ($this->isError() && is_array($this->body)) {
            return $this->body['error'] ?? $this->body['message'] ?? 'Unknown error';
        }
        return null;
    }
    
    public function toArray() {
        return [
            'status' => $this->statusCode,
            'body' => $this->body
        ];
    }
}
