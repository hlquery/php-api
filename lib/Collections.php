<?php
/*
 * hlquery PHP client collections service.
 */

namespace Hlquery;

class Collections extends Service
{
     public function list($offset = 0, $limit = 10)
     {
          \Hlquery\Utils\Validator::validatePagination((int) $offset, (int) $limit);

          return $this->client->executeRequest(
               'GET',
               '/collections',
               null,
               [
                    'offset' => (int) $offset,
                    'limit' => (int) $limit,
               ]
          );
     }

     public function distributed(array $params = [])
     {
          return $this->client->executeRequest('GET', '/collections/distributed', null, $params);
     }

     public function create($collection_name, array $schema)
     {
          \Hlquery\Utils\Validator::validateCollectionName($collection_name);

          $payload = array_merge(['name' => $collection_name], $schema);
          $response = $this->client->executeRequest('POST', '/collections', $payload);

          if ($response->getStatusCode() === 404)
          {
               return $this->client->executeRequest('POST', '/collections/' . rawurlencode($collection_name), $schema);
          }

          return $response;
     }

     public function get($collection_name)
     {
          \Hlquery\Utils\Validator::validateCollectionName($collection_name);

          return $this->client->executeRequest('GET', '/collections/' . rawurlencode($collection_name));
     }

     public function getFields($collection_name)
     {
          \Hlquery\Utils\Validator::validateCollectionName($collection_name);

          $response = $this->get($collection_name);

          if (!$response instanceof \Hlquery\Response || !$response->isSuccess())
          {
               return $response;
          }

          $body = $response->getBody();
          $fields = is_array($body) && isset($body['fields']) && is_array($body['fields']) ? $body['fields'] : [];

          return new \Hlquery\Response(
               $response->getStatusCode(),
               $response->getHeaders(),
               $fields,
               json_encode($fields),
               $response->getError()
          );
     }

     public function language($collection_name)
     {
          \Hlquery\Utils\Validator::validateCollectionName($collection_name);

          return $this->client->executeRequest('GET', '/collections/' . rawurlencode($collection_name) . '/lang');
     }

     public function vectorSearch($collection_name, array $params, $method = 'GET')
     {
          \Hlquery\Utils\Validator::validateCollectionName($collection_name);

          $path = '/collections/' . rawurlencode($collection_name) . '/vector_search';
          $method = strtoupper((string) $method);

          if ($method === 'POST')
          {
               return $this->client->executeRequest('POST', $path, $params);
          }

          return $this->client->executeRequest('GET', $path, null, $params);
     }

     public function searchAlias($collection_name, array $params, $method = 'GET')
     {
          \Hlquery\Utils\Validator::validateCollectionName($collection_name);

          $path = '/collections/' . rawurlencode($collection_name) . '/search';
          $method = strtoupper((string) $method);

          if ($method === 'POST')
          {
               return $this->client->executeRequest('POST', $path, $params);
          }

          return $this->client->executeRequest('GET', $path, null, $params);
     }

     public function update($collection_name, array $schema)
     {
          \Hlquery\Utils\Validator::validateCollectionName($collection_name);

          return $this->client->executeRequest('POST', '/collections/' . rawurlencode($collection_name) . '/update', $schema);
     }

     public function delete($collection_name)
     {
          \Hlquery\Utils\Validator::validateCollectionName($collection_name);

          return $this->client->executeRequest('DELETE', '/collections/' . rawurlencode($collection_name));
     }

     public function copy($source_name, $target_name, $batch_size = 500)
     {
          \Hlquery\Utils\Validator::validateCollectionName($source_name);
          \Hlquery\Utils\Validator::validateCollectionName($target_name);

          $size = (int) $batch_size;
          if ($size < 1)
          {
               $size = 500;
          }

          $source_response = $this->get($source_name);
          if (!$source_response->isSuccess())
          {
               return $source_response;
          }

          $source = $source_response->getBody();
          if (!is_array($source))
          {
               return $source_response;
          }

          $schema = [];

          if (isset($source['fields']) && is_array($source['fields']) && !empty($source['fields']))
          {
               $field_names = array_keys($source['fields']);
               sort($field_names);
               $fields = [];
               foreach ($field_names as $name)
               {
                    $type = isset($source['fields'][$name]) && is_string($source['fields'][$name]) ? $source['fields'][$name] : 'string';
                    $fields[] = ['name' => $name, 'type' => $type];
               }
               $schema['fields'] = $fields;
          }
          elseif (isset($source['searchable_fields']) && is_array($source['searchable_fields']) && !empty($source['searchable_fields']))
          {
               $schema['searchable_fields'] = $source['searchable_fields'];
          }
          else
          {
               $schema['searchable_fields'] = ['title', 'content'];
          }

          if (isset($source['filterable_fields']) && is_array($source['filterable_fields']))
          {
               $schema['filterable_fields'] = $source['filterable_fields'];
          }

          if (isset($source['sortable_fields']) && is_array($source['sortable_fields']))
          {
               $schema['sortable_fields'] = $source['sortable_fields'];
          }

          if (isset($source['metadata']) && is_array($source['metadata']))
          {
               foreach ($source['metadata'] as $key => $value)
               {
                    if (is_string($key) && $key !== '' && $key[0] === '_')
                    {
                         $schema[$key] = $value;
                    }
               }
          }

          $create_response = $this->create($target_name, $schema);
          if ($create_response->getStatusCode() !== 201)
          {
               return $create_response;
          }

          $offset = 0;

          while (true)
          {
               $list_response = $this->client->documents()->list($source_name, ['offset' => $offset, 'limit' => $size]);
               if (!$list_response->isSuccess())
               {
                    return $list_response;
               }

               $list_body = $list_response->getBody();
               $documents = (is_array($list_body) && isset($list_body['documents']) && is_array($list_body['documents'])) ? $list_body['documents'] : [];
               if (empty($documents))
               {
                    break;
               }

               $import_response = $this->client->documents()->import($target_name, $documents);
               if (!$import_response->isSuccess())
               {
                    return $import_response;
               }

               $offset += count($documents);
          }

          return $create_response;
     }
}
