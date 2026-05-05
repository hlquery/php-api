<?php
/*
 * hlquery PHP client SAM service.
 */

namespace Hlquery;

class SAM extends Service
{
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
}
