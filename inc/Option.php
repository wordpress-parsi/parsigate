<?php

namespace ParsiGate;

class Option
{

    public static string $gateways = 'parsigate_gateways';

    public function __construct()
    {

        add_filter('wpp_registered_settings_tabs', [$this, 'settings_tabs'], 20);
        add_filter('wpp_registered_settings', [$this, 'settings'], 20);
    }

    public static function all(): array
    {
        return wp_parsi_get_settings();
    }

    public static function get($name, $default = null)
    {
        $all = self::all();
        if (array_key_exists($name, $all)) {
            return $all[$name];
        }

        return $default;
    }

    public static function enable_gateways()
    {
        return self::get(self::$gateways, []);
    }

    public function settings_tabs($tabs): array
    {
        $raw = [];
        foreach ($tabs as $key => $title) {
            if ($key == 'tools') {
                $raw['parsigate'] = sprintf(__('%s ParsiGate', 'wp-parsidate'), '<span class="dashicons dashicons-admin-plugins"></span>');
            }
            $raw[$key] = $title;
        }

        return $raw;
    }

    public function settings($settings)
    {
        $options = [
            self::$gateways => [
                'id' => self::$gateways,
                'name' => __('Payment gateways', 'parsigate'),
                'type' => 'multicheck',
                'options' => Gateways::choices(),
                'std' => array(),
            ]
        ];

        $settings['parsigate'] = apply_filters('wpp_patsigate_settings', $options);
        return $settings;
    }


}

new Option();