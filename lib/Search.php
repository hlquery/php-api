<?php

namespace Hlquery;

use Hlquery\Utils\Validator;

class Search {
    private $request;
    private $collections;

    public function __construct(Request $request, Collections $collections) {
        $this->request = $request;
        $this->collections = $collections;
    }

    public function search($collectionName, $params = []) {
        return $this->searchCollection($collectionName, $params, false);
    }

    public function searchLegacy($collectionName, $params = []) {
        return $this->searchCollection($collectionName, $params, true);
    }

    private function searchCollection($collectionName, $params = [], $useLegacyPath = false) {
        Validator::validateCollectionName($collectionName);
        Validator::validateSearchParams($params);

        $query = [];

        if (isset($params['query']) && is_array($params['query'])) {
            if (isset($params['query']['q'])) {
                $query['q'] = $params['query']['q'];
            }
            if (isset($params['query']['query_by'])) {
                $query['query_by'] = is_array($params['query']['query_by'])
                    ? implode(',', $params['query']['query_by'])
                    : $params['query']['query_by'];
            }
        }

        if (isset($params['q'])) {
            $query['q'] = $params['q'];
        }

        if (isset($params['query_by'])) {
            $query['query_by'] = is_array($params['query_by']) ? implode(',', $params['query_by']) : $params['query_by'];
        } elseif (!empty($params['q'])) {
            $collection = $this->collections->get($collectionName);
            if ($collection->getStatusCode() === 200) {
                $body = $collection->getBody();
                if (!empty($body['searchable_fields']) && is_array($body['searchable_fields'])) {
                    $query['query_by'] = implode(',', $body['searchable_fields']);
                }
            }
        }

        if (array_key_exists('from', $params)) {
            $query['offset'] = $params['from'];
        } elseif (array_key_exists('offset', $params)) {
            $query['offset'] = $params['offset'];
        }

        if (array_key_exists('size', $params)) {
            $query['limit'] = $params['size'];
        } elseif (array_key_exists('limit', $params)) {
            $query['limit'] = $params['limit'];
        }

        foreach (['page', 'per_page', 'typo_tolerance', 'num_typos'] as $simpleKey) {
            if (array_key_exists($simpleKey, $params)) {
                $query[$simpleKey] = $params[$simpleKey];
            }
        }

        foreach (['facet_by', 'facets', 'highlight_fields', 'highlight_full_fields'] as $listKey) {
            if (!array_key_exists($listKey, $params)) {
                continue;
            }
            $targetKey = $listKey === 'facets' ? 'facet_by' : $listKey;
            $query[$targetKey] = is_array($params[$listKey]) ? implode(',', $params[$listKey]) : $params[$listKey];
        }

        if (array_key_exists('highlight', $params)) {
            $query['highlight'] = $params['highlight'] ? 'true' : 'false';
        }

        if (isset($params['filter_by'])) {
            $query['filter_by'] = $params['filter_by'];
        } elseif (isset($params['filter'])) {
            $query['filter_by'] = is_array($params['filter']) ? json_encode($params['filter']) : $params['filter'];
        }

        if (isset($params['sort'])) {
            if (is_array($params['sort'])) {
                $parts = [];
                foreach ($params['sort'] as $sortItem) {
                    if (is_array($sortItem)) {
                        foreach ($sortItem as $field => $order) {
                            $parts[] = $order === 'desc' ? '-' . $field : $field;
                        }
                    } else {
                        $parts[] = $sortItem;
                    }
                }
                $query['sort_by'] = implode(',', $parts);
            } else {
                $query['sort_by'] = $params['sort'];
            }
        } elseif (isset($params['sort_by'])) {
            $query['sort_by'] = is_array($params['sort_by']) ? implode(',', $params['sort_by']) : $params['sort_by'];
        }

        $method = array_key_exists('body', $params) ? 'POST' : 'GET';
        $body = $method === 'POST' ? $params['body'] : null;
        $path = $useLegacyPath
            ? '/collections/' . rawurlencode($collectionName) . '/documents/search'
            : '/collections/' . rawurlencode($collectionName) . '/search';

        return $this->request->execute($method, $path, $body, $query);
    }

    public function multiSearch($searches) {
        return $this->request->execute('POST', '/multi_search', ['searches' => $searches]);
    }

    public function globalSearch($params = []) {
        Validator::validateSearchParams($params);
        $method = array_key_exists('body', $params) ? 'POST' : 'GET';
        $body = $method === 'POST' ? $params['body'] : null;
        $query = $method === 'GET' ? $params : [];
        return $this->request->execute($method, '/search', $body, $query);
    }

    public function vectorSearch($collectionName, $params = []) {
        Validator::validateCollectionName($collectionName);

        $query = [];
        $forcePost = false;

        foreach (['vector_query', 'vectorQuery', 'vector', 'embedding'] as $vectorKey) {
            if (!array_key_exists($vectorKey, $params)) {
                continue;
            }
            $value = $params[$vectorKey];
            $query['vector_query'] = is_array($value) ? json_encode($value) : $value;
            break;
        }

        if (isset($params['field_name']) || isset($params['field']) || isset($params['fieldName'])) {
            $query['field_name'] = $params['field_name'] ?? $params['field'] ?? $params['fieldName'];
        }

        foreach (['limit', 'topk', 'top_k', 'topK', 'k', 'per_page'] as $limitKey) {
            if (array_key_exists($limitKey, $params)) {
                $query['limit'] = $params[$limitKey];
                break;
            }
        }

        foreach (['threshold', 'radius', 'max_distance', 'maxDistance', 'range_filter', 'rangeFilter', 'min_distance', 'minDistance'] as $passthroughKey) {
            if (array_key_exists($passthroughKey, $params)) {
                $targetKey = preg_replace('/[A-Z]/', '_$0', $passthroughKey);
                $query[strtolower($targetKey)] = $params[$passthroughKey];
            }
        }

        foreach (['output_fields', 'outputFields'] as $outputKey) {
            if (array_key_exists($outputKey, $params)) {
                $query['output_fields'] = is_array($params[$outputKey]) ? implode(',', $params[$outputKey]) : $params[$outputKey];
                break;
            }
        }

        foreach (['include_vector', 'includeVector', 'include_distance', 'includeDistance', 'normalize'] as $boolKey) {
            if (array_key_exists($boolKey, $params)) {
                $targetKey = preg_replace('/[A-Z]/', '_$0', $boolKey);
                $query[strtolower($targetKey)] = $params[$boolKey] ? 'true' : 'false';
            }
        }

        if (isset($params['filter_by'])) {
            $query['filter_by'] = $params['filter_by'];
        } elseif (isset($params['filterBy'])) {
            $query['filter_by'] = $params['filterBy'];
        } elseif (isset($params['filter'])) {
            $query['filter_by'] = is_array($params['filter']) ? json_encode($params['filter']) : $params['filter'];
        }

        foreach (['query_params', 'queryParams', 'params', 'vector_queries', 'vectorQueries', 'vectorQuery'] as $postOnlyKey) {
            if (array_key_exists($postOnlyKey, $params)) {
                $forcePost = true;
                break;
            }
        }

        $method = array_key_exists('body', $params) || $forcePost ? 'POST' : 'GET';
        $body = array_key_exists('body', $params) ? $params['body'] : ($forcePost ? $params : null);

        return $this->request->execute(
            $method,
            '/collections/' . rawurlencode($collectionName) . '/vector_search',
            $body,
            $query
        );
    }
}
