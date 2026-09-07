<?php

namespace ParsiGate\compatibility;

if (!defined('ABSPATH')) exit;

class Test extends Base
{

    public string $gateway_id = 'test';

    public function __construct()
    {
        add_action('init', [$this, 'page']);
    }

    public function page()
    {
        if (!$this->enable()) {
            return;
        }

        $query = \ParsiGate\gateways\Test::$query;
        if (isset($_GET[$query]) and is_numeric($_GET[$query]) and !empty($_GET['callback_url']) and !empty($_GET['parsigate_nonce'])) {

            $nonce = sanitize_text_field(wp_unslash($_GET['parsigate_nonce']));
            if (!wp_verify_nonce($nonce, 'parsigate_test_gateway_' . absint($_GET[$query]))) {
                wp_die(
                    esc_html__('Security verification failed. Invalid or expired nonce.', 'parsigate'),
                    esc_html__('Security Error', 'parsigate'),
                    ['response' => 403]
                );
            }

            $callback_url = urldecode_deep(sanitize_text_field(wp_unslash($_GET['callback_url'])));
            $order_id = sanitize_text_field(absint($_GET[$query]));
            $order = wc_get_order($order_id);
            if (!$order) {
                wp_die(esc_html__('Order ID is Invalid.', 'parsigate'));
            }

            if ($order->get_status() != 'pending') {
                wp_die(esc_html__('Order Status is not pending payment.', 'parsigate'));
            }

            wp_enqueue_style('parsigate-test-gateway', \ParsiGate::$plugin_url . '/assets/css/test-gateway.min.css', array(), \ParsiGate::$plugin_version, 'all');

            $template = apply_filters('parsigate_test_gateway_template', \ParsiGate::$plugin_path . '/inc/templates/test-gateway.php');
            if (file_exists($template)) {
                ob_start();
                include $template;
                $output = ob_get_contents();
                ob_end_clean();
                wp_die(
                // phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
                    $output,
                    esc_html__('Test Gateway', 'parsigate'),
                    array('response' => 200)
                );
            }
        }
    }
}

new Test();