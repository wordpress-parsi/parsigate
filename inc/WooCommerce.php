<?php

namespace ParsiGate;

class WooCommerce
{

    private static array $gateways_data = [];

    public static string $test_gateway_query = 'wc-parsigate-test-gateway';

    public function __construct()
    {
        // Define Classic Gateway
        add_filter('woocommerce_payment_gateways', [$this, 'woocommerce_payment_gateways']);

        // Define Block Gateway
        add_action('woocommerce_blocks_loaded', array($this, 'woocommerce_blocks_loaded'));
        add_action('wp_enqueue_scripts', [$this, 'enqueue_block_scripts']);

        // Define Test Gateway Page
        add_action('init', [$this, 'test_gateway_page']);
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

    public function test_gateway_page()
    {
        if (isset($_GET[self::$test_gateway_query]) and is_numeric($_GET[self::$test_gateway_query]) and !empty($_GET['callback_url'])) {

            $callback_url = urldecode_deep($_GET['callback_url']);
            $order_id = absint($_GET[self::$test_gateway_query]);

            $order = wc_get_order($order_id);
            if (!$order) {
                wp_die(__('Order ID is Invalid.', 'parsigate'));
            }

            if ($order->get_status() != 'pending') {
                wp_die(__('Order Status is not pending payment.', 'parsigate'));
            }

            $template = apply_filters('parsigate_test_gateway_template', \ParsiGate::$plugin_path . '/inc/templates/test-gateway.php');
            if (file_exists($template)) {
                ob_start();
                include $template;
                $output = ob_get_contents();
                ob_end_clean();
                wp_die($output, __('Test Gateway', 'parsigate'));
            }
        }
    }

    public static function get_order_description($order, $gateway = null)
    {
        $name = $order->get_billing_company();
        if (!empty($order->get_billing_first_name()) || !empty($order->get_billing_last_name())) {
            $name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
        }

        $site_url = get_option('siteurl');
        $domain = parse_url($site_url, PHP_URL_HOST);

        return apply_filters('parsigate_order_description_api', sprintf(
            'Order ID: %d | By: %s | Site: %s',
            $order->get_order_number(),
            trim($name),
            ucfirst($domain)
        ), $order, $gateway);
    }
}

new WooCommerce();