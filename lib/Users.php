<?php
/*
 * hlquery PHP client users service.
 */

namespace Hlquery;

class Users extends Service
{
     public function list(array $params = [])
     {
          return $this->client->executeRequest('GET', '/users', null, $params);
     }

     public function create(array $payload)
     {
          return $this->client->executeRequest('POST', '/users', $payload);
     }

     public function get($user_id)
     {
          return $this->client->executeRequest('GET', '/users/' . rawurlencode((string) $user_id));
     }

     public function update($user_id, array $payload)
     {
          return $this->client->executeRequest('PUT', '/users/' . rawurlencode((string) $user_id), $payload);
     }

     public function delete($user_id)
     {
          return $this->client->executeRequest('DELETE', '/users/' . rawurlencode((string) $user_id));
     }
}
