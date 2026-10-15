<?php

class WP_Error
{
    public function __construct(
        private string $message
    ) {}

    public function get_error_message(): string
    {
        return $this->message;
    }
}

function __($text, $domain = null)
{
    return $text;
}

function current_time($type)
{
    return '2026-01-01 00:00:00';
}

function wp_json_encode($value)
{
    return json_encode($value);
}

function is_wp_error($value)
{
    return $value instanceof WP_Error;
}

function wp_remote_post($url, $args = [])
{
    return $GLOBALS['parsigate_http_response'];
}

function wp_remote_retrieve_response_code($response)
{
    return $response['response']['code'] ?? 0;
}

function wp_remote_retrieve_body($response)
{
    return $response['body'] ?? '';
}

require_once __DIR__ . '/../inc/gateways/Base.php';
require_once __DIR__ . '/../inc/gateways/Vandar.php';
