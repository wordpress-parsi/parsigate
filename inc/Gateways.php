<?php

namespace ParsiGate;

use ParsiGate\gateways\AqayePardakht;
use ParsiGate\gateways\AsanPardakht;
use ParsiGate\gateways\Azkivam;
use ParsiGate\gateways\DigiPay;
use ParsiGate\gateways\EghtesadNovin;
use ParsiGate\gateways\IranKish;
use ParsiGate\gateways\Jibit;
use ParsiGate\gateways\Mellat;
use ParsiGate\gateways\Melli;
use ParsiGate\gateways\Parsian;
use ParsiGate\gateways\Pasargad;
use ParsiGate\gateways\PayFa;
use ParsiGate\gateways\PayPing;
use ParsiGate\gateways\Polam;
use ParsiGate\gateways\Saderat;
use ParsiGate\gateways\Sep;
use ParsiGate\gateways\Sepah;
use ParsiGate\gateways\Shepa;
use ParsiGate\gateways\SnappPay;
use ParsiGate\gateways\Tara;
use ParsiGate\gateways\Test;
use ParsiGate\gateways\Torob;
use ParsiGate\Gateways\ZarinPal;
use ParsiGate\gateways\Zibal;

class Gateways
{
    public function __construct()
    {
    }

    public static function types()
    {
        $list = [
            'bank' => __('Bank Gateway', 'parsigate'),
            'intermediary' => __('Intermediary Gateway', 'parsigate'),
            'installment' => __('Installment Gateway', 'parsigate'),
            'test' => __('Test Gateway', 'parsigate'),
        ];

        return apply_filters('parsigate_gateways_types', $list);
    }

    public static function list()
    {
        $list = [

            // Bank Gateway
            'parsian' => [
                'title' => __('Parsian', 'parsigate'),
                'class' => Parsian::class,
                'website' => 'pec.ir',
                'type' => 'bank',
                'usage' => ['standalone', 'woocommerce']
            ],
            'pasargad' => [
                'title' => __('Pasargad', 'parsigate'),
                'class' => Pasargad::class,
                'website' => 'pep.co.ir',
                'type' => 'bank',
                'usage' => ['standalone', 'woocommerce']
            ],
            'saman' => [
                'title' => __('Saman (Sep)', 'parsigate'),
                'class' => Sep::class,
                'website' => 'sep.ir',
                'type' => 'bank',
                'usage' => ['standalone', 'woocommerce']
            ],
            'mellat' => [
                'title' => __('Mellat (BehPardakht)', 'parsigate'),
                'class' => Mellat::class,
                'website' => 'behpardakht.com',
                'type' => 'bank',
                'usage' => ['standalone', 'woocommerce']
            ],
            'melli' => [
                'title' => __('Melli (Sadad)', 'parsigate'),
                'class' => Melli::class,
                'website' => 'sadadpsp.ir',
                'type' => 'bank',
                'usage' => ['standalone', 'woocommerce']
            ],
            'asanpardakht' => [
                'title' => __('Asan Pardakht', 'parsigate'),
                'class' => AsanPardakht::class,
                'website' => 'asanpardakht.ir',
                'type' => 'bank',
                'usage' => ['standalone', 'woocommerce']
            ],
            'saderat' => [
                'title' => __('Saderat (Sepehr)', 'parsigate'),
                'class' => Saderat::class,
                'website' => 'sepehrpay.com',
                'type' => 'bank',
                'usage' => ['standalone', 'woocommerce']
            ],
            'eghtesadnovin' => [
                'title' => __('Eghtesad Novin', 'parsigate'),
                'class' => EghtesadNovin::class,
                'website' => 'enbank.ir',
                'type' => 'bank',
                'usage' => ['standalone', 'woocommerce']
            ],
            'irankish' => [
                'title' => __('Iran Kish', 'parsigate'),
                'class' => IranKish::class,
                'website' => 'irankish.com',
                'type' => 'bank',
                'usage' => ['standalone', 'woocommerce']
            ],
            'sepah' => [
                'title' => __('Sepah', 'parsigate'),
                'class' => Sepah::class,
                'website' => 'banksepah.ir',
                'type' => 'bank',
                'usage' => ['standalone', 'woocommerce']
            ],

            // Intermediary Gateway
            'zarinpal' => [
                'title' => __('ZarinPal', 'parsigate'),
                'class' => Zarinpal::class,
                'website' => 'zarinpal.com',
                'type' => 'intermediary',
                'usage' => ['standalone', 'woocommerce']
            ],
            'zibal' => [
                'title' => __('Zibal', 'parsigate'),
                'class' => Zibal::class,
                'website' => 'zibal.ir',
                'type' => 'intermediary',
                'usage' => ['standalone', 'woocommerce']
            ],
            'payping' => [
                'title' => __('PayPing', 'parsigate'),
                'class' => PayPing::class,
                'website' => 'payping.io',
                'type' => 'intermediary',
                'usage' => ['standalone', 'woocommerce']
            ],
            'aqayepardakht' => [
                'title' => __('AqayePardakht', 'parsigate'),
                'class' => AqayePardakht::class,
                'website' => 'aqayepardakht.ir',
                'type' => 'intermediary',
                'usage' => ['standalone', 'woocommerce']
            ],
            'jibit' => [
                'title' => __('Jibit', 'parsigate'),
                'class' => Jibit::class,
                'website' => 'jibit.ir',
                'type' => 'intermediary',
                'usage' => ['standalone', 'woocommerce']
            ],
            'payfa' => [
                'title' => __('PayFa', 'parsigate'),
                'class' => PayFa::class,
                'website' => 'payfa.com',
                'type' => 'intermediary',
                'usage' => ['standalone', 'woocommerce']
            ],
            'shepa' => [
                'title' => __('Shepa', 'parsigate'),
                'class' => Shepa::class,
                'website' => 'shepa.com',
                'type' => 'intermediary',
                'usage' => ['standalone', 'woocommerce']
            ],
            'polam' => [
                'title' => __('Polam', 'parsigate'),
                'class' => Polam::class,
                'website' => 'polam.io',
                'type' => 'intermediary',
                'usage' => ['standalone', 'woocommerce']
            ],

            // Installment Gateway
            'snappay' => [
                'title' => __('SnappPay', 'parsigate'),
                'class' => SnappPay::class,
                'website' => 'snapppay.ir',
                'type' => 'installment',
                'usage' => ['standalone', 'woocommerce']
            ],
            'digipay' => [
                'title' => __('DigiPay', 'parsigate'),
                'class' => DigiPay::class,
                'website' => 'mydigipay.com',
                'type' => 'installment',
                'usage' => ['standalone', 'woocommerce']
            ],
            'azkivam' => [
                'title' => __('Azkivam', 'parsigate'),
                'class' => Azkivam::class,
                'website' => 'azkivam.com',
                'type' => 'installment',
                'usage' => ['standalone', 'woocommerce']
            ],
            'torob' => [
                'title' => __('Torob', 'parsigate'),
                'class' => Torob::class,
                'website' => 'torobpay.com',
                'type' => 'installment',
                'usage' => ['standalone', 'woocommerce']
            ],
            'tara' => [
                'title' => __('Tara', 'parsigate'),
                'class' => Tara::class,
                'website' => 'tara360.ir',
                'type' => 'installment',
                'usage' => ['standalone', 'woocommerce']
            ],

            // Test Gateway
            'test' => [
                'title' => __('Test Gateway', 'parsigate'),
                'class' => Test::class,
                'website' => 'wp-parsi.com',
                'type' => 'test',
                'usage' => ['standalone', 'woocommerce']
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
        $item = self::get(strtolower($id));
        if (!$item) {
            return false;
        }

        $option_name = Option::option_name($item['type']);
        $option = Option::get($option_name);
        if (!is_array($option)) {
            return false;
        }
        return in_array($id, $option);
    }

    public static function choices($type = null, $enable = null): array
    {
        $list = self::list();
        if (!is_null($type)) {
            $list = array_filter($list, function ($item) use ($type) {
                return $item['type'] == $type;
            });
        }
        $choices = [];

        foreach ($list as $id => $array) {
            if (is_bool($enable) and $enable === true and self::enable($id) === false) {
                continue;
            }

            $choices[$id] = $array['title'];
        }

        return $choices;
    }
}

new Gateways();