<?php

namespace Hlquery;

class System {
    private $request;

    public function __construct(Request $request) {
        $this->request = $request;
    }

    public function health() { return $this->request->execute('GET', '/health'); }
    public function status() { return $this->request->execute('GET', '/status'); }
    public function startup() { return $this->request->execute('GET', '/startup'); }
    public function bootStatus() { return $this->request->execute('GET', '/boot-status'); }
    public function info() { return $this->request->execute('GET', '/'); }
    public function stats() { return $this->request->execute('GET', '/stats'); }
    public function metrics() { return $this->request->execute('GET', '/metrics'); }
    public function metricsJson() { return $this->request->execute('GET', '/metrics.json'); }
    public function connections() { return $this->request->execute('GET', '/connections'); }
    public function rocksdb() { return $this->request->execute('GET', '/rocksdb'); }
    public function rocksdbInternal() { return $this->request->execute('GET', '/_rocksdb'); }
    public function docTotal() { return $this->request->execute('GET', '/doctotal'); }
    public function etc() { return $this->request->execute('GET', '/etc'); }
    public function sql($sql, $params = []) {
        if (!is_string($sql) || trim($sql) === '') {
            throw new ValidationException('SQL query must be a non-empty string');
        }

        if (!is_array($params)) {
            throw new ValidationException('SQL params must be an array');
        }

        return $this->request->execute('GET', '/sql', null, array_merge($params, ['sql' => $sql]));
    }
    public function execSql($sql) {
        if (!is_string($sql) || trim($sql) === '') {
            throw new ValidationException('SQL query must be a non-empty string');
        }

        return $this->request->execute('POST', '/sql', ['exec' => $sql]);
    }
    public function ping() { return $this->request->execute('GET', '/ping'); }
    public function flush() { return $this->request->execute('POST', '/flush'); }
    public function integrity() { return $this->request->execute('GET', '/integrity'); }
    public function consistency() { return $this->request->execute('GET', '/consistency'); }
    public function selfCheck() { return $this->request->execute('GET', '/self-check'); }
    public function storageStatus() { return $this->request->execute('GET', '/admin/storage_status'); }
}
