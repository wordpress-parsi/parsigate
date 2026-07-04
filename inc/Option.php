<?php

namespace WPParsidate\Addons\ParsiGateOption;

use ParsiGate\Gateways;
use WPParsidate\Addons\Addon;
use WPParsidate\Settings\Settings;

class ParsiGateOption extends Addon
{

    public string $addonID = 'parsigate';

    public const icon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#873EFF">
              <path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12Zm10.5-4.5a.75.75 0 0 0-1.5 0v.443c-.96.11-1.86.56-2.424 1.405-.77 1.153-.46 2.677.737 3.524.66.467 1.46.702 2.187.702.562 0 1.03.137 1.32.344.236.168.305.36.305.582 0 .243-.08.454-.267.623-.396.358-1.172.48-1.944.208a3.54 3.54 0 0 1-1.284-.782.75.75 0 1 0-.96 1.152c.6.5 1.38.868 2.33.988v.561a.75.75 0 0 0 1.5 0v-.596c.904-.143 1.693-.56 2.19-1.214.47-.618.64-1.44.454-2.204-.185-.764-.705-1.403-1.456-1.857-.544-.328-1.2-.494-1.904-.494-.457 0-.86-.106-1.118-.288-.206-.146-.293-.313-.293-.513 0-.178.066-.348.21-.493.31-.31.93-.458 1.61-.264.47.134.872.39 1.135.676a.75.75 0 1 0 1.102-1.018c-.5-.54-1.214-.93-2.096-1.066V7.5Z" clip-rule="evenodd"/>
            </svg>';

    private static ?array $settings = null;

    /* @method */
    public static function option_name($type): string
    {
        return strtolower($type);
    }

    /* @method */
    public static function get($name, $default = null)
    {
        if (Settings::get('internal_addon_parsigate', false) !== 1) {
            return null;
        }

        return Settings::get($name, $default, 'parsigate');
    }

    /* @method */
    public static function enable_log(): bool
    {
        $get = self::get('log');
        return ($get == "yes");
    }

    /* @class */
    public function initAction(): void
    {
    }

    /* @class */
    public function settings(): ?array
    {
        if (self::$settings === null) {

            $settings = [];

            // Gateways
            foreach (Gateways::types() as $key => $label) {

                $settings['gateways_' . $key] = [
                    'title' => $label,
                    'type' => 'startGrid',
                ];

                foreach (Gateways::choices($key) as $gateway_key => $gateway_title) {

                    $settings[$gateway_key] = [
                        'id' => $gateway_key,
                        'title' => $gateway_title,
                        'type' => 'toggle',
                        'value' => 1,
                        'default' => false,
                        'sanitize' => 'bool'
                    ];
                }

                $settings['gateways_' . $key . '_end'] = [
                    'type' => 'endGrid',
                ];
            }

            $settings['log_settings'] = array(
                'title' => __('Gateways log', 'parsigate'),
                'type' => 'startGrid',
            );

            // Log
            $settings['log'] = array(
                'id' => 'log',
                'title' => __('ParsiGate Log', 'parsigate'),
                'type' => 'select',
                'options' => array(
                    'yes' => __('Yes', 'parsigate'),
                    'no' => __('No', 'parsigate'),
                ),
                'default' => 'no',
                'sanitize' => 'text',
                'desc' => (static::enable_log() ? '<a href="' . add_query_arg(['page' => 'pg_log'], admin_url('tools.php')) . '" target="_blank">' . __("Show logs list", "parsigate") . '</a>' : '')
            );

            $settings['log_settings_end'] = [
                'type' => 'endGrid',
            ];

            self::$settings = [
                'title' => __('Parsi Gate', 'parsigate'),
                'desc' => __('Parsi Gate Tools', 'parsigate'),
                'settings_key' => $this->addonID,
                'settings' => $settings
            ];
        }

        return self::$settings;
    }

    /* @class */
    public function info(): array
    {
        return array(
            'id' => $this->addonID,
            'title' => __('Parsi Gate', 'parsigate'),
            'menu_title' => __('Parsi Gate', 'parsigate'),
            'has_page' => true,
            'force_enable' => false,
            'desc' => __('Payment gateways', 'parsigate'),
            'icon' => self::icon,
            'image_link' => 'https://parsidate.com',
            'more_info_link' => 'https://parsidate.com',
            'tags' => [
                __('Payment gateway', 'parsigate')
            ],
            'cat' => 'ecommerce',
            'settings_key' => $this->addonID,
        );
    }
}

new ParsiGateOption();