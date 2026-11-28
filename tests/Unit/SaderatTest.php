<?php

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ParsiGate\gateways\Saderat;

require_once __DIR__ . '/../../inc/gateways/Saderat.php';

final class SaderatTest extends TestCase
{
    protected function setUp(): void
    {
        set_error_handler(static function ($severity, $message, $file, $line) {
            throw new ErrorException($message, 0, $severity, $file, $line);
        }, E_WARNING);
    }

    protected function tearDown(): void
    {
        restore_error_handler();
        unset($GLOBALS['parsigate_http_response']);
    }

    public static function errorCodes(): iterable
    {
        foreach (['get_error_message', 'get_verify_error_message'] as $method) {
            foreach ([0, -999, 999, 'NEW_CODE'] as $code) {
                yield $method . '-' . $code => [$method, $code, 'Error Code: ' . $code];
            }
        }
        yield 'known-pay' => ['get_error_message', -1, 'Error Code: -1 (تراکنش پیدا نشد.)'];
        yield 'known-verify' => ['get_verify_error_message', '-1', 'Error Code: -1 (تراکنش پیدا نشد)'];
    }

    #[DataProvider('errorCodes')]
    public function test_error_message(string $method, $code, string $expected): void
    {
        $this->assertSame($expected, (new Saderat())->$method($code));
    }

    public static function payErrors(): iterable
    {
        yield 'unknown-decline' => [-999, 'Error Code: -999'];
        yield 'missing-token' => [0, 'Error Code: 0'];
        yield 'known-decline' => [-1, 'Error Code: -1 (تراکنش پیدا نشد.)'];
    }

    #[DataProvider('payErrors')]
    public function test_pay_error($code, string $expected): void
    {
        $GLOBALS['parsigate_http_response'] = [
            'response' => ['code' => 200],
            'body' => json_encode(['Status' => $code]),
        ];
        $result = (new Saderat())->pay([
            'Amount' => 10000,
            'callbackURL' => 'https://example.test/callback',
            'InvoiceID' => '100',
            'TerminalID' => 'test-terminal',
            'Payload' => 'test-payload',
        ]);
        $this->assertFalse($result['success']);
        $this->assertSame($expected, $result['message']);
        $this->assertNull($result['data']);
    }

    public static function callbackErrors(): iterable
    {
        yield 'unknown-callback' => ['-999', 'Error Code: -999'];
        yield 'known-callback' => ['-1', 'Error Code: -1 (تراکنش پیدا نشد)'];
    }

    #[DataProvider('callbackErrors')]
    public function test_failed_callback(string $code, string $expected): void
    {
        $GLOBALS['parsigate_http_response'] = new WP_Error('Unexpected HTTP request');
        $result = (new Saderat())->verify(['respcode' => $code]);
        $this->assertFalse($result['success']);
        $this->assertSame($expected, $result['message']);
        $this->assertNull($result['data']);
    }
}
