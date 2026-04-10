<?php

namespace Hlquery;

use Hlquery\Utils\Config;

class Client {
    private $request;
    private $collectionsApi;
    private $documentsApi;
    private $searchApi;
    private $keysApi;
    private $aliasesApi;
    private $overridesApi;
    private $synonymsApi;
    private $stopwordsApi;
    private $systemApi;

    public function __construct($baseUrl = null, $options = []) {
        $opts = Config::mergeDefaults($options);
        $baseUrl = Config::normalizeUrl($baseUrl ?: $opts['base_url']);

        if (!Config::isValidUrl($baseUrl)) {
            throw new ValidationException('Invalid base URL: ' . $baseUrl);
        }

        $this->request = new Request(
            $baseUrl,
            $opts['timeout'],
            $opts['token'],
            $opts['auth_method']
        );

        $this->collectionsApi = new Collections($this->request);
        $this->documentsApi = new Documents($this->request);
        $this->searchApi = new Search($this->request, $this->collectionsApi);
        $this->keysApi = new Keys($this->request);
        $this->aliasesApi = new Aliases($this->request);
        $this->overridesApi = new Overrides($this->request);
        $this->synonymsApi = new Synonyms($this->request);
        $this->stopwordsApi = new Stopwords($this->request);
        $this->systemApi = new System($this->request);
    }

    public function setAuthToken($token, $method = 'bearer') {
        $this->request->setAuthToken($token, $method);
        return $this;
    }

    public function clearAuth() {
        $this->request->clearAuth();
        return $this;
    }

    public function executeRequest($method, $path, $body = null, $queryParams = []) {
        return $this->request->execute($method, $path, $body, $queryParams);
    }

    public function collections() { return $this->collectionsApi; }
    public function documents() { return $this->documentsApi; }
    public function searchApi() { return $this->searchApi; }
    public function keys() { return $this->keysApi; }
    public function aliases() { return $this->aliasesApi; }
    public function overrides() { return $this->overridesApi; }
    public function synonyms() { return $this->synonymsApi; }
    public function stopwords() { return $this->stopwordsApi; }

    public function health() { return $this->systemApi->health(); }
    public function status() { return $this->systemApi->status(); }
    public function startup() { return $this->systemApi->startup(); }
    public function bootStatus() { return $this->systemApi->bootStatus(); }
    public function info() { return $this->systemApi->info(); }
    public function stats() { return $this->systemApi->stats(); }
    public function metrics() { return $this->systemApi->metrics(); }
    public function metricsJson() { return $this->systemApi->metricsJson(); }
    public function connections() { return $this->systemApi->connections(); }
    public function rocksdb() { return $this->systemApi->rocksdb(); }
    public function rocksdbInternal() { return $this->systemApi->rocksdbInternal(); }
    public function docTotal() { return $this->systemApi->docTotal(); }
    public function etc() { return $this->systemApi->etc(); }
    public function ping() { return $this->systemApi->ping(); }
    public function integrity() { return $this->systemApi->integrity(); }
    public function consistency() { return $this->systemApi->consistency(); }
    public function selfCheck() { return $this->systemApi->selfCheck(); }
    public function storageStatus() { return $this->systemApi->storageStatus(); }

    public function listCollections($offset = 0, $limit = 10) { return $this->collectionsApi->list($offset, $limit); }
    public function getCollection($name) { return $this->collectionsApi->get($name); }
    public function getCollectionFields($name) { return $this->collectionsApi->getFields($name); }
    public function listDocuments($collectionName, $params = []) { return $this->documentsApi->list($collectionName, $params); }
    public function getDocument($collectionName, $documentId) { return $this->documentsApi->get($collectionName, $documentId); }
    public function search($collectionName, $params = []) { return $this->searchApi->search($collectionName, $params); }
    public function vectorSearch($collectionName, $params = []) { return $this->searchApi->vectorSearch($collectionName, $params); }
}
