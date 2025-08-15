<?php

namespace ParsiGate;

class Gateway
{
    public string $driver;

    public array $args;

    public function __construct($driver, $args = [])
    {
        $this->driver = $driver;
        $this->args = $args;
    }
}