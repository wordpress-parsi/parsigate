<?php

use PHPUnit\Framework\TestCase;
use ParsiGate\CustomTable\Log;

class ParsiGateLogTestDatabase
{
    public $prefix = 'wp_';
    public $last_error = '';
    public $insert_id = 1;
    public $inserted = [];

    public function insert($table, $row)
    {
        $this->inserted = $row;
        return 1;
    }

    public function update($table, $row, $where)
    {
        $this->inserted = array_replace($this->inserted, $row);
        return 1;
    }

    public function prepare($sql, ...$args)
    {
        return vsprintf($sql, $args);
    }

    public function get_row($query, $output)
    {
        return $this->inserted;
    }
}

if (!function_exists('wp_parse_args')) {
    function wp_parse_args($args, $defaults = [])
    {
        return array_merge($defaults, $args);
    }
}
if (!function_exists('apply_filters')) {
    function apply_filters($name, $value, ...$args)
    {
        if (isset($GLOBALS['log_redaction_filter']) &&
            $name === 'parsigate_insert_data_wp_pg_log') {
            return ($GLOBALS['log_redaction_filter'])($value);
        }
        if (isset($GLOBALS['log_redaction_update_filter']) &&
            $name === 'parsigate_update_data_wp_pg_log') {
            return ($GLOBALS['log_redaction_update_filter'])($value);
        }
        return $value;
    }
}
if (!function_exists('add_filter')) {
    function add_filter(...$args)
    {
        return true;
    }
}
if (!function_exists('do_action')) {
    function do_action(...$args) {}
}
if (!defined('ARRAY_A')) {
    define('ARRAY_A', 'ARRAY_A');
}
if (!function_exists('esc_sql')) {
    function esc_sql($value) { return $value; }
}
if (!function_exists('wp_cache_get')) {
    function wp_cache_get($id, $group) { return false; }
}
if (!function_exists('wp_cache_set')) {
    function wp_cache_set($id, $value, $group) { return true; }
}
if (!function_exists('wp_cache_delete')) {
    function wp_cache_delete($id, $group) { return true; }
}

$GLOBALS['wpdb'] = new ParsiGateLogTestDatabase();

require_once __DIR__ . '/../../inc/custom-table/Base.php';
require_once __DIR__ . '/../../inc/custom-table/pg-log/Log.php';

final class LogRedactionTest extends TestCase
{
    protected function setUp(): void
    {
        $GLOBALS['wpdb'] = new ParsiGateLogTestDatabase();
        unset($GLOBALS['log_redaction_filter'], $GLOBALS['log_redaction_update_filter']);
    }

    private function inserted(): array
    {
        return $GLOBALS['wpdb']->inserted;
    }

    public function test_jibit_credentials_and_bearer_are_absent_from_database_insert(): void
    {
        $result = Log::insert([
            'gateway' => 'jibit',
            'url' => 'https://napi.jibit.ir/ppg/v3/tokens',
            'body' => ['apiKey' => 'jibit-api-123456', 'secretKey' => 'jibit-secret-123456'],
            'header' => ['Authorization' => 'Bearer very-secret-bearer', 'Content-Type' => 'application/json'],
            'response' => ['accessToken' => 'response-access-123456', 'status' => 'OK'],
        ]);

        $row = $this->inserted();
        $this->assertTrue($result['status']);
        $this->assertSame('[REDACTED]', json_decode($row['body'], true)['apiKey']);
        $this->assertSame('[REDACTED]', json_decode($row['header'], true)['Authorization']);
        $this->assertSame('OK', json_decode($row['response'], true)['status']);
        foreach (['jibit-api-123456', 'jibit-secret-123456', 'very-secret-bearer', 'response-access-123456'] as $secret) {
            $this->assertStringNotContainsString($secret, json_encode($row));
        }
    }

    public function test_mellat_password_and_plaintext_xml_are_redacted(): void
    {
        Log::insert([
            'gateway' => 'mellat',
            'body' => ['userPassword' => 'mellat-password-1234', 'orderId' => 234],
            'response' => '<soap:userPassword>soap-pass-2345</soap:userPassword>',
        ]);
        $row = $this->inserted();
        $this->assertSame(234, json_decode($row['body'], true)['orderId']);
        $this->assertStringNotContainsString('mellat-password-1234', json_encode($row));
        $this->assertStringNotContainsString('soap-pass-2345', json_encode($row));
    }

    public function test_payping_and_zarinplus_tokens_do_not_reach_storage(): void
    {
        foreach (['payping', 'zarinplus'] as $gateway) {
            Log::insert([
                'gateway' => $gateway,
                'body' => ['token' => $gateway . '-token-12345', 'amount' => 25000],
                'header' => ['Authorization' => 'Bearer ' . $gateway . '-bearer-12345'],
            ]);
            $row = $this->inserted();
            $this->assertSame(25000, json_decode($row['body'], true)['amount']);
            $this->assertStringNotContainsString($gateway . '-token-12345', json_encode($row));
            $this->assertStringNotContainsString($gateway . '-bearer-12345', json_encode($row));
        }
    }

    public function test_redaction_runs_after_insert_filters(): void
    {
        $GLOBALS['log_redaction_filter'] = static function ($row) {
            $row['header']['X-API-Key'] = 'injected-secret-56789';
            return $row;
        };
        Log::insert(['gateway' => 'jibit']);
        $this->assertStringNotContainsString('injected-secret-56789', json_encode($this->inserted()));
    }

    public function test_url_query_and_json_encoded_body_are_redacted(): void
    {
        Log::insert([
            'url' => 'https://example.test/request?api_key=query-secret-3333&amount=10',
            'body' => '{"api_key":"json-secret-4444","amount":10}',
            'response' => 'Authorization: Bearer text-secret-5555',
        ]);
        $row = $this->inserted();
        foreach (['query-secret-3333', 'json-secret-4444', 'text-secret-5555'] as $secret) {
            $this->assertStringNotContainsString($secret, json_encode($row));
        }
        $this->assertSame(10, json_decode($row['body'], true)['amount']);
        $this->assertStringContainsString('amount=10', $row['url']);
    }

    public function test_input_is_not_mutated_and_normal_fields_remain(): void
    {
        $original = [
            'gateway' => 'vandar',
            'type' => 1,
            'code' => 200,
            'body' => ['api_key' => 'key-123456789', 'amount' => 12000],
        ];
        Log::insert($original);
        $row = $this->inserted();
        $this->assertSame('key-123456789', $original['body']['api_key']);
        $this->assertSame('vandar', $row['gateway']);
        $this->assertSame(200, $row['code']);
        $this->assertSame(12000, json_decode($row['body'], true)['amount']);
        $this->assertSame('[REDACTED]', json_decode($row['body'], true)['api_key']);
    }

    public function test_update_redacts_secrets_and_preserves_json_fields(): void
    {
        Log::insert(['gateway' => 'jibit', 'body' => ['amount' => 12000]]);

        $updated = Log::update(1, [
            'body' => ['apiKey' => 'update-api-secret', 'amount' => 13000],
            'header' => ['Authorization' => 'Bearer update-bearer-secret'],
            'response' => ['accessToken' => 'update-access-secret', 'status' => 'OK'],
        ], false);

        $this->assertTrue($updated['status']);
        $row = $this->inserted();
        $body = json_decode($row['body'], true);
        $header = json_decode($row['header'], true);
        $response = json_decode($row['response'], true);
        $this->assertSame(JSON_ERROR_NONE, json_last_error());
        $this->assertSame(13000, $body['amount']);
        $this->assertSame('[REDACTED]', $body['apiKey']);
        $this->assertSame('[REDACTED]', $header['Authorization']);
        $this->assertSame('[REDACTED]', $response['accessToken']);
        $this->assertSame('OK', $response['status']);
        foreach (['update-api-secret', 'update-bearer-secret', 'update-access-secret'] as $secret) {
            $this->assertStringNotContainsString($secret, json_encode($row));
        }
    }

    public function test_soap_with_attributes_does_not_expose_password(): void
    {
        Log::insert([
            'gateway' => 'mellat',
            'response' => '<soap:userPassword xsi:type="xsd:string">secret-soap-attr</soap:userPassword>',
        ]);
        $row = $this->inserted();
        $this->assertStringNotContainsString('secret-soap-attr', $row['response']);
        $this->assertStringContainsString('[REDACTED]', $row['response']);
    }

    public function test_error_text_containing_json_fragment_does_not_expose_key(): void
    {
        Log::insert([
            'gateway' => 'jibit',
            'response' => 'Provider error: {"apiKey":"secret-json-embedded","status":"FAIL"}',
        ]);
        $row = $this->inserted();
        $this->assertStringNotContainsString('secret-json-embedded', $row['response']);
        $this->assertStringContainsString('FAIL', $row['response']);
    }

    public function test_encoded_query_key_does_not_expose_secret(): void
    {
        Log::insert([
            'gateway' => 'vandar',
            'url' => 'https://example.invalid/?api%5Fkey=secret-encoded-query&amount=12500',
        ]);
        $row = $this->inserted();
        $this->assertStringNotContainsString('secret-encoded-query', $row['url']);
        $this->assertStringContainsString('amount=12500', $row['url']);
    }

    public function test_update_redacts_data_injected_by_late_filter(): void
    {
        Log::insert(['gateway' => 'payping', 'body' => ['amount' => 1000]]);
        $GLOBALS['log_redaction_update_filter'] = static function ($row) {
            $row['header'] = wp_json_encode(['X-API-Key' => 'late-update-secret']);
            $row['url'] = 'https://example.test/?api_key=late-query-secret&amount=1000';
            $row['response'] = ['accessToken' => 'late-response-secret', 'status' => 'FAILED'];
            return $row;
        };

        Log::update(1, ['body' => ['amount' => 1500]], false);
        $row = $this->inserted();
        $this->assertSame('[REDACTED]', json_decode($row['header'], true)['X-API-Key']);
        $this->assertSame('[REDACTED]', json_decode($row['response'], true)['accessToken']);
        $this->assertSame('FAILED', json_decode($row['response'], true)['status']);
        $this->assertSame(1500, json_decode($row['body'], true)['amount']);
        $this->assertStringContainsString('amount=1000', $row['url']);
        foreach (['late-update-secret', 'late-query-secret', 'late-response-secret'] as $secret) {
            $this->assertStringNotContainsString($secret, json_encode($row));
        }
    }

    public function test_update_redacts_json_embedded_in_encoded_error_string(): void
    {
        Log::insert(['gateway' => 'jibit', 'response' => ['status' => 'PENDING']]);
        $message = 'Provider error: {"apiKey":"secret-update-escaped-123","status":"FAIL"}';

        Log::update(1, ['response' => $message], false);
        $row = $this->inserted();
        $decoded = json_decode($row['response'], true);

        $this->assertSame(JSON_ERROR_NONE, json_last_error());
        $this->assertSame(
            'Provider error: {"apiKey":"[REDACTED]","status":"FAIL"}',
            $decoded
        );
        $this->assertStringNotContainsString('secret-update-escaped-123', json_encode($row));
    }

    public function test_update_late_filter_redacts_escaped_scalar_json_and_retains_other_fields(): void
    {
        Log::insert(['gateway' => 'payping', 'body' => ['amount' => 1000]]);
        $GLOBALS['log_redaction_update_filter'] = static function ($data) {
            $data['response'] = wp_json_encode('Provider error: {"apiKey":"secret-filter-escaped-456"}');
            $data['body'] = wp_json_encode(['api_key' => 'secret-filter-789', 'amount' => 17000]);
            return $data;
        };

        Log::update(1, ['body' => ['amount' => 1500]], false);
        $row = $this->inserted();
        $response = json_decode($row['response'], true);
        $body = json_decode($row['body'], true);

        $this->assertSame(JSON_ERROR_NONE, json_last_error());
        $this->assertSame('Provider error: {"apiKey":"[REDACTED]"}', $response);
        $this->assertSame('[REDACTED]', $body['api_key']);
        $this->assertSame(17000, $body['amount']);
        $this->assertStringNotContainsString('secret-filter-escaped-456', json_encode($row));
        $this->assertStringNotContainsString('secret-filter-789', json_encode($row));
    }

    public function test_insert_redacts_double_encoded_json_error_string(): void
    {
        $message = 'Provider error: {"apiKey":"double-json-secret-123","status":"FAIL"}';
        Log::insert([
            'gateway' => 'jibit',
            'response' => wp_json_encode($message),
        ]);

        $row = $this->inserted();
        $encodedMessage = json_decode($row['response'], true);
        $decodedMessage = json_decode($encodedMessage, true);
        $this->assertSame(JSON_ERROR_NONE, json_last_error());
        $this->assertIsString($decodedMessage);
        $this->assertStringNotContainsString('double-json-secret-123', json_encode($row));
        $this->assertStringContainsString('"apiKey":"[REDACTED]"', $decodedMessage);
        $this->assertStringContainsString('"status":"FAIL"', $decodedMessage);
    }

    public function test_insert_redacts_raw_http_header_credentials(): void
    {
        Log::insert([
            'gateway' => 'payping',
            'header' => "X-Auth-Token: synthetic-header-secret\nSet-Cookie: session=synthetic-cookie-secret",
        ]);
        $row = $this->inserted();
        $this->assertStringNotContainsString('synthetic-header-secret', $row['header']);
        $this->assertStringNotContainsString('synthetic-cookie-secret', $row['header']);
    }

    public function test_insert_masks_entire_quoted_secret_with_spaces(): void
    {
        Log::insert([
            'response' => 'Provider error: {"apiKey": "secret with several words", "status":"FAIL"}',
        ]);
        $row = $this->inserted();
        $this->assertStringNotContainsString('secret with several words', $row['response']);
        $this->assertStringNotContainsString('several words', $row['response']);
        $this->assertStringContainsString('[REDACTED]', $row['response']);
        $this->assertStringContainsString('FAIL', $row['response']);
    }

    public function test_insert_redacts_private_key_text_and_url_userinfo(): void
    {
        Log::insert([
            'url' => 'https://synthetic-user:synthetic-pass@api.example.test/?amount=125',
            'response' => 'privateKey=synthetic-private-key',
        ]);
        $row = $this->inserted();
        $this->assertStringNotContainsString('synthetic-pass', $row['url']);
        $this->assertStringNotContainsString('synthetic-private-key', $row['response']);
        $this->assertStringContainsString('amount=125', $row['url']);
    }


    public function test_insert_redacts_escaped_json_fragment_in_plain_error(): void
    {
        Log::insert([
            'response' => 'Provider error: {\\"apiKey\\":\\"escaped-fragment-secret\\",\\"status\\":\\"FAIL\\"}',
        ]);
        $row = $this->inserted();
        $this->assertStringNotContainsString('escaped-fragment-secret', $row['response']);
        $this->assertStringContainsString('[REDACTED]', $row['response']);
        $this->assertStringContainsString('FAIL', $row['response']);
    }


    public function test_insert_redacts_extended_secret_header_names_in_arrays(): void
    {
        $secrets = [
            'X-Access-Key' => 'synthetic-access-key-491',
            'X-Signature' => 'synthetic-signature-492',
            'X-Merchant-Key' => 'synthetic-merchant-key-493',
            'session_key' => 'synthetic-session-key-494',
        ];

        Log::insert([
            'gateway' => 'payping',
            'header' => $secrets + ['Content-Type' => 'application/json'],
            'body' => ['amount' => 19000],
        ]);
        $row = $this->inserted();
        $headers = json_decode($row['header'], true);
        foreach ($secrets as $key => $secret) {
            $this->assertSame('[REDACTED]', $headers[$key]);
            $this->assertStringNotContainsString($secret, json_encode($row));
        }
        $this->assertSame('application/json', $headers['Content-Type']);
        $this->assertSame(19000, json_decode($row['body'], true)['amount']);
    }

    public function test_raw_header_variants_are_redacted_for_insert_and_update(): void
    {
        $secrets = [
            'synthetic-raw-access-501',
            'synthetic-raw-signature-502',
            'synthetic-raw-merchant-503',
            'synthetic-raw-api-token-504',
            'synthetic-raw-auth-token-505',
            'synthetic-raw-session-506',
        ];
        $raw = "X-Access-Key: {$secrets[0]}\r\n"
            . "X-Signature: {$secrets[1]}\r\n"
            . "X-Merchant-Key: {$secrets[2]}\r\n"
            . "Api-Token: {$secrets[3]}\r\n"
            . "auth_token={$secrets[4]}\r\n"
            . "session_key={$secrets[5]}\r\n"
            . 'Content-Type: application/json';

        Log::insert(['gateway' => 'jibit', 'header' => $raw, 'body' => ['amount' => 4000]]);
        foreach (['insert', 'update'] as $operation) {
            if ($operation === 'update') {
                Log::update(1, ['header' => $raw], false);
            }
            $row = $this->inserted();
            foreach ($secrets as $secret) {
                $this->assertStringNotContainsString($secret, json_encode($row));
            }
            $decodedHeader = json_decode($row['header'], true);
            $this->assertSame(JSON_ERROR_NONE, json_last_error());
            $this->assertIsString($decodedHeader);
            $this->assertStringContainsString('Content-Type: application/json', $decodedHeader);
            $this->assertSame(4000, json_decode($row['body'], true)['amount']);
        }
    }

}
