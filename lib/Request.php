<?php
/*
 * hlquery PHP Client - HTTP Request Handler
 * 
 * Copyright (C) 2021-2026, Carlos F. Ferry <carlos.ferry@gmail.com>
 * 
 * This file is part of hlquery, released under the BSD License version 3.
 */

namespace Hlquery;

/*
 * HTTP request handler
 */
class Request {
    private $baseUrl;
    private $timeout;
    private $authToken;
    private $authMethod;
    
    public function __construct($baseUrl, $timeout = 30, $authToken = null, $authMethod = 'bearer') {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->timeout = $timeout;
        $this->authToken = $authToken;
        $this->authMethod = $authMethod;
    }
    
    public function setAuthToken($token, $method = 'bearer') {
        $this->authToken = $token;
        $this->authMethod = $method;
    }
    
    public function clearAuth() {
        $this->authToken = null;
    }
    
    /*
     * Make HTTP request
     * 
     * @param string $method HTTP method
     * @param string $path API path
     * @param array|string|null $body Request body
     * @param array $queryParams Query parameters
     * @return Response
     * @throws RequestException
     */
    public function execute($method, $path, $body = null, $queryParams = []) {
        $url = $this->baseUrl . $path;
        
        if (!empty($queryParams)) {
            $url .= '?' . http_build_query($queryParams);
        }
        
        $ch = curl_init($url);
        $headers = [
            'Content-Type: application/json',
            'Accept: application/json'
        ];
        
        // Add authentication header if token is set
        if ($this->authToken !== null) {
            if ($this->authMethod === 'api-key') {
                $headers[] = 'X-API-Key: ' . $this->authToken;
            } else {
                $headers[] = 'Authorization: Bearer ' . $this->authToken;
            }
        }
        
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_HEADERFUNCTION => function($ch, $header) use (&$responseHeaders) {
                $len = strlen($header);
                $header = explode(':', $header, 2);
                if (count($header) == 2) {
                    $responseHeaders[strtolower(trim($header[0]))] = trim($header[1]);
                }
                return $len;
            }
        ]);
        
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, is_string($body) ? $body : json_encode($body));
        }
        
        $responseHeaders = [];
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new RequestException("cURL error: " . $error, 0);
        }
        
        if ($response === false) {
            throw new RequestException("Failed to get response from server", $httpCode ?: 500);
        }
        
        $decoded = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new Response($httpCode, $response, $responseHeaders);
        }
        
        // Check if server rejected token because auth is disabled
        if ($httpCode === 403 && isset($decoded['error']) && 
            (strpos($decoded['error'], 'Authentication is disabled') !== false ||
             (isset($decoded['message']) && strpos($decoded['message'], 'Tokens are not accepted when authentication is disabled') !== false))) {
            throw new AuthenticationException(
                "Authentication is disabled on the server. Remove the token from your client configuration. Server message: " . 
                (isset($decoded['message']) ? $decoded['message'] : $decoded['error'])
            );
        }
        
        return new Response($httpCode, $decoded, $responseHeaders);
    }
}
