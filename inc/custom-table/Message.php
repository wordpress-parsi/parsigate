<?php

namespace ParsiGate\CustomTable;

class Message
{

    public static string $key = 'flash-message';

    public static int $timeout = 4; // Second

    public static string $handler = 'cookie'; // 'meta' or 'cookie'

    public static function get($user_id = null)
    {
        if (is_null($user_id)) {
            $user_id = get_current_user_id();
        }

        $get = [];
        if (self::$handler == "cookie") {

            $cookie = (isset($_COOKIE[self::$key]) ? sanitize_text_field(wp_unslash($_COOKIE[self::$key])) : "[]");
            if (is_string($cookie) && is_array(json_decode(stripslashes_deep($cookie), true))) {
                $get = json_decode(stripslashes_deep($cookie), true);
            }
        } else {
            $get = get_user_meta($user_id, self::$key, true);
        }
        if (empty($get)) {
            return $get;
        }

        if (isset($get['type']) and isset($get['data']) and isset($get['expire']) and (int)$get['expire'] >= time()) {
            // First Clean
            self::clean($user_id);

            // Return
            return $get;
        }

        return [];
    }

    public static function set($data = '', $type = 'success', $user_id = null): bool
    {
        if (is_null($user_id)) {
            $user_id = get_current_user_id();
        }

        $args = [
            'type' => $type,
            'data' => $data,
            'expire' => current_time('timestamp') + self::$timeout
        ];

        if (self::$handler == "cookie") {
            setcookie(self::$key, json_encode($args, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), (time() + self::$timeout), COOKIEPATH, COOKIE_DOMAIN);
        } else {
            update_user_meta($user_id, self::$key, $args);
        }

        // Return
        return true;
    }

    public static function clean($user_id = null): bool
    {
        if (is_null($user_id)) {
            $user_id = get_current_user_id();
        }

        if (self::$handler == "cookie") {
            if (isset($_COOKIE[self::$key])) {
                unset($_COOKIE[self::$key]);
                @setcookie(self::$key, '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN);
            }
        } else {
            update_user_meta($user_id, self::$key, []);
        }

        return true;
    }

    public static function admin_notice($text, $model = "info", $close_button = true, $echo = true, $style_extra = 'padding:12px;')
    {
        $text = '
        <div class="notice notice-' . $model . '' . ($close_button === true ? " is-dismissible" : "") . '">
           <div style="' . $style_extra . ' inline">' . $text . '</div>
        </div>
        ';

        if ($echo) {
            echo wp_kses_post($text);
        } else {
            return $text;
        }
    }

    public static function admin_notice_handler($type = 'error', $message = '', $args = [], $priority = 90)
    {
        add_action('admin_notices', function () use ($type, $message, $args) {
            self::admin_notice($message, $type);
            $_SERVER['REQUEST_URI'] = remove_query_arg(array_merge([], $args));
        }, $priority);
    }
}