<?php

namespace ParsiGate;

use ParsiGate\gateways\AqayePardakht;
use ParsiGate\gateways\AsanPardakht;
use ParsiGate\gateways\Azkivam;
use ParsiGate\gateways\DigiPay;
use ParsiGate\gateways\IranKish;
use ParsiGate\gateways\Mellat;
use ParsiGate\gateways\Melli;
use ParsiGate\gateways\Parsian;
use ParsiGate\gateways\Pasargad;
use ParsiGate\gateways\PayPing;
use ParsiGate\gateways\Saderat;
use ParsiGate\gateways\Sep;
use ParsiGate\gateways\Shepa;
use ParsiGate\gateways\SnappPay;
use ParsiGate\gateways\Tara;
use ParsiGate\gateways\Test;
use ParsiGate\Gateways\ZarinPal;
use ParsiGate\gateways\Zibal;
use WPParsidate\Addons\ParsiGateOption\ParsiGateOption;

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
                            'description' => __('Please enter the gateway merchant id.', 'parsigate'),
                            'desc_tip' => false,
                            'class' => 'pg-ltr-input'
                        ]
                    ],
                    'pay' => function ($amount, $order, $option, $callback_url, $class) {

                        return [
                            'LoginAccount' => $option['merchant_id'],
                            'Amount' => $amount,
                            'OrderId' => $order->get_id() . wp_rand(1, 10000),
                            'CallBackUrl' => $callback_url,
                            'AdditionalData' => WooCommerce::get_order_description($order, 'parsian')
                        ];
                    },
                    'verify' => function ($amount, $order, $option, $class) {

                        // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
                        $token = (isset($_REQUEST['Token']) ? sanitize_text_field(wp_unslash($_REQUEST['Token'])) : '');

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
                'usage' => ['woocommerce'],
                'auth' => function ($option, $class, $args) {

                    $tokens = new Tokens('pasargad');
                    if (!$tokens->is_valid()) {

                        $token = $class->call('token', [
                            'username' => $option['username'],
                            'password' => $option['password']
                        ]);
                        if ($token['success'] === false) {
                            return new \WP_Error('invalid_token', $token['message']);
                        }

                        $access_token = $token['data']['access_token'];
                        $tokens->store($access_token, MINUTE_IN_SECONDS * 10);
                    } else {
                        $access_token = $tokens->get_value();
                    }

                    return $access_token;
                },
                'woocommerce' => [
                    'sandbox' => false,
                    'settings' => [
                        'terminal_id' => [
                            'title' => __('Terminal No.', 'parsigate'),
                            'type' => 'text',
                            'default' => '',
                            'description' => __("Please enter the gateway terminal number.", "parsigate"),
                            'desc_tip' => false,
                            'class' => 'pg-ltr-input'
                        ],
                        'username' => [
                            'title' => __('Username', 'parsigate'),
                            'type' => 'text',
                            'default' => '',
                            'desc_tip' => false,
                            'class' => 'pg-ltr-input'
                        ],
                        'password' => [
                            'title' => __('Password', 'parsigate'),
                            'type' => 'text',
                            'default' => '',
                            'description' => __('Please enter the gateway password.', 'parsigate'),
                            'desc_tip' => false,
                            'class' => 'pg-ltr-input'
                        ]
                    ],
                    'pay' => function ($amount, $order, $option, $callback_url, $class) {

                        $tokens = new Tokens('pasargad');

                        $iranTime = new \DateTime('now', new \DateTimeZone('Asia/Tehran'));
                        $invoiceDate = $iranTime->format("Y/m/d H:i:s");

                        return [
                            "access_token" => $tokens->get_value(),
                            "amount" => $amount,
                            "invoice" => time() . $order->get_id(),
                            "invoiceDate" => $invoiceDate,
                            "serviceCode" => 8,
                            "serviceType" => 'PURCHASE',
                            "callbackApi" => $callback_url,
                            "payerMail" => '',
                            "mobileNumber" => $order->get_billing_phone(),
                            "terminalNumber" => $option['terminal_id']
                        ];
                    },
                    'verify' => function ($amount, $order, $option, $class) {

                        // phpcs:disable WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
                        $invoiceId = (isset($_REQUEST['invoiceId']) ? sanitize_text_field(wp_unslash($_REQUEST['invoiceId'])) : '');
                        $status = (isset($_REQUEST['status']) ? sanitize_text_field(wp_unslash($_REQUEST['status'])) : '');
                        $referenceNumber = (isset($_REQUEST['referenceNumber']) ? sanitize_text_field(wp_unslash($_REQUEST['referenceNumber'])) : '');
                        $trackId = (isset($_REQUEST['trackId']) ? sanitize_text_field(wp_unslash($_REQUEST['trackId'])) : '');
                        // phpcs:enable WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended

                        $tokens = new Tokens('pasargad');

                        return [
                            'access_token' => $tokens->get_value(),
                            'invoiceId' => $invoiceId,
                            'status' => $status,
                            'referenceNumber' => $referenceNumber,
                            'trackId' => $trackId
                        ];
                    }
                ]
            ],
            'saman' => [
                'title' => __('Saman (Sep)', 'parsigate'),
                'class' => Sep::class,
                'website' => 'sep.ir',
                'type' => 'bank',
                'usage' => ['woocommerce'],
                'woocommerce' => [
                    'sandbox' => false,
                    'settings' => [
                        'terminal_id' => [
                            'title' => __('Terminal No.', 'parsigate'),
                            'type' => 'text',
                            'default' => '',
                            'description' => __("Please enter the gateway terminal number.", "parsigate"),
                            'desc_tip' => false,
                            'class' => 'pg-ltr-input'
                        ]
                    ],
                    'pay' => function ($amount, $order, $option, $callback_url, $class) {

                        $name = $order->get_billing_company();
                        if (!empty($order->get_billing_first_name()) || !empty($order->get_billing_last_name())) {
                            $name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
                        }

                        return [
                            "action" => "token",
                            "TerminalId" => $option['terminal_id'],
                            "Amount" => $amount,
                            "ResNum" => $order->get_id(),
                            "RedirectUrl" => $callback_url,
                            "CellNumber" => $order->get_billing_phone(),
                            "ResNum1" => mb_substr($name, 0, 50),
                            "ResNum2" => mb_substr($order->get_billing_email(), 0, 50),
                            "ResNum3" => (is_user_logged_in() ? mb_substr(wp_get_current_user()->user_login, 0, 50) : ''),
                            "ResNum4" => ''
                        ];
                    },
                    'verify' => function ($amount, $order, $option, $class) {

                        // phpcs:disable WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
                        $State = (isset($_POST['State']) ? sanitize_text_field(wp_unslash($_POST['State'])) : '');
                        $ResNum = (isset($_POST['ResNum']) ? sanitize_text_field(wp_unslash($_POST['ResNum'])) : '');
                        $RefNum = (isset($_POST['RefNum']) ? sanitize_text_field(wp_unslash($_POST['RefNum'])) : '');
                        // phpcs:enable WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended

                        return [
                            'State' => $State,
                            'ResNum' => $ResNum,
                            'RefNum' => $RefNum,
                            'TerminalNumber' => $option['terminal_id'],
                        ];
                    }
                ]
            ],
            'mellat' => [
                'title' => __('Mellat (BehPardakht)', 'parsigate'),
                'class' => Mellat::class,
                'website' => 'behpardakht.com',
                'type' => 'bank',
                'usage' => ['woocommerce'],
                'requirement' => (Utility::is_soap_enabled() === false ? __("Note: To activate the Gateway, the SoapClient module must be enabled in your host's PHP settings.", "parsigate") : ''),
                'woocommerce' => [
                    'sandbox' => false,
                    'settings' => [
                        'terminal' => [
                            'title' => __('Terminal No.', 'parsigate'),
                            'type' => 'text',
                            'default' => '',
                            'description' => __("Please enter the gateway terminal number.", "parsigate"),
                            'desc_tip' => false,
                            'class' => 'pg-ltr-input'
                        ],
                        'username' => [
                            'title' => __('Username', 'parsigate'),
                            'type' => 'text',
                            'default' => '',
                            'desc_tip' => false,
                            'class' => 'pg-ltr-input'
                        ],
                        'password' => [
                            'title' => __('Password', 'parsigate'),
                            'type' => 'text',
                            'default' => '',
                            'desc_tip' => false,
                            'class' => 'pg-ltr-input'
                        ]
                    ],
                    'pay' => function ($amount, $order, $option, $callback_url, $class) {

                        return [
                            'terminalId' => $option['terminal'],
                            'userName' => $option['username'],
                            'userPassword' => $option['password'],
                            'orderId' => $order->get_id(),
                            'amount' => $amount,
                            'localDate' => gmdate('Ymd'),
                            'localTime' => gmdate('His'),
                            'additionalData' => WooCommerce::get_order_description($order, 'mellat'),
                            'callBackUrl' => $callback_url,
                            'payerId' => $order->get_customer_id()
                        ];
                    },
                    'verify' => function ($amount, $order, $option, $class) {

                        // phpcs:disable WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
                        $resCode = (isset($_POST['ResCode']) ? sanitize_text_field(wp_unslash($_POST['ResCode'])) : '');
                        $saleOrderId = (isset($_POST['SaleOrderId']) ? sanitize_text_field(wp_unslash($_POST['SaleOrderId'])) : '');
                        $saleReferenceId = (isset($_POST['SaleReferenceId']) ? sanitize_text_field(wp_unslash($_POST['SaleReferenceId'])) : '');
                        $CardHolderInfo = (isset($_POST['CardHolderInfo']) ? sanitize_text_field(wp_unslash($_POST['CardHolderInfo'])) : '');
                        $CardHolderPan = (isset($_POST['CardHolderPan']) ? sanitize_text_field(wp_unslash($_POST['CardHolderPan'])) : '');
                        $FinalAmount = (isset($_POST['FinalAmount']) ? sanitize_text_field(wp_unslash($_POST['FinalAmount'])) : '');
                        // phpcs:enable WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended

                        return [
                            'ResCode' => $resCode,
                            'terminalId' => $option['terminal'],
                            'userName' => $option['username'],
                            'userPassword' => $option['password'],
                            'orderId' => $saleOrderId,
                            'saleOrderId' => $saleOrderId,
                            'saleReferenceId' => $saleReferenceId,
                            'CardHolderInfo' => $CardHolderInfo,
                            'CardHolderPan' => $CardHolderPan,
                            'FinalAmount' => $FinalAmount
                        ];
                    }
                ]
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
                            'description' => __("Please enter the gateway terminal number.", "parsigate"),
                            'desc_tip' => false,
                            'class' => 'pg-ltr-input'
                        ],
                        'merchant_id' => [
                            'title' => __('Merchant ID', 'parsigate'),
                            'type' => 'text',
                            'default' => '',
                            'description' => __('Please enter the gateway merchant id.', 'parsigate'),
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
                    'pay' => function ($amount, $order, $option, $callback_url, $class) {

                        return [
                            'TerminalId' => $option['terminal_id'],
                            'MerchantId' => $option['merchant_id'],
                            'Key' => $option['key'],
                            'Amount' => $amount,
                            'ReturnUrl' => $callback_url,
                            'LocalDateTime' => gmdate("m/d/Y g:i:s a"),
                            'OrderId' => $order->get_id()
                        ];
                    },
                    'verify' => function ($amount, $order, $option, $class) {

                        // phpcs:disable WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
                        $OrderId = (isset($_POST["OrderId"]) ? sanitize_text_field(wp_unslash($_POST["OrderId"])) : '');
                        $Token = (isset($_POST["token"]) ? sanitize_text_field(wp_unslash($_POST["token"])) : '');
                        $ResCode = (isset($_POST["ResCode"]) ? sanitize_text_field(wp_unslash($_POST["ResCode"])) : '');
                        // phpcs:enable WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended

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
                'usage' => ['woocommerce'],
                'woocommerce' => [
                    'sandbox' => false,
                    'settings' => [
                        'merchant_id' => [
                            'title' => __('Merchant ID', 'parsigate'),
                            'type' => 'text',
                            'default' => '',
                            'description' => __('Please enter the gateway merchant id.', 'parsigate'),
                            'desc_tip' => false,
                            'class' => 'pg-ltr-input'
                        ],
                        'username' => [
                            'title' => __('Username', 'parsigate'),
                            'type' => 'text',
                            'default' => '',
                            'description' => __('Please enter the gateway username.', 'parsigate'),
                            'desc_tip' => false,
                            'class' => 'pg-ltr-input'
                        ],
                        'password' => [
                            'title' => __('Password', 'parsigate'),
                            'type' => 'text',
                            'default' => '',
                            'description' => __('Please enter the gateway password.', 'parsigate'),
                            'desc_tip' => false,
                            'class' => 'pg-ltr-input'
                        ]
                    ],
                    'before' => function ($amount, $order, $option, $class) {

                        $order->update_meta_data('localInvoiceId', time() . $order->get_id());
                        $order->save();
                    },
                    'pay' => function ($amount, $order, $option, $callback_url, $class) {

                        return [
                            'username' => $option['username'],
                            'password' => $option['password'],
                            'serviceTypeId' => 1,
                            'merchantConfigurationId' => $option['merchant_id'],
                            'localInvoiceId' => $order->get_meta('localInvoiceId', true, ''),
                            'amountInRials' => $amount,
                            'localDate' => (new \DateTime('Asia/Tehran'))->format('Ymd His'),
                            'callbackURL' => $callback_url,
                            'paymentId' => $order->get_id(),
                            'additionalData' => '',
                        ];
                    },
                    'verify' => function ($amount, $order, $option, $class) {

                        return [
                            'username' => $option['username'],
                            'password' => $option['password'],
                            'merchantConfigurationId' => $option['merchant_id'],
                            'localInvoiceId' => $order->get_meta('localInvoiceId', true, ''),
                        ];
                    }
                ]
            ],
            'saderat' => [
                'title' => __('Saderat (Sepehr)', 'parsigate'),
                'class' => Saderat::class,
                'website' => 'sepehrpay.com',
                'type' => 'bank',
                'usage' => ['woocommerce'],
                'woocommerce' => [
                    'sandbox' => false,
                    'settings' => [
                        'terminal_id' => [
                            'title' => __('Terminal No.', 'parsigate'),
                            'type' => 'text',
                            'default' => '',
                            'desc_tip' => false,
                            'description' => __("Please enter the gateway terminal number.", "parsigate"),
                            'class' => 'pg-ltr-input'
                        ]
                    ],
                    'pay' => function ($amount, $order, $option, $callback_url, $class) {

                        return [
                            'Amount' => $amount,
                            'callbackURL' => $callback_url,
                            'InvoiceID' => $order->get_id(),
                            'TerminalID' => $option['terminal_id'],
                            'Payload' => $order->get_billing_phone()
                        ];
                    },
                    'verify' => function ($amount, $order, $option, $class) {

                        // phpcs:disable WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
                        $invoiceNumber = (isset($_POST['invoiceid'])) ? sanitize_text_field(wp_unslash($_POST['invoiceid'])) : "";
                        $digitalreceipt = (isset($_POST['digitalreceipt'])) ? sanitize_text_field(wp_unslash($_POST['digitalreceipt'])) : "";
                        $respcode = (isset($_POST['respcode'])) ? sanitize_text_field(wp_unslash($_POST['respcode'])) : "";
                        $rrn = (isset($_POST['rrn'])) ? sanitize_text_field(wp_unslash($_POST['rrn'])) : "";
                        // phpcs:enable WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended

                        return [
                            'respcode' => $respcode,
                            'digitalreceipt' => $digitalreceipt,
                            'Tid' => $option['terminal_id'],
                            'amount' => $amount,
                            'rrn' => $rrn,
                            'invoiceNumber' => $invoiceNumber
                        ];
                    }
                ]
            ],
            'irankish' => [
                'title' => __('Iran Kish', 'parsigate'),
                'class' => IranKish::class,
                'website' => 'irankish.com',
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
                            'description' => __("Please enter the gateway terminal number.", "parsigate"),
                            'desc_tip' => false,
                            'class' => 'pg-ltr-input'
                        ],
                        'password' => [
                            'title' => __('Password', 'parsigate'),
                            'type' => 'text',
                            'default' => '',
                            'description' => __('Please enter the gateway password.', 'parsigate'),
                            'desc_tip' => false,
                            'class' => 'pg-ltr-input'
                        ],
                        'acceptorId' => [
                            'title' => __('Acceptor Id', 'parsigate'),
                            'type' => 'text',
                            'default' => '',
                            'description' => __('Please enter the gateway acceptor Id.', 'parsigate'),
                            'desc_tip' => false,
                            'class' => 'pg-ltr-input'
                        ],
                        'pub_key' => [
                            'title' => __('Public Key', 'parsigate'),
                            'type' => 'text',
                            'default' => '',
                            'description' => __('Please enter the gateway public key.', 'parsigate'),
                            'desc_tip' => false,
                            'class' => 'pg-ltr-input'
                        ]
                    ],
                    'pay' => function ($amount, $order, $option, $callback_url, $class) {

                        return [
                            "pub_key" => $option['pub_key'],
                            "password" => $option['password'],
                            "acceptorId" => $option['acceptorId'],
                            "amount" => $amount,
                            "billInfo" => null,
                            "requestId" => uniqid(),
                            "paymentId" => (string)$order->get_id(),
                            "requestTimestamp" => time(),
                            "revertUri" => $callback_url,
                            "terminalId" => $option['terminal_id'],
                            "transactionType" => "Purchase"
                        ];
                    },
                    'verify' => function ($amount, $order, $option, $class) {

                        // phpcs:disable WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
                        $responseCode = (isset($_POST['responseCode']) ? sanitize_text_field(wp_unslash($_POST['responseCode'])) : '');
                        $retrievalReferenceNumber = (isset($_POST['retrievalReferenceNumber']) ? sanitize_text_field(wp_unslash($_POST['retrievalReferenceNumber'])) : '');
                        $systemTraceAuditNumber = (isset($_POST['systemTraceAuditNumber']) ? sanitize_text_field(wp_unslash($_POST['systemTraceAuditNumber'])) : '');
                        $tokenIdentity = (isset($_POST['token']) ? sanitize_text_field(wp_unslash($_POST['token'])) : '');
                        // phpcs:enable WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended

                        return [
                            'terminalId' => $option['terminal_id'],
                            'responseCode' => $responseCode,
                            'retrievalReferenceNumber' => $retrievalReferenceNumber,
                            'systemTraceAuditNumber' => $systemTraceAuditNumber,
                            'tokenIdentity' => $tokenIdentity
                        ];
                    }
                ]
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
                            'description' => __('Please enter the gateway merchant id.', 'parsigate'),
                            'desc_tip' => false,
                            'class' => 'pg-ltr-input'
                        ]
                    ],
                    'pay' => function ($amount, $order, $option, $callback_url, $class) {

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
                    'verify' => function ($amount, $order, $option, $class) {

                        // phpcs:disable WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
                        $authority = (isset($_GET['Authority']) ? sanitize_text_field(wp_unslash($_GET['Authority'])) : '');
                        $status = (isset($_GET['Status']) ? sanitize_text_field(wp_unslash($_GET['Status'])) : '');
                        // phpcs:enable WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended

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
                            'description' => __('Please enter the gateway merchant id.', 'parsigate'),
                            'desc_tip' => false,
                            'class' => 'pg-ltr-input'
                        ]
                    ],
                    'pay' => function ($amount, $order, $option, $callback_url, $class) {

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
                    'verify' => function ($amount, $order, $option, $class) {

                        // phpcs:disable WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
                        $success = (isset($_GET['success']) ? sanitize_text_field(wp_unslash($_GET['success'])) : '');
                        $trackId = (isset($_GET['trackId']) ? sanitize_text_field(wp_unslash($_GET['trackId'])) : '');
                        // phpcs:enable WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended

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
                    'pay' => function ($amount, $order, $option, $callback_url, $class) {

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
                    'verify' => function ($amount, $order, $option, $class) {

                        // phpcs:disable WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
                        if (!isset($_POST['status']) || !isset($_POST['data'])) {
                            return ['success' => false];
                        }

                        if ($_POST['status'] != '1') {
                            return ['success' => false];
                        }

                        $raw = sanitize_text_field(wp_unslash($_POST['data']));
                        // phpcs:enable WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended

                        $sanitize = str_ireplace("\\", "", $raw);
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
                    'pay' => function ($amount, $order, $option, $callback_url, $class) {

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
                    'verify' => function ($amount, $order, $option, $class) {

                        // phpcs:disable WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
                        $status = (isset($_POST['status']) ? sanitize_text_field(wp_unslash($_POST['status'])) : '');
                        $transid = (isset($_POST['transid']) ? sanitize_text_field(wp_unslash($_POST['transid'])) : '');
                        // phpcs:enable WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended

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
                    'pay' => function ($amount, $order, $option, $callback_url, $class) {

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
                    'verify' => function ($amount, $order, $option, $class) {

                        // phpcs:disable WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
                        $status = (isset($_GET['status']) ? sanitize_text_field(wp_unslash($_GET['status'])) : '');
                        $token = (isset($_GET['token']) ? sanitize_text_field(wp_unslash($_GET['token'])) : '');
                        // phpcs:enable WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended

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
            'snapppay' => [
                'title' => __('SnappPay', 'parsigate'),
                'class' => SnappPay::class,
                'website' => 'snapppay.ir',
                'type' => 'installment',
                'usage' => ['woocommerce'],
                'auth' => function ($option, $class, $args) {

                    $tokens = new Tokens('snapppay');
                    if (!$tokens->is_valid()) {

                        $token = $class->call('token', [
                            'client_id' => $option['client_id'],
                            'client_secret' => $option['client_secret'],
                            'username' => $option['username'],
                            'password' => $option['password']
                        ]);
                        if ($token['success'] === false) {
                            return new \WP_Error('invalid_token', $token['message']);
                        }

                        $access_token = $token['data']['access_token'];
                        $tokens->store($access_token, MINUTE_IN_SECONDS * 30);
                    } else {
                        $access_token = $tokens->get_value();
                    }

                    return $access_token;
                },
                'woocommerce' => [
                    'sandbox' => false,
                    'settings' => [
                        'client_id' => [
                            'title' => __('Client ID', 'parsigate'),
                            'type' => 'text',
                            'default' => '',
                            'description' => __('Please enter the gateway client id.', 'parsigate'),
                            'desc_tip' => false,
                            'class' => 'pg-ltr-input'
                        ],
                        'client_secret' => [
                            'title' => __('Client Secret', 'parsigate'),
                            'type' => 'text',
                            'default' => '',
                            'description' => __('Please enter the gateway client secret.', 'parsigate'),
                            'desc_tip' => false,
                            'class' => 'pg-ltr-input'
                        ],
                        'username' => [
                            'title' => __('Username', 'parsigate'),
                            'type' => 'text',
                            'default' => '',
                            'description' => __('Please enter the gateway username.', 'parsigate'),
                            'desc_tip' => false,
                            'class' => 'pg-ltr-input'
                        ],
                        'password' => [
                            'title' => __('Password', 'parsigate'),
                            'type' => 'text',
                            'default' => '',
                            'description' => __('Please enter the gateway password.', 'parsigate'),
                            'desc_tip' => false,
                            'class' => 'pg-ltr-input'
                        ]
                    ],
                    'pay' => function ($amount, $order, $option, $callback_url, $class) {

                        // Mobile
                        $mobile = $order->get_billing_phone();
                        if (empty($mobile)) {
                            return new \WP_Error('invalid_token', __('mobile is required !', 'parsigate'));
                        }

                        // Get Token
                        $tokens = new Tokens('snapppay');

                        // Get Cart List
                        $cart_list = [
                            'cartId' => $order->get_id(),
                            'totalAmount' => WooCommerce::price($amount, $order, 'snapppay'),
                        ];
                        $items = [];
                        foreach ($order->get_items() as $item_id => $item) {
                            $product = $item->get_product();
                            $item_params = [
                                "name" => $item->get_name(),
                                "count" => $item->get_quantity(),
                                "amount" => WooCommerce::price($product->get_price('edit'), $order, 'snapppay'),
                                "id" => $product->get_id()
                            ];
                            $items[] = $item_params;
                        }
                        $cart_list['cartItems'] = $items;
                        $order_data = $order->get_data();
                        $cart_list['taxAmount'] = false === empty($order_data['total_tax']) ? WooCommerce::price($order_data['total_tax'], $order, 'snapppay') : 0;
                        $cart_list['shippingAmount'] = false === empty($order_data['shipping_total']) ? WooCommerce::price($order_data['shipping_total'], $order, 'snapppay') : 0;
                        $cart_list['isShipmentIncluded'] = (int)$cart_list['shippingAmount'] > 0;
                        $cart_list['isTaxIncluded'] = (int)$cart_list['taxAmount'] > 0;

                        // Return
                        return [
                            'access_token' => $tokens->get_value(),
                            'username' => $option['username'],
                            'amount' => $amount,
                            'mobile' => $mobile,
                            'paymentMethodTypeDto' => 'INSTALLMENT',
                            'transactionId' => time() . '-' . $order->get_id(),
                            'returnURL' => $callback_url,
                            'cartList' => $cart_list,
                            'discountAmount' => ((int)$order_data['discount_total'] > 0 ? WooCommerce::price($order_data['discount_total'], $order, 'snapppay') : 0)
                        ];
                    },
                    'verify' => function ($amount, $order, $option, $class) {

                        // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
                        $state = (isset($_POST['state']) ? sanitize_text_field(wp_unslash($_POST['state'])) : '');

                        // Get Token
                        $tokens = new Tokens('snapppay');

                        // Return
                        return [
                            'access_token' => $tokens->get_value(),
                            'state' => $state,
                            'paymentToken' => $order->get_meta('authority', true, '')
                        ];
                    }
                ]
            ],
            'digipay' => [
                'title' => __('DigiPay', 'parsigate'),
                'class' => DigiPay::class,
                'website' => 'mydigipay.com',
                'type' => 'installment',
                'usage' => ['woocommerce'],
                'auth' => function ($option, $class, $args) {

                    $tokens = new Tokens('digipay');
                    if (!$tokens->is_valid()) {

                        $token = $class->call('token', [
                            'username' => $option['username'],
                            'password' => $option['password'],
                            'client_id' => $option['client_id'],
                            'client_secret' => $option['client_secret']
                        ]);
                        if ($token['success'] === false) {
                            return new \WP_Error('invalid_token', $token['message']);
                        }

                        $access_token = $token['data']['access_token'];
                        $tokens->store($access_token, (int)$token['data']['expires_in']);
                    } else {

                        $access_token = $tokens->get_value();
                    }

                    return $access_token;
                },
                'woocommerce' => [
                    'sandbox' => false,
                    'settings' => [
                        'client_id' => [
                            'title' => __('Client ID', 'parsigate'),
                            'type' => 'text',
                            'default' => '',
                            'description' => __('Please enter the gateway client id.', 'parsigate'),
                            'desc_tip' => false,
                            'class' => 'pg-ltr-input'
                        ],
                        'client_secret' => [
                            'title' => __('Client Secret', 'parsigate'),
                            'type' => 'text',
                            'default' => '',
                            'description' => __('Please enter the gateway client secret.', 'parsigate'),
                            'desc_tip' => false,
                            'class' => 'pg-ltr-input'
                        ],
                        'username' => [
                            'title' => __('Username', 'parsigate'),
                            'type' => 'text',
                            'default' => '',
                            'description' => __('Please enter the gateway username.', 'parsigate'),
                            'desc_tip' => false,
                            'class' => 'pg-ltr-input'
                        ],
                        'password' => [
                            'title' => __('Password', 'parsigate'),
                            'type' => 'text',
                            'default' => '',
                            'description' => __('Please enter the gateway password.', 'parsigate'),
                            'desc_tip' => false,
                            'class' => 'pg-ltr-input'
                        ]
                    ],
                    'pay' => function ($amount, $order, $option, $callback_url, $class) {

                        $tokens = new Tokens('digipay');
                        return [
                            'token' => $tokens->get_value(),
                            'amount' => $amount,
                            'cellNumber' => trim($order->get_billing_phone()),
                            'providerId' => $order->get_id(),
                            'callbackUrl' => $callback_url
                        ];
                    },
                    'verify' => function ($amount, $order, $option, $class) {

                        // phpcs:disable WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
                        $type = (isset($_REQUEST['type']) ? sanitize_text_field(wp_unslash($_REQUEST['type'])) : '');
                        $trackingCode = (isset($_REQUEST['trackingCode']) ? sanitize_text_field(wp_unslash($_REQUEST['trackingCode'])) : '');
                        // phpcs:enable WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended

                        // Get Token
                        $tokens = new Tokens('digipay');

                        // Return
                        return [
                            'token' => $tokens->get_value(),
                            'trackingCode' => $trackingCode,
                            'type' => $type
                        ];
                    },
                    'completed' => function ($order, $transaction_id, $verify) {

                        $meta_data = [];

                        foreach (['rrn', 'pspCode', 'pspName', 'fpCode', 'fpName'] as $Key) {
                            if (isset($verify['data'][$Key])) {
                                $meta_data[$Key] = $verify['data'][$Key];
                            }
                        }

                        if (!empty($meta_data)) {
                            foreach ($meta_data as $key => $value) {
                                $order->update_meta_data($key, $value);
                            }
                            $order->save();
                        }
                    }
                ]
            ],
            'azkivam' => [
                'title' => __('Azkivam', 'parsigate'),
                'class' => Azkivam::class,
                'website' => 'azkivam.com',
                'type' => 'installment',
                'usage' => ['woocommerce'],
                'requirement' => (Utility::is_enable_open_ssl() === false ? __("Note: To activate the Gateway, the OpenSSL module must be enabled in your host's PHP settings.", "parsigate") : ''),
                'woocommerce' => [
                    'sandbox' => false,
                    'settings' => [
                        'terminal_id' => [
                            'title' => __('Terminal No.', 'parsigate'),
                            'type' => 'text',
                            'default' => '',
                            'description' => __("Please enter the gateway terminal number.", "parsigate"),
                            'desc_tip' => false,
                            'class' => 'pg-ltr-input'
                        ],
                        'api_key' => [
                            'title' => __('API Key', 'parsigate'),
                            'type' => 'text',
                            'default' => '',
                            'description' => __('Please enter the gateway api key.', 'parsigate'),
                            'desc_tip' => false,
                            'class' => 'pg-ltr-input'
                        ],
                        'api_url' => [
                            'title' => __('API Url', 'parsigate'),
                            'type' => 'text',
                            'default' => 'https://api.azkiloan.com',
                            'description' => __('Please enter the gateway api url.', 'parsigate'),
                            'desc_tip' => false,
                            'class' => 'pg-ltr-input'
                        ]
                    ],
                    'pay' => function ($amount, $order, $option, $callback_url, $class) {

                        // Get Mobile Number
                        $mobile = $order->get_billing_phone();

                        // Items
                        $items = [];
                        for ($index = 0; $index < count($order->get_items()); $index++) {
                            $key = array_keys($order->get_items())[$index];
                            $value = $order->get_items()[$key];

                            $items[] = array(
                                'name' => $value->get_name(),
                                'url' => get_permalink($value->get_product_id()),
                                'count' => $value->get_quantity(),
                                'amount' => WooCommerce::price($value->get_total(), $order, 'azkivam') / $value->get_quantity()
                            );
                        }

                        if (0 < WC()->cart->get_shipping_total()) {
                            $items[] = array(
                                'name' => __('Shipping Cost', 'parsigate'),
                                'url' => home_url(),
                                'count' => 1,
                                'amount' => WooCommerce::price(intval(WC()->cart->get_shipping_total()), $order, 'azkivam')
                            );
                        }

                        // Return
                        return [
                            'api_url' => $option['api_url'],
                            'api_key' => $option['api_key'],
                            'MerchantId' => $option['terminal_id'],
                            'amount' => $amount,
                            'redirect_uri' => $callback_url,
                            'fallback_uri' => $callback_url,
                            'provider_id' => $order->get_id() . wp_rand(100000000, 999999999),
                            'mobile_number' => (!empty($mobile)) ? (preg_match('/^09[0-9]{9}/i', $mobile) ? $mobile : '') : '',
                            'items' => $items
                        ];
                    },
                    'verify' => function ($amount, $order, $option, $class) {

                        // phpcs:disable WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
                        $status = (isset($_GET['status']) ? sanitize_text_field(wp_unslash($_GET['status'])) : '');
                        $ticketId = (isset($_GET['ticketId']) ? sanitize_text_field(wp_unslash($_GET['ticketId'])) : '');
                        // phpcs:enable WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended

                        return [
                            'api_url' => $option['api_url'],
                            'api_key' => $option['api_key'],
                            'MerchantId' => $option['terminal_id'],
                            'status' => $status,
                            'ticket_id' => $ticketId,
                        ];
                    }
                ]
            ],
            'tara' => [
                'title' => __('Tara', 'parsigate'),
                'class' => Tara::class,
                'website' => 'tara360.ir',
                'type' => 'installment',
                'usage' => ['woocommerce'],
                'auth' => function ($option, $class, $args) {

                    $tokens = new Tokens('tara');
                    if (!$tokens->is_valid()) {

                        $token = $class->call('token', [
                            'username' => $option['username'],
                            'password' => $option['password']
                        ]);
                        if ($token['success'] === false) {
                            return new \WP_Error('invalid_token', $token['message']);
                        }

                        $access_token = $token['data']['access_token'];
                        $tokens->store($access_token, MINUTE_IN_SECONDS * 30);
                    } else {

                        $access_token = $tokens->get_value();
                    }

                    return $access_token;
                },
                'woocommerce' => [
                    'sandbox' => false,
                    'settings' => [
                        'merchant_id' => [
                            'title' => __('Merchant ID', 'parsigate'),
                            'type' => 'text',
                            'default' => '',
                            'description' => __('Please enter the gateway merchant id.', 'parsigate'),
                            'desc_tip' => false,
                            'class' => 'pg-ltr-input'
                        ],
                        'username' => [
                            'title' => __('Username', 'parsigate'),
                            'type' => 'text',
                            'default' => '',
                            'description' => __('Please enter the gateway username.', 'parsigate'),
                            'desc_tip' => false,
                            'class' => 'pg-ltr-input'
                        ],
                        'password' => [
                            'title' => __('Password', 'parsigate'),
                            'type' => 'text',
                            'default' => '',
                            'description' => __('Please enter the gateway password.', 'parsigate'),
                            'desc_tip' => false,
                            'class' => 'pg-ltr-input'
                        ]
                    ],
                    'pay' => function ($amount, $order, $option, $callback_url, $class) {

                        // Mobile
                        $mobile = $order->get_billing_phone();
                        if (empty($mobile)) {
                            return new \WP_Error('invalid_token', __('mobile is required !', 'parsigate'));
                        }

                        // Get Token
                        $tokens = new Tokens('tara');

                        // Invoices Items
                        $invoice_items = [];
                        $cart = WC()->cart->get_cart();
                        if ($cart && count($cart) > 0) {
                            foreach ($cart as $item) {
                                $product = new \stdClass();
                                $product->name = $item['data']->get_title();
                                $product->code = $item['product_id'];
                                $product->count = $item['quantity'];
                                $product->unit = 5;
                                $product->fee = WooCommerce::price(intval($item['data']->get_price()), $order);
                                $product->data = '#' . $order->get_id();
                                $invoice_items[] = $product;
                            }
                        }
                        if (count($invoice_items) === 0) {
                            $product = new \stdClass();
                            $product->name = __('Buy online from: ', 'parsigate') . get_bloginfo();
                            $product->code = 1;
                            $product->count = 1;
                            $product->unit = 5;
                            $product->fee = $amount;
                            $product->group = "26";
                            $product->groupTitle = __('Other', 'parsigate');
                            $product->data = '#' . $order->get_id();
                            $invoice_items[] = $product;
                        }

                        $vat_amount = WooCommerce::price((float)$order->get_total_tax(), $order);
                        $ship_total = (float)$order->get_shipping_total();
                        $ship_method = $order->get_shipping_method();
                        if ($ship_total > 0) {
                            $shipping_item = new \stdClass();
                            $shipping_item->name = __('Shipping Cost', 'parsigate') . (!empty($ship_method) ? ' (' . $ship_method . ')' : '');
                            $shipping_item->code = 999001;
                            $shipping_item->count = 1;
                            $shipping_item->unit = 5;
                            $shipping_item->fee = WooCommerce::price((int)$ship_total, $order);
                            $shipping_item->group = "40";
                            $shipping_item->groupTitle = __('Shipping', 'parsigate');
                            $shipping_item->data = '#' . $order->get_id();;
                            $invoice_items[] = $shipping_item;
                        }

                        // Return
                        return [
                            'access_token' => $tokens->get_value(),
                            'username' => $option['username'],
                            'additionalData' => WooCommerce::get_order_description($order, 'tara'),
                            'mobile' => $mobile,
                            'callBackUrl' => $callback_url,
                            'amount' => $amount,
                            'vat' => $vat_amount,
                            'serviceAmountList' => [
                                [
                                    'serviceId' => $option['merchant_id'],
                                    'amount' => $amount,
                                ]
                            ],
                            'taraInvoiceItemList' => $invoice_items,
                            'ip' => Utility::ip()
                        ];
                    },
                    'verify' => function ($amount, $order, $option, $class) {

                        // phpcs:disable WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
                        $token = (isset($_POST['token']) ? sanitize_text_field(wp_unslash($_POST['token'])) : '');
                        $result = (isset($_POST['result']) ? sanitize_text_field(wp_unslash($_POST['result'])) : '');
                        $channelRefNumber = (isset($_POST['channelRefNumber']) ? sanitize_text_field(wp_unslash($_POST['channelRefNumber'])) : '');
                        // phpcs:enable WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended

                        // Get Token
                        $tokens = new Tokens('tara');

                        // Return
                        return [
                            'access_token' => $tokens->get_value(),
                            'token' => $token,
                            'result' => $result,
                            'channelRefNumber' => $channelRefNumber,
                            'ip' => Utility::ip()
                        ];
                    }
                ]
            ],

            // Test Gateway
            'test' => [
                'title' => __('Test Gateway', 'parsigate'),
                'class' => Test::class,
                'website' => 'wp-parsi.com',
                'type' => 'test',
                'usage' => ['woocommerce'],
                'woocommerce' => [
                    'pay' => function ($amount, $order, $option, $callback_url, $class) {

                        return [
                            "order_id" => $order->get_id(),
                            "callback_url" => $callback_url,
                        ];
                    },
                    'verify' => function ($amount, $order, $option, $class) {

                        // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
                        $status = (isset($_GET['status']) ? sanitize_text_field(wp_unslash($_GET['status'])) : 'NOK');

                        return [
                            'status' => $status
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

        $option = ParsiGateOption::get(strtolower($id));
        return ((int)$option == 1);
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