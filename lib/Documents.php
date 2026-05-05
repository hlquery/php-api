<?php
/*
 * hlquery PHP client documents service.
 */

namespace Hlquery;

class Documents extends Service
{
     public function list($collection_name, array $params = [])
     {
          \Hlquery\Utils\Validator::validateCollectionName($collection_name);

          return $this->client->executeRequest(
               'GET',
               '/collections/' . rawurlencode($collection_name) . '/documents',
               null,
               $params
          );
     }

     public function get($collection_name, $document_id)
     {
          \Hlquery\Utils\Validator::validateCollectionName($collection_name);
          \Hlquery\Utils\Validator::validateDocumentId($document_id);

          return $this->client->executeRequest(
               'GET',
               '/collections/' . rawurlencode($collection_name) . '/documents/' . rawurlencode($document_id)
          );
     }

     public function add($collection_name, array $document)
     {
          \Hlquery\Utils\Validator::validateCollectionName($collection_name);
          \Hlquery\Utils\Validator::validateDocumentFields($document);

          if (isset($document['id']))
          {
               \Hlquery\Utils\Validator::validateDocumentId($document['id']);
          }

          return $this->client->executeRequest(
               'POST',
               '/collections/' . rawurlencode($collection_name) . '/documents',
               $document
          );
     }

     public function update($collection_name, $document_id, array $document)
     {
          \Hlquery\Utils\Validator::validateCollectionName($collection_name);
          \Hlquery\Utils\Validator::validateDocumentId($document_id);
          \Hlquery\Utils\Validator::validateDocumentFields($document);

          return $this->client->executeRequest(
               'PATCH',
               '/collections/' . rawurlencode($collection_name) . '/documents/' . rawurlencode($document_id),
               $document
          );
     }

     public function delete($collection_name, $document_id)
     {
          \Hlquery\Utils\Validator::validateCollectionName($collection_name);
          \Hlquery\Utils\Validator::validateDocumentId($document_id);

          return $this->client->executeRequest(
               'DELETE',
               '/collections/' . rawurlencode($collection_name) . '/documents/' . rawurlencode($document_id)
          );
     }

     public function import($collection_name, array $documents, array $params = [])
     {
          \Hlquery\Utils\Validator::validateCollectionName($collection_name);

          foreach ($documents as $document)
          {
               \Hlquery\Utils\Validator::validateDocumentFields($document);
          }

          return $this->client->executeRequest(
               'POST',
               '/collections/' . rawurlencode($collection_name) . '/documents/import',
               $documents,
               $params
          );
     }

     public function deleteByFilter($collection_name, array $params)
     {
          \Hlquery\Utils\Validator::validateCollectionName($collection_name);

          return $this->client->executeRequest(
               'DELETE',
               '/collections/' . rawurlencode($collection_name) . '/documents',
               null,
               $params
          );
     }

     public function search($collection_name, array $params)
     {
          \Hlquery\Utils\Validator::validateCollectionName($collection_name);
          \Hlquery\Utils\Validator::validateSearchParams($params);

          return $this->client->executeRequest(
               'GET',
               '/collections/' . rawurlencode($collection_name) . '/documents/search',
               null,
               $params
          );
     }
}
