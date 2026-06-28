<?php
/*
 * hlquery PHP client overrides service.
 */

namespace Hlquery;

class Overrides extends Service
{
     public function list($collection_name, array $params = [])
     {
          \Hlquery\Utils\Validator::validateCollectionName($collection_name);

          return $this->client->executeRequest(
               'GET',
               '/collections/' . rawurlencode($collection_name) . '/overrides',
               null,
               $params
          );
     }

     public function listCurations($collection_name, array $params = [])
     {
          \Hlquery\Utils\Validator::validateCollectionName($collection_name);

          return $this->client->executeRequest(
               'GET',
               '/collections/' . rawurlencode($collection_name) . '/curations',
               null,
               $params
          );
     }

     public function upsert($collection_name, $override_id, array $payload)
     {
          return $this->update($collection_name, $override_id, $payload);
     }

     public function create($collection_name, $override_id, array $payload)
     {
          \Hlquery\Utils\Validator::validateCollectionName($collection_name);

          return $this->client->executeRequest(
               'POST',
               '/collections/' . rawurlencode($collection_name) . '/overrides/' . rawurlencode((string) $override_id),
               $payload
          );
     }

     public function createCuration($collection_name, $curation_id, array $payload)
     {
          \Hlquery\Utils\Validator::validateCollectionName($collection_name);

          return $this->client->executeRequest(
               'POST',
               '/collections/' . rawurlencode($collection_name) . '/curations/' . rawurlencode((string) $curation_id),
               $payload
          );
     }

     public function update($collection_name, $override_id, array $payload)
     {
          \Hlquery\Utils\Validator::validateCollectionName($collection_name);

          return $this->client->executeRequest(
               'PUT',
               '/collections/' . rawurlencode($collection_name) . '/overrides/' . rawurlencode((string) $override_id),
               $payload
          );
     }

     public function updateCuration($collection_name, $curation_id, array $payload)
     {
          \Hlquery\Utils\Validator::validateCollectionName($collection_name);

          return $this->client->executeRequest(
               'PUT',
               '/collections/' . rawurlencode($collection_name) . '/curations/' . rawurlencode((string) $curation_id),
               $payload
          );
     }

     public function get($collection_name, $override_id)
     {
          \Hlquery\Utils\Validator::validateCollectionName($collection_name);

          return $this->client->executeRequest(
               'GET',
               '/collections/' . rawurlencode($collection_name) . '/overrides/' . rawurlencode((string) $override_id)
          );
     }

     public function getCuration($collection_name, $curation_id)
     {
          \Hlquery\Utils\Validator::validateCollectionName($collection_name);

          return $this->client->executeRequest(
               'GET',
               '/collections/' . rawurlencode($collection_name) . '/curations/' . rawurlencode((string) $curation_id)
          );
     }

     public function delete($collection_name, $override_id)
     {
          \Hlquery\Utils\Validator::validateCollectionName($collection_name);

          return $this->client->executeRequest(
               'DELETE',
               '/collections/' . rawurlencode($collection_name) . '/overrides/' . rawurlencode((string) $override_id)
          );
     }

     public function deleteCuration($collection_name, $curation_id)
     {
          \Hlquery\Utils\Validator::validateCollectionName($collection_name);

          return $this->client->executeRequest(
               'DELETE',
               '/collections/' . rawurlencode($collection_name) . '/curations/' . rawurlencode((string) $curation_id)
          );
     }
}
