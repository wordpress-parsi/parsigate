<?php

namespace ParsiGate\gateways;

class Melli extends Base
{
    public function pay(array $args = []): array
    {
        return $this->success(
            [
                'ref_id' => '',
                'redirect' => ''
            ],
            [
                'url' => '',
                'body' => [],
                'response' => [],
                'header' => [],
            ],
            200
        );
    }

    public function verify(array $args = []): array
    {
        return $this->success(
            [
                'transaction_id' => '',
            ],
            [
                'url' => '',
                'body' => [],
                'response' => [],
                'header' => [],
            ],
            200
        );
    }
}