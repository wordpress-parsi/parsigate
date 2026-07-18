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

Parsigate connects to third-party payment providers to process and verify online payments.

External requests are made only when:
* A site administrator enables a payment gateway.
* A customer selects that payment gateway during checkout.
* A payment request, verification, inquiry, or confirmation is required by the payment provider.

Depending on the selected gateway, the plugin may transmit the following information:

* Order ID
* Order amount
* Currency
* Callback URL
* Merchant credentials configured by the site administrator
* Payment token or authority code
* Transaction reference
* Other payment parameters required by the selected payment provider

No payment-related data is transmitted unless a customer initiates a payment.

=== AqayePardakht ===

Used to process online payments through the AqayePardakht payment gateway.

Payment requests are sent to:
https://panel.aqayepardakht.ir/

The plugin sends the order amount, order ID, callback URL, merchant credentials and other payment parameters required to create and verify the transaction.

Website: https://aqayepardakht.ir/
Terms: https://aqayepardakht.ir/terms/
Privacy: https://aqayepardakht.ir/

=== Asan Pardakht ===

Used to process online payments through the Asan Pardakht payment gateway.

Payment requests are sent to:
https://ipgrest.asanpardakht.ir/

The plugin sends the order amount, order ID, callback URL, merchant credentials and other payment parameters required to create and verify the transaction.

Website: https://asanpardakht.ir/
Terms: https://asanpardakht.ir/faq
Privacy: https://asanpardakht.ir/privacy

=== Azki Loan ===

Used to process installment payments through Azki Loan.

Payment requests are sent to:
https://api.azkiloan.com/

The plugin sends the order amount, order ID, callback URL and other payment parameters required to create and verify the transaction.

Website: https://www.azki.com/
Terms: https://www.azki.com/terms
Privacy: https://azkiloan.com/

=== Beh Pardakht Mellat ===

Used to process online payments through Beh Pardakht Mellat.

Payment requests are sent to:
https://bpm.shaparak.ir/

The plugin sends payment request and verification data required by the gateway.

Website: https://behpardakht.com/
Terms: https://pep.co.ir/shaparak-requirements/
Privacy: https://pep.co.ir/shaparak-requirements/

=== DigiPay ===

Used to process installment payments through DigiPay.

Payment requests are sent to:
https://api.mydigipay.com/

The plugin sends the order amount, order ID, callback URL and other payment parameters required to create and verify the transaction.

Website: https://www.mydigipay.com/
Terms: https://www.mydigipay.com/rules/
Privacy: https://www.mydigipay.com/privacy-policy

=== Iran Kish ===

Used to process online payments through Iran Kish.

Payment requests are sent to:
https://ikc.shaparak.ir/

The plugin sends payment request and payment confirmation data required by the gateway.

Website: https://www.irankish.com/
Terms: https://www.irankish.com/Page/buyers
Privacy: https://www.irankish.com/Page/buyers

=== Melli (Sadad) ===

Used to process online payments through Bank Melli Sadad.

Payment requests are sent to:
https://sadad.shaparak.ir/

The plugin sends payment request and payment verification data required by the gateway.

Website: https://sadadpsp.ir/
Terms: https://sadadpsp.ir/
Privacy: https://sadadpsp.ir/

=== Parsian (PEC) ===

Used to process online payments through Parsian Electronic Commerce.

Payment requests are sent to:
https://pec.shaparak.ir/

The plugin sends the order amount, order ID, callback URL, merchant credentials and other payment parameters required to create and verify the transaction.

Website: https://pec.ir/
Terms: https://pec.ir/
Privacy: https://pec.ir/

=== Pasargad (PEP) ===

Used to process online payments through Pasargad Electronic Payment.

Payment requests are sent to:
https://pep.shaparak.ir/

The plugin sends payment request and payment inquiry data required by the gateway.

Website: https://pep.co.ir/
Terms: https://pep.co.ir/shaparak-requirements/
Privacy: https://pep.co.ir/shaparak-requirements/

=== PayPing ===

Used to process online payments through PayPing.

Payment requests are sent to:
https://api.payping.ir/

The plugin sends the order amount, order ID, callback URL, merchant credentials and other payment parameters required to create and verify the transaction.

Website: https://payping.ir/
Terms: https://payping.ir/terms
Privacy: https://payping.ir/terms/

=== Saman Electronic Payment (SEP) ===

Used to process online payments through Saman Electronic Payment.

Payment requests are sent to:
https://sep.shaparak.ir/

The plugin sends payment request and payment verification data required by the gateway.

Website: https://sep.ir/
Terms: https://sep.ir/
Privacy: https://sep.ir/

=== Sepehr ===

Used to process online payments through Sepehr Payment.

Payment requests are sent to:
https://sepehr.shaparak.ir/

The plugin sends the order amount, order ID, callback URL, merchant credentials and other payment parameters required to create and verify the transaction.

Website: https://sepehrpay.com/
Terms: https://sepehrpay.com/
Privacy: https://sepehrpay.com/

=== Shepa ===

Used to process online payments through Shepa.

Payment requests are sent to:
https://merchant.shepa.com/

The plugin sends the order amount, order ID, callback URL and other payment parameters required to create and verify the transaction.

Website: https://shepa.com/
Terms: https://shepa.com/shepa_rules/
Privacy: https://shepa.com/privacy-policy

=== SnappPay ===

Used to process installment payments through SnappPay.

Payment requests are sent to:
https://api.snapppay.ir/

The plugin sends the order amount, order ID, callback URL, merchant credentials and payment status requests required to create and verify installment payments.

Website: https://snapppay.ir/
Terms: https://snapppay.ir/merchant-acquisition/
Privacy: https://snapppay.ir/merchant-acquisition/

=== Tara ===

Used to process installment payments through Tara.

Payment requests are sent to:
https://pay.tara360.ir/

The plugin sends the order amount, order ID, callback URL and other payment parameters required to create and verify the transaction.

Website: https://tara360.ir/
Terms: https://tara360.ir/termscondition/
Privacy: https://tara360.ir/termscondition/

=== ZarinPal ===

Used to process online payments through ZarinPal.

Payment requests are sent to:
https://api.zarinpal.com/

The plugin sends the order amount, order ID, callback URL, merchant credentials and other payment parameters required to create and verify the transaction.

Website: https://www.zarinpal.com/
Terms: https://www.zarinpal.com/terms
Privacy: https://www.zarinpal.com/policy

=== Zibal ===

Used to process online payments through Zibal.

Payment requests are sent to:
https://gateway.zibal.ir/

The plugin sends the order amount, order ID, callback URL, merchant credentials and other payment parameters required to create and verify the transaction.

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
