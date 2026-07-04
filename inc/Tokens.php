<?php

namespace ParsiGate;

class Tokens
{

    public string $gateway_id;

    private string $option_name;

    public function __construct($gateway_id)
    {
        $this->gateway_id = $gateway_id;
        $this->option_name = 'pg_gateway_tokens';
    }

    public function all()
    {
        $all_tokens = get_option($this->option_name, []);
        if (empty($all_tokens) || !is_array($all_tokens)) {
            $all_tokens = [];
        }

        return $all_tokens;
    }

    public function store($token, $expiry = null)
    {
        $all_tokens = $this->all();
        $all_tokens[$this->gateway_id] = [
            'token' => $token,
            'expiry' => time() + ($expiry - 30),
            'created_at' => time(),
            'gateway_id' => $this->gateway_id
        ];

        return update_option($this->option_name, $all_tokens, 'no');
    }

    public function get()
    {
        $all_tokens = $this->all();
        return $all_tokens[$this->gateway_id] ?? null;
    }

    public function get_value()
    {
        $token_data = $this->get();
        return $token_data['token'] ?? null;
    }

    public function is_valid()
    {
        $token_data = $this->get();

        if (!$token_data) {
            return false;
        }

        if (isset($token_data['expiry']) && $token_data['expiry'] && time() > $token_data['expiry']) {
            $this->delete();
            return false;
        }

        return true;
    }

    public function delete()
    {
        $all_tokens = $this->all();
        if (isset($all_tokens[$this->gateway_id])) {
            unset($all_tokens[$this->gateway_id]);
            return update_option($this->option_name, $all_tokens, 'no');
        }

        return false;
    }
}