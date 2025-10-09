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

        // Setup Curl
        $process = $this->curl('request', $body, $isSandBox);

        // Check Error
        if (!$process['status']) {

            return $this->error(
                $process['message'],
                [
                    'url' => $process['url'],
                    'body' => $body,
                    'response' => (array)$process['message'],
                    'header' => [],
                ],
                $process['status_code']
            );
        }

        // Save Transaction Authority
        /**
         * {
         * "data": {
         * "authority": "S000000000000000000000000000000rzj2w",
         * "fee": 1000,
         * "fee_type": "Merchant",
         * "code": 100,
         * "message": "Success"
         * },
         * "errors": []
         * }
         */
        $Authority = $process['data']['data']['authority'];

        // Setup Url
        $url = ($isSandBox === true ? 'https://sandbox.zarinpal.com/pg/StartPay/%s/' : 'https://www.zarinpal.com/pg/StartPay/%s/ZarinGate');

        // return
        return $this->success(
            [
                'authority' => $Authority,
                'redirect' => sprintf($url, $Authority)
            ],
            [
                'url' => $process['url'],
                'body' => $body,
                'response' => $process['data'],
                'header' => [],
            ],
            $process['status_code']
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

        // Curl
        $process = $this->curl('verify', $body, $isSandBox);

        // Check Error
        if (!$process['status']) {

            return $this->error(
                $process['message'],
                [
                    'url' => $process['url'],
                    'body' => $body,
                    'response' => (array)$process['message'],
                    'header' => [],
                ],
                $process['status_code']
            );
        }

        // Setup Completed
        # {"code":100,"ref_id":"1053981", "card_pan": ""}
        $Status = $process['data']['data']['code'];
        $RefID = $process['data']['data']['ref_id'];
        if ($Status == 101 || ($Status == 100 and !empty($RefID))) {

            return $this->success(
                [
                    'transaction_id' => $RefID
                ],
                [
                    'url' => $process['url'],
                    'body' => $body,
                    'response' => $process['data'],
                    'header' => [],
                ],
                $process['status_code']
            );
        }

        return $this->error();
    }

    private function curl($action, $params, $is_sandbox = false): array
    {
        $url = 'https://api.zarinpal.com/pg/v4/payment/' . $action . '.json';
        if ($is_sandbox) {
            $url = 'https://sandbox.zarinpal.com/pg/v4/payment/' . $action . '.json';
        }

        $ch = curl_init($url);
        $json = json_encode($params);
        curl_setopt($ch, CURLOPT_USERAGENT, 'ZarinPal Rest Api v1');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($json)
        ));
        $result = curl_exec($ch);
        $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if (curl_errno($ch)) {
            $get_error_message = curl_error($ch);
        }
        curl_close($ch);

        if (isset($get_error_message)) {

            return [
                'status' => false,
                'url' => $url,
                'status_code' => $status_code,
                'message' => $get_error_message
            ];
        }

        $result = json_decode($result, true);
        if (!empty($result['errors'])) {

            return [
                'status' => false,
                'url' => $url,
                'status_code' => $status_code,
                'code' => $result['errors']['code'],
                'message' => $result['errors']['message']
            ];
        }

        // Return Success
        return [
            'status' => true,
            'url' => $url,
            'data' => $result,
            'status_code' => $status_code
        ];
    }
}