<?php
/*
 * hlquery PHP client base service.
 */

namespace Hlquery;

abstract class Service
{
     protected $client;

     public function __construct(Client $client)
     {
          $this->client = $client;
     }
}
