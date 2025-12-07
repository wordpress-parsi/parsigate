<?php

namespace ParsiGate\gateways;

use ParsiGate\WooCommerce;

class Test extends Base
{
    public function pay(array $args = []): array
    {
        $redirect = add_query_arg([
            WooCommerce::$test_gateway_query => $args['order_id'],
            'callback_url' => urlencode_deep($args['callback_url']),
        ], get_site_url(null, "/"));

        return $this->success(
            [
                'authority' => wp_generate_uuid4(),
                'redirect' => $redirect
            ],
            [
                'url' => '',
                'body' => [],
                'response' => [],
                'header' => [],
            ],
            200
        );
    }

    public function verify(array $args = []): array
    {
        // Get Params
        $status = $args['status'];

        // Check Status
        if ($status == "NOK") {
            return $this->error();
        }

        // Success
        return $this->success(
            [
                'transaction_id' => wp_rand(1000000000, 9999999999),
            ],
            [
                'url' => '',
                'body' => [],
                'response' => [],
                'header' => [],
            ],
            200
        );
    }
}