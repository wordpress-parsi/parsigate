<?php

namespace ParsiGate\gateways;

class Melli extends Base
{

    public function pay(array $args = []): array
    {

        // Prepare Params
        $OrderId = $args['OrderId'];
        $TerminalId = $args['TerminalId'];
        $key = $args['Key'];
        $Amount = $args['Amount'];
        $SignData = $this->encrypt_pkcs7("$TerminalId;$OrderId;$Amount", "$key");

        // Setup Body
        $body = [
            'TerminalId' => $TerminalId,
            'MerchantId' => $args['MerchantId'],
            'Amount' => $Amount,
            'SignData' => $SignData,
            'ReturnUrl' => $args['ReturnUrl'],
            'LocalDateTime' => ($args['LocalDateTime'] ?? gmdate("m/d/Y g:i:s a")),
            'OrderId' => $OrderId
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

        $url = 'https://sadad.shaparak.ir/vpg/api/v0/Request/PaymentRequest';
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
        if (isset($json['ResCode']) and $json['ResCode'] == 0 and !empty($json['Token'])) {

            return $this->success(
                [
                    'authority' => $json['Token'],
                    'redirect' => "https://sadad.shaparak.ir/VPG/Purchase?Token=" . $json['Token']
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
        $error_messages = (!empty($json['Description']) ? $json['Description'] : '');

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
        // Check ResCode
        if ($args['ResCode'] != '0') {
            return $this->error();
        }

        // Setup Body
        $body = [
            'Token' => $args['Token'],
            'SignData' => $this->encrypt_pkcs7($args['Token'], $args['Key'])
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

        $url = 'https://sadad.shaparak.ir/vpg/api/v0/Advice/Verify';
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

        if (array_key_exists('ResCode', $json) and $json['ResCode'] == 0 and !empty($json['SystemTraceNo'])) {

            return $this->success(
                [
                    'transaction_id' => $json['SystemTraceNo']
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
        $error_messages = (!empty($json['Description']) ? $json['Description'] : '');

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

    private function encrypt_pkcs7($str, $key): string
    {
        $key = base64_decode($key);
        $ciphertext = OpenSSL_encrypt($str, "DES-EDE3", $key, OPENSSL_RAW_DATA);
        return base64_encode($ciphertext);
    }
}