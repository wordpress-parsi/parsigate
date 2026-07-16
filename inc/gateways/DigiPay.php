<?php

namespace ParsiGate\gateways;

class DigiPay extends Base
{

    const VERSION = '2022-02-02';

    public function token(array $args = []): array
    {

        // Setup Body
        $body = [
            'username' => $args['username'],
            'password' => $args['password'],
            'grant_type' => 'password',
        ];

        // Setup Headers
        $headers = [
            'Authorization' => 'Basic ' . base64_encode($args['client_id'] . ':' . $args['client_secret']),
            'Content-Type' => 'application/x-www-form-urlencoded',
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

        $url = 'https://api.mydigipay.com/digipay/api/oauth/token';
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
            /**
             * {
             * "access_token": "",
             * "token_type": "bearer",
             * "refresh_token": "",
             * "expires_in": 3599,
             * "scope": "",
             * "jti": ""
             * }
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
            'cellNumber' => $args['cellNumber'],
            'providerId' => $args['providerId'],
            'callbackUrl' => $args['callbackUrl']
        ];

        if (isset($args['basketDetailsDto']) and !empty($args['basketDetailsDto'])) {
            $body['basketDetailsDto'] = $args['basketDetailsDto'];
        }

        // Setup Headers
        $headers = [
            'Agent' => 'WEB',
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $args['token'],
            'Digipay-Version' => self::VERSION,
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

        $url = 'https://api.mydigipay.com/digipay/api/tickets/business' . '?type=' . $args['type'];
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
        if ($status_code == "200" and isset($json['ticket']) and !empty($json['ticket']) and isset($json['redirectUrl']) and !empty($json['redirectUrl'])) {

            return $this->success(
                [
                    'authority' => $json['ticket'],
                    'redirect' => $json['redirectUrl']
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
        $error_messages = $json['result']['message'] ?? '';

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

        // Setup Body
        $body = [];

        // Setup Headers
        $headers = [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $args['token'],
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

        $url = 'https://api.mydigipay.com/digipay/api/purchases/verify/' . $args['trackingCode'] . '?type=' . $args['type'];
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

        if ($status_code == "200" and isset($json['trackingCode']) and !empty($json['trackingCode'])) {

            return $this->success(
                array_merge(
                    [
                        'transaction_id' => $json['trackingCode']
                    ],
                    $json
                ),
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
        $error_messages = $json['result']['message'] ?? '';

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