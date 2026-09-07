<?php

namespace ParsiGate\compatibility;

use WPParsidate\Addons\ParsiGateOption\ParsiGateOption;

if (!defined('ABSPATH')) exit;

class Base
{

    public string $gateway_id = '';

    public function enable(): bool
    {
        $option = ParsiGateOption::get($this->gateway_id);
        return ((int)$option == 1);
    }

    public function wc(): array
    {
        $settings = get_option('woocommerce_' . $this->gateway_id . '_settings');
        if (!empty($settings) and is_array($settings)) {
            return $settings;
        }

        return [];
    }

}