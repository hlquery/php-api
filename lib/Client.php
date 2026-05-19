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
     private $sam_service;

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
               case 'sam':
                    return $this->sam();
          }

          trigger_error('Undefined property: ' . __CLASS__ . '::$' . (string) $name, E_USER_NOTICE);
          return null;
     }

     public function __isset($name)
     {
          return in_array($name, ['collections', 'documents', 'keys', 'sql', 'sam'], true);
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

     public function health()
     {
          return $this->executeRequest('GET', '/health');
     }

     public function info()
     {
          return $this->executeRequest('GET', '/');
     }

     public function stats()
     {
          return $this->executeRequest('GET', '/stats');
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

     public function sam()
     {
          if ($this->sam_service === null)
          {
               $this->sam_service = new SAM($this);
          }

          return $this->sam_service;
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

     public function vectorSearch($collection_name, array $params)
     {
          return $this->search($collection_name, $params);
     }
}
