<?php

namespace ParsiGate;

class Gateway
{
    public string $driver;

    public array $args;

    public array $gateway;

    public function __construct($driver, $args = [])
    {
        // Setup Variable
        $this->driver = strtolower($driver);
        $this->args = $args;

        // Check Log
        if (!isset($this->args['log'])) {
            $this->args['log'] = Option::enable_log();
        }

        // Setup Gateway
        $this->gateway = Gateways::get($this->driver);
    }

    public function exist(): bool
    {
        if ($this->gateway === false) {
            return false;
        }

        $class = $this->gateway['class'];
        if (!class_exists($class)) {
            return false;
        }

        return true;
    }

    public function save_log($response)
    {
        if (!$this->args['log']) {
            return false;
        }

        if (isset($response['request']['body']) and !empty($response['request']['body'])) {

            $class = $this->gateway['class'];
            $array = apply_filters('parsigate_gateways_types', [
                'gateway' => $this->driver,
                'url' => $response['request']['url'],
                'type' => (property_exists($class, 'type') ? $class::$type : '1'),
                'code' => $response['status_code'],
                'header' => $response['request']['header'],
                'body' => $response['request']['body'],
                'response' => $response['request']['response'],
                'meta' => [],
                'created_at' => current_time('mysql')
            ], $this->gateway, $response);
            return \ParsiGate\CustomTable\Log::insert($array);
        }

        return false;
    }

    public function pay(array $args = []): array
    {
        return $this->call('pay', $args);
    }

    public function verify(array $args = []): array
    {
        return $this->call('verify', $args);
    }

    public function call($method, $args = [], $log = true)
    {
        if (!$this->exist()) {

            return [
                'success' => false,
                'message' => __('Gateway class not found', 'parsigate'),
            ];
        }

        $class = new $this->gateway['class'];
        $run = $class->{$method}($args);
        if ($log) {
            $this->save_log($run);
        }

        return $run;
    }
}