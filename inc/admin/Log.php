<?php

namespace ParsiGate\Admin;

use ParsiGate\Option;

class Log
{
    public static string $slug = 'pg_log';

    public function __construct()
    {
        add_action('admin_menu', [$this, 'admin_menu']);
    }

    public function admin_menu()
    {
        if (!Option::enable_log()) {
            return;
        }

        add_management_page(
            __('ParsiGate Log', 'parsigate'),
            __('ParsiGate Log', 'parsigate'),
            apply_filters('parsigate_log_menu_permission', 'manage_options'),
            self::$slug,
            [$this, 'page']
        );
    }

    public static function url($args = []): string
    {
        return add_query_arg(array_replace([
            'page' => self::$slug
        ], $args), admin_url('tools.php'));
    }

    public function page()
    {
        echo 'test';
    }

}

new Log();