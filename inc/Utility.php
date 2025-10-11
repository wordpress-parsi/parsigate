<?php

namespace ParsiGate;

class Utility
{

    public static function is_enable_open_ssl(): bool
    {
        return (extension_loaded('openssl') and function_exists('OpenSSL_encrypt'));
    }

}
