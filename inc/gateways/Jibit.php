<?php

namespace ParsiGate\gateways;

class Jibit extends Base
{

    public function token(array $args = []): array
    {

        // Setup Body
        $body = [
            'apiKey' => $args['apiKey'],
            'secretKey' => $args['secretKey']
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

        $url = 'https://napi.jibit.ir/ppg/v3/tokens';
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
        if ($status_code >= 200 and $status_code < 300 and isset($json['accessToken']) and !empty($json['accessToken'])) {

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
            'wage' => ($args['wage'] ?? 0),
            'currency' => $args['currency'],
            'clientReferenceNumber' => $args['clientReferenceNumber'],
            'description' => $args['description'],
            'callbackUrl' => $args['callbackUrl']
        ];

        if (isset($args['payerMobileNumber']) and !empty($args['payerMobileNumber'])) {
            $body['payerMobileNumber'] = $args['payerMobileNumber'];
        }

        if (array_key_exists('checkPayerMobileNumber', $args)) {
            $body['checkPayerMobileNumber'] = (bool)$args['checkPayerMobileNumber'];
        }

        if (isset($args['userIdentifier']) and !empty($args['userIdentifier'])) {
            $body['userIdentifier'] = $args['userIdentifier'];
        }

        // Setup Headers
        $headers = [
            'Content-Type' => 'application/json',
            'X-JIBIT-AGENT' => '',
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

        $url = 'https://napi.jibit.ir/ppg/v3/purchases';
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
        if ($status_code >= 200 and $status_code < 300 and isset($json['pspSwitchingUrl']) and !empty($json['pspSwitchingUrl']) and isset($json['purchaseId']) and !empty($json['purchaseId'])) {

            return $this->success(
                [
                    'authority' => $json['purchaseId'],
                    'redirect' => $json['pspSwitchingUrl']
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
        $error_messages = $json['errors'][0]['message'] ?? '';

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
        if (strtoupper($args['status']) == "FAILED") {
            return $this->error();
        }

        // Setup Body
        $body = [];

        // Setup Headers
        $headers = [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $args['token'],
        ];

        // Setup Request args
        $request = [
            'timeout' => 30,
            'redirection' => '5',
            'httpsversion' => '1.0',
            'blocking' => true,
            'headers' => $headers,
            'cookies' => array()
        ];

        $url = 'https://napi.jibit.ir/ppg/v3/purchases/' . $args['purchaseId'] . '/verify';
        $response = wp_remote_get($url, $request);
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

        if ($status_code >= 200 and $status_code < 300 and isset($json['status']) and strtoupper($json['status']) == "SUCCESSFUL") {

            return $this->success(
                array_merge(
                    [
                        'transaction_id' => $args['purchaseId']
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
        $error_messages = $json['errors'][0]['message'] ?? '';

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