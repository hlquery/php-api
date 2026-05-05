<?php
/*
 * hlquery PHP client response wrapper.
 */

namespace Hlquery;

class Response
{
     private $status_code;
     private $headers;
     private $body;
     private $raw_body;
     private $error;

     public function __construct($status_code, array $headers = [], $body = null, $raw_body = '', $error = '')
     {
          $this->status_code = (int) $status_code;
          $this->headers = $headers;
          $this->body = $body;
          $this->raw_body = is_string($raw_body) ? $raw_body : '';
          $this->error = is_string($error) ? $error : '';
     }

     public function isSuccess()
     {
          return $this->status_code >= 200 && $this->status_code < 300;
     }

     public function getStatusCode()
     {
          return $this->status_code;
     }

     public function getHeaders()
     {
          return $this->headers;
     }

     public function getBody()
     {
          return $this->body;
     }

     public function getRawBody()
     {
          return $this->raw_body;
     }

     public function getError()
     {
          return $this->error;
     }

     public function getMessage()
     {
          if ($this->error !== '')
          {
               return $this->error;
          }

          if (is_array($this->body))
          {
               if (isset($this->body['message']) && is_string($this->body['message']))
               {
                    return $this->body['message'];
               }

               if (isset($this->body['error']) && is_string($this->body['error']))
               {
                    return $this->body['error'];
               }
          }

          return '';
     }
}
