<?php

namespace ParsiGate;

class Option
{

    public static string $prefix = 'pg_';

    public function __construct()
    {

        add_filter('wpp_registered_settings_tabs', [$this, 'settings_tabs'], 20);
        add_filter('wpp_registered_settings', [$this, 'settings'], 20);
    }

    public static function all(): array
    {
        return wp_parsi_get_settings();
    }

    public static function option_name($type): string
    {
        return self::$prefix . strtolower($type);
    }

    public static function get($name, $default = null)
    {
        $all = self::all();
        if (array_key_exists($name, $all)) {
            return $all[$name];
        }

        return $default;
    }

    public function settings_tabs($tabs): array
    {
        $raw = [];
        foreach ($tabs as $key => $title) {
            if ($key == 'tools') {
                /* translators: %s: Dashicon HTML for ParsiGate logo */
                $raw['parsigate'] = sprintf(__('%s ParsiGate', 'parsigate'), '<span class="dashicons dashicons-bank"></span>');
            }
            $raw[$key] = $title;
        }

        return $raw;
    }

    public function settings($settings)
    {
        $options = [];

        // Gateways
        foreach (Gateways::types() as $key => $label) {
            $option_id = self::option_name($key);
            $options[$option_id] = [
                'id' => $option_id,
                'name' => $label,
                'type' => 'multicheck',
                'options' => Gateways::choices($key),
                'std' => array(),
            ];
        }

        // Log
        $options[self::$prefix . 'log'] = array(
            'id' => self::$prefix . 'log',
            'name' => __('ParsiGate Log', 'parsigate'),
            'type' => 'select',
            'options' => array(
                'yes' => __('Yes', 'parsigate'),
                'no' => __('No', 'parsigate'),
            ),
            'std' => 'no',
            'desc' => (Option::enable_log() ? '<a href="' . add_query_arg(['page' => 'pg_log'], admin_url('tools.php')) . '" target="_blank">(' . __("Show Logs", "parsigate") . ')</a>' : '')
        );

        $settings['parsigate'] = apply_filters('parsigate_settings', $options);
        return $settings;
    }

    public static function enable_log(): bool
    {
        $get = self::get(self::$prefix . 'log');
        return ($get == "yes");
    }

}

new Option();