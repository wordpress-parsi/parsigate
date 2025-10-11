<?php

namespace ParsiGate\gateways;

use ParsiGate\Utility;

class Parsian extends Base
{

    public static int $type = 2;

    public function pay(array $args = []): array
    {

        // Setup Body
        $body = [
            "requestData" => [
                'LoginAccount' => $args['LoginAccount'],
                'Amount' => $args['Amount'],
                'OrderId' => $args['OrderId'],
                'CallBackUrl' => $args['CallBackUrl'],
                'AdditionalData' => $args['AdditionalData']
            ]
        ];

        // Setup Headers
        $headers = [
            'soap_version' => 'SOAP_1_1',
            'cache_wsdl' => WSDL_CACHE_NONE,
            'encoding' => 'UTF-8',
            'trace' => 1
        ];

        // Soap Method
        $soapMethod = ['X-Soap-Method' => 'SalePaymentRequest'];

        // Setup Request args
        $url = 'https://pec.shaparak.ir/NewIPGServices/Sale/SaleService.asmx?WSDL';
        try {

            $client = new \SoapClient($url, $headers);
            $json = $client->SalePaymentRequest($body);
            $output = [
                'Status' => ($json->SalePaymentRequestResult->Status ?? null),
                'Token' => ($json->SalePaymentRequestResult->Token ?? null),
                'Message' => ($json->SalePaymentRequestResult->Message ?? ''),
            ];

            if ($output['Status'] == '0' and (int)$output['Token'] > 0) {

                return $this->success(
                    [
                        'authority' => $output['Token'],
                        'redirect' => 'https://pec.shaparak.ir/NewIPG/?Token=' . $output['Token']
                    ],
                    [
                        'url' => $url,
                        'body' => $body,
                        'response' => $json,
                        'header' => array_merge($headers, $soapMethod),
                    ],
                    Utility::soap_status_code($client)
                );
            }

            // Error
            $error_messages = (!empty($output['Message']) ? $output['Message'] : '');
            return $this->error(
                $error_messages,
                [
                    'url' => $url,
                    'body' => $body,
                    'response' => $json,
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
        // Check Empty Token
        if (empty($args['Token'])) {
            return $this->error();
        }

        // Setup Body
        $body = [
            "requestData" => [
                'LoginAccount' => $args['LoginAccount'],
                'Token' => $args['Token'],
            ]
        ];

        // Setup Headers
        $headers = [
            'soap_version' => 'SOAP_1_1',
            'cache_wsdl' => WSDL_CACHE_NONE,
            'encoding' => 'UTF-8',
            'trace' => 1
        ];

        // Soap Method
        $soapMethod = ['X-Soap-Method' => 'ConfirmPayment'];

        // Setup Request args
        $url = 'https://pec.shaparak.ir/NewIPGServices/Confirm/ConfirmService.asmx?WSDL';
        try {

            $client = new \SoapClient($url, $headers);
            $json = $client->ConfirmPayment($body);
            $output = [
                'Status' => ($json->ConfirmPaymentResult->Status ?? null),
                'RRN' => ($json->ConfirmPaymentResult->RRN ?? null)
            ];

            if ($output['Status'] == '0' and (int)$output['RRN'] > 0) {

                return $this->success(
                    [
                        'transaction_id' => $output['RRN']
                    ],
                    [
                        'url' => $url,
                        'body' => $body,
                        'response' => $json,
                        'header' => array_merge($headers, $soapMethod),
                    ],
                    Utility::soap_status_code($client)
                );
            }

            // Error
            $error_messages = __('Error Code: ', 'parsigate') . ($output['Status'] ?? '');
            return $this->error(
                $error_messages,
                [
                    'url' => $url,
                    'body' => $body,
                    'response' => $json,
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

}