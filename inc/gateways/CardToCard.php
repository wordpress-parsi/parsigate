<?php

namespace ParsiGate\gateways;

class CardToCard extends Base
{

    public function pay(array $args = []): array
    {

        return $this->success(
            [
                'authority' => $args['authority'],
                'redirect' => $args['redirect']
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
                'transaction_id' => $args['transaction_id'],
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