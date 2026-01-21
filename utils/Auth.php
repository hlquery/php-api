<?php
/*
 * hlquery PHP Client - Authentication Utilities
 * 
 * Copyright (C) 2021-2026, Carlos F. Ferry <carlos.ferry@gmail.com>
 * 
 * This file is part of hlquery, released under the BSD License version 3.
 */

namespace Hlquery\Utils;

/*
 * Authentication utility class
 */
class Auth {
    /*
     * Generate MD5 hash for token (utility function for token generation)
     * Note: Authentication only requires a token - no username/password needed
     * 
     * @param string $token
     * @return string MD5 hash
     */
    public static function generateToken($token) {
        return md5($token);
    }
    
    /*
     * Validate token format
     * 
     * @param string $token
     * @return bool
     */
    public static function isValidToken($token) {
        return !empty($token) && is_string($token);
    }
    
    /*
     * Get authentication header value
     * 
     * @param string $token
     * @param string $method 'bearer' or 'api-key'
     * @return array ['header' => 'Header-Name', 'value' => 'Header-Value']
     */
    public static function getAuthHeader($token, $method = 'bearer') {
        if ($method === 'api-key') {
            return ['header' => 'X-API-Key', 'value' => $token];
        }
        return ['header' => 'Authorization', 'value' => 'Bearer ' . $token];
    }
}
