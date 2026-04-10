<?php

namespace Hlquery;

use Hlquery\Utils\Validator;

class Aliases {
    private $request;

    public function __construct(Request $request) {
        $this->request = $request;
    }

    public function list() { return $this->request->execute('GET', '/aliases'); }
    public function get($name) { Validator::validateCollectionName($name); return $this->request->execute('GET', '/aliases/' . rawurlencode($name)); }
    public function create($name, $params) { Validator::validateCollectionName($name); return $this->request->execute('POST', '/aliases/' . rawurlencode($name), $params); }
    public function update($name, $params) { Validator::validateCollectionName($name); return $this->request->execute('PUT', '/aliases/' . rawurlencode($name), $params); }
    public function upsert($name, $params) { return $this->create($name, $params); }
    public function delete($name) { Validator::validateCollectionName($name); return $this->request->execute('DELETE', '/aliases/' . rawurlencode($name)); }
}
