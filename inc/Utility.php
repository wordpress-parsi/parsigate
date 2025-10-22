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

    public static function ip()
    {
        // pre
        $pre = apply_filters('parsigate_pre_get_user_ip', null);
        if (!is_null($pre)) {
            return $pre;
        }

        // Cloudflare
        if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            return trim($_SERVER['HTTP_CF_CONNECTING_IP']);
        }

        $headers = [
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'HTTP_X_FORWARDED',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'HTTP_CLIENT_IP'
        ];

        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];

                if (strpos($ip, ',') !== false) {
                    $ips = explode(',', $ip);
                    $ip = trim($ips[0]);
                }

                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return $_SERVER['REMOTE_ADDR'] ?? '';
    }

}
