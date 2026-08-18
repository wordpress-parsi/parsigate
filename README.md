# ParsiGate درگاه پرداخت ووکامرس برای فروشگاه‌های وردپرسی
![Version](https://img.shields.io/badge/version-1.0.0-blue)
![WordPress Compatible](https://img.shields.io/badge/WordPress-5.3%2B-green)
![License](https://img.shields.io/badge/license-GPLv2-blue)
[![WordPress Plugin Downloads](https://img.shields.io/wordpress/plugin/dt/parsigate.svg)](https://wordpress.org/plugins/parsigate/)
![PHP Version](https://img.shields.io/badge/PHP-7.4%2B-purple)

**دریافت افزونه از وردپرس:** [https://fa.wordpress.org/plugins/parsigate](https://fa.wordpress.org/plugins/parsigate/)

پارسی‌گیت یک افزونه حرفه‌ای برای اتصال فروشگاه‌های ووکامرس به درگاه‌های پرداخت ایرانی است. تنها با چند دقیقه تنظیمات، می‌توانید پرداخت آنلاین را در فروشگاه خود فعال کنید و بدون نیاز به دانش برنامه‌نویسی، سفارش‌های مشتریان را به‌صورت امن و خودکار مدیریت کنید.
این افزونه با تمرکز بر سرعت، امنیت، سازگاری و تجربه کاربری توسعه داده شده و تلاش می‌کند فرآیند پرداخت را برای مدیر فروشگاه و مشتری تا حد امکان ساده و مطمئن کند.
اگر فروشگاه شما با WooCommerce ساخته شده و قصد دارید از درگاه‌های پرداخت ایرانی مانند زرین‌پال، زیبال، نکست‌پی و سایر ارائه‌دهندگان استفاده کنید، ParsiGate انتخابی مناسب برای شماست.
## چرا پارسی گیت؟
پرداخت آنلاین یکی از مهم‌ترین بخش‌های هر فروشگاه اینترنتی است. یک درگاه پرداخت ناپایدار یا ناسازگار می‌تواند باعث از دست رفتن سفارش‌ها و کاهش اعتماد مشتریان شود.
ParsiGate با هدف ارائه یک راهکار پایدار برای فروشگاه‌های ایرانی توسعه داده شده تا بتوانید با خیال راحت روی فروش کسب‌وکار خود تمرکز کنید.
### مهم‌ترین مزایای افزونه
- نصب و راه‌اندازی آسان
- اتصال سریع به درگاه‌های پرداخت ایرانی
- سازگاری کامل با آخرین نسخه‌های وردپرس و ووکامرس
- استفاده از استانداردهای برنامه‌نویسی وردپرس
- عملکرد سریع و سبک
- بروزرسانی منظم و توسعه مداوم
- پشتیبانی از هوک‌ها و قابلیت توسعه برای برنامه‌نویسان
- رابط کاربری ساده و کاملاً فارسی
- سازگار با قالب‌ها و افزونه‌های محبوب ووکامرس

## ویژگی‌ها
- اتصال آسان ووکامرس به درگاه‌های پرداخت ایرانی
- مدیریت تنظیمات هر درگاه از داخل پیشخوان وردپرس
- ثبت و مدیریت تراکنش‌ها
- بازگشت خودکار به فروشگاه پس از پرداخت
- تأیید امن پرداخت‌ها
- قابلیت توسعه برای برنامه‌نویسان
- سازگار با PHP و نسخه‌های جدید وردپرس
- بروزرسانی منظم

## توسعه مداوم
پارسی گیت یک پروژه فعال است و امکانات جدید به‌صورت مستمر به آن اضافه می‌شود. هدف ما این است که این افزونه به مرجع اصلی درگاه‌های پرداخت ووکامرس برای کاربران فارسی‌زبان تبدیل شود.
اگر پیشنهاد یا ایده‌ای برای بهبود افزونه دارید، خوشحال می‌شویم آن را با ما در میان بگذارید.


# Parsigate - Iranian Payment Gateways for WooCommerce

Parsigate is a free and open-source WooCommerce plugin that supports multiple Iranian payment gateways, including bank, installment, and intermediary gateways.

## Requirements
- WordPress
- WooCommerce
- [ParsiDate Plugin](https://wordpress.org/plugins/wp-parsidate/) must be installed and activated

## Supported Gateways
### Bank Gateways
- Parsian
- Pasargad
- Saman (Sep)
- Mellat (BehPardakht)
- Melli (Sadad)
- Asan Pardakht
- Saderat (Sepehr)
- Iran Kish

### Installment Gateways
- SnappPay
- Tara
- DigiPay
- Azki Vam

### Intermediary Gateways
- Zibal
- Zarinpal
- PayPing
- AghaPardakht
- Shepa
- Jibit

### Test Gateway
- Test payment gateway for development and debugging

## Developer Guide

### How to use in your project

```php
$gateway = new \ParsiGate\Gateway($driver = 'zarinpal');
$pay = $gateway->pay($params = []);
$verify = $gateway->verify($params = []);
```

#### Get List of drivers

```php
$gateways = \ParsiGate\Gateways::list();
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
        'usage' => ['woocommerce'],
        'woocommerce' => [
            'sandbox' => false,
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
            'verify' => function ($amount, $order, $option, $class, $request) {
                $authority = ($request['get']['Authority'] ?? '');
                $status = ($request['get']['Status'] ?? '');
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
