<?php
/*
 * hlquery PHP Client - Autoloader
 * 
 * Copyright (C) 2021-2026, Carlos F. Ferry <carlos.ferry@gmail.com>
 * 
 * This file is part of hlquery, released under the BSD License version 3.
 */

// Load Exceptions first since Validator depends on it
require_once __DIR__ . '/Exceptions.php';

spl_autoload_register(function ($class) {
    // Check for Utils namespace first (longer prefix must be checked first)
    $utilsPrefix = 'Hlquery\\Utils\\';
    $utilsLen = strlen($utilsPrefix);
    if (strncmp($utilsPrefix, $class, $utilsLen) === 0) {
        $relativeClass = substr($class, $utilsLen);
        $file = __DIR__ . '/../utils/' . str_replace('\\', '/', $relativeClass) . '.php';
        if (file_exists($file)) {
            require $file;
            return;
        }
    }
    
    // Check for main Hlquery namespace
    $prefix = 'Hlquery\\';
    $baseDir = __DIR__ . '/';
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) === 0) {
        // Get relative class name
        $relativeClass = substr($class, $len);
        
        // Replace namespace separators with directory separators
        $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
        
        // If file exists, require it
        if (file_exists($file)) {
            require $file;
        }
    }
});
