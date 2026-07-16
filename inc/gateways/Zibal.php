<?php

namespace ParsiGate\gateways;

class Zibal extends Base
{
    public function pay(array $args = []): array
    {
        // SandBox
        $isSandbox = (isset($args['sandbox']) and $args['sandbox'] === true);

        // Setup Body
        $body = [
            'merchant' => ($isSandbox ? 'zibal' : $args['merchant']),
            'amount' => $args['amount'],
            'callbackUrl' => $args['callbackUrl'],
            'orderId' => $args['orderId'],
            'mobile' => $args['mobile'],
            'email' => $args['email'],
            'description' => $args['description']
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

        $url = 'https://gateway.zibal.ir/v1/request';
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
        if (isset($json['result']) and $json['result'] == "100" and !empty($json['trackId'])) {

            return $this->success(
                [
                    'authority' => $json['trackId'],
                    'redirect' => sprintf('https://gateway.zibal.ir/start/%s', $json['trackId'])
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
        $error_messages = [
            __('Error Code: ', 'parsigate') . ($json['result'] ?? '')
        ];
        if (isset($json['message']) and !empty($json['message'])) {
            $error_messages = array_merge($error_messages, (array)$json['message']);
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
        // SandBox
        $isSandbox = (isset($args['sandbox']) and $args['sandbox'] === true);

        // Check Success
        if ($args['success'] != '1') {
            return $this->error();
        }

        // Setup Body
        $body = [
            'merchant' => ($isSandbox ? 'zibal' : $args['merchant']),
            'trackId' => $args['trackId']
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

        $url = 'https://gateway.zibal.ir/v1/verify';
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

        if (isset($json['result']) and in_array($json['result'], ['100', '201'])) {

            return $this->success(
                [
                    'transaction_id' => $args['trackId']
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
        $error_messages = [
            __('Error Code: ', 'parsigate') . ($json['result'] ?? '')
        ];
        if (isset($json['message']) and !empty($json['message'])) {
            $error_messages = array_merge($error_messages, (array)$json['message']);
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