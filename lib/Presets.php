<?php
/*
 * hlquery PHP client presets service.
 */

namespace Hlquery;

class Presets extends Service
{
     public function list()
     {
          return $this->client->executeRequest('GET', '/presets');
     }

     public function upsert($name, array $payload)
     {
          return $this->client->executeRequest('PUT', '/presets/' . rawurlencode((string) $name), $payload);
     }

     public function create($name, array $payload)
     {
          return $this->client->executeRequest('POST', '/presets/' . rawurlencode((string) $name), $payload);
     }

     public function get($name)
     {
          return $this->client->executeRequest('GET', '/presets/' . rawurlencode((string) $name));
     }

     public function delete($name)
     {
          return $this->client->executeRequest('DELETE', '/presets/' . rawurlencode((string) $name));
     }
}
