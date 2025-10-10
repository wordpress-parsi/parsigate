<?php

namespace ParsiGate\gateways;

class AqayePardakht extends Base
{

    public function pay(array $args = []): array
    {
        // SandBox
        $isSandbox = (isset($args['sandbox']) and $args['sandbox'] === true);

        // Setup Body
        $body = [
            'pin' => ($isSandbox ? 'sandbox' : $args['pin']),
            'amount' => $args['amount'],
            'callback' => $args['callback'],
            'invoice_id' => $args['invoice_id'],
            'mobile' => $args['mobile'],
            'email' => $args['email'],
            'description' => $args['description'],
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

        $url = 'https://panel.aqayepardakht.ir/api/v2/create';
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
        if (isset($json['status']) and $json['status'] == "success" and !empty($json['transid'])) {

            return $this->success(
                [
                    'authority' => $json['transid'],
                    'redirect' => sprintf('https://panel.aqayepardakht.ir/startpay/' . ($isSandbox ? 'sandbox/' : '') . '%s', $json['transid'])
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
        $error_messages = __('Error Code: ', 'parsigate') . ($json['code'] ?? '');

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
        // SandBox
        $isSandbox = (isset($args['sandbox']) and $args['sandbox'] === true);

        // Check Status
        if ($args['status'] != '1') {
            return $this->error();
        }

        // Setup Body
        $body = [
            'pin' => ($isSandbox ? 'sandbox' : $args['pin']),
            'amount' => $args['amount'],
            'transid' => $args['transid']
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

        $url = 'https://panel.aqayepardakht.ir/api/v2/verify';
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

        if (isset($json['code']) and in_array($json['code'], ['1', '2'])) {

            return $this->success(
                [
                    'transaction_id' => $args['transid']
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
        $error_messages = __('Error Code: ', 'parsigate') . ($json['code'] ?? '');

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