<?php

namespace Hlquery;

class Request {
    private $baseUrl;
    private $timeout;
    private $authToken;
    private $authMethod;

    private function normalizeMethod($method) {
        if (!is_string($method) || trim($method) === '') {
            throw new ValidationException('HTTP method must be a non-empty string');
        }

        return strtoupper(trim($method));
    }

    private function normalizePath($path) {
        if (!is_string($path) || $path === '') {
            throw new ValidationException('Request path must be a non-empty string');
        }

        if ($path[0] !== '/') {
            throw new ValidationException('Request path must start with /');
        }

        return $path;
    }

    private function normalizeAuthMethod($method) {
        $normalized = is_string($method) ? strtolower(trim($method)) : '';

        if ($normalized === '' || $normalized === 'bearer' || $normalized === 'api-key') {
            return $normalized === '' ? 'bearer' : $normalized;
        }

        throw new ValidationException('Authentication method must be bearer or api-key');
    }

    private function buildQueryString($queryParams) {
        if (!is_array($queryParams) || empty($queryParams)) {
            return '';
        }

        $filteredQuery = [];

        foreach ($queryParams as $key => $value) {
            if ($value === null) {
                continue;
            }

            if (is_bool($value)) {
                $filteredQuery[$key] = $value ? 'true' : 'false';
                continue;
            }

            $filteredQuery[$key] = $value;
        }

        return empty($filteredQuery) ? '' : http_build_query($filteredQuery);
    }

    public function __construct($baseUrl, $timeout = 30, $authToken = null, $authMethod = 'bearer') {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->timeout = max(1, (int)$timeout);
        $this->authToken = $authToken;
        $this->authMethod = $this->normalizeAuthMethod($authMethod);
    }

    public function setAuthToken($token, $method = 'bearer') {
        if ($token !== null && (!is_string($token) || trim($token) === '')) {
            throw new ValidationException('Authentication token must be a non-empty string');
        }

        $this->authToken = $token;
        $this->authMethod = $this->normalizeAuthMethod($method);
    }

    public function clearAuth() {
        $this->authToken = null;
        $this->authMethod = 'bearer';
    }

    public function execute($method, $path, $body = null, $queryParams = []) {
        $method = $this->normalizeMethod($method);
        $path = $this->normalizePath($path);
        $url = $this->baseUrl . $path;
        $queryString = $this->buildQueryString($queryParams);

        if ($queryString !== '') {
            $url .= '?' . $queryString;
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
                throw new RequestException('Failed to encode request body: ' . json_last_error_msg());
            }
        }

        $responseHeaders = [];
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => min($this->timeout, 10),
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
