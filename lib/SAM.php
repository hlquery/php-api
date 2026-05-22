<?php
/*
 * hlquery PHP client SAM service.
 */

namespace Hlquery;

class SAM extends Service
{
     private function addCollectionParam($collection_name, array $params)
     {
          if ($collection_name !== null && $collection_name !== '')
          {
               \Hlquery\Utils\Validator::validateCollectionName($collection_name);
               $params['collection'] = $collection_name;
          }

          return $params;
     }

     public function search($collection_name, $query, array $params = [])
     {
          \Hlquery\Utils\Validator::validateCollectionName($collection_name);

          $query_params = array_merge(
               [
                    'collection' => $collection_name,
                    'q' => (string) $query,
               ],
               $params
          );

          return $this->client->executeRequest('GET', '/sam/search', null, $query_params);
     }

     public function rebuild($collection_name = null, array $params = [])
     {
          return $this->client->executeRequest('POST', '/sam/rebuild', null, $this->addCollectionParam($collection_name, $params));
     }

     public function status($collection_name = null, array $params = [])
     {
          $query_params = $params;

          if ($collection_name !== null && $collection_name !== '')
          {
               \Hlquery\Utils\Validator::validateCollectionName($collection_name);
               $query_params['collection'] = $collection_name;
          }

          return $this->client->executeRequest('GET', '/sam/status', null, $query_params);
     }

     public function history($collection_name = null, $limit = 100, array $params = [])
     {
          $query_params = array_merge(
               [
                    'limit' => (int) $limit,
               ],
               $params
          );

          if ($collection_name !== null && $collection_name !== '')
          {
               \Hlquery\Utils\Validator::validateCollectionName($collection_name);
               $query_params['collection'] = $collection_name;
          }

          return $this->client->executeRequest('GET', '/sam/history', null, $query_params);
     }

     public function debug($collection_name = null, array $params = [])
     {
          return $this->client->executeRequest('GET', '/sam/debug', null, $this->addCollectionParam($collection_name, $params));
     }

     public function pause($paused = true, array $params = [])
     {
          if (is_bool($paused))
          {
               $params['pause'] = $paused ? (string) ((int) (microtime(true) * 1000) + 300000) : '0';
          }
          else
          {
               $params['pause'] = (string) $paused;
          }

          return $this->client->executeRequest('POST', '/sam/pause', null, $params);
     }

     public function improve($collection_name = null, array $params = [])
     {
          return $this->client->executeRequest('POST', '/sam/improve', null, $this->addCollectionParam($collection_name, $params));
     }

     public function flushActorMetadata(array $params = [])
     {
          return $this->client->executeRequest('POST', '/sam/flush_actor_metadata', null, $params);
     }

     public function documents($collection_name, array $params = [])
     {
          \Hlquery\Utils\Validator::validateCollectionName($collection_name);
          $params['collection'] = $collection_name;

          return $this->client->executeRequest('GET', '/sam/documents', null, $params);
     }

     public function document($collection_name, $document_id, array $params = [])
     {
          \Hlquery\Utils\Validator::validateCollectionName($collection_name);
          \Hlquery\Utils\Validator::validateDocumentId($document_id);

          return $this->client->executeRequest(
               'GET',
               '/sam/documents/' . rawurlencode($collection_name) . '/' . rawurlencode($document_id),
               null,
               $params
          );
     }

     public function addDocumentLabel($collection_name, $document_id, $label, array $params = [])
     {
          \Hlquery\Utils\Validator::validateCollectionName($collection_name);
          \Hlquery\Utils\Validator::validateDocumentId($document_id);

          $payload = is_array($label) ? ['labels' => array_values($label)] : ['label' => (string) $label];

          return $this->client->executeRequest(
               'POST',
               '/sam/label/add/' . rawurlencode($collection_name) . '/' . rawurlencode($document_id),
               $payload,
               $params
          );
     }
}
