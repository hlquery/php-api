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

          $response = $this->client->executeRequest(
               'PUT',
               '/collections/' . rawurlencode($collection_name) . '/documents/' . rawurlencode($document_id),
               $document
          );

          if ($response->getStatusCode() === 404 || $response->getStatusCode() === 405)
          {
               return $this->client->executeRequest(
                    'PATCH',
                    '/collections/' . rawurlencode($collection_name) . '/documents/' . rawurlencode($document_id),
                    $document
               );
          }

          return $response;
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

          $response = $this->client->executeRequest(
               'POST',
               '/collections/' . rawurlencode($collection_name) . '/documents/import',
               ['documents' => array_values($documents)],
               $params
          );

          if ($response->getStatusCode() === 404)
          {
               $inserted = 0;
               foreach ($documents as $document)
               {
                    $single = $this->add($collection_name, $document);
                    if ($single->getStatusCode() !== 201)
                    {
                         return $single;
                    }
                    $inserted++;
               }

               return new \Hlquery\Response(201, [], ['imported' => $inserted, 'fallback' => true]);
          }

          return $response;
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

     public function copy($collection_name, $source_id, $target_id)
     {
          \Hlquery\Utils\Validator::validateCollectionName($collection_name);
          \Hlquery\Utils\Validator::validateDocumentId($source_id);
          \Hlquery\Utils\Validator::validateDocumentId($target_id);

          if ($source_id === $target_id)
          {
               throw new \InvalidArgumentException('Source and target document ids must differ.');
          }

          $target_check = $this->get($collection_name, $target_id);
          if ($target_check->getStatusCode() === 200)
          {
               return $target_check;
          }
          if ($target_check->getStatusCode() !== 404)
          {
               return $target_check;
          }

          $source_response = $this->get($collection_name, $source_id);
          if (!$source_response->isSuccess())
          {
               return $source_response;
          }

          $doc = $source_response->getBody();
          if (!is_array($doc))
          {
               return $source_response;
          }

          unset($doc['collection_id']);
          unset($doc['score']);
          $doc['id'] = $target_id;

          return $this->import($collection_name, [$doc]);
     }

     public function recent($collection_name, $limit = 20, $offset = 0)
     {
          \Hlquery\Utils\Validator::validateCollectionName($collection_name);
          \Hlquery\Utils\Validator::validatePagination((int) $offset, (int) $limit);

          $sql = 'select * from ' . $collection_name . ' order by timestamp desc limit ' . (int) $limit . ' offset ' . (int) $offset;
          return $this->client->sql()->query($collection_name, $sql);
     }
}
