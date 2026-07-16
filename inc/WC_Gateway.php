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
        $attributes = apply_filters('parsigate_gateway_icon_attributes', [
            'src' => esc_url($this->icon),
            'alt' => $this->get_title()
        ], $this->id);

        $html = '';
        if (!empty($attributes['src'])) {

            $html = '<img';
            foreach ($attributes as $key => $value) {
                $html .= ' ' . esc_attr($key) . '="' . esc_attr($value) . '"';
            }
            $html .= '>';
        }

        return apply_filters('parsigate_woocommerce_gateway_icon', $html, $this->id);
    }

    public function init_form_fields()
    {
        $fields = [
            'enabled' => [
                'title' => __('Enabled/Disabled', 'parsigate'),
                'type' => 'checkbox',
                /* translators: %s: Gateway method title */
                'label' => sprintf(__('Activate or deactivate %s gateway', 'parsigate'), $this->method_title),
                'default' => 'no',
                'description' => ((isset($this->gateway['requirement']) and !empty($this->gateway['requirement'])) ? '<span style="color: red;">' . $this->gateway['requirement'] . '</span>' : ''),
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
                'default' => ((isset($this->gateway['type']) and $this->gateway['type'] == "installment") ?
                    /* translators: %s: Gateway name for credit/installment payments */
                    sprintf(__("Credit and installment payments through %s", 'parsigate'), $this->method_title) :
                    /* translators: %s: Payment gateway name for Shetab card payments */
                    sprintf(__("Secure payment by all Shetab's cards through %s", 'parsigate'), $this->method_title)
                )
            ]
        ];

        if (isset($this->gateway['woocommerce']['settings']) and is_array($this->gateway['woocommerce']['settings']) and !empty($this->gateway['woocommerce']['settings'])) {
            $fields = $fields + $this->gateway['woocommerce']['settings'];
        }

        if (isset($this->gateway['woocommerce']['sandbox']) and $this->gateway['woocommerce']['sandbox'] === true) {
            $fields['sandbox'] = [
                'title' => __('Sandbox', 'parsigate'),
                'type' => 'select',
                'class' => 'wc-enhanced-select',
                'description' => __('is Enable SandBox?', 'parsigate'),
                'default' => 'no',
                'desc_tip' => true,
                'options' => array(
                    'no' => __('No', 'parsigate'),
                    'yes' => __('Yes', 'parsigate')
                )
            ];
        }

        $fields = $fields + [
                'failed_massage' => [
                    'title' => __('Payment failed message', 'parsigate'),
                    'type' => 'textarea',
                    'description' => __('Enter the text of the message you want to display to the user after an unsuccessful payment.', 'parsigate'),
                    'default' => __('Your payment has failed. Please try again or contact us in case of problems.', 'parsigate')
                ],
                'roles' => [
                    'title' => __('Display for User Roles', 'parsigate'),
                    'type' => 'multiselect',
                    'class' => 'wc-enhanced-select',
                    'description' => __('Gateway will only be displayed for selected user roles.', 'parsigate'),
                    'default' => '',
                    'options' => $this->get_user_roles(),
                ],
                'min_cart_price' => [
                    'title' => __('Minimum Cart Amount', 'parsigate'),
                    'type' => 'number',
                    /* translators: %s: Current currency symbol (e.g. $, €, ﷼) */
                    'description' => sprintf(__('Minimum cart amount to display the gateway. Current currency: %s', 'parsigate'), get_woocommerce_currency_symbol()),
                    'default' => '',
                    'placeholder' => 'xxx',
                    'min' => 0,
                    'class' => 'pg-ltr-input'
                ],
                'categories' => [
                    'title' => __('Allowed Products Categories', 'parsigate'),
                    'type' => 'multiselect',
                    'class' => 'wc-enhanced-select',
                    'description' => __('Gateway will only be displayed if cart contains products from selected categories.', 'parsigate'),
                    'default' => '',
                    'options' => $this->get_product_categories(),
                ],
                'product_ids' => [
                    'title' => __('Product IDs', 'parsigate'),
                    'type' => 'text',
                    'description' => __('Enter product IDs separated by commas. Gateway will be displayed only if ALL products in cart are from this list.', 'parsigate'),
                    'default' => '',
                    'placeholder' => __('e.g., 123, 456, 789', 'parsigate'),
                    'class' => 'pg-ltr-input'
                ]
            ];

        $this->form_fields = apply_filters('parsigate_gateway_config', $fields, $this->id);
    }

    private function get_user_roles()
    {
        $roles = [];
        if (function_exists('wp_roles')) {
            $wp_roles = wp_roles();
            $roles = $wp_roles->get_names();
        }

        return $roles;
    }

    private function get_product_categories()
    {
        $categories = array();

        $terms = get_terms(array(
            'taxonomy' => 'product_cat',
            'hide_empty' => false,
        ));

        if (!is_wp_error($terms)) {
            foreach ($terms as $term) {
                $categories[$term->term_id] = $term->name;
            }
        }

        return $categories;
    }

    public function is_available()
    {
        if (!parent::is_available()) {
            return false;
        }

        return apply_filters('parsigate_is_available_gateway', (
            $this->check_user_role() &&
            $this->check_min_cart_price() &&
            $this->check_cart_categories() &&
            $this->check_cart_products()
        ), $this->id);
    }

    private function check_user_role()
    {
        $pre = apply_filters('parsigate_pre_check_user_role', null, $this->id);
        if (!is_null($pre)) {
            return $pre;
        }

        $allowed_roles = $this->get_option('roles', array());
        if (empty($allowed_roles)) {
            return true;
        }

        if (!is_user_logged_in()) {
            return in_array('guest', $allowed_roles);
        }

        $user = wp_get_current_user();
        $user_roles = (array)$user->roles;

        return !empty(array_intersect($user_roles, $allowed_roles));
    }

    private function check_min_cart_price()
    {
        $pre = apply_filters('parsigate_pre_check_min_cart_price', null, $this->id);
        if (!is_null($pre)) {
            return $pre;
        }

        $min_price = $this->get_option('min_cart_price', 0);
        if (empty($min_price) || (float)$min_price <= 0) {
            return true;
        }

        if (!WC()->cart || WC()->cart->is_empty()) {
            return false;
        }

        $cart_total = WC()->cart->get_subtotal();
        if (apply_filters('parsigate_min_cart_amount_total', false) === true) {
            $cart_total = WC()->cart->get_total('edit');
        }
        $cart_total_float = (float)preg_replace('/[^0-9\.]/', '', $cart_total);
        return ($cart_total_float >= (float)$min_price);
    }

    private function check_cart_categories()
    {
        $pre = apply_filters('parsigate_pre_check_cart_categories', null, $this->id);
        if (!is_null($pre)) {
            return $pre;
        }

        $allowed_categories = $this->get_option('categories', array());
        if (empty($allowed_categories)) {
            return true;
        }

        if (!WC()->cart || WC()->cart->is_empty()) {
            return false;
        }

        foreach (WC()->cart->get_cart() as $cart_item) {

            $product_id = $cart_item['product_id'];
            $product_categories = wp_get_post_terms($product_id, 'product_cat', array('fields' => 'ids'));
            if (empty(array_intersect($product_categories, $allowed_categories))) {
                return false;
            }
        }

        return true;
    }

    private function check_cart_products()
    {
        $pre = apply_filters('parsigate_pre_check_cart_products', null, $this->id);
        if (!is_null($pre)) {
            return $pre;
        }

        $allowed_product_ids = $this->get_option('product_ids', '');
        if (empty($allowed_product_ids)) {
            return true;
        }

        if (!WC()->cart || WC()->cart->is_empty()) {
            return false;
        }

        $allowed_ids = array_map('intval', array_filter(explode(',', $allowed_product_ids)));
        foreach (WC()->cart->get_cart() as $cart_item) {
            $product_ids_to_check = [];

            $product_ids_to_check[] = $cart_item['product_id'];
            if ($cart_item['variation_id'] > 0) {

                $product_ids_to_check[] = $cart_item['variation_id'];
                $variation_product = wc_get_product($cart_item['variation_id']);
                if ($variation_product && $variation_product->get_parent_id() > 0) {
                    $product_ids_to_check[] = $variation_product->get_parent_id();
                }
            }

            $product_is_allowed = false;
            foreach ($product_ids_to_check as $product_id) {
                if (in_array($product_id, $allowed_ids)) {
                    $product_is_allowed = true;
                    break;
                }
            }

            if (!$product_is_allowed) {
                return false;
            }
        }

        return true;
    }

    public function get_amount($order)
    {
        $order_total = $order->get_total();
        $amount = intval($order_total);
        return apply_filters('parsigate_get_amount', WooCommerce::price($amount, $order, $this->id), $amount, $order, $this->id);
    }

    public function get_callback_url($order)
    {
        $args = apply_filters('parsigate_get_callback_query', [
            'wc-api' => $this->id,
            'order_id' => $order->get_id()
        ], $order, $this->id);

        return add_query_arg($args, get_site_url(null, '/'));
    }

    public function process_payment($order_id)
    {

        $option = WooCommerce::get_gateway_option($this->id);
        $order = wc_get_order($order_id);
        $amount = $this->get_amount($order);

        $class = new Gateway($this->driver);

        do_action('parsigate_gateway_before_process_payment', $order, $this->id, $class);

        if (isset($this->gateway['woocommerce']['before']) && is_callable($this->gateway['woocommerce']['before'])) {
            $this->gateway['woocommerce']['before']($amount, $order, $option, $class);
        }

        if (isset($this->gateway['auth']) && is_callable($this->gateway['auth'])) {

            $auth = $this->gateway['auth']($option, $class, ['order' => $order, 'amount' => $amount]);
            if ($auth instanceof \WP_Error) {

                wc_add_notice($auth->get_error_message(), 'error');
                return [
                    'result' => 'failure',
                    'messages' => $auth->get_error_message(),
                    'reload' => false
                ];
            }
        }

        $params = [];
        if (isset($this->gateway['woocommerce']['pay']) && is_callable($this->gateway['woocommerce']['pay'])) {
            $params = $this->gateway['woocommerce']['pay']($amount, $order, $option, $this->get_callback_url($order), $class);
        }

        if ($params instanceof \WP_Error) {

            wc_add_notice($params->get_error_message(), 'error');
            return [
                'result' => 'failure',
                'messages' => $params->get_error_message(),
                'reload' => false
            ];
        }

        $pay = $class->pay(apply_filters('parsigate_gateway_process_payment_params', $params, $order, $this->id));

        do_action('parsigate_gateway_after_process_payment', $pay, $order, $this->id, $class, wp_is_json_request());

        if ($pay['success'] === false) {

            $error_message = '';
            if (isset($pay['errors']) and !empty($pay['errors']) and is_array($pay['errors'])) {
                $error_message = wp_json_encode($pay['errors'], JSON_UNESCAPED_UNICODE);
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

        if (isset($pay['data']['authority']) and !empty($pay['data']['authority']) and apply_filters('parsigate_save_authority', true, $this->id) === true) {

            $order->update_meta_data('authority', $pay['data']['authority']);
            $order->save();
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
        // phpcs:disable WordPress.Security.NonceVerification.Missing
        // phpcs:disable WordPress.Security.NonceVerification.Recommended

        $action = (isset($_GET['action']) ? sanitize_text_field(wp_unslash($_GET['action'])) : '');
        $order_id = isset($_GET['order_id'])
            ? absint(wp_unslash($_GET['order_id']))
            : 0;

        if ($order_id < 1) {
            wp_die(esc_html__('Invalid order id.', 'parsigate'));
        }

        $request = apply_filters('parsigate_gateway_verify_payment_inputs', [
            'get' => wp_unslash($_GET),
            'post' => wp_unslash($_POST),
            'body' => file_get_contents('php://input'),
        ], $order_id, $this->id);

        $order = wc_get_order($order_id);

        switch ($action) {
            case 'redirect':
                $this->redirect_to_gateway($order, $request);
                break;

            default:
                $this->verify_payment($order, $request);
                break;
        }

        // phpcs:enable WordPress.Security.NonceVerification.Missing
        // phpcs:enable WordPress.Security.NonceVerification.Recommended
    }

    public function redirect_to_gateway($order, $request)
    {
        $redirect_url = isset($request['get']['url'])
            ? esc_url_raw(urldecode($request['get']['url']))
            : '';
        ?>
        <html lang="fa-IR">
        <head>
            <meta charset="UTF-8"/>
        </head>
        <body onload="document.forms['redirect'].submit()">
        <form name="redirect" method="post" action="<?php echo esc_url($redirect_url); ?>">
            <?php
            foreach ($request['get'] as $key => $value) {
                if (in_array($key, ['wc-api', 'action', 'order_id', 'url'], true)) {
                    continue;
                }
                ?>
                <input type="hidden" name="<?php echo esc_attr(trim($key)); ?>" value="<?php echo esc_attr($value); ?>">
                <?php
            }
            ?>
        </form>
        </body>
        </html>
        <?php
        exit;
    }

    public function verify_payment($order, $request)
    {
        $option = WooCommerce::get_gateway_option($this->id);
        $amount = $this->get_amount($order);

        do_action('parsigate_gateway_before_verify_payment', $order, $this->id, $request);

        $class = new Gateway($this->driver);

        if (isset($this->gateway['auth']) && is_callable($this->gateway['auth'])) {

            $auth = $this->gateway['auth']($option, $class, ['order' => $order, 'amount' => $amount]);
            if ($auth instanceof \WP_Error) {
                $this->set_failed_payment($order, ['message' => $auth->get_error_message()]);
            }
        }

        $params = [];
        if (isset($this->gateway['woocommerce']['verify']) && is_callable($this->gateway['woocommerce']['verify'])) {
            $params = $this->gateway['woocommerce']['verify']($amount, $order, $option, $class, $request);
        }

        if ($params instanceof \WP_Error) {
            $this->set_failed_payment($order, ['message' => $params->get_error_message()]);
        }

        $verify = $class->verify(apply_filters('parsigate_gateway_verify_payment_params', $params, $order, $this->id));

        do_action('parsigate_gateway_after_verify_payment', $verify, $order, $this->id, wp_is_json_request(), $request);

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
        if (apply_filters('parsigate_gateway_save_order_note', true, $this->id, $order) === true) {

            /* translators: %s: Transaction ID number */
            $note = sprintf(__("Payment Success, Transaction ID: %s", 'parsigate'), $transaction_id);
            $order->add_order_note(apply_filters('parsigate_gateway_completed_order_note', $note, $order, $transaction_id, $this->id));
        }

        // Remove cart.
        WC()->cart->empty_cart();

        // Completed handler.
        if (isset($this->gateway['woocommerce']['completed']) && is_callable($this->gateway['woocommerce']['completed'])) {
            $this->gateway['woocommerce']['completed']($order, $transaction_id, $verify);
        }

        // Action
        do_action('parsigate_gateway_completed_payment', $order, $transaction_id, $this->id, $verify);

        // Redirect
        wp_safe_redirect($this->get_return_url($order));
        exit;
    }

    public function set_failed_payment($order, $verify)
    {
        // Notices
        $error_message = $this->get_option('failed_massage');
        if (empty($error_message)) {

            if (isset($verify['errors']) and !empty($verify['errors']) and is_array($verify['errors'])) {
                $error_message = wp_json_encode($verify['errors'], JSON_UNESCAPED_UNICODE);
            }
            if (isset($verify['message']) and !empty($verify['message'])) {
                $error_message = $verify['message'];
            }
        }
        wc_add_notice($error_message, 'error');

        // Action
        do_action('parsigate_gateway_failed_payment', $order, $verify);

        // Redirect
        wp_safe_redirect(wc_get_checkout_url());
        exit;
    }

}
