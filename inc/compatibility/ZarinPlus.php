<?php

namespace ParsiGate\compatibility;

use ParsiGate\WooCommerce;
use WPParsidate\Addons\ParsiGateOption\ParsiGateOption;

if (!defined('ABSPATH')) exit;

class ZarinPlus
{

    public string $gateway_id;

    public function __construct()
    {
        $this->gateway_id = 'zarinplus';

        add_action('woocommerce_update_options_payment_gateways_' . $this->gateway_id, [$this, 'save_option']);
        add_filter('parsigate_' . $this->gateway_id . '_token_description', [$this, 'description'], 30);
        add_filter('parsigate_gateways_list', [$this, 'setup_gateways'], 30);
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

    public function setup_gateways($lists)
    {
        // Check enable ZarinPlus
        $option = ParsiGateOption::get($this->gateway_id);
        $enable = ((int)$option == 1);
        if (!$enable) {
            return $lists;
        }

        // Check MultiPay Gateways ZarinPal Lists
        $settings = get_option('woocommerce_' . $this->gateway_id . '_settings');
        if (is_array($settings) and isset($settings['lists']) and is_array($settings['lists']) and !empty($settings['lists'])) {
            foreach ($settings['lists'] as $item) {

                if ($item['slug'] == $this->gateway_id) {
                    continue;
                }

                $title = $item['title'];
                $icon = $item['icon'];
                $slug = $this->gateway_id . '_' . $item['slug'];
                $lists[$slug] = [
                    'title' => $title,
                    'logo' => $icon,
                    'force_enable' => true,
                    'hidden' => true,
                    'class' => \ParsiGate\gateways\ZarinPlus::class,
                    'website' => 'zarinplus.com',
                    'type' => 'installment',
                    'usage' => ['woocommerce'],
                    'woocommerce' => [
                        'settings' => [
                            'token' => [
                                'title' => __('Merchant token', 'parsigate'),
                                'type' => 'text',
                                'default' => ($settings['token'] ?? ''),
                                'description' => __('Please enter the gateway merchant token.', 'parsigate'),
                                'desc_tip' => false,
                                'class' => 'pg-ltr-input'
                            ]
                        ],
                        'pay' => function ($amount, $order, $option, $callback_url, $class) use ($item, $slug) {

                            return [
                                'amount' => $amount,
                                'cancel' => wc_get_checkout_url(),
                                'success' => $callback_url,
                                'item' => WooCommerce::get_order_description($order, $slug),
                                'cellphone' => $order->get_billing_phone(),
                                'email' => $order->get_billing_email(),
                                'token' => $option['token'],
                                'gateway_slug' => $item['slug']
                            ];
                        },
                        'verify' => function ($amount, $order, $option, $class, $request) {

                            $authority = (isset($request['get']['authority']) ? sanitize_text_field($request['get']['authority']) : '');
                            return [
                                'authority' => $authority,
                                "token" => $option['token'],
                                "amount" => $amount
                            ];
                        }
                    ]
                ];
            }
        }

        return $lists;
    }
}

new ZarinPlus();