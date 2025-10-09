<?php

namespace ParsiGate\Gateways;

abstract class Base
{
    // @see \ParsiGate\CustomTable\Log::get_type_list
    public static int $type = 1;

    abstract public function pay(array $args = []): array;

    abstract public function verify(array $args = []): array;

    protected function success($data, array $request, int $code): array
    {
        return [
            'success' => true,
            'request' => [
                'url' => ($request['url'] ?? ''),
                'body' => ($request['body'] ?? ''),
                'response' => ($request['response'] ?? ''),
                'header' => ($request['header'] ?? ''),
            ],
            'status_code' => $code,
            'data' => (array)$data,
            'message' => (is_string($data) ? $data : ''),
            'errors' => null,
            'datetime' => current_time('mysql')
        ];
    }

    protected function error($errors = [], array $request = [], int $code = 0): array
    {
        return [
            'success' => false,
            'request' => [
                'url' => ($request['url'] ?? ''),
                'body' => ($request['body'] ?? ''),
                'response' => ($request['response'] ?? ''),
                'header' => ($request['header'] ?? ''),
            ],
            'status_code' => $code,
            'data' => null,
            'errors' => $errors,
            'message' => (is_string($errors) ? $errors : ''),
            'datetime' => current_time('mysql')
        ];
    }
}
