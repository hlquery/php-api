<?php
/*
 * hlquery PHP client entry point.
 */

namespace Hlquery;

class Client
{
     private $base_url;
     private $options;
     private $http_client;
     private $collections_service;
     private $documents_service;
     private $keys_service;
     private $sql_service;
     private $synonyms_service;
     private $stopwords_service;
     private $overrides_service;
     private $aliases_service;
     private $links_service;
     private $users_service;
     private $modules_service;
     private $analytics_service;
     private $presets_service;

     public function __get($name)
     {
          switch ($name)
          {
               case 'collections':
                    return $this->collections();
               case 'documents':
                    return $this->documents();
               case 'keys':
                    return $this->keys();
               case 'sql':
                    return $this->sql();
               case 'synonyms':
               case 'synonymSets':
                    return $this->synonyms();
               case 'stopwords':
               case 'stopwordSets':
                    return $this->stopwords();
               case 'overrides':
               case 'curations':
                    return $this->overrides();
               case 'aliases':
                    return $this->aliases();
               case 'links':
                    return $this->links();
               case 'users':
                    return $this->users();
               case 'modules':
                    return $this->modules();
               case 'analytics':
                    return $this->analytics();
               case 'presets':
                    return $this->presets();
          }

          trigger_error('Undefined property: ' . __CLASS__ . '::$' . (string) $name, E_USER_NOTICE);
          return null;
     }

     public function __isset($name)
     {
          return in_array($name, ['collections', 'documents', 'keys', 'sql', 'synonyms', 'synonymSets', 'stopwords', 'stopwordSets', 'overrides', 'curations', 'aliases', 'links', 'users', 'modules', 'analytics', 'presets'], true);
     }

     public function __construct($base_url = null, array $options = [])
     {
          $merged_options = \Hlquery\Utils\Config::mergeDefaults($options);
          $resolved_base_url = $base_url ?: $merged_options['base_url'];

          if (!\Hlquery\Utils\Config::isValidUrl($resolved_base_url))
          {
               throw new ValidationException('Base URL must be a valid URL.');
          }

          $this->base_url = \Hlquery\Utils\Config::normalizeUrl($resolved_base_url);
          $this->options = $merged_options;
          $this->options['base_url'] = $this->base_url;
          $this->http_client = new HttpClient(
               $this->base_url,
               $this->options['timeout'],
               $this->options['token'],
               $this->options['auth_method']
          );
     }

     public function setAuthToken($token, $auth_method = 'bearer')
     {
          if (!\Hlquery\Utils\Auth::isValidToken($token))
          {
               throw new ValidationException('Authentication token must be a non-empty string.');
          }

          $this->options['token'] = $token;
          $this->options['auth_method'] = $auth_method ?: 'bearer';
          $this->http_client->setAuthToken($token, $this->options['auth_method']);

          return $this;
     }

     public function executeRequest($method, $path, $payload = null, array $query = [])
     {
          return $this->http_client->request($method, $path, $payload, $query);
     }

     public function executeRequestWithHeaders($method, $path, $payload = null, array $query = [], array $headers = [])
     {
          return $this->http_client->request($method, $path, $payload, $query, $headers);
     }

     public function health()
     {
          return $this->executeRequest('GET', '/health');
     }

     public function status()
     {
          return $this->executeRequest('GET', '/status');
     }

     public function query()
     {
          return $this->executeRequest('GET', '/query');
     }

     public function ready()
     {
          return $this->executeRequest('GET', '/ready');
     }

     public function ping()
     {
          return $this->executeRequest('GET', '/ping');
     }

     public function info()
     {
          return $this->executeRequest('GET', '/');
     }

     public function stats()
     {
          return $this->executeRequest('GET', '/stats');
     }

     public function metrics()
     {
          return $this->executeRequest('GET', '/metrics');
     }

     public function metricsJson()
     {
          return $this->executeRequest('GET', '/metrics.json');
     }

     public function metricsHistory()
     {
          return $this->executeRequest('GET', '/metrics/history');
     }

     public function metricsHistoryAlias()
     {
          return $this->executeRequest('GET', '/metrics-history');
     }

     public function connections()
     {
          return $this->executeRequest('GET', '/connections');
     }

     public function rocksdb()
     {
          return $this->executeRequest('GET', '/rocksdb');
     }

     public function rocksdbUnderscore()
     {
          return $this->executeRequest('GET', '/_rocksdb');
     }

     public function docTotal()
     {
          return $this->executeRequest('GET', '/doctotal');
     }

     public function searchConfig()
     {
          return $this->executeRequest('GET', '/search-config');
     }

     public function startup()
     {
          return $this->executeRequest('GET', '/startup');
     }

     public function bootStatus()
     {
          return $this->executeRequest('GET', '/boot-status');
     }

     public function cache()
     {
          return $this->executeRequest('GET', '/cache');
     }

     public function integrity()
     {
          return $this->executeRequest('GET', '/integrity');
     }

     public function consistency()
     {
          return $this->executeRequest('GET', '/consistency');
     }

     public function selfCheck()
     {
          return $this->executeRequest('GET', '/self-check');
     }

     public function storageStatus()
     {
          return $this->executeRequest('GET', '/admin/storage_status');
     }

     public function flush()
     {
          return $this->executeRequest('POST', '/flush');
     }

     public function repair(array $payload = null, array $query = [])
     {
          return $this->executeRequest($payload === null ? 'GET' : 'POST', '/repair', $payload, $query);
     }

     public function updateCounters(array $payload = null, array $query = [])
     {
          return $this->executeRequest($payload === null ? 'GET' : 'POST', '/update-counters', $payload, $query);
     }

     public function debugCounters()
     {
          return $this->executeRequest('GET', '/debug/counters');
     }

     public function etc()
     {
          return $this->executeRequest('GET', '/etc');
     }

     public function collections()
     {
          if ($this->collections_service === null)
          {
               $this->collections_service = new Collections($this);
          }

          return $this->collections_service;
     }

     public function documents()
     {
          if ($this->documents_service === null)
          {
               $this->documents_service = new Documents($this);
          }

          return $this->documents_service;
     }

     public function keys()
     {
          if ($this->keys_service === null)
          {
               $this->keys_service = new Keys($this);
          }

          return $this->keys_service;
     }

     public function sql()
     {
          if ($this->sql_service === null)
          {
               $this->sql_service = new SQL($this);
          }

          return $this->sql_service;
     }

     public function synonyms()
     {
          if ($this->synonyms_service === null)
          {
               $this->synonyms_service = new Synonyms($this);
          }

          return $this->synonyms_service;
     }

     public function stopwords()
     {
          if ($this->stopwords_service === null)
          {
               $this->stopwords_service = new Stopwords($this);
          }

          return $this->stopwords_service;
     }

     public function overrides()
     {
          if ($this->overrides_service === null)
          {
               $this->overrides_service = new Overrides($this);
          }

          return $this->overrides_service;
     }

     public function aliases()
     {
          if ($this->aliases_service === null)
          {
               $this->aliases_service = new Aliases($this);
          }

          return $this->aliases_service;
     }

     public function links()
     {
          if ($this->links_service === null)
          {
               $this->links_service = new Links($this);
          }

          return $this->links_service;
     }

     public function users()
     {
          if ($this->users_service === null)
          {
               $this->users_service = new Users($this);
          }

          return $this->users_service;
     }

     public function modules()
     {
          if ($this->modules_service === null)
          {
               $this->modules_service = new Modules($this);
          }

          return $this->modules_service;
     }

     public function analytics()
     {
          if ($this->analytics_service === null)
          {
               $this->analytics_service = new Analytics($this);
          }

          return $this->analytics_service;
     }

     public function presets()
     {
          if ($this->presets_service === null)
          {
               $this->presets_service = new Presets($this);
          }

          return $this->presets_service;
     }

     public function searchApi()
     {
          return $this;
     }

     public function listCollections($offset = 0, $limit = 10)
     {
          return $this->collections()->list($offset, $limit);
     }

     public function getCollection($collection_name)
     {
          return $this->collections()->get($collection_name);
     }

     public function getCollectionFields($collection_name)
     {
          return $this->collections()->getFields($collection_name);
     }

     public function listDocuments($collection_name, array $params = [])
     {
          return $this->documents()->list($collection_name, $params);
     }

     public function getDocument($collection_name, $document_id)
     {
          return $this->documents()->get($collection_name, $document_id);
     }

     public function search($collection_name, array $params)
     {
          return $this->documents()->search($collection_name, $params);
     }

     public function multiSearch(array $payload)
     {
          return $this->executeRequest('POST', '/multi_search', $payload);
     }

     public function multiSearchGet(array $params = [])
     {
          return $this->executeRequest('GET', '/multi_search', null, $params);
     }

     public function listSynonymSets(array $params = [])
     {
          return $this->synonyms()->listSynonymSets($params);
     }

     public function listGlobalSynonymSet(array $params = [])
     {
          return $this->synonyms()->listGlobalSynonymSet($params);
     }

     public function listCurations($collection_name, array $params = [])
     {
          return $this->overrides()->listCurations($collection_name, $params);
     }

     public function listStopwordSets(array $params = [])
     {
          return $this->stopwords()->listStopwordSets($params);
     }

     public function listGlobalStopwordSet(array $params = [])
     {
          return $this->stopwords()->listGlobalStopwordSet($params);
     }

     public function globalSearch(array $params, $method = 'GET')
     {
          $method = strtoupper((string) $method);

          if ($method === 'POST')
          {
               return $this->executeRequest('POST', '/search', $params);
          }

          return $this->executeRequest('GET', '/search', null, $params);
     }

     public function vectorSearch($collection_name, array $params)
     {
          return $this->collections()->vectorSearch($collection_name, $params);
     }
}
