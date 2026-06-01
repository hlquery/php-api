<?php
/*
 * hlquery PHP client aliases service.
 */

namespace Hlquery;

class Aliases extends Service
{
     public function list(array $params = [])
     {
          return $this->client->executeRequest('GET', '/aliases', null, $params);
     }

     public function listForCollection($collection_name, array $params = [])
     {
          \Hlquery\Utils\Validator::validateCollectionName($collection_name);

          return $this->client->executeRequest(
               'GET',
               '/collections/' . rawurlencode($collection_name) . '/aliases',
               null,
               $params
          );
     }

     public function upsert($alias, array $payload)
     {
          return $this->update($alias, $payload);
     }

     public function create($alias, array $payload)
     {
          \Hlquery\Utils\Validator::validateAliasName($alias);

          return $this->client->executeRequest('POST', '/aliases/' . rawurlencode((string) $alias), $payload);
     }

     public function update($alias, array $payload)
     {
          \Hlquery\Utils\Validator::validateAliasName($alias);

          return $this->client->executeRequest('PUT', '/aliases/' . rawurlencode((string) $alias), $payload);
     }

     public function get($alias)
     {
          \Hlquery\Utils\Validator::validateAliasName($alias);

          return $this->client->executeRequest('GET', '/aliases/' . rawurlencode((string) $alias));
     }

     public function delete($alias)
     {
          \Hlquery\Utils\Validator::validateAliasName($alias);

          return $this->client->executeRequest('DELETE', '/aliases/' . rawurlencode((string) $alias));
     }
}
