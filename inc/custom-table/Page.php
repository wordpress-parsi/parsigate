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

        // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
        return ($pagenow == "admin.php" and isset($_GET['page']) and $_GET['page'] == static::page_slug());
    }

    public static function is_screen($name = ''): bool
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
        return (static::is_page() and !empty($_GET['screen']) and $_GET['screen'] == $name);
    }

    public static function screen(): string
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
        $screen = (isset($_GET['screen']) ? sanitize_text_field(wp_unslash($_GET['screen'])) : '');
        return (!empty($screen) ? trim($screen) : '');
    }

    public static function url($args = []): string
    {
        return add_query_arg(array_replace(['page' => static::page_slug()], $args), admin_url('admin.php'));
    }

    public static function reset_entry_form(): void
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
        if (isset($_POST['ct'])) {
            $_POST['ct'] = '';
        }
    }

}