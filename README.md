# Parsigate - Iranian Payment Gateways for WooCommerce

Parsigate is a free and open-source WooCommerce plugin that supports multiple Iranian payment gateways, including bank, installment, and intermediary gateways.

## Requirements
- WordPress
- WooCommerce
- [ParsiDate Plugin](https://wordpress.org/plugins/wp-parsidate/) must be installed and activated

## Supported Gateways
### Bank Gateways
- Parsian ✅
- Pasargad
- Saman (Sep) ✅
- Mellat (BehPardakht) ✅
- Melli (Sadad) ✅
- Asan Pardakht ✅
- Saderat (Sepehr) ✅
- Eghtesad Novin
- Iran Kish ✅
- Sepah

### Installment Gateways
- SnappPay ✅
- Tara ✅
- DigiPay ✅
- Azki Vam ✅

### Intermediary Gateways
- Zibal ✅
- Zarinpal ✅
- PayPing ✅
- AghaPardakht ✅
- Shepa ✅

### Test Gateway
- Test payment gateway for development and debugging ✅

## Developer Guide

### How to use in your project

```php
$gateway = new \ParsiGate\Gateway($driver = 'zarinpal');
$pay = $gateway->pay($params = []);
$verify = $gateway->verify($params = []);
```

### How to add custom gateway

```php
// 1. Create Your Own Gateway Class for Process
class MyGateway extends \ParsiGate\Gateways\Base
{
    public function pay(array $args = []): array
    {
        return $this->success();
    }

    public function verify(array $args = []): array
    {
        return $this->success();
    }
}

// 2. Append to Gateway List with `parsigate_gateways_list` Hook
add_filter('parsigate_gateways_list', 'new_define_gateway_parsigate', 10, 1);
function new_define_gateway_parsigate($list)
{
    $list['my-gateway'] = [
        'title' => __('My Gateway Name', 'parsigate'),
        'class' => MyGateway::class,
        'website' => 'gateway-site.com',
        'logo' => 'https://gateway-site.com/logo.png',
        'type' => 'bank',
        'usage' => ['woocommerce', 'gravity'],
        'woocommerce' => [
            'settings' => [
                'merchant_id' => [
                    'title' => __('Merchant ID', 'parsigate'),
                    'type' => 'text',
                    'default' => '',
                    'desc_tip' => false
                ]
            ],
            'pay' => function ($amount, $order, $option, $callback_url, $class) {
                return [
                    "merchant_id" => $option['merchant_id'],
                    "amount" => $amount,
                    "callback_url" => $callback_url,
                    "description" => sprintf(__('Order ID: %d', 'parsigate'), $order->get_order_number()),
                ];
            },
            'verify' => function ($amount, $order, $option, $class) {
                $authority = ($_GET['Authority'] ?? '');
                $status = ($_GET['Status'] ?? '');
                return [
                    "merchant_id" => $option['merchant_id'],
                    "amount" => $amount,
                    'Authority' => $authority,
                    'Status' => $status
                ];
            }
        ]
    ];
    
    return $list;
}
```

## Installation
1. Install and activate the **ParsiDate** plugin.
2. Upload and activate **Parsigate** via WordPress admin or FTP.
3. Go to WooCommerce → Settings → Payments and configure your desired gateways.

## License
GNU General Public License v2.0 or later.

## Author
[wordpress-parsi](https://parsidate.com)
