<?php

namespace ParsiGate\gateways;

/**
 * @Document: http://docs.zarinplus.com/
 */
class ZarinPlus extends Base
{

    public function pay(array $args = []): array
    {
        // Setup Data
        $body = [
            'amount' => $args['amount'],
            'cancel' => $args['cancel'],
            'success' => $args['success'],
            'item' => $args['item'],
            'cellphone' => $args['cellphone'],
            'email' => $args['email'],
            'token' => $args['token'],
            'gateway_slug' => ($args['gateway_slug'] ?? 'zarinplus')
        ];

        // Setup Headers
        $headers = [
            'Content-Type' => 'application/json',
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

        $url = 'https://api.zarinplus.com/payment/v2/request/';
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
        if ($status_code == "200" and !empty($json['redirect_url'])) {

            return $this->success(
                [
                    'authority' => $json['authority'],
                    'redirect' => $json['redirect_url']
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
        $error_messages = !empty($json['message']) ? $json['message'] : $json;

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

        // Setup Data
        $body = [
            "authority" => $args['authority'],
            "token" => $args['token'],
            "amount" => $args['amount']
        ];

        // Setup Headers
        $headers = [
            'Content-Type' => 'application/json',
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

        $url = 'https://api.zarinplus.com/payment/v2/verify/';
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

        // Get Data
        if (isset($json['status']) and $json['status'] === true and isset($json['data']['code']) and in_array($json['data']['code'], ['200', '201'])) {

            return $this->success(
                [
                    'transaction_id' => $json['data']['reference'] ?? ''
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
        $error_messages = !empty($json['message']) ? $json['message'] : $json;

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

    public static function gateways(array $args = []): array
    {

        // Setup Data
        $body = [
            "token" => $args['token']
        ];

        // Setup Headers
        $headers = [
            'Content-Type' => 'application/json',
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

        $url = 'https://api.zarinplus.com/payment/gateways/';
        $response = wp_remote_post($url, $request);
        $status_code = wp_remote_retrieve_response_code($response);
        if (is_wp_error($response)) {

            return [
                'status' => false,
                'message' => $response->get_error_message(),
                'code' => $status_code
            ];
        }

        // Get Response Body
        $response_body = wp_remote_retrieve_body($response);
        $json = json_decode($response_body, true);

        // Success
        if ($status_code == "200" and isset($json['status']) and $json['status'] === true) {

            return [
                'status' => true,
                'data' => $json['data'],
                'code' => $status_code
            ];
        }

        // Error
        return [
            'status' => false,
            'message' => $json['message'] ?? '',
            'code' => $status_code
        ];
    }

}