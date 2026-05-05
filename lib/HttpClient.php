<?php
/*
 * hlquery PHP client HTTP transport.
 */

namespace Hlquery;

class HttpClient
{
     private $base_url;
     private $timeout;
     private $token;
     private $auth_method;

     public function __construct($base_url, $timeout = 30, $token = null, $auth_method = 'bearer')
     {
          $this->base_url = rtrim((string) $base_url, '/');
          $this->timeout = (int) $timeout;
          $this->token = $token;
          $this->auth_method = $auth_method ?: 'bearer';
     }

     public function setAuthToken($token, $auth_method = 'bearer')
     {
          $this->token = $token;
          $this->auth_method = $auth_method ?: 'bearer';
     }

     public function request($method, $path, $payload = null, array $query = [])
     {
          $url = $this->buildUrl($path, $query);
          $headers = [
               'Accept: application/json',
          ];

          if ($this->token !== null && $this->token !== '')
          {
               $auth_header = \Hlquery\Utils\Auth::getAuthHeader($this->token, $this->auth_method);
               $headers[] = $auth_header['header'] . ': ' . $auth_header['value'];
          }

          $body = null;

          if ($payload !== null)
          {
               $headers[] = 'Content-Type: application/json';
               $body = json_encode($payload);
          }

          $response_headers = [];
          $handle = curl_init();

          curl_setopt_array(
               $handle,
               [
                    CURLOPT_URL => $url,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_CUSTOMREQUEST => strtoupper((string) $method),
                    CURLOPT_HTTPHEADER => $headers,
                    CURLOPT_TIMEOUT => $this->timeout,
                    CURLOPT_HEADERFUNCTION => function ($curl, $header_line) use (&$response_headers)
                    {
                         $trimmed = trim($header_line);

                         if ($trimmed === '' || strpos($trimmed, ':') === false)
                         {
                              return strlen($header_line);
                         }

                         list($name, $value) = explode(':', $trimmed, 2);
                         $response_headers[trim($name)] = trim($value);
                         return strlen($header_line);
                    },
               ]
          );

          if ($body !== null)
          {
               curl_setopt($handle, CURLOPT_POSTFIELDS, $body);
          }

          $raw_body = curl_exec($handle);
          $error = curl_error($handle);
          $status_code = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
          curl_close($handle);

          if ($raw_body === false)
          {
               return new Response(0, $response_headers, null, '', $error);
          }

          $decoded = json_decode($raw_body, true);
          $body_value = json_last_error() === JSON_ERROR_NONE ? $decoded : $raw_body;

          return new Response($status_code, $response_headers, $body_value, $raw_body, $error);
     }

     private function buildUrl($path, array $query)
     {
          $normalized_path = '/' . ltrim((string) $path, '/');
          $url = $this->base_url . $normalized_path;

          if (!empty($query))
          {
               $query_string = http_build_query($query);

               if ($query_string !== '')
               {
                    $url .= '?' . $query_string;
               }
          }

          return $url;
     }
}
