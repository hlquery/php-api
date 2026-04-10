<?php

namespace Hlquery;

use Hlquery\Utils\Validator;

class Collections {
    private $request;

    public function __construct(Request $request) {
        $this->request = $request;
    }

    public function list($offset = 0, $limit = 10) {
        Validator::validatePagination((int)$offset, (int)$limit);
        return $this->request->execute('GET', '/collections', null, [
            'offset' => (int)$offset,
            'limit' => (int)$limit,
        ]);
    }

    public function get($name) {
        Validator::validateCollectionName($name);
        return $this->request->execute('GET', '/collections/' . rawurlencode($name));
    }

    public function create($name, $schema) {
        Validator::validateCollectionName($name);
        return $this->request->execute('POST', '/collections', array_merge(['name' => $name], $schema));
    }

    public function delete($name) {
        Validator::validateCollectionName($name);
        return $this->request->execute('DELETE', '/collections/' . rawurlencode($name));
    }

    public function update($name, $schema) {
        Validator::validateCollectionName($name);
        return $this->request->execute('POST', '/collections/' . rawurlencode($name) . '/update', $schema);
    }

    public function getFields($name) {
        $response = $this->get($name);
        if ($response->getStatusCode() !== 200) {
            return $response;
        }

        $body = $response->getBody();
        $allFields = [];
        $fieldTypes = [];
        foreach (['searchable_fields' => 'searchable', 'filterable_fields' => 'filterable', 'sortable_fields' => 'sortable'] as $key => $label) {
            foreach (($body[$key] ?? []) as $field) {
                if (!in_array($field, $allFields, true)) {
                    $allFields[] = $field;
                }
                if (!isset($fieldTypes[$field])) {
                    $fieldTypes[$field] = [];
                }
                $fieldTypes[$field][] = $label;
            }
        }

        $fields = [];
        foreach ($allFields as $field) {
            $fields[] = [
                'name' => $field,
                'type' => implode(', ', $fieldTypes[$field]),
            ];
        }

        return new Response(200, [
            'collection' => $name,
            'fields' => $fields,
            'field_count' => count($fields),
            'searchable_fields' => $body['searchable_fields'] ?? [],
            'filterable_fields' => $body['filterable_fields'] ?? [],
            'sortable_fields' => $body['sortable_fields'] ?? [],
        ]);
    }
}
