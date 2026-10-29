<?php

use PHPUnit\Framework\TestCase;
use ParsiGate\gateways\Vandar;

final class VandarTest extends TestCase
{
    private function callbackUrl(): string
    {
        return 'https' . '://' . 'example.test/callback';
    }

    public function test_payment_success(): void
    {
        $GLOBALS['parsigate_http_response'] = [
            'response' => ['code' => 200],
            'body' => '{"status":1,"token":"test-token"}',
        ];

        $result = (new Vandar())->pay([
            'api_key' => 'secret-key',
            'amount' => 10000,
            'callback_url' => $this->callbackUrl(),
            'factorNumber' => '123',
        ]);

        $this->assertTrue($result['success']);
        $this->assertSame('test-token', $result['data']['authority']);
        $this->assertSame(
            'https' . '://' . 'ipg.vandar.io/v4/test-token',
            $result['data']['redirect']
        );
        $this->assertSame('***', $result['request']['body']['api_key']);
    }

    public function test_payment_network_error(): void
    {
        $GLOBALS['parsigate_http_response'] = new WP_Error('Connection failed');

        $result = (new Vandar())->pay([
            'api_key' => 'secret-key',
            'amount' => 10000,
            'callback_url' => $this->callbackUrl(),
            'factorNumber' => '123',
        ]);

        $this->assertFalse($result['success']);
        $this->assertSame('Connection failed', $result['message']);
    }

    public function test_payment_rejects_invalid_json(): void
    {
        $GLOBALS['parsigate_http_response'] = [
            'response' => ['code' => 200],
            'body' => 'not-json',
        ];

        $result = (new Vandar())->pay([
            'api_key' => 'secret-key',
            'amount' => 10000,
            'callback_url' => $this->callbackUrl(),
            'factorNumber' => '123',
        ]);

        $this->assertFalse($result['success']);
        $this->assertSame('Invalid response from Vandar.', $result['message']);
    }

    public function test_payment_rejects_response_without_token(): void
    {
        $GLOBALS['parsigate_http_response'] = [
            'response' => ['code' => 200],
            'body' => '{"status":1}',
        ];

        $result = (new Vandar())->pay([
            'api_key' => 'secret-key',
            'amount' => 10000,
            'callback_url' => $this->callbackUrl(),
            'factorNumber' => '123',
        ]);

        $this->assertFalse($result['success']);
    }

    public function test_verify_success(): void
    {
        $GLOBALS['parsigate_http_response'] = [
            'response' => ['code' => 200],
            'body' => '{"status":1,"amount":"10000","realAmount":10000,"transId":123456,"factorNumber":null}',
        ];

        $result = (new Vandar())->verify([
            'api_key' => 'secret-key',
            'token' => 'test-token',
            'payment_status' => 'OK',
            'amount' => 10000,
            'order_id' => '123',
        ]);

        $this->assertTrue($result['success']);
        $this->assertSame('123456', $result['data']['transaction_id']);
        $this->assertSame(10000, (int)$result['data']['paid_amount']);
        $this->assertSame('***', $result['request']['body']['api_key']);
    }

    public function test_verify_rejects_failed_callback_without_http_request(): void
    {
        $GLOBALS['parsigate_http_response'] = new WP_Error('Should not be called');

        $result = (new Vandar())->verify([
            'api_key' => 'secret-key',
            'token' => 'test-token',
            'payment_status' => 'FAILED',
            'amount' => 10000,
            'order_id' => '123',
        ]);

        $this->assertFalse($result['success']);
        $this->assertSame(
            'Vandar payment was cancelled or failed.',
            $result['message']
        );
    }

    public function test_verify_rejects_network_error(): void
    {
        $GLOBALS['parsigate_http_response'] = new WP_Error('Verify connection failed');

        $result = (new Vandar())->verify([
            'api_key' => 'secret-key',
            'token' => 'test-token',
            'payment_status' => 'OK',
            'amount' => 10000,
            'order_id' => '123',
        ]);

        $this->assertFalse($result['success']);
        $this->assertSame('Verify connection failed', $result['message']);
    }

    public function test_verify_rejects_invalid_json(): void
    {
        $GLOBALS['parsigate_http_response'] = [
            'response' => ['code' => 200],
            'body' => 'not-json',
        ];

        $result = (new Vandar())->verify([
            'api_key' => 'secret-key',
            'token' => 'test-token',
            'payment_status' => 'OK',
            'amount' => 10000,
            'order_id' => '123',
        ]);

        $this->assertFalse($result['success']);
        $this->assertSame('Invalid response from Vandar.', $result['message']);
    }

    public function test_verify_rejects_lower_paid_amount(): void
    {
        $GLOBALS['parsigate_http_response'] = [
            'response' => ['code' => 200],
            'body' => '{"status":1,"amount":"9000","realAmount":9000,"transId":123456}',
        ];

        $result = (new Vandar())->verify([
            'api_key' => 'secret-key',
            'token' => 'test-token',
            'payment_status' => 'OK',
            'amount' => 10000,
            'order_id' => '123',
        ]);

        $this->assertFalse($result['success']);
        $this->assertSame(
            'Vandar payment amount is invalid.',
            $result['message']
        );
    }

    public function test_verify_rejects_missing_transaction_id(): void
    {
        $GLOBALS['parsigate_http_response'] = [
            'response' => ['code' => 200],
            'body' => '{"status":1,"amount":"10000"}',
        ];

        $result = (new Vandar())->verify([
            'api_key' => 'secret-key',
            'token' => 'test-token',
            'payment_status' => 'OK',
            'amount' => 10000,
            'order_id' => '123',
        ]);

        $this->assertFalse($result['success']);
    }
}
