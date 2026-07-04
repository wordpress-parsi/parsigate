<?php

namespace ParsiGate\gateways;

use ParsiGate\Utility;
use WPParsidate\Addons\ParsiGateOption\ParsiGateOption;

class Mellat extends Base
{

    public static int $type = 2;

    public function pay(array $args = []): array
    {

        // Setup Body
        $body = [
            'terminalId' => $args['terminalId'],
            'userName' => $args['userName'],
            'userPassword' => $args['userPassword'],
            'orderId' => $args['orderId'],
            'amount' => $args['amount'],
            'localDate' => ($args['localDate'] ?? gmdate('Ymd')),
            'localTime' => ($args['localTime'] ?? gmdate('His')),
            'additionalData' => $args['additionalData'],
            'callBackUrl' => $args['callBackUrl'],
            'payerId' => $args['payerId']
        ];

        // Setup Headers
        $headers = [
            'soap_version' => 'SOAP_1_1',
            'cache_wsdl' => WSDL_CACHE_NONE,
            'encoding' => 'UTF-8',
            'trace' => 1
        ];

        // Soap Method
        $soapMethod = ['X-Soap-Method' => 'bpPayRequest'];

        // Setup Request args
        $url = 'https://bpm.shaparak.ir/pgwchannel/services/pgw?wsdl';
        try {

            $client = new \SoapClient($url, $headers);
            $json = $client->bpPayRequest($body);

            $output = explode(',', $json->return);
            $ResCode = $output[0];

            if ($ResCode == '0') {

                return $this->success(
                    [
                        'authority' => $output[1],
                        'redirect' => [
                            'with_post' => true,
                            'url' => 'https://bpm.shaparak.ir/pgwchannel/startpay.mellat',
                            'inputs' => [
                                'RefId' => $output[1]
                            ]
                        ]
                    ],
                    [
                        'url' => $url,
                        'body' => $body,
                        'response' => (array)$json,
                        'header' => array_merge($headers, $soapMethod),
                    ],
                    Utility::soap_status_code($client)
                );
            }

            // Error
            $error_messages = $this->get_error_message($ResCode);
            return $this->error(
                $error_messages,
                [
                    'url' => $url,
                    'body' => $body,
                    'response' => (array)$json,
                    'header' => array_merge($headers, $soapMethod),
                ],
                Utility::soap_status_code($client)
            );

        } catch (\SoapFault $e) {

            return $this->error(
                $e->getMessage(),
                [
                    'url' => $url,
                    'body' => $body,
                    'response' => [
                        'code' => $e->faultcode,
                        'message' => $e->getMessage(),
                    ],
                    'header' => array_merge($headers, $soapMethod),
                ],
                (isset($client) ? Utility::soap_status_code($client) : 400)
            );
        }
    }

    public function verify(array $args = []): array
    {
        // Check ResCode
        if ($args['ResCode'] != '0') {

            return $this->error(
                $this->get_error_message($args['ResCode'])
            );
        }

        // Setup Body
        $body = [
            'terminalId' => $args['terminalId'],
            'userName' => $args['userName'],
            'userPassword' => $args['userPassword'],
            'orderId' => $args['orderId'],
            'saleOrderId' => $args['saleOrderId'],
            'saleReferenceId' => $args['saleReferenceId']
        ];

        // Step 1 | VerifyRequest
        $VerifyRequest = $this->call($body, 'bpVerifyRequest');

        // Save Log
        if (ParsiGateOption::enable_log() and $VerifyRequest['status'] === true) {

            \ParsiGate\CustomTable\Log::insert([
                'gateway' => 'mellat',
                'url' => $VerifyRequest['request']['url'],
                'type' => 2,
                'code' => $VerifyRequest['request']['code'],
                'header' => $VerifyRequest['request']['header'],
                'body' => $VerifyRequest['request']['body'],
                'response' => $VerifyRequest['request']['response'],
                'created_at' => current_time('mysql')
            ]);
        }

        // Check Handle Error
        if ($VerifyRequest['status'] === false) {

            return $this->error(
                $VerifyRequest['message'],
                [
                    'url' => $VerifyRequest['request']['url'],
                    'body' => $VerifyRequest['request']['body'],
                    'response' => $VerifyRequest['request']['response'],
                    'header' => $VerifyRequest['request']['header'],
                ],
                $VerifyRequest['request']['code']
            );
        }

        // Step 2 | SettleRequest
        $SettleRequest = $this->call($body, 'bpSettleRequest');;

        // Success
        if ($SettleRequest['status'] === true) {

            return $this->success(
                [
                    'transaction_id' => $args['saleReferenceId']
                ],
                [
                    'url' => $SettleRequest['request']['url'],
                    'body' => $SettleRequest['request']['body'],
                    'response' => $SettleRequest['request']['response'],
                    'header' => $SettleRequest['request']['header'],
                ],
                $SettleRequest['request']['code']
            );
        }

        // Error
        return $this->error(
            $SettleRequest['message'],
            [
                'url' => $SettleRequest['request']['url'],
                'body' => $SettleRequest['request']['body'],
                'response' => $SettleRequest['request']['response'],
                'header' => $SettleRequest['request']['header'],
            ],
            $SettleRequest['request']['code']
        );
    }

    public function call(array $body = [], $method = 'bpVerifyRequest'): array
    {
        // Setup Headers
        $headers = [
            'soap_version' => 'SOAP_1_1',
            'cache_wsdl' => WSDL_CACHE_NONE,
            'encoding' => 'UTF-8',
            'trace' => 1
        ];

        // Soap Method
        $soapMethod = ['X-Soap-Method' => $method];

        // Setup Request args
        $url = 'https://bpm.shaparak.ir/pgwchannel/services/pgw?wsdl';
        try {

            $client = new \SoapClient($url, $headers);
            $json = $client->$method($body);

            if ($json->return == '0') {

                return [
                    'status' => true,
                    'request' => [
                        'code' => Utility::soap_status_code($client),
                        'url' => $url,
                        'body' => $body,
                        'response' => (array)$json,
                        'header' => array_merge($headers, $soapMethod),
                    ]
                ];
            }

            // Error
            $error_messages = $this->get_error_message($json);
            return [
                'status' => false,
                'message' => $error_messages,
                'request' => [
                    'code' => Utility::soap_status_code($client),
                    'url' => $url,
                    'body' => $body,
                    'response' => (array)$json,
                    'header' => array_merge($headers, $soapMethod),
                ]
            ];

        } catch (\SoapFault $e) {

            return [
                'status' => false,
                'message' => $e->getMessage(),
                'request' => [
                    'code' => (isset($client) ? Utility::soap_status_code($client) : 400),
                    'url' => $url,
                    'body' => $body,
                    'response' => [
                        'code' => $e->faultcode,
                        'message' => $e->getMessage(),
                    ],
                    'header' => array_merge($headers, $soapMethod),
                ]
            ];
        }
    }

    public function get_error_message($resCode): string
    {
        $messages = [
            '11' => 'شماره کارت نامعتبر است',
            '12' => 'موجودی کافی نیست',
            '13' => 'رمز نادرست است',
            '14' => 'تعداد دفعات وارد کردن رمز بیش از حد مجاز است',
            '15' => 'کارت نامعتبر است',
            '16' => 'دفعات برداشت وجه بیش از حد مجاز است',
            '17' => 'کاربر از انجام تراکنش منصرف شده است',
            '18' => 'تاریخ انقضای کارت گذشته است',
            '19' => 'مبلغ برداشت وجه بیش از حد مجاز است',
            '21' => 'پذیرنده نامعتبر است',
            '23' => 'خطای امنیتی رخ داده است',
            '24' => 'اطلاعات کاربری پذیرنده نامعتبر است',
            '25' => 'مبلغ نامعتبر است',
            '31' => 'پاسخ نامعتبر است',
            '32' => 'فرمت اطلاعات وارد شده صحیح نیست',
            '33' => 'حساب نامعتبر است',
            '34' => 'خطای سیستمی',
            '35' => 'تاریخ نامعتبر است',
            '41' => 'شماره درخواست تکراری است',
            '42' => 'تراکنش Sale یافت نشد',
            '43' => 'قبلا درخواست Verify داده شده است',
            '44' => 'درخواست Verify یافت نشد',
            '45' => 'تراکنش Settle شده است',
            '46' => 'تراکنش Settle نشده است',
            '47' => 'تراکنش Settle یافت نشد',
            '48' => 'تراکنش Reverse شده است',
            '49' => 'تراکنش Refund یافت نشد',
            '51' => 'تراکنش تکراری است',
            '54' => 'تراکنش مرجع موجود نیست',
            '55' => 'تراکنش نامعتبر است',
            '61' => 'خطا در واریز'
        ];

        return __('Error Code: ', 'parsigate') . $resCode . ($messages[$resCode] ? ' (' . $messages[$resCode] . ')' : '');
    }
}