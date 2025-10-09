<?php

namespace ParsiGate;

class WC_Gateway_Block extends \Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType
{

    public $driver;
    public $gateway;

    public function __construct($driver)
    {
        $this->driver = $driver;
        $this->name = $driver;
    }

    public function initialize()
    {
        $this->settings = get_option("woocommerce_{$this->driver}_settings", []);
        $this->gateway = Gateways::get($this->driver);
    }

    public function is_active()
    {
        return filter_var($this->settings['enabled'], FILTER_VALIDATE_BOOLEAN);
    }

    public function get_payment_method_script_handles()
    {

        $script_id = "parsigate-blocks";
        wp_register_script(
            $script_id,
            \ParsiGate::$plugin_url . '/assets/js/parsigate-blocks.min.js',
            [
                'wc-blocks-registry',
                'wc-settings',
                'wp-element',
                'wp-html-entities',
                'wp-i18n',
            ],
            \ParsiGate::$plugin_version,
            true
        );

        return [$script_id];
    }

    public function get_payment_method_data(): array
    {

        return [
            'name' => $this->name,
            'title' => (empty($this->settings['title']) ? $this->gateway['title'] : $this->settings['title']),
            'description' => (empty($this->settings['description']) ? $this->gateway['description'] : $this->settings['description']),
            'icon' => $this->get_gateway_icon(),
            'driver' => $this->driver,
            'supports' => ['products']
        ];
    }

    protected function get_gateway_icon()
    {
        $logo = '';

        $image = \ParsiGate::$plugin_path . '/assets/logo/' . $this->driver . '.png';
        if (file_exists($image)) {
            $logo = \ParsiGate::$plugin_url . '/assets/logo/' . $this->driver . '.png';
        }

        if (isset($this->gateway['logo']) and !empty($this->gateway['logo'])) {
            $logo = $this->gateway['logo'];
        }

        return apply_filters('parsigate_gateway_icon', $logo, $this->driver);
    }
}