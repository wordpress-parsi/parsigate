<?php

namespace ParsiGate\CustomTable;

class Page
{


    public static function page_slug(): string
    {
        return '';
    }

    public static function screen_id(): string
    {
        return 'toplevel_page_' . static::page_slug();
    }

    public static function is_page(): bool
    {
        global $pagenow;
        return ($pagenow == "admin.php");
    }

    public static function url($args = []): string
    {
        return add_query_arg(array_replace([
            'page' => static::page_slug()
        ], $args), admin_url('admin.php'));
    }

}