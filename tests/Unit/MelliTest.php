<?php

use PHPUnit\Framework\TestCase;
use ParsiGate\gateways\Melli;

require_once __DIR__ . '/../../inc/gateways/Melli.php';

final class MelliTest extends TestCase
{
    private function verify($response, string $callbackCode = '0'): array
    {
        $GLOBALS['parsigate_http_response'] = $response;

        return (new Melli())->verify([
            'ResCode' => $callbackCode,
            'Token' => 'test-token',
            'Key' => base64_encode('123456789012345678901234'),
        ]);
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['parsigate_http_response']);
    }

    public static function invalidResponses(): array
    {
        return [
            'html-502' => [502, '<html>Bad Gateway</html>'],
            'empty-503' => [503, ''],
            'json-null' => [200, 'null'],
            'json-string' => [200, '"unavailable"'],
            'json-number' => [200, '123'],
            'json-boolean' => [200, 'false'],
            'missing-code' => [200, '{}'],
            'missing-trace' => [200, '{"ResCode":0}'],
        ];
    }

    /** @dataProvider invalidResponses */
    public function test_verify_rejects_invalid_response(int $code, string $body): void
    {
        $result = $this->verify(['response' => ['code' => $code], 'body' => $body]);

        $this->assertFalse($result['success']);
        $this->assertNull($result['data']);
        $this->assertSame($code, $result['status_code']);
    }

    public function test_verify_success(): void
    {
        $result = $this->verify([
            'response' => ['code' => 200],
            'body' => '{"ResCode":0,"SystemTraceNo":"123456"}',
        ]);

        $this->assertTrue($result['success']);
        $this->assertSame('123456', $result['data']['transaction_id']);
    }

    public function test_verify_decline(): void
    {
        $result = $this->verify([
            'response' => ['code' => 200],
            'body' => '{"ResCode":101,"Description":"Synthetic decline"}',
        ]);

        $this->assertFalse($result['success']);
        $this->assertSame('Synthetic decline', $result['message']);
    }

    public function test_verify_empty_array(): void
    {
        $result = $this->verify(['response' => ['code' => 200], 'body' => '[]']);

        $this->assertFalse($result['success']);
        $this->assertNull($result['data']);
    }

    public function test_verify_cancelled_callback(): void
    {
        $result = $this->verify(new WP_Error('Unexpected HTTP request'), '1');

        $this->assertFalse($result['success']);
        $this->assertSame('', $result['message']);
    }
}
