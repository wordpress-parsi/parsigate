<?php

namespace ParsiGate;

class Utility
{

    public static function is_enable_open_ssl(): bool
    {
        return (extension_loaded('openssl') and function_exists('OpenSSL_encrypt'));
    }

    public static function is_soap_enabled(): bool
    {
        return extension_loaded('soap');
    }

    public static function soap_status_code($client, $default = '200')
    {
        $headers = $client->__getLastResponseHeaders();
        if (preg_match('/HTTP\/\d\.\d\s+(\d+)/', $headers, $matches)) {
            return (int)$matches[1];
        }

        return $default;
    }

}
