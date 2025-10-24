<?php

namespace ParsiGate\gateways;

use ParsiGate\Option;

/**
 * @see https://github.com/pep-ipg
 */
class Pasargad extends Base
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
            'body' => json_encode($body),
            'timeout' => 30,
            'redirection' => '5',
            'httpsversion' => '1.0',
            'blocking' => true,
            'headers' => $headers,
            'cookies' => array()
        ];

        $url = 'https://pep.shaparak.ir/dorsa1/token/getToken';
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
        if (isset($json['resultCode']) and $json['resultCode'] == "0" and isset($json['token']) and !empty($json['token'])) {

            return $this->success(
                [
                    'access_token' => $json['token']
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
        if (!empty($json['resultMsg'])) {
            $error_messages .= ' (' . $json['resultMsg'] . ')';
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
            "amount" => $args['amount'],
            "invoice" => $args['invoice'],
            "invoiceDate" => $args['invoiceDate'],
            "serviceCode" => $args['serviceCode'],
            "serviceType" => $args['serviceType'],
            "callbackApi" => $args['callbackApi'],
            "payerMail" => $args['payerMail'],
            "mobileNumber" => $args['mobileNumber'],
            "terminalNumber" => $args['terminalNumber']
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

        $url = 'https://pep.shaparak.ir/dorsa1/api/payment/purchase';
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
        if (isset($json['resultCode']) and $json['resultCode'] == "0" and isset($json['data']['url']) and !empty($json['data']['url'])) {

            return $this->success(
                [
                    'authority' => $json['data']['urlId'],
                    'redirect' => $json['data']['url']
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
        $error_messages = __('Error Code: ', 'parsigate') . ($json['resultCode'] ?? '');
        if (!empty($json['resultMsg'])) {
            $error_messages .= ' (' . $json['resultMsg'] . ')';
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
        // Setup Headers
        $headers = [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $args['access_token']
        ];

        /**
         * Step 1) Payment Inquiry
         */

        // Setup Body
        $body = [
            'invoiceId' => $args['invoiceId']
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

        $url = 'https://pep.shaparak.ir/dorsa1/api/payment/payment-inquiry';
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

        // Error
        if (!isset($json['data']['status']) || $json['data']['status'] != "2") {

            $error_messages = __('Error Code: ', 'parsigate') . ($json['resultCode'] ?? '');
            if (!empty($json['resultMsg'])) {
                $error_messages .= ' (' . $json['resultMsg'] . ')';
            }

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

        // Save Log
        if (Option::enable_log()) {

            \ParsiGate\CustomTable\Log::insert([
                'gateway' => 'pasargad',
                'url' => $url,
                'type' => 1,
                'code' => $status_code,
                'header' => $headers,
                'body' => $body,
                'response' => $json,
                'created_at' => current_time('mysql')
            ]);
        }

        // Get Transaction Id
        $transaction_id = $json['data']['transactionId'];
        $token = $json['data']['url'];

        /**
         * Step 2) Confirm Transaction
         */

        // Setup Body
        $body = [
            "invoice" => $args['invoiceId'],
            "urlId" => $token
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

        $url = "https://pep.shaparak.ir/dorsa1/api/payment/confirm-transactions";
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

        if (isset($json['resultCode']) and $json['resultCode'] == "0") {

            return $this->success(
                [
                    'transaction_id' => $transaction_id
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
        $error_messages = __('Error Code: ', 'parsigate') . ($json['resultCode'] ?? '');
        if (!empty($json['resultMsg'])) {
            $error_messages .= ' (' . $json['resultMsg'] . ')';
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