<?php

namespace ParsiGate\gateways;

if (!defined('ABSPATH')) exit;

class Vandar extends Base
{
    private const REQUEST_URL = 'https://ipg.vandar.io/api/v4/send';
    private const VERIFY_URL = 'https://ipg.vandar.io/api/v4/verify';
    private const REDIRECT_URL = 'https://ipg.vandar.io/v4/';

    public function pay(array $args = []): array
    {
        $body = [
            'api_key' => (string)($args['api_key'] ?? ''),
            'amount' => (int)($args['amount'] ?? 0),
            'callback_url' => (string)($args['callback_url'] ?? ''),
            'mobile_number' => (string)($args['mobile_number'] ?? ''),
            'factorNumber' => (string)($args['factorNumber'] ?? ''),
            'description' => (string)($args['description'] ?? ''),
        ];

        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];

        $request = [
            'body' => wp_json_encode($body),
            'timeout' => 30,
            'redirection' => 5,
            'httpversion' => '1.0',
            'blocking' => true,
            'headers' => $headers,
            'cookies' => [],
        ];

        $response = wp_remote_post(self::REQUEST_URL, $request);
        $status_code = is_wp_error($response)
            ? 0
            : (int)wp_remote_retrieve_response_code($response);

        $response_body = is_wp_error($response)
            ? ''
            : wp_remote_retrieve_body($response);

        $json = json_decode($response_body, true);

        if (is_wp_error($response)) {
            return $this->error(
                $response->get_error_message(),
                [
                    'url' => self::REQUEST_URL,
                    'body' => $this->log_body($body),
                    'response' => $response->get_error_message(),
                    'header' => $headers,
                ],
                $status_code
            );
        }

        if (!is_array($json)) {
            return $this->error(
                __('Invalid response from Vandar.', 'parsigate'),
                [
                    'url' => self::REQUEST_URL,
                    'body' => $this->log_body($body),
                    'response' => $response_body,
                    'header' => $headers,
                ],
                $status_code
            );
        }

        $token = (string)($json['token'] ?? '');

        if ((int)($json['status'] ?? 0) === 1 && $token !== '') {
            return $this->success(
                [
                    'authority' => $token,
                    'redirect' => self::REDIRECT_URL . rawurlencode($token),
                ],
                [
                    'url' => self::REQUEST_URL,
                    'body' => $this->log_body($body),
                    'response' => $json,
                    'header' => $headers,
                ],
                $status_code
            );
        }

        return $this->error(
            $this->get_error_message($json, __('Vandar payment request failed.', 'parsigate')),
            [
                'url' => self::REQUEST_URL,
                'body' => $this->log_body($body),
                'response' => $json,
                'header' => $headers,
            ],
            $status_code
        );
    }

    public function verify(array $args = []): array
    {
        $token = (string)($args['token'] ?? '');
        $payment_status = (string)($args['payment_status'] ?? '');

        if ($payment_status !== 'OK' || $token === '') {
            return $this->error(
                __('Vandar payment was cancelled or failed.', 'parsigate'),
                [
                    'url' => '',
                    'body' => [
                        'token' => $token,
                        'payment_status' => $payment_status,
                    ],
                    'response' => '',
                    'header' => [],
                ],
                0
            );
        }

        $body = [
            'api_key' => (string)($args['api_key'] ?? ''),
            'token' => $token,
        ];

        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];

        $request = [
            'body' => wp_json_encode($body),
            'timeout' => 30,
            'redirection' => 5,
            'httpversion' => '1.0',
            'blocking' => true,
            'headers' => $headers,
            'cookies' => [],
        ];

        $response = wp_remote_post(self::VERIFY_URL, $request);
        $status_code = is_wp_error($response)
            ? 0
            : (int)wp_remote_retrieve_response_code($response);

        $response_body = is_wp_error($response)
            ? ''
            : wp_remote_retrieve_body($response);

        $json = json_decode($response_body, true);

        if (is_wp_error($response)) {
            return $this->error(
                $response->get_error_message(),
                [
                    'url' => self::VERIFY_URL,
                    'body' => $this->log_body($body),
                    'response' => $response->get_error_message(),
                    'header' => $headers,
                ],
                $status_code
            );
        }

        if (!is_array($json)) {
            return $this->error(
                __('Invalid response from Vandar.', 'parsigate'),
                [
                    'url' => self::VERIFY_URL,
                    'body' => $this->log_body($body),
                    'response' => $response_body,
                    'header' => $headers,
                ],
                $status_code
            );
        }

        if ((int)($json['status'] ?? 0) === 1 && !empty($json['transId'])) {
            $expected_amount = (int)($args['amount'] ?? 0);
            $paid_amount = (int)($json['amount'] ?? 0);

            if (
                $expected_amount <= 0 ||
                $paid_amount < $expected_amount
            ) {
                return $this->error(
                    __('Vandar payment amount is invalid.', 'parsigate'),
                    [
                        'url' => self::VERIFY_URL,
                        'body' => $this->log_body($body),
                        'response' => $json,
                        'header' => $headers,
                    ],
                    $status_code
                );
            }

            return $this->success(
                [
                    'transaction_id' => (string)$json['transId'],
                    'paid_amount' => $json['realAmount'] ?? $json['amount'],
                    'reference_number' => $json['refnumber'] ?? null,
                    'tracking_code' => $json['trackingCode'] ?? null,
                    'card_number' => $json['cardNumber'] ?? null,
                ],
                [
                    'url' => self::VERIFY_URL,
                    'body' => $this->log_body($body),
                    'response' => $json,
                    'header' => $headers,
                ],
                $status_code
            );
        }

        return $this->error(
            $this->get_error_message($json, __('Vandar verification failed.', 'parsigate')),
            [
                'url' => self::VERIFY_URL,
                'body' => $this->log_body($body),
                'response' => $json,
                'header' => $headers,
            ],
            $status_code
        );
    }

    private function log_body(array $body): array
    {
        if (isset($body['api_key'])) {
            $body['api_key'] = '***';
        }

        return $body;
    }

    private function get_error_message(array $json, string $fallback): string
    {
        if (!empty($json['message'])) {
            return (string)$json['message'];
        }

        if (!empty($json['errors']) && is_array($json['errors'])) {
            return implode(', ', array_map('strval', $json['errors']));
        }

        return $fallback;
    }
}
