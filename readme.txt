=== Parsigate | پارسی گیت ===
Contributors: wordpress-parsi
Donate link: https://parsidate.com
Tags: woocommerce, payment, gateway, درگاه, ووکامرس
Requires at least: 5.6
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.txt

Support for Iranian banking, installment, and intermediary payment gateways for WooCommerce. Requires the Parsidate plugin to be installed and active.

== Description ==

Parsigate allows you to integrate various Iranian payment gateways into your WooCommerce store.

Features:
* Bank gateways: Parsian, Pasargad, Sadaad, Melli, Behpardakht, Asan Pardakht, Saman, Saderat, Eghtesad Novin, Iran Kish, Sepehr
* Installment gateways: SnappPay, Tara, Digipay, Azki Loan
* Intermediary gateways: Zibal, Zarinpal, PayPing, Aghayepardakht
* Test gateway for simulating purchases without a bank account
* Unified settings interface integrated with the Parsidate plugin

Requirements:
* [Parsidate plugin](https://wordpress.org/plugins/wp-parsidate/) must be installed and active
* WooCommerce version 4.0 or higher

== Installation ==

1. Ensure that the "Parsidate" plugin is installed and active.
2. Upload the Parsigate plugin files to the `/wp-content/plugins/` directory, or install it via the WordPress plugins screen.
3. Activate the plugin through the 'Plugins' screen in WordPress.
4. Go to Parsidate settings → "Payment Gateways" tab, configure your desired gateways, and save.

== Frequently Asked Questions ==

= Does Parsigate work without Parsidate? =
No. Parsigate is an extension module and requires the Parsidate plugin to be installed and active.

= Can I use multiple payment gateways at the same time? =
Yes. You can enable and configure multiple gateways from the Parsidate settings.

= Is there a test gateway for development purposes? =
Yes. Parsigate includes a test gateway so you can simulate the checkout process without a real bank account.

= Which currencies are supported? =
Parsigate is designed for Iranian Rials (IRR) and Tomans (IRT). Make sure your WooCommerce currency setting matches your desired usage.

= How do I add new gateways in the future? =
Parsigate has a modular structure. Developers can add new gateways by creating a new gateway class in the `includes/gateways/` folder and registering it in the main plugin class.

= Is Parsigate free? =
Yes. Parsigate is open-source and free to use under the GPLv2 license.

== Changelog ==

= 1.0.0 =
* Initial release with Zarinpal and Zibal support
* Modular structure for easy gateway expansion
* Integrated with Parsidate settings
