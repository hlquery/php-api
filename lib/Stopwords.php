<?php
/*
 * hlquery PHP client stopwords service.
 */

namespace Hlquery;

class Stopwords extends Service
{
     public function listAll(array $params = [])
     {
          return $this->client->executeRequest('GET', '/stopwords', null, $params);
     }

     public function listStopwordSets(array $params = [])
     {
          return $this->client->executeRequest('GET', '/stopword_sets', null, $params);
     }

     public function listGlobal(array $params = [])
     {
          return $this->client->executeRequest('GET', '/stopwords/global', null, $params);
     }

     public function listGlobalStopwordSet(array $params = [])
     {
          return $this->client->executeRequest('GET', '/stopword_sets/global', null, $params);
     }

     public function createGlobal(array $payload)
     {
          return $this->client->executeRequest('POST', '/stopwords/global', $payload);
     }

     public function createInGlobalStopwordSet(array $payload)
     {
          return $this->client->executeRequest('POST', '/stopword_sets/global', $payload);
     }

     public function deleteGlobal($term)
     {
          return $this->client->executeRequest('DELETE', '/stopwords/global/' . rawurlencode((string) $term));
     }

     public function deleteFromGlobalStopwordSet($term)
     {
          return $this->client->executeRequest('DELETE', '/stopword_sets/global/items/' . rawurlencode((string) $term));
     }

     public function list($collection_name, array $params = [])
     {
          \Hlquery\Utils\Validator::validateCollectionName($collection_name);

          return $this->client->executeRequest(
               'GET',
               '/collections/' . rawurlencode($collection_name) . '/stopwords',
               null,
               $params
          );
     }

     public function create($collection_name, array $payload)
     {
          \Hlquery\Utils\Validator::validateCollectionName($collection_name);

          return $this->client->executeRequest(
               'POST',
               '/collections/' . rawurlencode($collection_name) . '/stopwords',
               $payload
          );
     }

     public function delete($collection_name, $term)
     {
          \Hlquery\Utils\Validator::validateCollectionName($collection_name);

          return $this->client->executeRequest(
               'DELETE',
               '/collections/' . rawurlencode($collection_name) . '/stopwords/' . rawurlencode((string) $term)
          );
     }
}
