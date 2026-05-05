<?php
/*
 * hlquery PHP client autoloader.
 */

spl_autoload_register(
     function ($class)
     {
          $prefixes = [
               'Hlquery\\Utils\\' => __DIR__ . '/../utils/',
               'Hlquery\\' => __DIR__ . '/',
          ];

          foreach ($prefixes as $prefix => $base_dir)
          {
               $prefix_length = strlen($prefix);

               if (strncmp($class, $prefix, $prefix_length) !== 0)
               {
                    continue;
               }

               $relative_class = substr($class, $prefix_length);
               $path = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

               if (is_file($path))
               {
                    require_once $path;
               }
          }
     }
);
