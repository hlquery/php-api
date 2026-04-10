<?php

require_once __DIR__ . '/Exceptions.php';

spl_autoload_register(function ($class) {
    $prefixes = [
        'Hlquery\\Utils\\' => __DIR__ . '/../utils/',
        'Hlquery\\' => __DIR__ . '/',
    ];

    foreach ($prefixes as $prefix => $baseDir) {
        $prefixLen = strlen($prefix);
        if (strncmp($class, $prefix, $prefixLen) !== 0) {
            continue;
        }

        $relativeClass = substr($class, $prefixLen);
        $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

        if (is_file($file)) {
            require_once $file;
        }
    }
});
