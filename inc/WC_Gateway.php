<?php

namespace ParsiGate;

/**
 * @reference:
 * https://woocommerce.github.io/code-reference/files/woocommerce-includes-abstracts-abstract-wc-payment-gateway.html
 */
class WC_Gateway extends \WC_Payment_Gateway
{
    // Gateway property
    public $driver;
    public $gateway;

    // WC Property
    public $id;
    public $method_title;
    public $method_description;
    public $has_fields;
    public $title;
    public $description;

    public function __construct()
    {
    }

    public function setup_gateway($driver = 'test')
    {
        // Get Driver
        $this->driver = $driver;

        // Get Gateway
        $this->gateway = Gateways::get($this->driver);

        // Set Property
        $this->id = $this->driver;
        $this->method_title = $this->gateway['title'];
        $this->method_description = $this->method_title . ' ' . __('payment gateway (By ParsiGate)', 'parsigate');
        $this->title = $this->get_option('title', $this->gateway['title']);
        $this->description = $this->get_option('description', $this->method_description);
        $this->has_fields = true;

        // Setup icon
        $this->icon = static::icon();

        // Setup Settings & Fields
        $this->init_form_fields();
        $this->init_settings();

        // Save Admin Option
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));

        // Handle Request
        add_action('woocommerce_api_' . $this->id, array($this, 'handle_gateway_response'));
    }

    public function icon()
    {
        $logo = '';

        $image = \ParsiGate::$plugin_path . '/assets/logo/' . $this->id . '.png';
        if (file_exists($image)) {
            $logo = \ParsiGate::$plugin_url . '/assets/logo/' . $this->id . '.png';
        }

        if (isset($this->gateway['logo']) and !empty($this->gateway['logo'])) {
            $logo = $this->gateway['logo'];
        }

        return apply_filters('parsigate_gateway_icon', $logo, $this->id);
    }

    public function get_icon()
    {
        $icon = $this->icon ? '<img src="' . esc_url($this->icon) . '" alt="' . esc_attr($this->get_title()) . '" />' : '';
        return apply_filters('woocommerce_gateway_icon', $icon, $this->id);
    }

    public function init_form_fields()
    {
        $fields = [
            'enabled' => [
                'title' => __('Enabled/Disabled', 'parsigate'),
                'type' => 'checkbox',
                'label' => sprintf(__('Activate or deactivate %s gateway', 'parsigate'), $this->method_title),
                'default' => 'no',
                'description' => (isset($this->gateway['requirement']) ? '<span style="color: red;">' . $this->gateway['requirement'] . '</span>' : ''),
            ],
            'title' => [
                'title' => __('Gateway title', 'parsigate'),
                'type' => 'text',
                'description' => __('This name is displayed to the customer during the purchase process', 'parsigate'),
                'default' => $this->method_title
            ],
            'description' => [
                'title' => __('Gateway description', 'parsigate'),
                'type' => 'textarea',
                'description' => __('The description that will be displayed during the purchase process for the gateway', 'parsigate'),
                'default' => sprintf(__("Secure payment by all Shetab's cards through %s", 'parsigate'), $this->method_title)
            ]
        ];

        if (isset($this->gateway['woocommerce']['settings']) and is_array($this->gateway['woocommerce']['settings']) and !empty($this->gateway['woocommerce']['settings'])) {
            $fields = $fields + $this->gateway['woocommerce']['settings'];
        }

        $fields = $fields + [
                'failed_massage' => [
                    'title' => __('Payment failed message', 'wp-parsidate'),
                    'type' => 'textarea',
                    'description' => __('Enter the text of the message you want to display to the user after an unsuccessful payment.', 'parsigate'),
                    'default' => __('Your payment has failed. Please try again or contact us in case of problems.', 'parsigate')
                ]
            ];

        $this->form_fields = apply_filters('parsigate_gateway_config', $fields, $this->id);
    }

    public function is_available()
    {
        return parent::is_available();
    }

    public function get_amount($order)
    {
        $currency = $order->get_currency();
        $order_total = $order->get_total();
        $amount = intval($order_total);
        $currency = strtolower($currency);

        if (in_array($currency, array(
            'irt',
            'toman',
            'iran toman',
            'iranian toman',
            'iran-toman',
            'iran_toman',
            'تومان',
            'تومان ایران'
        ))) {
            $amount = $amount * 10;
        } else if ('irht' === $currency) {
            $amount = $amount * 1000 * 10;
        } else if ('irhr' === $currency) {
            $amount = $amount * 1000;
        }

        return $amount;
    }

    public function get_callback_url($order)
    {
        return add_query_arg(
            array(
                'wc-api' => $this->id,
                'order_id' => $order->get_id()
            ),
            get_site_url(null, '/')
        );
    }

    public function process_payment($order_id)
    {

        $option = WooCommerce::get_gateway_option($this->id);
        $order = wc_get_order($order_id);
        $amount = $this->get_amount($order);

        do_action('parsigate_gateway_before_process_payment', $order, $this->id);

        $params = [];
        if (isset($this->gateway['woocommerce']['pay']) && is_callable($this->gateway['woocommerce']['pay'])) {
            $params = $this->gateway['woocommerce']['pay']($amount, $order, $option, $this->get_callback_url($order));
        }

        $class = new Gateway($this->driver);
        $pay = $class->pay(apply_filters('parsigate_gateway_process_payment_params', $params, $order, $this->id));

        do_action('parsigate_gateway_after_process_payment', $pay, $order, $this->id, wp_is_json_request());

        if ($pay['success'] === false) {

            $error_message = '';
            if (isset($pay['errors']) and !empty($pay['errors']) and is_array($pay['errors'])) {
                $error_message = json_encode($pay['errors'], JSON_UNESCAPED_UNICODE);
            }
            if (isset($pay['message']) and !empty($pay['message'])) {
                $error_message = $pay['message'];
            }
            if (empty($error_message)) {
                $error_message = $this->get_option('failed_massage');
            }

            wc_add_notice($error_message, 'error');
            return [
                'result' => 'failure',
                'messages' => $error_message,
                'reload' => false
            ];
        }

        $redirect = $pay['data']['redirect'];

        if (isset($pay['data']['redirect']) and is_array($pay['data']['redirect']) and isset($pay['data']['redirect']['with_post'])) {

            $args = [
                    'wc-api' => $this->id,
                    'action' => 'redirect',
                    'order_id' => $order_id,
                    'url' => urlencode_deep($pay['data']['redirect']['url']),
                ] + $pay['data']['redirect']['inputs'];
            $redirect = add_query_arg($args, get_site_url(null, '/'));
        }

        return [
            'result' => 'success',
            'redirect' => $redirect
        ];
    }

    public function handle_gateway_response()
    {
        $action = $_GET['action'] ?? '';
        $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
        $order = wc_get_order($order_id);

        switch ($action) {
            case 'redirect':
                $this->redirect_to_gateway($order);
                break;

            default:
                $this->verify_payment($order);
                break;
        }
    }

    public function redirect_to_gateway($order)
    {
        ?>
        <html lang="fa-IR">
        <head>
            <meta charset="UTF-8"/>
        </head>
        <body onload="document.forms['redirect'].submit()">
        <form name="redirect" method="post" action="<?php echo urldecode_deep($_GET['url']) ?>">
            <?php
            foreach ($_GET as $key => $value) {
                if (in_array($key, ['wc-api', 'action', 'order_id', 'url'])) {
                    continue;
                }
                ?>
                <input type="hidden" name="<?php echo trim($key); ?>" value="<?php echo esc_attr($value); ?>">
                <?php
            }
            ?>
        </form>
        <script type="text/javascript">
            setTimeout(function () {
                document.forms['redirect'].submit();
            }, 100);
        </script>
        </body>
        </html>
        <?php
        exit;
    }

    public function verify_payment($order)
    {
        $option = WooCommerce::get_gateway_option($this->id);
        $amount = $this->get_amount($order);

        do_action('parsigate_gateway_before_verify_payment', $order, $this->id);

        $params = [];
        if (isset($this->gateway['woocommerce']['verify']) && is_callable($this->gateway['woocommerce']['verify'])) {
            $params = $this->gateway['woocommerce']['verify']($amount, $order, $option);
        }

        $class = new Gateway($this->driver);
        $verify = $class->verify(apply_filters('parsigate_gateway_verify_payment_params', $params, $order, $this->id));

        do_action('parsigate_gateway_after_verify_payment', $verify, $order, $this->id, wp_is_json_request());

        if ($verify['success'] === true) {
            $this->set_completed_payment($order, $verify['data']['transaction_id'], $verify);
        }

        $this->set_failed_payment($order, $verify);
    }

    public function set_completed_payment($order, $transaction_id, $verify)
    {
        $pre = apply_filters('parsigate_gateway_set_completed', null, $order, $transaction_id, $this->id);
        if (!is_null($pre)) {
            return $pre;
        }

        // Set Payment Completed
        $order->payment_complete($transaction_id);

        // Add Order Note
        $order->add_order_note(
            sprintf(__("Payment Success, Transaction ID: %s", 'parsigate'), $transaction_id)
        );

        // Remove cart.
        WC()->cart->empty_cart();

        // Action
        do_action('parsigate_gateway_completed_payment', $order, $transaction_id, $this->id, $verify);

        // Redirect
        wp_redirect($this->get_return_url($order));
        exit;
    }

    public function set_failed_payment($order, $verify)
    {
        // Notices
        $error_message = $this->get_option('failed_massage');
        if (empty($error_message)) {

            if (isset($verify['errors']) and !empty($verify['errors']) and is_array($verify['errors'])) {
                $error_message = json_encode($verify['errors'], JSON_UNESCAPED_UNICODE);
            }
            if (isset($verify['message']) and !empty($verify['message'])) {
                $error_message = $verify['message'];
            }
        }
        wc_add_notice($error_message, 'error');

        // Action
        do_action('parsigate_gateway_failed_payment', $order, $verify);

        // Redirect
        wp_redirect(wc_get_checkout_url());
        exit;
    }

}
