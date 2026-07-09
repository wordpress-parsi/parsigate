=== Parsigate ===
Contributors: saeedfard, wordpress-parsi, mehrshaddarzi
Donate link: https://wp-parsi.com/support/
Tags: woocommerce, payment, gateway, درگاه, ووکامرس
Requires at least: 5.6
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

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

== External Services ==

This plugin integrates with third-party payment gateways to process online payments.

The plugin communicates with the selected payment provider only when:
* The site administrator has enabled that gateway.
* A customer initiates a payment during checkout.
* A payment verification request is required.

The plugin may transmit the following data depending on the selected payment gateway:

* Order ID
* Order amount
* Currency
* Merchant credentials configured by the administrator
* Callback URL
* Payment token / authority code
* Transaction reference
* Any additional parameters required by the selected payment gateway API

No data is sent unless a payment is initiated by the customer.

Supported external services:

AqayePardakht
Website: https://aqayepardakht.ir/
Terms: https://aqayepardakht.ir/terms/
Privacy: https://aqayepardakht.ir/

Asan Pardakht
Website: https://asanpardakht.ir/
Terms: https://asanpardakht.ir/faq
Privacy: https://asanpardakht.ir/privacy

Azki Loan
Website: https://www.azki.com/
Terms: https://www.azki.com/terms
Privacy: https://azkiloan.com/

Beh Pardakht Mellat
Website: https://behpardakht.com/
Terms: https://pep.co.ir/shaparak-requirements/
Privacy: https://pep.co.ir/shaparak-requirements/

DigiPay
Website: https://www.mydigipay.com/
Terms: https://www.mydigipay.com/rules/
Privacy: https://www.mydigipay.com/privacy-policy

Iran Kish
Website: https://www.irankish.com/
Terms: https://www.irankish.com/Page/buyers
Privacy: https://www.irankish.com/Page/buyers

Melli (Sadad)
Website: https://sadadpsp.ir/
Terms: https://pep.co.ir/shaparak-requirements/
Privacy: https://pep.co.ir/shaparak-requirements/

Parsian (PEC)
Website: https://pec.ir/
Terms: https://pep.co.ir/shaparak-requirements/
Privacy: https://pep.co.ir/shaparak-requirements/

Pasargad (PEP)
Website: https://pep.co.ir/
Terms: https://pep.co.ir/shaparak-requirements/
Privacy: https://pep.co.ir/shaparak-requirements/

PayPing
Website: https://payping.ir/
Terms: https://payping.ir/terms
Privacy: https://payping.ir/privacy

Saman Electronic Payment (SEP)
Website: https://sep.ir/
Terms: https://pep.co.ir/shaparak-requirements/
Privacy: https://pep.co.ir/shaparak-requirements/

Sepehr (Bank Saderat)
Website: https://sepehrpay.com/
Terms: https://pep.co.ir/shaparak-requirements/
Privacy: https://pep.co.ir/shaparak-requirements/

Shepa
Website: https://shepa.com/
Terms: https://shepa.com/shepa_rules/
Privacy: https://shepa.com/privacy-policy

SnappPay
Website: https://snapppay.ir/
Terms: https://snapppay.ir/merchant-acquisition/
Privacy: https://snapppay.ir/merchant-acquisition/

Tara
Website: https://tara360.ir/
Terms: https://tara360.ir/termscondition/
Privacy: https://tara360.ir/termscondition/

ZarinPal
Website: https://www.zarinpal.com/
Terms: https://www.zarinpal.com/terms
Privacy: https://www.zarinpal.com/policy

Zibal
Website: https://zibal.ir/
Terms: https://zibal.ir/privacy-policy
Privacy: https://zibal.ir/privacy-policy

== Screenshots ==

1. Setting page
2. Gateway settings

== Changelog ==

= 1.0.0 =
* Initial release with Zarinpal and Zibal support
* Modular structure for easy gateway expansion
* Integrated with Parsidate settings
