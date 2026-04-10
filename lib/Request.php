<?php

namespace Hlquery;

class Request {
    private $baseUrl;
    private $timeout;
    private $authToken;
    private $authMethod;

    public function __construct($baseUrl, $timeout = 30, $authToken = null, $authMethod = 'bearer') {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->timeout = (int)$timeout;
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

    public function execute($method, $path, $body = null, $queryParams = []) {
        $url = $this->baseUrl . $path;
        $filteredQuery = [];
        foreach ($queryParams as $key => $value) {
            if ($value !== null) {
                $filteredQuery[$key] = $value;
            }
        }

        if (!empty($filteredQuery)) {
            $url .= '?' . http_build_query($filteredQuery);
        }

        $headers = [
            'Accept: application/json',
            'Content-Type: application/json',
        ];

        if ($this->authToken !== null) {
            if ($this->authMethod === 'api-key') {
                $headers[] = 'X-API-Key: ' . $this->authToken;
            } else {
                $headers[] = 'Authorization: Bearer ' . $this->authToken;
            }
        }

        $payload = null;
        if ($body !== null) {
            $payload = is_string($body) ? $body : json_encode($body);
            if ($payload === false) {
                throw new RequestException('Failed to encode request body');
            }
        }

        $responseHeaders = [];
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_HEADERFUNCTION => function ($curl, $header) use (&$responseHeaders) {
                $trimmed = trim($header);
                if ($trimmed === '' || strpos($trimmed, ':') === false) {
                    return strlen($header);
                }

                list($name, $value) = explode(':', $trimmed, 2);
                $responseHeaders[strtolower(trim($name))] = trim($value);
                return strlen($header);
            },
        ]);

        if ($payload !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        }

        $rawBody = curl_exec($ch);
        if ($rawBody === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new RequestException('Request failed: ' . $error, 0);
        }

        $statusCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        $decoded = json_decode($rawBody, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $decoded = $rawBody;
        }

        return new Response($statusCode, $decoded, $responseHeaders);
    }
}
