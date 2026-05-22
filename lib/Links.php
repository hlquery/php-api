<?php
/*
 * hlquery PHP client links service.
 */

namespace Hlquery;

class Links extends Service
{
     public function list(array $params = [])
     {
          return $this->client->executeRequest('GET', '/links', null, $params);
     }

     public function ping(array $params = [])
     {
          return $this->client->executeRequest('GET', '/links/ping', null, $params);
     }

     public function connect($endpoint)
     {
          return $this->client->executeRequest('POST', '/links/connect', ['endpoint' => (string) $endpoint]);
     }

     public function disconnect($endpoint)
     {
          return $this->client->executeRequest('POST', '/links/disconnect', ['endpoint' => (string) $endpoint]);
     }
}
