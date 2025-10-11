<?php

namespace ParsiGate\gateways;

class Sep extends Base
{

    public function pay(array $args = []): array
    {
        // Setup Body
        $body = [
            "action" => "token",
            "TerminalId" => $args['TerminalId'],
            "Amount" => $args['Amount'],
            "ResNum" => $args['ResNum'],
            "RedirectUrl" => $args['RedirectUrl'],
            "CellNumber" => $args['CellNumber'],
            "ResNum1" => ($args['ResNum1'] ?? ''),
            "ResNum2" => ($args['ResNum2'] ?? ''),
            "ResNum3" => ($args['ResNum3'] ?? ''),
            "ResNum4" => ($args['ResNum4'] ?? '')
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

        $url = 'https://sep.shaparak.ir/OnlinePG/OnlinePG';
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
        if (isset($json['status']) and $json['status'] == "1" and !empty($json['token'])) {

            return $this->success(
                [
                    'authority' => $json['token'],
                    'redirect' => [
                        'with_post' => true,
                        'url' => 'https://sep.shaparak.ir/OnlinePG/OnlinePG',
                        'inputs' => [
                            'Token' => $json['token'],
                            'GetMethod' => ''
                        ]
                    ]
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
        $error_messages = (!empty($json['errorCode']) ? __('Error Code: ', 'parsigate') . $json['errorCode'] : '');

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
        // Check State
        if (strtoupper($args['State']) != 'OK' || empty($args['ResNum']) || empty($args['RefNum'])) {
            return $this->error();
        }

        // Setup Body
        $body = [
            "TerminalNumber" => $args['TerminalNumber'],
            "RefNum" => $args['RefNum'],
            "CellNumber" => "",
            "NationalCode" => "",
            "IgnoreNationalcode" => true,
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

        $url = "https://sep.shaparak.ir/verifyTxnRandomSessionkey/ipg/VerifyTransaction";
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

        if (isset($json['ResultCode']) and $json['ResultCode'] == "0" and !empty($json['TransactionDetail']['RRN'])) {

            return $this->success(
                [
                    'transaction_id' => $json['TransactionDetail']['RRN']
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
        $error_messages = (!empty($json['ResultCode']) ? __('Error Code: ', 'parsigate') . $json['ResultCode'] : '');

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