<?php
/*
 * hlquery PHP client analytics service.
 */

namespace Hlquery;

class Analytics extends Service
{
     public function click(array $payload)
     {
          return $this->client->executeRequest('POST', '/analytics/click', $payload);
     }
}
