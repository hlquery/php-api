<?php
/*
 * hlquery PHP client synonyms service.
 */

namespace Hlquery;

class Synonyms extends Service
{
     public function listAll(array $params = [])
     {
          return $this->client->executeRequest('GET', '/synonyms', null, $params);
     }

     public function list($collection_name, array $params = [])
     {
          \Hlquery\Utils\Validator::validateCollectionName($collection_name);

          return $this->client->executeRequest(
               'GET',
               '/collections/' . rawurlencode($collection_name) . '/synonyms',
               null,
               $params
          );
     }

     public function upsert($collection_name, $term, array $payload)
     {
          \Hlquery\Utils\Validator::validateCollectionName($collection_name);

          return $this->client->executeRequest(
               'PUT',
               '/collections/' . rawurlencode($collection_name) . '/synonyms/' . rawurlencode((string) $term),
               $payload
          );
     }

     public function get($collection_name, $term)
     {
          \Hlquery\Utils\Validator::validateCollectionName($collection_name);

          return $this->client->executeRequest(
               'GET',
               '/collections/' . rawurlencode($collection_name) . '/synonyms/' . rawurlencode((string) $term)
          );
     }

     public function delete($collection_name, $term)
     {
          \Hlquery\Utils\Validator::validateCollectionName($collection_name);

          return $this->client->executeRequest(
               'DELETE',
               '/collections/' . rawurlencode($collection_name) . '/synonyms/' . rawurlencode((string) $term)
          );
     }

     public function listGlobal(array $params = [])
     {
          return $this->client->executeRequest('GET', '/synonyms/global', null, $params);
     }

     public function upsertGlobal($term, array $payload)
     {
          return $this->client->executeRequest(
               'PUT',
               '/synonyms/global/' . rawurlencode((string) $term),
               $payload
          );
     }

     public function getGlobal($term)
     {
          return $this->client->executeRequest('GET', '/synonyms/global/' . rawurlencode((string) $term));
     }

     public function deleteGlobal($term)
     {
          return $this->client->executeRequest('DELETE', '/synonyms/global/' . rawurlencode((string) $term));
     }
}
