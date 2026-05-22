<?php
/*
 * hlquery PHP client overrides service.
 */

namespace Hlquery;

class Overrides extends Service
{
     public function list($collection_name, array $params = [])
     {
          \Hlquery\Utils\Validator::validateCollectionName($collection_name);

          return $this->client->executeRequest(
               'GET',
               '/collections/' . rawurlencode($collection_name) . '/overrides',
               null,
               $params
          );
     }

     public function upsert($collection_name, $override_id, array $payload)
     {
          \Hlquery\Utils\Validator::validateCollectionName($collection_name);

          return $this->client->executeRequest(
               'PUT',
               '/collections/' . rawurlencode($collection_name) . '/overrides/' . rawurlencode((string) $override_id),
               $payload
          );
     }

     public function get($collection_name, $override_id)
     {
          \Hlquery\Utils\Validator::validateCollectionName($collection_name);

          return $this->client->executeRequest(
               'GET',
               '/collections/' . rawurlencode($collection_name) . '/overrides/' . rawurlencode((string) $override_id)
          );
     }

     public function delete($collection_name, $override_id)
     {
          \Hlquery\Utils\Validator::validateCollectionName($collection_name);

          return $this->client->executeRequest(
               'DELETE',
               '/collections/' . rawurlencode($collection_name) . '/overrides/' . rawurlencode((string) $override_id)
          );
     }
}
