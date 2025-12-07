<?php

namespace ParsiGate\CustomTable;

use ParsiGate\Gateways;

class Log extends Base
{

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
        return (isset($list[$type_id]) ? trim($list[$type_id]) : 'نامشخص');
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