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

     public function loadAlias($module)
     {
          return $this->client->executeRequest('POST', '/modules/load/' . rawurlencode((string) $module));
     }

     public function unloadAlias($module)
     {
          return $this->client->executeRequest('POST', '/modules/unload/' . rawurlencode((string) $module));
     }

     public function syntax($module)
     {
          return $this->client->executeRequest('GET', '/modules/' . rawurlencode((string) $module) . '/syntax');
     }

     public function request($method, $path, $payload = null, array $query = [])
     {
          $parts = array_values(array_filter(explode('/', trim((string) $path, '/')), 'strlen'));
          $normalized = implode('/', array_map('rawurlencode', $parts));

          return $this->client->executeRequest(
               strtoupper((string) $method),
               '/modules/' . $normalized,
               $payload,
               $query
          );
     }
}
