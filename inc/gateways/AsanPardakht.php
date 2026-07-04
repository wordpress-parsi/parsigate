<?php

namespace ParsiGate\gateways;

use ParsiGate\Option;
use WPParsidate\Addons\ParsiGateOption\ParsiGateOption;

class AsanPardakht extends Base
{

    public function pay(array $args = []): array
    {

        // Setup Body
        $body = [
            'serviceTypeId' => $args['serviceTypeId'],
            'merchantConfigurationId' => $args['merchantConfigurationId'],
            'localInvoiceId' => $args['localInvoiceId'],
            'amountInRials' => $args['amountInRials'],
            'localDate' => $args['localDate'],
            'callbackURL' => $args['callbackURL'],
            'paymentId' => $args['paymentId'],
            'additionalData' => $args['additionalData'],
        ];

        // Setup Headers
        $headers = [
            'Content-Type' => 'application/json',
            'Usr' => $args['username'],
            'Pwd' => $args['password'],
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

        $url = "https://ipgrest.asanpardakht.ir/v1/Token";
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
        $json = (json_decode($response_body, true) != null ? json_decode($response_body, true) : (array)$response_body);

        // Success
        if ($status_code == "200" and !empty($response_body)) {

            return $this->success(
                [
                    'authority' => $response_body,
                    'redirect' => [
                        'with_post' => true,
                        'url' => 'https://asan.shaparak.ir',
                        'inputs' => [
                            'RefId' => $response_body
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
        $error_messages = ($json['error']['message'] ?? $this->get_error_message($status_code));

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
        // Setup Headers
        $headers = [
            'Content-Type' => 'application/json',
            'Usr' => $args['username'],
            'Pwd' => $args['password'],
        ];

        // Setup Body
        $body = [];

        /**
         * Step 1) Transaction Result
         */

        // Setup Request args
        $request = [
            'body' => [],
            'timeout' => 30,
            'redirection' => '5',
            'httpsversion' => '1.0',
            'blocking' => true,
            'headers' => $headers,
            'cookies' => array()
        ];
        $url = 'https://ipgrest.asanpardakht.ir/v1/TranResult?merchantConfigurationId=' . $args['merchantConfigurationId'] . '&localInvoiceId=' . $args['localInvoiceId'];
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

        // Error
        if ($status_code != "200") {

            $error_messages = $this->get_error_message($status_code);
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

        // Save Log
        if (ParsiGateOption::enable_log()) {

            \ParsiGate\CustomTable\Log::insert([
                'gateway' => 'asanpardakht',
                'url' => $url,
                'type' => 1,
                'code' => $status_code,
                'header' => $headers,
                'body' => $body,
                'response' => $json,
                'created_at' => current_time('mysql')
            ]);
        }

        // Get Transaction Id
        $transaction_id = $json['payGateTranID'];

        /**
         * Step 2) Verify
         */

        // Setup Body
        $body = [
            'merchantConfigurationId' => (int)$args['merchantConfigurationId'],
            'payGateTranId' => (int)$transaction_id
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

        $url = "https://ipgrest.asanpardakht.ir/v1/Verify";
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
        $json = (array)$response_body;

        // Error
        if ($status_code != "200") {

            $error_messages = $this->get_error_message($status_code);
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

        // Save Log
        if (ParsiGateOption::enable_log()) {

            \ParsiGate\CustomTable\Log::insert([
                'gateway' => 'asanpardakht',
                'url' => $url,
                'type' => 1,
                'code' => $status_code,
                'header' => $headers,
                'body' => $body,
                'response' => $json,
                'created_at' => current_time('mysql')
            ]);
        }

        /**
         * Step 3) Settlement
         */

        $url = "https://ipgrest.asanpardakht.ir/v1/Settlement";
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
        $json = (array)$response_body;

        if ($status_code == "200") {

            return $this->success(
                [
                    'transaction_id' => $transaction_id
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
        $error_messages = $this->get_error_message($status_code);

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

    public function get_error_message($resCode): string
    {
        $messages = [
            400 => "bad request",
            401 => "unauthorized. probably wrong or unsent header(s)",
            471 => "identity not trusted to proceed",
            472 => "no records found",
            473 => "invalid merchant username or password",
            474 => "invalid incoming request machine ip. check response body to see your actual public IP address",
            475 => "invoice identifier is not a number",
            476 => "request amount is not a number",
            477 => "request local date length is invalid",
            478 => "request local date is not in valid format",
            479 => "invalid service type id",
            480 => "invalid payer id",
            481 => "incorrect settlement description format",
            482 => "settlement slices does not match total amount",
            483 => "unregistered iban",
            484 => "internal error for other reasons",
            485 => "invalid local date",
            486 => "amount not in range",
            487 => "service not found or not available for merchant",
            488 => "invalid default callback",
            489 => "duplicate local invoice id",
            490 => "merchant disabled or misconfigured",
            491 => "too many settlement destinations",
            492 => "unprocessable request",
            493 => "error processing special request for other reasons like business restrictions",
            494 => "invalid payment_id for governmental payment",
            495 => "invalid referenceId in additionalData",
            496 => "invalid json in additionalData",
            497 => "invalid payment_id location",
            571 => "misconfiguration OR not yet processed",
            572 => "misconfiguration OR transaction status undetermined",
            573 => "misconfiguraed valid ips for configuration OR unable to request for verification due to an internal error",
            574 => "internal error in uthorization",
            575 => "no valid ibans found for merchant",
            576 => "internal error",
            577 => "internal error",
            578 => "no default sharing is defined for merchant",
            579 => "cant submit ibans with default sharing endpoint",
            580 => "error processing special request"
        ];

        return __('Error Code: ', 'parsigate') . $resCode . ($messages[$resCode] ? ' (' . $messages[$resCode] . ')' : '');
    }
}