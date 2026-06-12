<?php
/*
 * hlquery PHP client autoloader.
 */

spl_autoload_register(function ($class) {
    $prefix = 'Hlquery\\';

    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }

    $relative = substr($class, strlen($prefix));

    if (strncmp($relative, 'Utils\\', 6) === 0) {
        $relative = substr($relative, 6);
        $base_dir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'utils';
    } else {
        $base_dir = __DIR__;
    }

    $path = $base_dir . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $relative) . '.php';

    if (is_file($path)) {
        require $path;
    }
});
