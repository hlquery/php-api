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
          return $this->update($collection_name, $term, $payload);
     }

     public function create($collection_name, $term, array $payload)
     {
          \Hlquery\Utils\Validator::validateCollectionName($collection_name);

          return $this->client->executeRequest(
               'POST',
               '/collections/' . rawurlencode($collection_name) . '/synonyms/' . rawurlencode((string) $term),
               $payload
          );
     }

     public function update($collection_name, $term, array $payload)
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

     public function listSynonymSets(array $params = [])
     {
          return $this->client->executeRequest('GET', '/synonym_sets', null, $params);
     }

     public function listGlobalSynonymSet(array $params = [])
     {
          return $this->client->executeRequest('GET', '/synonym_sets/global', null, $params);
     }

     public function upsertGlobal($term, array $payload)
     {
          return $this->updateGlobal($term, $payload);
     }

     public function createGlobal($term, array $payload)
     {
          return $this->client->executeRequest(
               'POST',
               '/synonyms/global/' . rawurlencode((string) $term),
               $payload
          );
     }

     public function createInGlobalSynonymSet($term, array $payload)
     {
          return $this->client->executeRequest(
               'POST',
               '/synonym_sets/global/items/' . rawurlencode((string) $term),
               $payload
          );
     }

     public function updateGlobal($term, array $payload)
     {
          return $this->client->executeRequest(
               'PUT',
               '/synonyms/global/' . rawurlencode((string) $term),
               $payload
          );
     }

     public function updateInGlobalSynonymSet($term, array $payload)
     {
          return $this->client->executeRequest(
               'PUT',
               '/synonym_sets/global/items/' . rawurlencode((string) $term),
               $payload
          );
     }

     public function getGlobal($term)
     {
          return $this->client->executeRequest('GET', '/synonyms/global/' . rawurlencode((string) $term));
     }

     public function getFromGlobalSynonymSet($term)
     {
          return $this->client->executeRequest('GET', '/synonym_sets/global/items/' . rawurlencode((string) $term));
     }

     public function deleteGlobal($term)
     {
          return $this->client->executeRequest('DELETE', '/synonyms/global/' . rawurlencode((string) $term));
     }

     public function deleteFromGlobalSynonymSet($term)
     {
          return $this->client->executeRequest('DELETE', '/synonym_sets/global/items/' . rawurlencode((string) $term));
     }
}
