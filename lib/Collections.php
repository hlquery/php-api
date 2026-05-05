<?php
/*
 * hlquery PHP client collections service.
 */

namespace Hlquery;

class Collections extends Service
{
     public function list($offset = 0, $limit = 10)
     {
          \Hlquery\Utils\Validator::validatePagination((int) $offset, (int) $limit);

          return $this->client->executeRequest(
               'GET',
               '/collections',
               null,
               [
                    'offset' => (int) $offset,
                    'limit' => (int) $limit,
               ]
          );
     }

     public function create($collection_name, array $schema)
     {
          \Hlquery\Utils\Validator::validateCollectionName($collection_name);

          return $this->client->executeRequest('POST', '/collections/' . rawurlencode($collection_name), $schema);
     }

     public function get($collection_name)
     {
          \Hlquery\Utils\Validator::validateCollectionName($collection_name);

          return $this->client->executeRequest('GET', '/collections/' . rawurlencode($collection_name));
     }

     public function getFields($collection_name)
     {
          \Hlquery\Utils\Validator::validateCollectionName($collection_name);

          return $this->client->executeRequest('GET', '/collections/' . rawurlencode($collection_name) . '/fields');
     }

     public function update($collection_name, array $schema)
     {
          \Hlquery\Utils\Validator::validateCollectionName($collection_name);

          return $this->client->executeRequest('PATCH', '/collections/' . rawurlencode($collection_name), $schema);
     }

     public function delete($collection_name)
     {
          \Hlquery\Utils\Validator::validateCollectionName($collection_name);

          return $this->client->executeRequest('DELETE', '/collections/' . rawurlencode($collection_name));
     }
}
