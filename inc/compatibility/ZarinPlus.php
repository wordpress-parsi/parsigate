<?php

namespace ParsiGate\compatibility;

use ParsiGate\WooCommerce;

class ZarinPlus
{

    public string $gateway_id;

    public function __construct($gateways_id)
    {
        $this->gateway_id = $gateways_id;

        add_action('woocommerce_update_options_payment_gateways_' . $this->gateway_id, [$this, 'save_option']);
        add_filter('parsigate_' . $this->gateway_id . '_token_description', [$this, 'description'], 30);
    }

    public function save_option()
    {

        $settings = get_option('woocommerce_' . $this->gateway_id . '_settings');
        if (is_array($settings) and isset($settings['token']) and !empty($settings['token'])) {

            $gateways = \ParsiGate\gateways\ZarinPlus::gateways([
                'token' => $settings['token']
            ]);
            if ($gateways['status'] === true) {

                $settings['lists'] = $gateways['data'];
                update_option('woocommerce_' . $this->gateway_id . '_settings', $settings);
            }
        }
    }

    public function description($desc)
    {
        if (WooCommerce::is_gateway_option_page($this->gateway_id)) {

            $settings = get_option('woocommerce_' . $this->gateway_id . '_settings');
            if (is_array($settings) and isset($settings['lists']) and is_array($settings['lists']) and !empty($settings['lists'])) {

                $desc = sprintf(
                    wp_kses_post('<p>%d %s</p>'),
                    count($settings['lists']),
                    __('Gateways is found:', 'parsigate')
                );
                foreach ($settings['lists'] as $list) {
                    $desc .= wp_kses_post('<div style="display: inline-block;margin-left: 15px;"><img src="' . $list['icon'] . '" alt="' . $list['title'] . '" style="width: 40px;height: 40px;background: #ffffff;border-radius: 5px;margin-left: 10px;padding:2px;"><span style="vertical-align: 20px;">' . $list['title'] . '</span></div>');
                }
            }
        }

        return $desc;
    }
}