<?php
/*
 * hlquery PHP client modules service.
 */

namespace Hlquery;

class Modules extends Service
{
     public function list(array $params = [])
     {
          return $this->client->executeRequest('GET', '/modules', null, $params);
     }

     public function load($module)
     {
          return $this->client->executeRequest('POST', '/loadmodule/' . rawurlencode((string) $module));
     }

     public function loadWithPayload(array $payload = [])
     {
          return $this->client->executeRequest('POST', '/loadmodule', $payload);
     }

     public function unload($module)
     {
          return $this->client->executeRequest('POST', '/unloadmodule/' . rawurlencode((string) $module));
     }

     public function unloadWithPayload(array $payload = [])
     {
          return $this->client->executeRequest('POST', '/unloadmodule', $payload);
     }

     public function syntax($module)
     {
          return $this->client->executeRequest('GET', '/modules/' . rawurlencode((string) $module) . '/syntax');
     }

     public function request($method, $path, $payload = null, array $query = [])
     {
          $normalized = trim((string) $path, '/');

          return $this->client->executeRequest(
               strtoupper((string) $method),
               '/modules/' . $normalized,
               $payload,
               $query
          );
     }
}
