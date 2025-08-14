<?php

namespace ParsiGate;

use ParsiGate\Gateways\ZarinPal;

class Gateways
{
    public function __construct()
    {
    }

    public static function list()
    {
        $list = [
            'zarinpal' => [
                'title' => __('ZarinPal', 'parsigate'),
                'class' => Zarinpal::class,
                'enable' => self::enable('zarinpal')
            ]
        ];

        return apply_filters('parsigate_gateways_list', $list);
    }

    public static function get($name)
    {
        $list = self::list();
        return $list[$name] ? apply_filters('parsigate_gateway', $list[$name], $name) : false;
    }

    public static function enable($id): bool
    {
        $get_enables = Option::enable_gateways();
        return in_array($id, $get_enables);
    }

    public static function choices(): array
    {
        $list = self::list();
        $choices = [];
        foreach ($list as $id => $array) {
            $choices[$id] = $array['title'];
        }

        return $choices;
    }
}

new Gateways();