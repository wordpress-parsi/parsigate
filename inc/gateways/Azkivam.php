<?php

namespace ParsiGate\gateways;

class Azkivam extends Base
{
    public function pay(array $args = []): array
    {

        // Setup Body
        $body = [
            'amount' => $args['amount'],
            'redirect_uri' => $args['redirect_uri'],
            'fallback_uri' => $args['fallback_uri'],
            'provider_id' => $args['provider_id'],
            'mobile_number' => $args['mobile_number'],
            'items' => $args['items']
        ];

        // Setup Headers
        $headers = [
            'Content-Type' => 'application/json',
            'Signature' => $this->signature("/payment/purchase", 'POST', $args['api_key']),
            'MerchantId' => $args['MerchantId'],
        ];

        // Setup Request args
        $request = [
            'body' => wp_json_encode($body),
            'timeout' => 30,
            'redirection' => '5',
            'httpsversion' => '1.0',
            'blocking' => true,
            'headers' => $headers,
            'cookies' => array()
        ];

        $url = $args['api_url'] . "/payment/purchase";
        $response = wp_remote_post($url, $request);
        $status_code = wp_remote_retrieve_response_code($response);
        if (is_wp_error($response)) {

            return $this->error(
                $response->get_error_message(),
                [
                    'url' => $url,
                    'body' => $body,
                    'response' => (array)$response->get_error_message(),
                    'header' => $headers,
                ],
                $status_code
            );
        }

        // Get Response Body
        $response_body = wp_remote_retrieve_body($response);
        $json = json_decode($response_body, true);

        // Success
        if (isset($json['rsCode']) and $json['rsCode'] == "0" and !empty($json['result']['payment_uri'])) {

            return $this->success(
                [
                    'authority' => ($json['result']['ticket_id'] ?? ''),
                    'redirect' => $json['result']['payment_uri']
                ],
                [
                    'url' => $url,
                    'body' => $body,
                    'response' => $json,
                    'header' => $headers,
                ],
                $status_code
            );
        }

        // Setup Errors
        $error_messages = __('Error Code: ', 'parsigate') . $json['rsCode'] . ($json['message'] ? ' (' . $json['message'] . ')' : '');

        // Error
        return $this->error(
            $error_messages,
            [
                'url' => $url,
                'body' => $body,
                'response' => $json,
                'header' => $headers,
            ],
            $status_code
        );
    }

    public function verify(array $args = []): array
    {

        // Check Status
        if (strtolower($args['status']) == 'done') {
            return $this->error();
        }

        // Setup Body
        $body = [
            'ticket_id' => $args['ticket_id']
        ];

        // Setup Headers
        $headers = [
            'Content-Type' => 'application/json',
            'Signature' => $this->signature("/payment/verify", 'POST', $args['api_key']),
            'MerchantId' => $args['MerchantId'],
        ];

        // Setup Request args
        $request = [
            'body' => wp_json_encode($body),
            'timeout' => 30,
            'redirection' => '5',
            'httpsversion' => '1.0',
            'blocking' => true,
            'headers' => $headers,
            'cookies' => array()
        ];

        $url = $args['api_url'] . "/payment/verify";
        $response = wp_remote_post($url, $request);
        $status_code = wp_remote_retrieve_response_code($response);
        if (is_wp_error($response)) {

            return $this->error(
                $response->get_error_message(),
                [
                    'url' => $url,
                    'body' => $body,
                    'response' => (array)$response->get_error_message(),
                    'header' => $headers,
                ],
                $status_code
            );
        }

        // Get Response Body
        $response_body = wp_remote_retrieve_body($response);
        $json = json_decode($response_body, true);

        if (isset($json['rsCode']) and $json['rsCode'] == "0") {

            return $this->success(
                [
                    'transaction_id' => $args['ticket_id']
                ],
                [
                    'url' => $url,
                    'body' => $body,
                    'response' => $json,
                    'header' => $headers,
                ],
                $status_code
            );
        }

        // Setup Errors
        $error_messages = __('Error Code: ', 'parsigate') . ($json['rsCode'] ?? '');

        // Error
        return $this->error(
            $error_messages,
            [
                'url' => $url,
                'body' => $body,
                'response' => $json,
                'header' => $headers,
            ],
            $status_code
        );
    }

    public function signature($sub_url, $request_method, $api_key): string
    {
        $plain = $sub_url . '#' . time() . '#' . $request_method . '#' . $api_key;
        $key = hex2bin($api_key);
        $digest = openssl_encrypt($plain, 'AES-256-CBC', $key, OPENSSL_RAW_DATA);

        return bin2hex($digest);
    }

}