<?php

namespace ParsiGate\gateways;

class Tara extends Base
{

    public function token(array $args = []): array
    {

        // Setup Body
        $body = [
            'username' => $args['username'],
            'password' => $args['password'],
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

        $url = 'https://pay.tara360.ir/pay/api/v2/authenticate';
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
        if ($status_code == "200" and isset($json['accessToken']) and !empty($json['accessToken'])) {

            return $this->success(
                [
                    'access_token' => $json['accessToken']
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
        $error_messages = __('Invalid gateway settings input.', 'parsigate');
        if (!empty($json['description'])) {
            $error_messages = $json['description'];
        } elseif (!empty($json['body']['message'])) {
            $error_messages = $json['body']['message'];
        }

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
            'additionalData' => $args['additionalData'],
            'mobile' => $args['mobile'],
            'callBackUrl' => $args['callBackUrl'],
            'amount' => $args['amount'],
            'vat' => $args['vat'],
            'serviceAmountList' => $args['serviceAmountList'],
            'taraInvoiceItemList' => $args['taraInvoiceItemList'],
            'ip' => $args['ip']
        ];

        // Setup Headers
        $headers = [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $args['access_token'],
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

        $url = 'https://pay.tara360.ir/pay/api/getToken';
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
        if (isset($json['token']) and !empty($json['token'])) {

            return $this->success(
                [
                    'authority' => $json['token'],
                    'redirect' => [
                        'with_post' => true,
                        'url' => 'https://pay.tara360.ir/pay/api/ipgPurchase',
                        'inputs' => [
                            'username' => $args['username'],
                            'token' => $json['token']
                        ]
                    ]
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
        $error_messages = __('Error Code: ', 'parsigate') . ($status_code ?? '');
        if (!empty($json['description'])) {
            $error_messages = $json['description'];
        } elseif (!empty($json['message'])) {
            $error_messages = $json['message'];
        }

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
        // Check result
        if ($args['result'] != '0') {
            return $this->error();
        }

        // Setup Body
        $body = [
            'token' => $args['token'],
            'ip' => $args['ip']
        ];

        // Setup Headers
        $headers = [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $args['access_token'],
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

        $url = 'https://pay.tara360.ir/pay/api/purchaseVerify';
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

        if ($status_code == "200" and isset($json['result']) and $json['result'] == "0" and !empty($json['rrn'])) {

            return $this->success(
                [
                    'transaction_id' => $json['rrn']
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
        $error_messages = __('Error Code: ', 'parsigate') . ($status_code ?? '');
        if (!empty($json['description'])) {
            $error_messages = $json['description'];
        } elseif (!empty($json['message'])) {
            $error_messages = $json['message'];
        }

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