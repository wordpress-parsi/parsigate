<?php

namespace ParsiGate;

use ParsiGate\gateways\AqayePardakht;
use ParsiGate\gateways\AsanPardakht;
use ParsiGate\gateways\Azkivam;
use ParsiGate\gateways\DigiPay;
use ParsiGate\gateways\EghtesadNovin;
use ParsiGate\gateways\IranKish;
use ParsiGate\gateways\Mellat;
use ParsiGate\gateways\Melli;
use ParsiGate\gateways\Parsian;
use ParsiGate\gateways\Pasargad;
use ParsiGate\gateways\PayPing;
use ParsiGate\gateways\Saderat;
use ParsiGate\gateways\Sep;
use ParsiGate\gateways\Sepah;
use ParsiGate\gateways\Shepa;
use ParsiGate\gateways\SnappPay;
use ParsiGate\gateways\Tara;
use ParsiGate\gateways\Test;
use ParsiGate\gateways\Torob;
use ParsiGate\Gateways\ZarinPal;
use ParsiGate\gateways\Zibal;

class Gateways
{
    public function __construct()
    {
    }

    public static function types()
    {
        $list = [
            'bank' => __('Bank Gateway', 'parsigate'),
            'intermediary' => __('Intermediary Gateway', 'parsigate'),
            'installment' => __('Installment Gateway', 'parsigate'),
            'test' => __('Test Gateway', 'parsigate'),
        ];

        return apply_filters('parsigate_gateways_types', $list);
    }

    public static function list()
    {
        $list = [

            // Bank Gateway
            'parsian' => [
                'title' => __('Parsian', 'parsigate'),
                'class' => Parsian::class,
                'website' => 'pec.ir',
                'type' => 'bank',
                'usage' => ['woocommerce'],
                'requirement' => (Utility::is_soap_enabled() === false ? __("Note: To activate the Gateway, the SoapClient module must be enabled in your host's PHP settings.", "parsigate") : ''),
                'woocommerce' => [
                    'sandbox' => false,
                    'settings' => [
                        'merchant_id' => [
                            'title' => __('Merchant ID', 'parsigate'),
                            'type' => 'text',
                            'default' => '',
                            'desc_tip' => false,
                            'class' => 'pg-ltr-input'
                        ]
                    ],
                    'pay' => function ($amount, $order, $option, $callback_url) {

                        return [
                            'LoginAccount' => $option['merchant_id'],
                            'Amount' => $amount,
                            'OrderId' => $order->get_id() . mt_rand(1, 10000),
                            'CallBackUrl' => $callback_url,
                            'AdditionalData' => WooCommerce::get_order_description($order, 'parsian')
                        ];
                    },
                    'verify' => function ($amount, $order, $option) {

                        $token = ($_REQUEST['Token'] ?? '');
                        $status = ($_REQUEST['status'] ?? '');
                        $OrderId = ($_REQUEST['OrderId'] ?? '');

                        return [
                            'LoginAccount' => $option['merchant_id'],
                            'Token' => $token
                        ];
                    }
                ]
            ],
            'pasargad' => [
                'title' => __('Pasargad', 'parsigate'),
                'class' => Pasargad::class,
                'website' => 'pep.co.ir',
                'type' => 'bank',
                'usage' => ['woocommerce']
            ],
            'saman' => [
                'title' => __('Saman (Sep)', 'parsigate'),
                'class' => Sep::class,
                'website' => 'sep.ir',
                'type' => 'bank',
                'usage' => ['woocommerce']
            ],
            'mellat' => [
                'title' => __('Mellat (BehPardakht)', 'parsigate'),
                'class' => Mellat::class,
                'website' => 'behpardakht.com',
                'type' => 'bank',
                'usage' => ['woocommerce']
            ],
            'melli' => [
                'title' => __('Melli (Sadad)', 'parsigate'),
                'class' => Melli::class,
                'website' => 'sadadpsp.ir',
                'type' => 'bank',
                'usage' => ['woocommerce'],
                'requirement' => (Utility::is_enable_open_ssl() === false ? __("Note: To activate the Gateway, the OpenSSL module must be enabled in your host's PHP settings.", "parsigate") : ''),
                'woocommerce' => [
                    'sandbox' => false,
                    'settings' => [
                        'terminal_id' => [
                            'title' => __('Terminal No.', 'parsigate'),
                            'type' => 'text',
                            'default' => '',
                            'desc_tip' => false,
                            'class' => 'pg-ltr-input'
                        ],
                        'merchant_id' => [
                            'title' => __('Merchant ID', 'parsigate'),
                            'type' => 'text',
                            'default' => '',
                            'desc_tip' => false,
                            'class' => 'pg-ltr-input'
                        ],
                        'key' => [
                            'title' => __('Gateway Key', 'parsigate'),
                            'type' => 'text',
                            'default' => '',
                            'desc_tip' => false,
                            'class' => 'pg-ltr-input'
                        ]
                    ],
                    'pay' => function ($amount, $order, $option, $callback_url) {

                        return [
                            'TerminalId' => $option['terminal_id'],
                            'MerchantId' => $option['merchant_id'],
                            'Key' => $option['key'],
                            'Amount' => $amount,
                            'ReturnUrl' => $callback_url,
                            'LocalDateTime' => date("m/d/Y g:i:s a"),
                            'OrderId' => $order->get_id()
                        ];
                    },
                    'verify' => function ($amount, $order, $option) {

                        $OrderId = ($_POST["OrderId"] ?? '');
                        $Token = ($_POST["token"] ?? '');
                        $ResCode = ($_POST["ResCode"] ?? '');

                        return [
                            'ResCode' => $ResCode,
                            'Token' => $Token,
                            'Key' => $option['key'],
                        ];
                    }
                ]
            ],
            'asanpardakht' => [
                'title' => __('Asan Pardakht', 'parsigate'),
                'class' => AsanPardakht::class,
                'website' => 'asanpardakht.ir',
                'type' => 'bank',
                'usage' => ['woocommerce']
            ],
            'saderat' => [
                'title' => __('Saderat (Sepehr)', 'parsigate'),
                'class' => Saderat::class,
                'website' => 'sepehrpay.com',
                'type' => 'bank',
                'usage' => ['woocommerce']
            ],
            'eghtesadnovin' => [
                'title' => __('Eghtesad Novin', 'parsigate'),
                'class' => EghtesadNovin::class,
                'website' => 'enbank.ir',
                'type' => 'bank',
                'usage' => ['woocommerce']
            ],
            'irankish' => [
                'title' => __('Iran Kish', 'parsigate'),
                'class' => IranKish::class,
                'website' => 'irankish.com',
                'type' => 'bank',
                'usage' => ['woocommerce']
            ],
            'sepah' => [
                'title' => __('Sepah', 'parsigate'),
                'class' => Sepah::class,
                'website' => 'banksepah.ir',
                'type' => 'bank',
                'usage' => ['woocommerce']
            ],

            // Intermediary Gateway
            'zarinpal' => [
                'title' => __('ZarinPal', 'parsigate'),
                'class' => Zarinpal::class,
                'website' => 'zarinpal.com',
                'type' => 'intermediary',
                'usage' => ['woocommerce'],
                'woocommerce' => [
                    'sandbox' => true,
                    'settings' => [
                        'merchant_id' => [
                            'title' => __('Merchant ID', 'parsigate'),
                            'type' => 'text',
                            'default' => '',
                            'desc_tip' => false,
                            'class' => 'pg-ltr-input'
                        ]
                    ],
                    'pay' => function ($amount, $order, $option, $callback_url) {

                        $metadata = [];
                        if (!empty($order->get_billing_phone())) {
                            $metadata['mobile'] = $order->get_billing_phone();
                        }

                        return [
                            "sandbox" => isset($option['sandbox']) and $option['sandbox'] == 'yes',
                            "merchant_id" => $option['merchant_id'],
                            "amount" => $amount,
                            "callback_url" => $callback_url,
                            "description" => WooCommerce::get_order_description($order, 'zarinpal'),
                            "metadata" => $metadata,
                        ];
                    },
                    'verify' => function ($amount, $order, $option) {

                        $authority = ($_GET['Authority'] ?? '');
                        $status = ($_GET['Status'] ?? '');
                        return [
                            'sandbox' => isset($option['sandbox']) and $option['sandbox'] == 'yes',
                            "merchant_id" => $option['merchant_id'],
                            "amount" => $amount,
                            'Authority' => $authority,
                            'Status' => $status
                        ];
                    }
                ]
            ],
            'zibal' => [
                'title' => __('Zibal', 'parsigate'),
                'class' => Zibal::class,
                'website' => 'zibal.ir',
                'type' => 'intermediary',
                'usage' => ['woocommerce'],
                'woocommerce' => [
                    'sandbox' => true,
                    'settings' => [
                        'merchant_id' => [
                            'title' => __('Merchant ID', 'parsigate'),
                            'type' => 'text',
                            'default' => '',
                            'desc_tip' => false,
                            'class' => 'pg-ltr-input'
                        ]
                    ],
                    'pay' => function ($amount, $order, $option, $callback_url) {

                        return [
                            "sandbox" => isset($option['sandbox']) and $option['sandbox'] == 'yes',
                            'merchant' => $option['merchant_id'],
                            'amount' => $amount,
                            'callbackUrl' => $callback_url,
                            'orderId' => $order->get_id(),
                            'mobile' => trim($order->get_billing_phone()),
                            'email' => $order->get_billing_email(),
                            'description' => WooCommerce::get_order_description($order, 'zibal')
                        ];
                    },
                    'verify' => function ($amount, $order, $option) {

                        $success = ($_GET['success'] ?? '');
                        $trackId = ($_GET['trackId'] ?? '');

                        return [
                            'sandbox' => isset($option['sandbox']) and $option['sandbox'] == 'yes',
                            "merchant" => $option['merchant_id'],
                            'trackId' => $trackId,
                            'success' => $success,
                        ];
                    }
                ]
            ],
            'payping' => [
                'title' => __('PayPing', 'parsigate'),
                'class' => PayPing::class,
                'website' => 'payping.io',
                'type' => 'intermediary',
                'usage' => ['woocommerce'],
                'woocommerce' => [
                    'settings' => [
                        'token' => [
                            'title' => __('Token', 'parsigate'),
                            'type' => 'text',
                            'default' => '',
                            'desc_tip' => false,
                            'class' => 'pg-ltr-input'
                        ]
                    ],
                    'pay' => function ($amount, $order, $option, $callback_url) {

                        $payerName = $order->get_billing_company();
                        if (!empty($order->get_billing_first_name()) || !empty($order->get_billing_last_name())) {
                            $payerName = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
                        }

                        $email = $order->get_billing_email();
                        $mobile = eng_number($order->get_billing_phone());

                        $payerIdentity = '';
                        if (is_email($email)) {
                            $payerIdentity = $email;
                        } elseif (!empty($mobile)) {
                            $payerIdentity = $mobile;
                        }

                        return [
                            'token' => $option['token'],
                            'payerName' => $payerName,
                            'Amount' => $amount,
                            'payerIdentity' => $payerIdentity,
                            'returnUrl' => $callback_url,
                            'Description' => WooCommerce::get_order_description($order, 'payping'),
                            'clientRefId' => (string)$order->get_id()
                        ];
                    },
                    'verify' => function ($amount, $order, $option) {

                        if (!isset($_POST['status']) || !isset($_POST['data'])) {
                            return ['success' => false];
                        }

                        if ($_POST['status'] != '1') {
                            return ['success' => false];
                        }

                        $sanitize = str_ireplace("\\", "", trim($_POST['data']));
                        $data = json_decode($sanitize, true);
                        if (!is_array($data) || empty($data) || !isset($data['paymentRefId']) || !isset($data['paymentCode'])) {
                            return ['success' => false];
                        }

                        return [
                            'token' => $option['token'],
                            'PaymentRefId' => trim((string)$data['paymentRefId']),
                            "paymentCode" => trim((string)$data['paymentCode']),
                            "amount" => $amount
                        ];
                    }
                ]
            ],
            'aqayepardakht' => [
                'title' => __('AqayePardakht', 'parsigate'),
                'class' => AqayePardakht::class,
                'website' => 'aqayepardakht.ir',
                'type' => 'intermediary',
                'usage' => ['woocommerce'],
                'woocommerce' => [
                    'sandbox' => true,
                    'settings' => [
                        'pin' => [
                            'title' => __('Pin', 'parsigate'),
                            'type' => 'text',
                            'default' => '',
                            'desc_tip' => false,
                            'class' => 'pg-ltr-input'
                        ]
                    ],
                    'pay' => function ($amount, $order, $option, $callback_url) {

                        return [
                            "sandbox" => isset($option['sandbox']) and $option['sandbox'] == 'yes',
                            'pin' => $option['pin'],
                            'amount' => $amount,
                            'callback' => $callback_url,
                            'invoice_id' => $order->get_id(),
                            'mobile' => trim($order->get_billing_phone()),
                            'email' => $order->get_billing_email(),
                            'description' => WooCommerce::get_order_description($order, 'aqayepardakht')
                        ];
                    },
                    'verify' => function ($amount, $order, $option) {

                        $status = ($_POST['status'] ?? '');
                        $transid = ($_POST['transid'] ?? '');

                        return [
                            'sandbox' => isset($option['sandbox']) and $option['sandbox'] == 'yes',
                            'pin' => $option['pin'],
                            'status' => $status,
                            'amount' => $amount,
                            'transid' => $transid,
                        ];
                    }
                ]
            ],
            'shepa' => [
                'title' => __('Shepa', 'parsigate'),
                'class' => Shepa::class,
                'website' => 'shepa.com',
                'type' => 'intermediary',
                'usage' => ['woocommerce'],
                'woocommerce' => [
                    'sandbox' => true,
                    'settings' => [
                        'token' => [
                            'title' => __('API Token', 'parsigate'),
                            'type' => 'text',
                            'default' => '',
                            'desc_tip' => false,
                            'class' => 'pg-ltr-input'
                        ]
                    ],
                    'pay' => function ($amount, $order, $option, $callback_url) {

                        return [
                            "sandbox" => isset($option['sandbox']) and $option['sandbox'] == 'yes',
                            'api' => $option['token'],
                            'amount' => $amount,
                            'callback' => $callback_url,
                            'mobile' => trim($order->get_billing_phone()),
                            'email' => $order->get_billing_email(),
                            'description' => WooCommerce::get_order_description($order, 'shepa')
                        ];
                    },
                    'verify' => function ($amount, $order, $option) {

                        $status = ($_GET['status'] ?? '');
                        $token = ($_GET["token"] ?? '');

                        return [
                            'sandbox' => isset($option['sandbox']) and $option['sandbox'] == 'yes',
                            'api' => $option['token'],
                            'amount' => $amount,
                            'token' => $token,
                            'status' => $status
                        ];
                    }
                ]
            ],

            // Installment Gateway
            'snappay' => [
                'title' => __('SnappPay', 'parsigate'),
                'class' => SnappPay::class,
                'website' => 'snapppay.ir',
                'type' => 'installment',
                'usage' => ['woocommerce']
            ],
            'digipay' => [
                'title' => __('DigiPay', 'parsigate'),
                'class' => DigiPay::class,
                'website' => 'mydigipay.com',
                'type' => 'installment',
                'usage' => ['woocommerce']
            ],
            'azkivam' => [
                'title' => __('Azkivam', 'parsigate'),
                'class' => Azkivam::class,
                'website' => 'azkivam.com',
                'type' => 'installment',
                'usage' => ['woocommerce']
            ],
            'torob' => [
                'title' => __('Torob', 'parsigate'),
                'class' => Torob::class,
                'website' => 'torobpay.com',
                'type' => 'installment',
                'usage' => ['woocommerce']
            ],
            'tara' => [
                'title' => __('Tara', 'parsigate'),
                'class' => Tara::class,
                'website' => 'tara360.ir',
                'type' => 'installment',
                'usage' => ['woocommerce']
            ],

            // Test Gateway
            'test' => [
                'title' => __('Test Gateway', 'parsigate'),
                'class' => Test::class,
                'website' => 'wp-parsi.com',
                'type' => 'test',
                'usage' => ['woocommerce'],
                'woocommerce' => [
                    'pay' => function ($amount, $order, $option, $callback_url) {

                        return [
                            "order_id" => $order->get_id(),
                            "callback_url" => $callback_url,
                        ];
                    },
                    'verify' => function ($amount, $order, $option) {

                        return [
                            'status' => ($_GET['status'] ?? 'NOK')
                        ];
                    }
                ]
            ]
        ];

        return apply_filters('parsigate_gateways_list', $list);
    }

    public static function get($name)
    {
        $list = self::list();
        return $list[$name] ? apply_filters('parsigate_gateway', $list[$name], $name) : false;
    }

    public static function enable($id): bool
    {
        $item = self::get(strtolower($id));
        if (!$item) {
            return false;
        }

        $option_name = Option::option_name($item['type']);
        $option = Option::get($option_name);
        if (!is_array($option)) {
            return false;
        }
        return in_array($id, $option);
    }

    public static function choices($type = null, $enable = null): array
    {
        $list = self::list();
        if (!is_null($type)) {
            $list = array_filter($list, function ($item) use ($type) {
                return $item['type'] == $type;
            });
        }
        $choices = [];

        foreach ($list as $id => $array) {
            if (is_bool($enable) and $enable === true and self::enable($id) === false) {
                continue;
            }

            $choices[$id] = $array['title'];
        }

        return $choices;
    }
}

new Gateways();