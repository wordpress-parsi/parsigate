<?php

namespace ParsiGate\Gateways;

/**
 * Document: https://next.zarinpal.com/paymentGateway/sandbox.html
 */
class ZarinPal extends Base
{

    public function pay(array $args = []): array
    {
        // Check SandBox
        $isSandBox = isset($args['sandbox']) && $args['sandbox'] === true;

        // Setup Data
        $body = [
            "merchant_id" => ($args['merchant_id'] ?? ''),
            "amount" => ($args['amount'] ?? 0), # Price is Rial By ZarinPal document
            "callback_url" => ($args['callback_url'] ?? ''),
            "description" => ($args['description'] ?? ''),
            "metadata" => ($args['metadata'] ?? []), # ['mobile' => '', 'email' => '']
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

        $url = 'https://api.zarinpal.com/pg/v4/payment/request.json';
        if ($isSandBox) {
            $url = 'https://sandbox.zarinpal.com/pg/v4/payment/request.json';
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
        if ($status_code == "200" and !empty($json['data']['authority'])) {

            $redirect = ($isSandBox === true ? 'https://sandbox.zarinpal.com/pg/StartPay/%s/' : 'https://www.zarinpal.com/pg/StartPay/%s/ZarinGate');
            return $this->success(
                [
                    'authority' => $json['data']['authority'],
                    'redirect' => sprintf($redirect, $json['data']['authority'])
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
        $error_messages = !empty($json['errors']['message']) ? $json['errors']['message'] : $json['data']['message'];

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
        // Check SandBox
        $isSandBox = isset($args['sandbox']) && $args['sandbox'] === true;

        // Get Params
        $Authority = $args['Authority'];
        $Status = $args['Status'];

        // Check Status
        if ($Status == "NOK") {
            return $this->error();
        }

        // Setup Data
        $body = [
            'merchant_id' => $args['merchant_id'],
            'authority' => $Authority,
            'amount' => $args['amount']
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

        $url = 'https://api.zarinpal.com/pg/v4/payment/verify.json';
        if ($isSandBox) {
            $url = 'https://sandbox.zarinpal.com/pg/v4/payment/verify.json';
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

        // Get Data
        $Status = ($json['data']['code'] ?? '');
        $RefID = ($json['data']['ref_id'] ?? '');
        if ($Status == 101 || ($Status == 100 and !empty($RefID))) {

            return $this->success(
                [
                    'transaction_id' => $RefID
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
        $error_messages = !empty($json['errors']['message']) ? $json['errors']['message'] : $json['data']['message'];

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