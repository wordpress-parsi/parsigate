<?php

namespace ParsiGate\gateways;

class IranKish extends Base
{

    public function pay(array $args = []): array
    {

        // Setup Body
        $body = [
            'request' => [
                "acceptorId" => $args['acceptorId'],
                "amount" => $args['amount'],
                "billInfo" => $args['billInfo'],
                "requestId" => $args['requestId'],
                "paymentId" => $args['paymentId'],
                "requestTimestamp" => $args['requestTimestamp'],
                "revertUri" => $args['revertUri'],
                "terminalId" => $args['terminalId'],
                "transactionType" => $args['transactionType'],
                "authenticationEnvelope" => $this->key($args['pub_key'], $args['terminalId'], $args['password'], $args['amount'])
            ]
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

        $url = "https://ikc.shaparak.ir/api/v3/tokenization/make";
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
        if (isset($json['responseCode']) and in_array($json['responseCode'], ["0", "00"]) and !empty($json['result']['token'])) {

            return $this->success(
                [
                    'authority' => $json['result']['token'],
                    'redirect' => [
                        'with_post' => true,
                        'url' => 'https://ikc.shaparak.ir/iuiv3/IPG/Index/',
                        'inputs' => [
                            'tokenIdentity' => $json['result']['token']
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
        $error_messages = $this->get_error_message($json['responseCode']) . (!empty($json["description"]) ? ' (' . $json["description"] . ')' : '');

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
        // Check responseCode
        if ($args['responseCode'] != "00") {
            return $this->error(
                $this->get_error_message($args['responseCode'])
            );
        }

        // Setup Body
        $body = [
            "terminalId" => $args['terminalId'],
            "retrievalReferenceNumber" => $args['retrievalReferenceNumber'],
            "systemTraceAuditNumber" => $args['systemTraceAuditNumber'],
            "tokenIdentity" => $args['tokenIdentity'],
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

        $url = "https://ikc.shaparak.ir/api/v3/confirmation/purchase";
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

        if (isset($json['responseCode']) and in_array($json['responseCode'], ["0", "00"])) {

            return $this->success(
                [
                    'transaction_id' => $args['retrievalReferenceNumber']
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
        $error_messages = $this->get_error_message($json['responseCode']) . (!empty($json["description"]) ? ' (' . $json["description"] . ')' : '');

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

    public function key($pub_key, $terminalID, $password, $amount): array
    {
        $data = $terminalID . $password . str_pad($amount, 12, '0', STR_PAD_LEFT) . '00';
        $data = hex2bin($data);
        $AESSecretKey = openssl_random_pseudo_bytes(16);
        $ivlen = openssl_cipher_iv_length($cipher = "AES-128-CBC");
        $iv = openssl_random_pseudo_bytes($ivlen);
        $ciphertext_raw = openssl_encrypt($data, $cipher, $AESSecretKey, $options = OPENSSL_RAW_DATA, $iv);
        $hmac = hash('sha256', $ciphertext_raw, true);
        $crypttext = '';

        openssl_public_encrypt($AESSecretKey . $hmac, $crypttext, $pub_key);

        return array(
            "data" => bin2hex($crypttext),
            "iv" => bin2hex($iv),
        );
    }

    public function get_error_message($resCode): string
    {
        $messages = [
            5 => 'از انجام تراکنش صرف نظر شد',
            17 => 'از انجام تراکنش صرف نظر شد',
            3 => 'پذیرنده فروشگاهی نامعتبر است',
            64 => 'مبلغ تراکنش نادرست است، جمع مبالغ تقسیم وجوه برابر مبلغ کل تراکنش نمی باشد',
            94 => 'تراکنش تکراری است',
            25 => 'تراکنش اصلی یافت نشد',
            77 => 'روز مالی تراکنش نا معتبر است',
            63 => 'کد اعتبار سنجی پیام نا معتبر است',
            97 => 'کد تولید کد اعتبار سنجی نا معتبر است',
            30 => 'فرمت پیام نادرست است',
            86 => 'شتاب در حال  Off Sign است',
            55 => 'رمز کارت نادرست است',
            40 => 'عمل درخواستی پشتیبانی نمی شود',
            57 => 'انجام تراکنش مورد درخواست توسط پایانه انجام دهنده مجاز نمی باشد',
            58 => 'انجام تراکنش مورد درخواست توسط پایانه انجام دهنده مجاز نمی باشد',
            96 => 'قوانین سامانه نقض گردیده است ، خطای داخلی سامانه',
            2 => 'تراکنش قبال برگشت شده است',
            54 => 'تاریخ انقضا کارت سررسید شده است',
            62 => 'کارت محدود شده است',
            75 => 'تعداد دفعات ورود رمز اشتباه از حد مجاز فراتر رفته است',
            14 => 'اطالعات کارت صحیح نمی باشد',
            51 => 'موجودی حساب کافی نمی باشد',
            56 => 'اطالعات کارت یافت نشد',
            61 => 'مبلغ تراکنش بیش از حد مجاز است',
            65 => 'تعداد دفعات انجام تراکنش بیش از حد مجاز است',
            78 => 'کارت فعال نیست',
            79 => 'حساب متصل به کارت بسته یا دارای اشکال است',
            42 => 'کارت یا حساب مبدا در وضعیت پذیرش نمی باشد',
            31 => 'عدم تطابق کد ملی خریدار با دارنده کارت',
            98 => 'سقف استفاده از رمز دوم ایستا به پایان رسیده است',
            901 => 'درخواست نا معتبر است )Tokenization(',
            902 => 'پارامترهای اضافی درخواست نامعتبر می باشد )Tokenization(',
            903 => 'شناسه پرداخت نامعتبر می باشد )Tokenization(',
            904 => 'اطالعات مرتبط با قبض نا معتبر می باشد )Tokenization(',
            905 => 'شناسه درخواست نامعتبر می باشد )Tokenization(',
            906 => 'درخواست تاریخ گذشته است )Tokenization(',
            907 => 'آدرس بازگشت نتیجه پرداخت نامعتبر می باشد )Tokenization(',
            909 => 'پذیرنده نامعتبر می باشد)Tokenization(',
            910 => 'پارامترهای مورد انتظار پرداخت تسهیمی تامین نگردیده است)Tokenization(',
            911 => 'پارامترهای مورد انتظار پرداخت تسهیمی نا معتبر یا دارای اشکال می باشد)Tokenization(',
            912 => 'تراکنش درخواستی برای پذیرنده فعال نیست )Tokenization(',
            913 => 'تراکنش تسهیم برای پذیرنده فعال نیست )Tokenization(',
            914 => 'آدرس آی پی دریافتی درخواست نا معتبر می باشد',
            915 => 'شماره پایانه نامعتبر می باشد )Tokenization(',
            916 => 'شماره پذیرنده نا معتبر می باشد )Tokenization(',
            917 => 'نوع تراکنش اعالم شده در خواست نا معتبر می باشد )Tokenization(',
            918 => 'پذیرنده فعال نیست)Tokenization(',
            919 => 'مبالغ تسهیمی ارائه شده با توجه به قوانین حاکم بر وضعیت تسهیم پذیرنده ، نا معتبر است )Tokenization(',
            920 => 'شناسه نشانه نامعتبر می باشد',
            921 => 'شناسه نشانه نامعتبر و یا منقضی شده است',
            922 => 'نقض امنیت درخواست )Tokenization(',
            923 => 'ارسال شناسه پرداخت در تراکنش قبض مجاز نیست)Tokenization(',
            928 => 'مبلغ مبادله شده نا معتبر می باشد)Tokenization(',
            929 => 'شناسه پرداخت ارائه شده با توجه به الگوریتم متناظر نا معتبر می باشد)Tokenization(',
            930 => 'کد ملی ارائه شده نا معتبر می باشد)Tokenization('
        ];

        return __('Error Code: ', 'parsigate') . $resCode . ($messages[$resCode] ? ' (' . $messages[$resCode] . ')' : '');
    }
}