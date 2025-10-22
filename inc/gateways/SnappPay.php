<?php

namespace ParsiGate\gateways;

class SnappPay extends Base
{

    public function token(array $args = []): array
    {

        // Setup Body
        $body = [
            'grant_type' => 'password',
            'scope' => 'online-merchant',
            'username' => $args['username'],
            'password' => $args['password'],
        ];

        // Setup Headers
        $headers = [
            'Authorization' => 'Basic ' . base64_encode("{$args['client_id']}:{$args['client_secret']}"),
            'Content-Type' => 'application/x-www-form-urlencoded'
        ];

        // Setup Request args
        $request = [
            'body' => $body,
            'timeout' => 30,
            'redirection' => '5',
            'httpsversion' => '1.0',
            'blocking' => true,
            'headers' => $headers,
            'cookies' => array()
        ];

        $url = 'https://api.snapppay.ir/api/online/v1/oauth/token';
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
        if ($status_code == "200" and isset($json['access_token']) and !empty($json['access_token'])) {

            return $this->success(
                $json,
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
        $error_messages = __('Invalid gateway settings input.', 'parsigate');

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

    public function pay(array $args = []): array
    {

        // Setup Body
        $body = [
            'amount' => $args['amount'],
            'mobile' => $args['mobile'],
            'paymentMethodTypeDto' => $args['paymentMethodTypeDto'],
            'transactionId' => $args['transactionId'],
            'returnURL' => $args['returnURL'],
            'cartList' => $args['cartList'],
            'discountAmount' => $args['discountAmount']
        ];

        // Setup Headers
        $headers = [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $args['access_token'],
        ];

        // Setup Request args
        $request = [
            'body' => json_encode($body),
            'timeout' => 30,
            'redirection' => '5',
            'httpsversion' => '1.0',
            'blocking' => true,
            'headers' => $headers,
            'cookies' => array()
        ];

        $url = 'https://api.snapppay.ir/api/online/payment/v1/token';
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
        if ($status_code == "200" and !empty($json['response']['paymentToken']) and !empty($json['response']['paymentPageUrl'])) {

            return $this->success(
                [
                    'authority' => $json['response']['paymentToken'],
                    'redirect' => $json['response']['paymentPageUrl']
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
        $error_messages = ($json['errorData']['message'] ?? __('Error Code: ', 'parsigate') . ($status_code ?? ''));

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
        // Check State
        if (strtolower($args['state']) != 'ok') {
            return $this->error();
        }

        // Setup Body
        $body = [
            'paymentToken' => $args['paymentToken']
        ];

        // Setup Headers
        $headers = [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $args['access_token'],
        ];

        // Setup Request args
        $request = [
            'body' => json_encode($body),
            'timeout' => 30,
            'redirection' => '5',
            'httpsversion' => '1.0',
            'blocking' => true,
            'headers' => $headers,
            'cookies' => array()
        ];

        $url = 'https://api.snapppay.ir/api/online/payment/v1/verify';
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

        if ($status_code == "200" and isset($json['successful']) and $json['successful'] === true and !empty($json['response']['transactionId'])) {

            return $this->success(
                [
                    'transaction_id' => $json['response']['transactionId']
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
        $error_messages = ($json['errorData']['message'] ?? __('Error Code: ', 'parsigate') . ($status_code ?? ''));

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

    public function status(array $args = []): array
    {

        // Setup Body
        $body = [
            'paymentToken' => $args['paymentToken']
        ];

        // Setup Headers
        $headers = [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $args['access_token'],
        ];

        // Setup Request args
        $request = [
            'body' => json_encode($body),
            'timeout' => 30,
            'redirection' => '5',
            'httpsversion' => '1.0',
            'blocking' => true,
            'headers' => $headers,
            'cookies' => array()
        ];

        $url = 'https://api.snapppay.ir/api/online/payment/v1/status';
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

        if ($status_code == "200" and isset($json['successful']) and $json['successful'] === true) {

            return $this->success(
            /**
             * 'status' => 'VERIFY'
             */
                $json,
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
        $error_messages = ($json['errorData']['message'] ?? __('Error Code: ', 'parsigate') . ($status_code ?? ''));

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
}