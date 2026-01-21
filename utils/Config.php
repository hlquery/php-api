<?php
/*
 * hlquery PHP Client - Configuration Utilities
 * 
 * Copyright (C) 2021-2026, Carlos F. Ferry <carlos.ferry@gmail.com>
 * 
 * This file is part of hlquery, released under the BSD License version 3.
 */

namespace Hlquery\Utils;

/*
 * Configuration utility class
 */
class Config {
    /*
     * Default configuration values
     */
    const DEFAULT_TIMEOUT = 30;
    const DEFAULT_BASE_URL = 'http://localhost:9200';
    const DEFAULT_AUTH_METHOD = 'bearer';
    
    /*
     * Merge user options with defaults
     * 
     * @param array $userOptions
     * @return array
     */
    public static function mergeDefaults($userOptions = []) {
        return array_merge([
            'timeout' => self::DEFAULT_TIMEOUT,
            'base_url' => self::DEFAULT_BASE_URL,
            'auth_method' => self::DEFAULT_AUTH_METHOD,
            'token' => null
        ], $userOptions);
    }
    
    /*
     * Validate base URL
     * 
     * @param string $url
     * @return bool
     */
    public static function isValidUrl($url) {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }
    
    /*
     * Normalize base URL (remove trailing slash)
     * 
     * @param string $url
     * @return string
     */
    public static function normalizeUrl($url) {
        return rtrim($url, '/');
    }
}
