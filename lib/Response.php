<?php
/*
 * hlquery PHP client response wrapper.
 */

namespace Hlquery;

class Response implements \ArrayAccess, \Countable, \IteratorAggregate, \JsonSerializable
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

     /**
      * Return an array response body, or the supplied fallback for scalar bodies.
      *
      * Response implements PHP's collection interfaces, so most callers can use
      * array access or foreach directly instead of calling this method.
      */
     public function toArray(array $default = [])
     {
          return is_array($this->body) ? $this->body : $default;
     }

     public function offsetExists($offset)
     {
          return is_array($this->body) && array_key_exists($offset, $this->body);
     }

     public function offsetGet($offset)
     {
          if (!is_array($this->body) || !array_key_exists($offset, $this->body))
          {
               return null;
          }

          return $this->body[$offset];
     }

     public function offsetSet($offset, $value)
     {
          throw new \BadMethodCallException('HLQuery responses are read-only.');
     }

     public function offsetUnset($offset)
     {
          throw new \BadMethodCallException('HLQuery responses are read-only.');
     }

     public function count()
     {
          return is_array($this->body) ? count($this->body) : 0;
     }

     public function getIterator()
     {
          return new \ArrayIterator($this->toArray());
     }

     public function jsonSerialize()
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
