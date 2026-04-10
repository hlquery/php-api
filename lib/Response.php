<?php

namespace Hlquery;

class Response {
    private $statusCode;
    private $body;
    private $headers;

    public function __construct($statusCode, $body, $headers = []) {
        $this->statusCode = (int)$statusCode;
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

        if ($this->isError() && is_string($this->body) && $this->body !== '') {
            return $this->body;
        }

        return null;
    }

    public function toArray() {
        return [
            'status' => $this->statusCode,
            'body' => $this->body,
        ];
    }
}
