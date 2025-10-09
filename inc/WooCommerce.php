<?php

namespace ParsiGate;

class WooCommerce
{

    private static array $gateways_data = [];

    public function __construct()
    {
        add_filter('woocommerce_payment_gateways', [$this, 'woocommerce_payment_gateways']);
        add_action('woocommerce_blocks_loaded', array($this, 'woocommerce_blocks_loaded'));
        add_action('wp_enqueue_scripts', [$this, 'enqueue_block_scripts']);
    }

    public function woocommerce_payment_gateways($methods)
    {
        $gateways = Gateways::list();
        foreach ($gateways as $gateway_id => $option) {
            if (!Gateways::enable($gateway_id) || !in_array('woocommerce', $option['usage'])) {
                continue;
            }

            $gateway_class = new \ParsiGate\WC_Gateway();
            $gateway_class->setup_gateway($gateway_id);
            $methods[] = $gateway_class;
        }

        return $methods;
    }

    public function woocommerce_blocks_loaded()
    {
        if (!class_exists('\Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType')) {
            return;
        }

        add_action('woocommerce_blocks_payment_method_type_registration', [$this, 'register_block_gateways']);
    }

    public function register_block_gateways($payment_method_registry)
    {
        $gateways = Gateways::list();
        foreach ($gateways as $gateway_id => $option) {
            if (!Gateways::enable($gateway_id) || !in_array('woocommerce', $option['usage'])) {
                continue;
            }

            $block_gateway = new WC_Gateway_Block($gateway_id);
            $block_gateway->initialize();

            $woocommerce_settings = get_option('woocommerce_' . $gateway_id . '_settings', []);
            $is_enabled_in_woocommerce = isset($woocommerce_settings['enabled']) && $woocommerce_settings['enabled'] === 'yes';
            if (!$block_gateway->is_active() || !$is_enabled_in_woocommerce) continue;

            $payment_method_registry->register($block_gateway);
            $method_data = $block_gateway->get_payment_method_data();
            self::$gateways_data[] = [
                'name' => $gateway_id,
                'title' => $method_data['title'],
                'description' => $method_data['description'],
                'icon' => $method_data['icon'],
                'driver' => $gateway_id
            ];
        }
    }

    public function enqueue_block_scripts()
    {
        if (has_block('woocommerce/checkout') || has_block('woocommerce/cart')) {
            wp_enqueue_script(
                'parsigate-blocks',
                \ParsiGate::$plugin_url . '/assets/js/parsigate-blocks.min.js',
                ['wc-blocks-registry', 'wc-settings', 'wp-element', 'wp-html-entities', 'wp-i18n'],
                \ParsiGate::$plugin_version,
                true
            );

            wp_localize_script('parsigate-blocks', 'parsigate_gateways', self::$gateways_data);
        }
    }

    public static function get_gateway_option($id)
    {
        return get_option('woocommerce_' . $id . '_settings', []);
    }
}

new WooCommerce();