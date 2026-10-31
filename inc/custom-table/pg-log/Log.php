<?php

namespace ParsiGate\CustomTable;

if (!defined('ABSPATH')) exit;

use ParsiGate\Gateways;

if ( ! defined( 'ABSPATH' ) ) exit;

class Log extends Base
{
    // Final log-only redaction, after write filters and before database storage.
    protected static function sanitize_before_write(array $row): array
    {
        foreach (['url', 'header', 'body', 'response', 'meta'] as $field) {
            if (array_key_exists($field, $row)) {
                $row[$field] = self::redact_log_value($row[$field]);
            }
        }

        return $row;
    }

    private static function is_secret_key($key): bool
    {
        $key = strtolower((string) preg_replace('/[^a-z0-9]/i', '', (string) $key));

        if (in_array($key, [
            'key', 'apikey', 'xapikey', 'secretkey', 'accesskey', 'privatekey', 'clientsecret',
            'secret', 'token', 'accesstoken', 'refreshtoken', 'bearertoken',
            'authorization', 'proxyauthorization', 'authkey', 'credential',
            'credentials', 'password', 'pass', 'pwd', 'pin', 'username',
            'merchantid', 'merchantkey', 'merchanttoken', 'terminalid',
            'signature', 'cookie', 'setcookie', 'sessionid', 'authority',
        ], true)) {
            return true;
        }

        return (bool) preg_match('/(?:password|secret|apikey|token)$/', $key);
    }

    private static function redact_log_value($value)
    {
        if (is_array($value)) {
            $redacted = [];
            foreach ($value as $key => $item) {
                $redacted[$key] = self::is_secret_key($key)
                    ? '[REDACTED]'
                    : self::redact_log_value($item);
            }
            return $redacted;
        }

        if (is_object($value)) {
            return self::redact_log_value((array) $value);
        }

        if (!is_string($value)) {
            return $value;
        }

        $json = json_decode($value, true);
        if (JSON_ERROR_NONE === json_last_error() && is_array($json)) {
            return self::redact_log_value($json);
        }

        // Protect a credential if it appears as plain text, a URL query parameter
        // or a serialized HTTP Authorization header.
        $value = preg_replace('/(Bearer\s+)\S+/i', '$1[REDACTED]', $value);
        $value = preg_replace('/((?:Proxy-)?Authorization\s*:\s*(?:Basic|Token)\s+)\S+/i', '$1[REDACTED]', $value);
        $name = '(?:x[_-]?api[_-]?key|api[_-]?key|secret[_-]?key|client[_-]?secret|access[_-]?token|'
            . 'refresh[_-]?token|authorization|auth[_-]?key|merchant[_-]?(?:id|key|token)|'
            . 'terminal[_-]?id|user[_-]?password|password|secret|signature|'
            . 'cookie|session[_-]?id|token|authority|pin|key)';

        // Handle key=value and quoted JSON fragments embedded in error messages.
        $value = preg_replace_callback(
            '/((?:^|[?&;,\s{])["\']?' . $name . '["\']?\s*[:=]\s*["\']?)[^&;,\s"\'<>}]+/i',
            static function ($matches) {
                return $matches[1] . '[REDACTED]';
            },
            $value
        );

        // Encoded query parameter names (for example api%5Fkey) also need masking.
        $value = preg_replace_callback(
            '/([?&]([^=&#\s]+)=)([^&#\s]*)/i',
            static function ($matches) {
                return self::is_secret_key(rawurldecode($matches[2]))
                    ? $matches[1] . '[REDACTED]'
                    : $matches[0];
            },
            $value
        );

        // SOAP elements can carry namespace declarations and xsi:type attributes.
        $value = preg_replace_callback(
            '/(<(?P<tag>(?:[\w.-]+:)?' . $name . ')(?:\s[^>]*)?>)(.*?)(<\/(?P=tag)\s*>)/is',
            static function ($matches) {
                return $matches[1] . '[REDACTED]' . $matches[4];
            },
            $value
        );

        return $value;
    }


    public function __construct()
    {
        add_filter('parsigate_prepare_' . static::table(), [$this, 'prepare_item']);
    }

    /* @config */
    public static function slug(): string
    {
        return 'pg_log';
    }

    /* @config */
    public static function title(): string
    {
        return __('ParsiGate Log', 'parsigate');
    }

    /* @config */
    public static function primary_key(): string
    {
        return 'ID';
    }

    /* @config */
    public static function get_json_fields(): array
    {
        return ['body', 'response', 'header', 'meta'];
    }

    /* @config */
    public static function default(): array
    {
        return array(
            'gateway' => '',
            'url' => '',
            /**
             * Type List:
             *
             * 1: REST API
             * 2: Soap
             */
            'type' => 1,
            'code' => 200,
            'header' => [],
            'body' => [],
            'response' => [],
            'meta' => [],
            'created_at' => current_time('mysql')
        );
    }

    /* @hook */
    public static function setup_mysql_table(): void
    {
        // push to main plugin method { register_activation_hook }
    }

    /* @method */
    public static function get_type_list(): array
    {
        return [
            '1' => 'REST API',
            '2' => 'SOAP'
        ];
    }

    /* @method */
    public static function get_type_name($type_id): string
    {
        $list = static::get_type_list();
        return (isset($list[$type_id]) ? trim($list[$type_id]) : __('Unknown', 'parsigate'));
    }

    /* @method */
    public static function get_gateway_log_list($gateway, $code = null)
    {
        $args = [
            'order' => 'DESC',
            'query' => [
                [
                    'key' => 'gateway',
                    'value' => $gateway,
                    'compare' => '='
                ]
            ],
            'prepare' => true
        ];
        if (!is_null($code) and is_numeric($code)) {

            $args['query'][] = [
                'key' => 'code',
                'value' => $code,
                'compare' => '='
            ];
        }

        $items = static::list($args);
        if (empty($items)) {
            return [];
        }

        return $items;
    }

    /* @method */
    public function prepare_item($item)
    {
        $item['header_status'] = get_status_header_desc($item['code']);
        $item['gateway_object'] = Gateways::get($item['gateway']);
        $item['type_name'] = self::get_type_name($item['type']);
        return $item;
    }
}

new Log();