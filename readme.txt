=== Parsigate ===
Contributors: saeedfard, wordpress-parsi, mehrshaddarzi, man4toman
Donate link: https://wp-parsi.com/support/
Tags: woocommerce, payment, gateway, درگاه, ووکامرس
Requires at least: 5.6
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Support for Iranian banking, installment, and intermediary payment gateways for WooCommerce. Requires the Parsidate plugin to be installed and active.

== Description ==

ParsiGate is a professional WooCommerce payment gateway plugin designed for Iranian online stores. With ParsiGate, you can easily connect your WooCommerce store to Iranian payment providers and offer your customers a simple, secure, and reliable online payment experience.

Built with WordPress and WooCommerce standards in mind, ParsiGate focuses on performance, compatibility, and ease of use. Whether you run a small online shop, a digital product store, an educational website, or a growing ecommerce business, ParsiGate helps you manage your online payments with confidence.

## Features
- Connect WooCommerce stores to Iranian payment gateways
- Easy gateway configuration from WordPress dashboard
- Secure transaction verification
- Automatic customer redirection after payment
- Lightweight and performance-friendly architecture
- Compatible with modern WooCommerce stores
- Developer-friendly structure with WordPress standards
- Regular updates and improvements

## Supported Gateways:
* Bank gateways: Mellat, Parsian, Pasargad, Sadaad, Melli, Behpardakht, Asan Pardakht, Saman, Saderat, Eghtesad Novin, Iran Kish, Sepehr
* Installment gateways: SnappPay, Tara, Digipay, Azki, ZarinPlus
* Intermediary gateways: Zibal, Zarinpal, PayPing, ParsPal, Jibit, Vandar, Aghayepardakht, Shepa
* Offline gateways: Card to Card (customer uploads the payment receipt, the order is put on hold for manual review)
* Test gateway for simulating purchases without a bank account
* Unified settings interface integrated with the Parsidate plugin

## Requirements:
* [Parsidate plugin](https://wordpress.org/plugins/wp-parsidate/) must be installed and active
* WooCommerce version 4.0 or higher

== Installation ==

1. Ensure that the "Parsidate" plugin is installed and active.
2. Upload the Parsigate plugin files to the `/wp-content/plugins/` directory, or install it via the WordPress plugins screen.
3. Activate the plugin through the 'Plugins' screen in WordPress.
4. Go to Parsidate settings → "Payment Gateways" tab, configure your desired gateways, and save.

= External Services =

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

The Card to Card and Test gateways do not contact any external service. Card to Card payment receipts uploaded by customers are stored in the site's own Media Library and attached to the order.

= AqayePardakht =

Used to process online payments through the AqayePardakht payment gateway.

Payment requests are sent to:
https://panel.aqayepardakht.ir/

The plugin sends the order amount, order ID, callback URL, merchant credentials and other payment parameters required to create and verify the transaction.

Website: https://aqayepardakht.ir/
Terms: https://aqayepardakht.ir/terms/
Privacy: https://aqayepardakht.ir/terms/

= Asan Pardakht =

Used to process online payments through the Asan Pardakht payment gateway.

Payment requests are sent to:
https://ipgrest.asanpardakht.ir/

The plugin sends the order amount, order ID, callback URL, merchant credentials and other payment parameters required to create and verify the transaction.

Website: https://asanpardakht.ir/
Terms: https://asanpardakht.ir/faq
Privacy: https://asanpardakht.ir/privacy

= Azki Loan =

Used to process installment payments through Azki Loan.

Payment requests are sent to:
https://api.azkiloan.com/

The plugin sends the order amount, order ID, callback URL and other payment parameters required to create and verify the transaction.

Website: https://www.azki.com/
Terms: https://www.azki.com/terms
Privacy: https://www.azki.com/terms

= Beh Pardakht Mellat =

Used to process online payments through Beh Pardakht Mellat.

Payment requests are sent to:
https://bpm.shaparak.ir/

The plugin sends payment request and verification data required by the gateway.

Website: https://behpardakht.com/
Terms: https://pep.co.ir/shaparak-requirements/
Privacy: https://pep.co.ir/shaparak-requirements/

= DigiPay =

Used to process installment payments through DigiPay.

Payment requests are sent to:
https://api.mydigipay.com/

The plugin sends the order amount, order ID, callback URL and other payment parameters required to create and verify the transaction.

Website: https://www.mydigipay.com/
Terms: https://www.mydigipay.com/rules/
Privacy: https://www.mydigipay.com/privacy-policy

= Iran Kish =

Used to process online payments through Iran Kish.

Payment requests are sent to:
https://ikc.shaparak.ir/

The plugin sends payment request and payment confirmation data required by the gateway.

Website: https://www.irankish.com/
Terms: https://www.irankish.com/Page/buyers
Privacy: https://www.irankish.com/Page/buyers

= Jibit =

Used to process online payments through Jibit.

Payment requests are sent to:
https://napi.jibit.ir/

The plugin sends the API key and secret key (to obtain an access token), the order amount, order ID, callback URL, order description, and — when available — the customer's mobile number and username, as required to create and verify the transaction.

Website: https://jibit.ir/
Terms: https://jibit.ir/
Privacy: https://jibit.ir/

= Melli (Sadad) =

Used to process online payments through Bank Melli Sadad.

Payment requests are sent to:
https://sadad.shaparak.ir/

The plugin sends payment request and payment verification data required by the gateway.

Website: https://sadadpsp.ir/
Terms: https://sadadpsp.ir/admission-rules
Privacy: https://sadadpsp.ir/admission-rules

= Parsian (PEC) =

Used to process online payments through Parsian Electronic Commerce.

Payment requests are sent to:
https://pec.shaparak.ir/

The plugin sends the order amount, order ID, callback URL, merchant credentials and other payment parameters required to create and verify the transaction.

Website: https://pec.ir/
Terms: https://pep.co.ir/shaparak-requirements/
Privacy: https://pep.co.ir/shaparak-requirements/

= Pasargad (PEP) =

Used to process online payments through Pasargad Electronic Payment.

Payment requests are sent to:
https://pep.shaparak.ir/

The plugin sends payment request and payment inquiry data required by the gateway.

Website: https://pep.co.ir/
Terms: https://pep.co.ir/shaparak-requirements/
Privacy: https://pep.co.ir/shaparak-requirements/

= PayPing =

Used to process online payments through PayPing.

Payment requests are sent to:
https://api.payping.ir/

The plugin sends the order amount, order ID, callback URL, merchant credentials and other payment parameters required to create and verify the transaction.

Website: https://payping.ir/
Terms: https://payping.ir/terms/
Privacy: https://payping.ir/terms/

= ParsPal =

Used to process online payments through ParsPal.

Payment requests are sent to:
https://api.parspal.com/
Sandbox: https://sandbox.api.parspal.com/

The plugin sends the order amount (Rial), order ID, callback URL, API Key and payer details required to create and verify the transaction.

Website: https://parspal.com/
Terms: https://parspal.com/
Privacy: https://parspal.com/

= Saman Electronic Payment (SEP) =

Used to process online payments through Saman Electronic Payment.

Payment requests are sent to:
https://sep.shaparak.ir/

The plugin sends payment request and payment verification data required by the gateway.

Website: https://sep.ir/
Terms: https://www.sep.ir/
Privacy: https://www.sep.ir/

= Sepehr =

Used to process online payments through Sepehr Payment.

Payment requests are sent to:
https://sepehr.shaparak.ir/

The plugin sends the order amount, order ID, callback URL, merchant credentials and other payment parameters required to create and verify the transaction.

Website: https://sepehrpay.com/
Terms: https://pep.co.ir/shaparak-requirements/
Privacy: https://pep.co.ir/shaparak-requirements/

= Shepa =

Used to process online payments through Shepa.

Payment requests are sent to:
https://merchant.shepa.com/

The plugin sends the order amount, order ID, callback URL and other payment parameters required to create and verify the transaction.

Website: https://shepa.com/
Terms: https://shepa.com/shepa_rules/
Privacy: https://shepa.com/privacy-policy

= SnappPay =

Used to process installment payments through SnappPay.

Payment requests are sent to:
https://api.snapppay.ir/

The plugin sends the order amount, order ID, callback URL, merchant credentials and payment status requests required to create and verify installment payments.

Website: https://snapppay.ir/
Terms: https://snapppay.ir/merchant-acquisition/
Privacy: https://snapppay.ir/merchant-acquisition/

= Tara =

Used to process installment payments through Tara.

Payment requests are sent to:
https://pay.tara360.ir/

The plugin sends the order amount, order ID, callback URL and other payment parameters required to create and verify the transaction.

Website: https://tara360.ir/
Terms: https://tara360.ir/termscondition/
Privacy: https://tara360.ir/termscondition/

= Vandar =

Used to process online payments through Vandar.

Payment requests are sent to:
https://ipg.vandar.io/

The plugin sends the API key, order amount, order ID, callback URL, order description and the customer's mobile number required to create and verify the transaction.

Website: https://vandar.io/
Terms: https://vandar.io/
Privacy: https://vandar.io/

= ZarinPal =

Used to process online payments through ZarinPal.

Payment requests are sent to:
https://api.zarinpal.com/

The plugin sends the order amount, order ID, callback URL, merchant credentials and other payment parameters required to create and verify the transaction.

Website: https://www.zarinpal.com/
Terms: https://www.zarinpal.com/terms
Privacy: https://www.zarinpal.com/policy

= ZarinPlus =

Used to process installment payments through ZarinPlus.

Payment requests are sent to:
https://api.zarinplus.com/

The plugin sends the merchant token (also used to fetch the list of gateways available to the merchant when settings are saved), the order amount, order description, callback URL and the customer's mobile number and email required to create and verify the transaction.

Website: https://zarinplus.com/
Terms: https://zarinplus.com/
Privacy: https://zarinplus.com/

= Zibal =

Used to process online payments through Zibal.

Payment requests are sent to:
https://gateway.zibal.ir/

The plugin sends the order amount, order ID, callback URL, merchant credentials and other payment parameters required to create and verify the transaction.

Website: https://zibal.ir/
Terms: https://zibal.ir/privacy-policy
Privacy: https://zibal.ir/privacy-policy

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
Parsigate has a modular structure. Developers can add new gateways by creating a new gateway class in the `inc/gateways/` folder and registering it in the main plugin class.

= How does the Card to Card gateway work? =
The customer sees your bank account / card details after placing the order and uploads an image of the payment receipt (JPG, PNG or WebP). The order is set to "On hold" so you can check the receipt from the order page and complete it manually.

= Is Parsigate free? =
Yes. Parsigate is open-source and free to use under the GPLv2 license.

== Screenshots ==

1. Setting page
2. Gateway settings

== Changelog ==

= 1.1.0 =
* New: Vandar payment gateway
* New: Jibit payment gateway
* New: ParsPal payment gateway (with sandbox mode)
* New: ZarinPlus installment gateway, including the merchant's sub-gateways
* New: Card to Card offline gateway with receipt upload and admin review
* New: Gateway logos on the settings page and a new "Offline" gateway category
* New: Custom default description per gateway
* New: Admin notice detects missing, inactive or outdated (below 6.0) Parsidate and offers install / activate / update buttons
* New: Developer hooks `parsigate_enable_gateway`, `parsigate_before_woocommerce_gateway_loaded`, `parsigate_after_woocommerce_gateway_loaded`; `parsigate_gateway_set_completed` now receives the gateway object
* Fixed: Azki successful payments were treated as failed
* Fixed: Asan Pardakht token parsing, request parameter types and error messages
* Fixed: Mellat error message handling
* Fixed: ParsPal sandbox mode
* Fixed: Payment verification continued after an invalid callback
* Fixed: PHP notice in WooCommerce Blocks when a gateway had no saved settings
* Security: Payment callbacks are rejected when the order was not placed with that gateway, and already-paid orders are never processed again
* Security: Jibit callbacks must match the purchase created for the order
* Security: Card to Card callbacks can no longer change the status of orders that are not awaiting payment
* Security: The test gateway page is only available while the test gateway is enabled

= 1.0.1 =
* Update readme
* Fixed: parsigate icon

= 1.0.0 =
* Initial release with Zarinpal and Zibal support
* Modular structure for easy gateway expansion
* Integrated with Parsidate settings

== Upgrade Notice ==

= 1.1.0 =
Adds Vandar, Jibit, ParsPal, ZarinPlus and Card to Card gateways, fixes Azki payments and includes security hardening for payment callbacks. Requires Parsidate 6.0 or later for full functionality.
