<?php

declare(strict_types=1);

namespace ParsiGate\gateways;

/**
 * ParsPal Payment Gateway
 *
 * Document: https://developer.parspal.com/
 * API: https://api.parspal.com/v1/payment/request | /verify
 */
class ParsPal extends Base
{
    public static string $request_url = 'https://api.parspal.com/v1/payment/request';

    public static string $verify_url = 'https://api.parspal.com/v1/payment/verify';

    public static string $sandbox_request_url = 'https://sandbox.api.parspal.com/v1/payment/request';

    public static string $sandbox_verify_url = 'https://sandbox.api.parspal.com/v1/payment/verify';

    /**
     * Request payment and get redirect link.
     *
     * @param array $args {
     *     @type bool   $sandbox
     *     @type string $merchant_id  API Key
     *     @type int    $amount       Amount in Rial
     *     @type string $return_url
     *     @type string $order_id
     *     @type string $description
     *     @type string $name
     *     @type string $mobile
     *     @type string $email
     * }
     * @return array
     */
    public function pay(array $args = []): array
    {
        $is_sandbox = isset($args['sandbox']) && $args['sandbox'] === true;

        $body = [
            'amount'     => (int) ($args['amount'] ?? 0),
            'return_url' => (string) ($args['return_url'] ?? ''),
            'order_id'   => (string) ($args['order_id'] ?? ''),
            'description'=> (string) ($args['description'] ?? ''),
            'payer'      => [
                'name'   => (string) ($args['name'] ?? ''),
                'mobile' => (string) ($args['mobile'] ?? ''),
                'email'  => (string) ($args['email'] ?? ''),
            ],
        ];

        $headers = [
            'ApiKey'       => (string) ($args['merchant_id'] ?? ''),
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
        ];

        $request = [
            'body'        => wp_json_encode($body),
            'timeout'     => 30,
            'redirection' => 5,
            'httpversion' => '1.0',
            'blocking'    => true,
            'headers'     => $headers,
            'cookies'     => [],
        ];

        $url      = $is_sandbox ? static::$sandbox_request_url : static::$request_url;
        $response = wp_remote_post($url, $request);
        $status_code = (int) wp_remote_retrieve_response_code($response);

        if (is_wp_error($response)) {
            return $this->error(
                $response->get_error_message(),
                [
                    'url'      => $url,
                    'body'     => $body,
                    'response' => (array) $response->get_error_message(),
                    'header'   => $headers,
                ],
                $status_code
            );
        }

        $response_body = wp_remote_retrieve_body($response);
        $json          = json_decode($response_body, true);

        if (!is_array($json)) {
            return $this->error(
                __('Invalid gateway settings input.', 'parsigate'),
                [
                    'url'      => $url,
                    'body'     => $body,
                    'response' => $response_body,
                    'header'   => $headers,
                ],
                $status_code
            );
        }

        // Success: status === ACCEPTED and link + payment_id exist
        if (
            isset($json['status'])
            && $json['status'] === 'ACCEPTED'
            && !empty($json['link'])
            && !empty($json['payment_id'])
        ) {
            return $this->success(
                [
                    'authority' => (string) $json['payment_id'],
                    'redirect'  => (string) $json['link'],
                ],
                [
                    'url'      => $url,
                    'body'     => $body,
                    'response' => $json,
                    'header'   => $headers,
                ],
                $status_code
            );
        }

        $error_message = $json['message'] ?? __('Failed Payment', 'parsigate');
        if (!empty($json['error_code'])) {
            $error_message = __('Error Code: ', 'parsigate') . $json['error_code'] . ' - ' . $error_message;
        }

        return $this->error(
            $error_message,
            [
                'url'      => $url,
                'body'     => $body,
                'response' => $json,
                'header'   => $headers,
            ],
            $status_code
        );
    }

    /**
     * Verify payment after callback.
     *
     * @param array $args {
     *     @type bool   $sandbox
     *     @type string $merchant_id
     *     @type int    $amount
     *     @type string $status          Callback status (100 = success)
     *     @type string $receipt_number  From callback
     * }
     * @return array
     */
    public function verify(array $args = []): array
    {
        $is_sandbox = isset($args['sandbox']) && $args['sandbox'] === true;

        $callback_status = isset($args['status']) ? (string) $args['status'] : '';
        $receipt_number  = isset($args['receipt_number']) ? (string) $args['receipt_number'] : '';

        // User cancelled or failed payment
        if ($callback_status !== '100' || empty($receipt_number)) {
            $message = $this->translate_callback_status($callback_status);
            return $this->error(
                $message,
                [
                    'url'      => '',
                    'body'     => $args,
                    'response' => [],
                    'header'   => [],
                ],
                0
            );
        }

        $body = [
            'amount'         => (int) ($args['amount'] ?? 0),
            'receipt_number' => $receipt_number,
        ];

        $headers = [
            'ApiKey'       => (string) ($args['merchant_id'] ?? ''),
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
        ];

        $request = [
            'body'        => wp_json_encode($body),
            'timeout'     => 30,
            'redirection' => 5,
            'httpversion' => '1.0',
            'blocking'    => true,
            'headers'     => $headers,
            'cookies'     => [],
        ];

        $url      = $is_sandbox ? static::$sandbox_verify_url : static::$verify_url;
        $response = wp_remote_post($url, $request);
        $status_code = (int) wp_remote_retrieve_response_code($response);

        if (is_wp_error($response)) {
            return $this->error(
                $response->get_error_message(),
                [
                    'url'      => $url,
                    'body'     => $body,
                    'response' => (array) $response->get_error_message(),
                    'header'   => $headers,
                ],
                $status_code
            );
        }

        $response_body = wp_remote_retrieve_body($response);
        $json          = json_decode($response_body, true);

        if (!is_array($json)) {
            return $this->error(
                __('Invalid gateway settings input.', 'parsigate'),
                [
                    'url'      => $url,
                    'body'     => $body,
                    'response' => $response_body,
                    'header'   => $headers,
                ],
                $status_code
            );
        }

        // Success: status === SUCCESSFUL
        if (isset($json['status']) && $json['status'] === 'SUCCESSFUL') {
            return $this->success(
                [
                    'transaction_id' => $receipt_number,
                    'paid_amount'    => $json['paid_amount'] ?? null,
                    'id'             => $json['id'] ?? null,
                ],
                [
                    'url'      => $url,
                    'body'     => $body,
                    'response' => $json,
                    'header'   => $headers,
                ],
                $status_code
            );
        }

        $error_message = $json['message'] ?? __('Failed Payment', 'parsigate');
        if (!empty($json['error_code'])) {
            $error_message = __('Error Code: ', 'parsigate') . $json['error_code'] . ' - ' . $error_message;
        }

        return $this->error(
            $error_message,
            [
                'url'      => $url,
                'body'     => $body,
                'response' => $json,
                'header'   => $headers,
            ],
            $status_code
        );
    }

    /**
     * Translate callback status codes to human-readable messages.
     *
     * @param string $status
     * @return string
     */
    private function translate_callback_status(string $status): string
    {
        $messages = [
            '99' => __('User cancelled the payment.', 'parsigate'),
            '88' => __('Failed Payment', 'parsigate'),
            '77' => __('Payment cancelled by user.', 'parsigate'),
        ];

        return $messages[$status] ?? __('Unknown error occurred. If the amount was deducted, it will be refunded within 72 hours.', 'parsigate');
    }
}
