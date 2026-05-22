<?php
/*
 * hlquery PHP client keys service.
 */

namespace Hlquery;

class Keys extends Service
{
     public function list($offset = 0, $limit = 100)
     {
          return $this->client->executeRequest(
               'GET',
               '/keys',
               null,
               [
                    'offset' => (int) $offset,
                    'limit' => (int) $limit,
               ]
          );
     }

     public function create(array $payload)
     {
          return $this->client->executeRequest('POST', '/keys', $payload);
     }

     public function get($key_id)
     {
          return $this->client->executeRequest('GET', '/keys/' . rawurlencode((string) $key_id));
     }

     public function update($key_id, array $payload)
     {
          return $this->client->executeRequest('PUT', '/keys/' . rawurlencode((string) $key_id), $payload);
     }

     public function delete($key_id)
     {
          return $this->client->executeRequest('DELETE', '/keys/' . rawurlencode((string) $key_id));
     }
}
