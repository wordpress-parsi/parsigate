<?php

namespace ParsiGate\gateways;

class PayPing extends Base
{

    public static string $request_url = 'https://api.payping.ir/v3/pay';

    public static string $verify_url = 'https://api.payping.ir/v3/pay/verify';

    public function pay(array $args = []): array
    {

        // Setup Body
        $body = [
            'payerName' => $args['payerName'],
            'Amount' => $args['Amount'],
            'payerIdentity' => $args['payerIdentity'],
            'returnUrl' => $args['returnUrl'],
            'Description' => $args['Description'],
            'clientRefId' => (string)$args['clientRefId']
        ];

        // Setup Headers
        $headers = [
            'Authorization' => 'Bearer ' . $args['token'],
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
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

        $response = wp_remote_post(static::$request_url, $request);
        $status_code = wp_remote_retrieve_response_code($response);
        if (is_wp_error($response)) {

            return $this->error(
                $response->get_error_message(),
                [
                    'url' => static::$request_url,
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
        if ($status_code == "200" and isset($json["url"]) and !empty($json["url"])) {
            /**
             * {
             * "paymentCode": "480132d6-679f-4112-8bc7-2c95459438b2",
             * "url": "https://api.payping.ir/v3/pay/start/480132d6-679f-4112-8bc7-2c95459438b2",
             * "amount": 604000
             * }
             */
            return $this->success(
                [
                    'authority' => ($json["paymentCode"] ?? ''),
                    'redirect' => $json["url"]
                ],
                [
                    'url' => static::$request_url,
                    'body' => $body,
                    'response' => $json,
                    'header' => $headers,
                ],
                $status_code
            );
        }

        // Setup Errors
        $error_messages = [
            __('Error Code: ', 'parsigate') . ($json['metaData']['code'] ?? '')
        ];
        if (isset($json['metaData']['errors']) && is_array($json['metaData']['errors'])) {
            $error_messages = array_merge($error_messages, array_column($json['metaData']['errors'], 'message'));
        }

        // Error
        return $this->error(
            $error_messages,
            [
                'url' => static::$request_url,
                'body' => $body,
                'response' => $json,
                'header' => $headers,
            ],
            $status_code
        );
    }

    public function verify(array $args = []): array
    {
        // Check Success
        if (array_key_exists('success', $args) and $args['success'] === false) {
            return $this->error();
        }

        // Setup Body
        $body = [
            'PaymentRefId' => trim((string)$args['paymentRefId']),
            "paymentCode" => trim((string)$args['paymentCode']),
            "amount" => $args['amount']
        ];

        // Setup Headers
        $headers = [
            'Authorization' => 'Bearer ' . $args['token'],
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
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

        $response = wp_remote_post(static::$verify_url, $request);
        $status_code = wp_remote_retrieve_response_code($response);
        if (is_wp_error($response)) {

            return $this->error(
                $response->get_error_message(),
                [
                    'url' => static::$verify_url,
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

        // https://github.com/payping/payping-php-client/blob/master/src/Payment.php
        if ($status_code >= 200 && $status_code < 300) {

            return $this->success(
                [
                    'transaction_id' => trim((string)$args['paymentRefId'])
                ],
                [
                    'url' => static::$verify_url,
                    'body' => $body,
                    'response' => $json,
                    'header' => $headers,
                ],
                $status_code
            );
        }

        // Setup Errors
        $error_messages = [
            __('Error Code: ', 'parsigate') . ($json['metaData']['code'] ?? '')
        ];
        if (isset($json['metaData']['errors']) && is_array($json['metaData']['errors'])) {
            $error_messages = array_merge($error_messages, array_column($json['metaData']['errors'], 'message'));
        }

        // Error
        return $this->error(
            $error_messages,
            [
                'url' => static::$request_url,
                'body' => $body,
                'response' => $json,
                'header' => $headers,
            ],
            $status_code
        );
    }
}