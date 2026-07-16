<?php

namespace ParsiGate\gateways;

class Shepa extends Base
{

    public function pay(array $args = []): array
    {
        // SandBox
        $isSandbox = (isset($args['sandbox']) and $args['sandbox'] === true);

        // Setup Body
        $body = [
            'api' => $args['api'],
            'amount' => $args['amount'],
            'callback' => $args['callback'],
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
            'body' => wp_json_encode($body),
            'timeout' => 30,
            'redirection' => '5',
            'httpsversion' => '1.0',
            'blocking' => true,
            'headers' => $headers,
            'cookies' => array()
        ];

        $url = "https://merchant.shepa.com/api/v1/token";
        if ($isSandbox) {
            $url = "https://sandbox.shepa.com/api/v1/token";
        }
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
        if (isset($json['success']) and strcasecmp($json['success'], "true") and !empty($json['result']['url'])) {

            return $this->success(
                [
                    'authority' => $json['result']['token'],
                    'redirect' => $json['result']['url']
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
        $error_messages = empty($json['error']) ? $json['errors'] : $json['error'];

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
        if ($args['status'] == 'failed') {
            return $this->error();
        }

        // Setup Body
        $body = [
            'api' => $args['api'],
            'amount' => $args['amount'],
            'token' => $args['token'],
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

        $url = "https://merchant.shepa.com/api/v1/verify";
        if ($isSandbox) {
            $url = "https://sandbox.shepa.com/api/v1/verify";
        }
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

        if (isset($json['success']) and $json['success'] === true and !empty($json['result']['transaction_id'])) {

            return $this->success(
                [
                    'transaction_id' => $json['result']['transaction_id']
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
        $error_messages = empty($json['error']) ? $json['errors'] : $json['error'];

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