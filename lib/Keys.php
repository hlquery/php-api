<?php

namespace Hlquery;

class Keys {
    private $request;

    public function __construct(Request $request) {
        $this->request = $request;
    }

    public function list() {
        return $this->request->execute('GET', '/keys');
    }

    public function get($id) {
        if (!$id) {
            throw new ValidationException('Key ID is required');
        }
        return $this->request->execute('GET', '/keys/' . rawurlencode($id));
    }

    public function create($params) {
        if (empty($params['collections']) || !is_array($params['collections'])) {
            throw new ValidationException('Collections array is required');
        }
        if (empty($params['actions']) || !is_array($params['actions'])) {
            throw new ValidationException('Actions array is required');
        }
        return $this->request->execute('POST', '/keys', $params);
    }

    public function update($id, $params) {
        if (!$id) {
            throw new ValidationException('Key ID is required');
        }
        return $this->request->execute('PUT', '/keys/' . rawurlencode($id), $params);
    }

    public function delete($id) {
        if (!$id) {
            throw new ValidationException('Key ID is required');
        }
        return $this->request->execute('DELETE', '/keys/' . rawurlencode($id));
    }
}
