<?php
/*
 * hlquery PHP client SQL service.
 */

namespace Hlquery;

class SQL extends Service
{
     public function query($collection_name, $statement, array $params = [])
     {
          $payload = array_merge(
               [
                    'collection' => $collection_name,
                    'q' => $statement,
               ],
               $params
          );

          return $this->client->executeRequest('POST', '/sql', $payload);
     }

     public function execute($statement, array $params = [])
     {
          $payload = array_merge(
               [
                    'q' => $statement,
               ],
               $params
          );

          return $this->client->executeRequest('POST', '/sql', $payload);
     }

     public function raw($statement, array $params = [])
     {
          return $this->execute($statement, $params);
     }
}
