<?php

namespace ParsiGate\gateways;

class Saderat extends Base
{

    public function pay(array $args = []): array
    {

        // Setup Body
        $body = [
            'Amount' => $args['Amount'],
            'callbackURL' => $args['callbackURL'],
            'InvoiceID' => $args['InvoiceID'],
            'TerminalID' => $args['TerminalID'],
            'Payload' => $args['Payload'],
        ];

        // Setup Headers
        $headers = [
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

        $url = "https://sepehr.shaparak.ir:8081/V1/PeymentApi/GetToken";
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
        if (isset($json['Status']) and $json['Status'] == "0" and !empty($json['Accesstoken'])) {

            return $this->success(
                [
                    'authority' => $json['Accesstoken'],
                    'redirect' => [
                        'with_post' => true,
                        'url' => 'https://sepehr.shaparak.ir:8080/Pay',
                        'inputs' => [
                            'TerminalID' => $args['TerminalID'],
                            'getMethod' => '1',
                            'token' => $json['Accesstoken']
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
        $error_messages = $this->get_error_message($json['Status']);

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
        // Check respCode
        if ($args['respcode'] != '0') {
            return $this->error(
                $this->get_verify_error_message($args['respcode'])
            );
        }

        // Setup Body
        $body = [
            'digitalreceipt' => $args['digitalreceipt'],
            'Tid' => $args['Tid']
        ];

        // Setup Headers
        $headers = [
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

        $url = "https://sepehr.shaparak.ir:8081/V1/PeymentApi/Advice";
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

        if (isset($json['Status']) and strtoupper($json['Status']) == "OK" and isset($json['ReturnId']) and floatval($json['ReturnId']) == floatval($args['amount'])) {

            return $this->success(
                [
                    'transaction_id' => (!empty($args['rrn']) ? trim($args['rrn']) : $args['digitalreceipt']),
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

        // Error
        return $this->error(
            [],
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
            -1 => 'تراکنش پیدا نشد.',
            -2 => 'عدم تطابق ip و یا بسته بودن port 8081',
            -3 => 'خطای Total Error رخ داده است.',
            -4 => 'امکان انجام درخواست برای این تراکنش وجود ندارد.',
            -5 => 'آدرس ip نامعتبر می‌باشد.',
            -6 => 'عدم فعال بودن سرویس برگشت تراکنش برای پذیرنده',
        ];

        return __('Error Code: ', 'parsigate') . $resCode . ($messages[$resCode] ? ' (' . $messages[$resCode] . ')' : '');
    }

    public function get_verify_error_message($resCode): string
    {
        $messages = [
            -1 => 'تراکنش پیدا نشد',
            -2 => 'در زمان دریافت توکن به دلیل عدم وجود (عدم تطابق) IP' .
                ' و یا به دلیل بسته بودن خروجی پورت 8081 از سمت Host این خطا ایجاد میگردد.' .
                ' تراکنش قبلا Reverse شده است.',
            -3 => 'Total Error خطای عمومی – خطای Exception ها',
            -4 => 'امکان انجام درخواست برای این تراکنش وجود ندارد',
            -5 => 'آدرس IP نامعتبر میباشد (IP در لیست آدرسهای معرفی شده توسط پذیرنده موجود نمیباشد)',
            -6 => 'عدم فعال بودن سرویس برگشت تراکنش برای پذیرنده'
        ];

        return __('Error Code: ', 'parsigate') . $resCode . ($messages[$resCode] ? ' (' . $messages[$resCode] . ')' : '');
    }

}