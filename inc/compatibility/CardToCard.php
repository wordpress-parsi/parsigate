<?php

namespace ParsiGate\compatibility;

if (!defined('ABSPATH')) exit;

class CardToCard extends Base
{

    public string $gateway_id = 'cardtocard';

    public static string $attachment_id = '_cardtocard_receipt_image_id';

    public function __construct()
    {

        add_filter('parsigate_gateway_set_completed', [$this, 'parsigate_gateway_set_completed'], 30, 5);
        add_filter('woocommerce_payment_complete_order_status', [$this, 'payment_completed_status'], 20, 3);
        add_action('woocommerce_admin_order_data_after_billing_address', [$this, 'render_admin_receipt'], 20);
        add_action('woocommerce_before_delete_order', [$this, 'delete_receipt_attachment'], 10, 2);
        add_action('woocommerce_receipt_' . $this->gateway_id, [$this, 'render_receipt_area'], 20);
        add_action('woocommerce_thankyou_' . $this->gateway_id, [$this, 'render_receipt_area'], 20);
        add_action('woocommerce_view_order', [$this, 'render_view_order_area'], 20);
        add_action('template_redirect', [$this, 'handle_upload']);
    }

    public function parsigate_gateway_set_completed($return, $order, $transaction_id, $gateway_id, $gateway)
    {
        if (!$this->enable()) {
            return $return;
        }

        if ($gateway_id != $this->gateway_id) {
            return $return;
        }

        if (!$order->has_status('pending')) {
            $order->update_status('pending');
        }

        if (function_exists('WC')) {
            WC()->cart->empty_cart();
        }

        $return_url = apply_filters(
            'parsigate_' . $this->gateway_id . '_payment_completed_url',
            $order->get_checkout_payment_url(true),
            $order
        );
        wp_safe_redirect($return_url);
        exit;
    }

    public function payment_completed_status($status, $order_id, $order): string
    {

        if (!$this->enable()) {
            return $status;
        }

        if ($order->get_payment_method() != $this->gateway_id) {
            return $status;
        }

        return apply_filters('parsigate_' . $this->gateway_id . '_payment_completed_status', 'on-hold');
    }

    public function render_admin_receipt($order)
    {
        if (!$this->enable()) {
            return;
        }

        if (!$order instanceof \WC_Order || $this->gateway_id !== $order->get_payment_method()) {
            return;
        }

        $attachment_id = absint($order->get_meta(static::$attachment_id, true));

        echo '<div class="parsigate-cardtocard-admin-receipt">';
        echo '<p><strong>' . esc_html__('Deposit Image', 'parsigate') . '</strong></p>';

        if (!$attachment_id) {
            echo '<p>' . esc_html__('No receipt has been submitted for this order.', 'parsigate') . '</p>';
            echo '</div>';
            return;
        }

        $thumb = wp_get_attachment_image(
            $attachment_id,
            array(120, 120),
            false,
            array('style' => 'max-width:120px;height:auto;display:block;')
        );
        $full = wp_get_attachment_url($attachment_id);

        if ($thumb and $full) {
            echo '<a href="' . esc_url($full) . '" target="_blank" rel="noopener noreferrer" title="' . esc_attr__('View full image', 'parsigate') . '">';
            echo wp_kses_post($thumb);
            echo '</a>';
        }

        echo '</div>';
    }

    public function delete_receipt_attachment($order_id, $order = null)
    {
        if (!$this->enable()) {
            return;
        }

        if (!$order instanceof \WC_Order) {
            $order = wc_get_order($order_id);
        }

        if (!$order) {
            return;
        }

        if ($order->get_payment_method() !== $this->gateway_id) {
            return;
        }

        $attachment_id = absint($order->get_meta(static::$attachment_id, true));

        if ($attachment_id > 0 and get_post_type($attachment_id) === 'attachment') {
            wp_delete_attachment($attachment_id, true);
        }

        $order->delete_meta_data(static::$attachment_id);
        $order->save_meta_data();
    }

    public function render_receipt_area($order_id)
    {
        $this->render($order_id);
    }

    public function render_view_order_area($order_id)
    {
        $order = wc_get_order($order_id);
        if (!$order || $this->gateway_id !== $order->get_payment_method()) {
            return;
        }

        $this->render($order_id);
    }

    private function render($order_id)
    {
        // phpcs:disable WordPress.Security.NonceVerification.Missing
        // phpcs:disable WordPress.Security.NonceVerification.Recommended

        if (!$this->enable()) {
            return;
        }

        $order = wc_get_order($order_id);
        if (!$order || $this->gateway_id !== $order->get_payment_method()) {
            return;
        }

        if ($order->is_paid()) {
            return;
        }

        $wc_settings = $this->wc();
        $max_size_mb = $wc_settings['max_file_size'] ?? '2';
        $attachment_id = absint($order->get_meta(static::$attachment_id, true));

        $message = '';
        if (isset($_GET['parsigate_cardtocard_notice'])) {

            $notice = sanitize_key(wp_unslash($_GET['parsigate_cardtocard_notice']));
            $messages = array(
                'error' => __('Failed to upload the receipt. Please try again.', 'parsigate'),
                'size' => __('The receipt file size must not exceed the allowed limit.', 'parsigate'),
                'type' => __('Invalid file format. Only JPG, PNG, and WebP are allowed.', 'parsigate'),
                'already' => __('A receipt has already been submitted for this order.', 'parsigate'),
                'success' => __('Your payment receipt has been submitted successfully.', 'parsigate'),
            );
            if (isset($messages[$notice])) {
                $class = 'success' === $notice ? 'woocommerce-message' : 'woocommerce-error';
                $message = '<div class="' . esc_attr($class) . '">' . esc_html($messages[$notice]) . '</div>';
            }
        }

        $action = add_query_arg('parsigate_cardtocard_upload', '1', $order->get_checkout_payment_url(true));
        $template = apply_filters(
            'parsigate_cardtocard_gateway_template',
            \ParsiGate::$plugin_path . '/inc/templates/cardtocard-gateway.php'
        );

        if (file_exists($template)) {
            include $template;
        }

        // phpcs:enable WordPress.Security.NonceVerification.Missing
        // phpcs:enable WordPress.Security.NonceVerification.Recommended
    }

    public function handle_upload()
    {
        if (empty($_GET['parsigate_cardtocard_upload']) || '1' !== $_GET['parsigate_cardtocard_upload']) {
            return;
        }

        $order_id = isset($_POST['parsigate_cardtocard_order_id']) ? absint($_POST['parsigate_cardtocard_order_id']) : 0;
        $order = $order_id ? wc_get_order($order_id) : false;

        if (!$order || $this->gateway_id !== $order->get_payment_method()) {
            wp_die(esc_html__('Invalid order.', 'parsigate'));
        }

        if (!isset($_POST['parsigate_cardtocard_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['parsigate_cardtocard_nonce'])), 'parsigate_cardtocard_upload_receipt_' . $order_id)) {
            wp_die(esc_html__('Invalid request.', 'parsigate'));
        }

        if (!$this->can_access_order($order)) {
            wp_die(esc_html__('You do not have permission to access this order.', 'parsigate'), 403);
        }

        if ($order->get_meta(static::$attachment_id, true)) {
            $this->redirect_with_notice($order, 'already');
        }

        if (empty($_FILES['parsigate_cardtocard_receipt']) || !isset($_FILES['parsigate_cardtocard_receipt']['error'])) {
            $this->redirect_with_notice($order, 'error');
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        add_filter('wp_handle_upload_prefilter', [$this, 'parsigate_limit_upload_size'], 20, 3);

        $attachment_id = media_handle_upload(
            'parsigate_cardtocard_receipt',
            0,
            [
                'post_title' => sprintf(
                /* translators: %s: order number */
                    __('Card to Card Receipt for Order #%s', 'parsigate'),
                    $order->get_order_number()
                ),
                'post_content' => '',
                'post_status' => 'inherit',
            ],
            [
                'test_form' => false,
                'mimes' => [
                    'jpg|jpeg|jpe' => 'image/jpeg',
                    'png' => 'image/png',
                    'webp' => 'image/webp',
                ],
            ]
        );

        if (is_wp_error($attachment_id)) {
            $this->redirect_with_notice($order, 'error');
        }

        remove_filter('wp_handle_upload_prefilter', [$this, 'parsigate_limit_upload_size'], 20);

        $order->update_meta_data(static::$attachment_id, absint($attachment_id));
        $order->save();
        $order->payment_complete();

        $return_url = $order->get_checkout_order_received_url();
        wp_safe_redirect($return_url);
        exit;
    }

    public function parsigate_limit_upload_size($file)
    {
        $wc_settings = $this->wc();
        $max_size_mb = $wc_settings['max_file_size'] ?? 2;
        $max_size_bytes = $max_size_mb * 1024 * 1024;

        if ($file['size'] > $max_size_bytes) {
            $file['error'] = __('File size exceeds the allowed limit.', 'parsigate');
        }

        return $file;
    }

    private function can_access_order($order): bool
    {
        // phpcs:disable WordPress.Security.NonceVerification.Missing
        // phpcs:disable WordPress.Security.NonceVerification.Recommended

        if (is_user_logged_in()) {
            return (int)$order->get_user_id() === get_current_user_id();
        }

        $order_key = isset($_GET['key']) ? wc_clean(sanitize_text_field(wp_unslash($_GET['key']))) : '';
        return $order_key and hash_equals((string)$order->get_order_key(), (string)$order_key);

        // phpcs:enable WordPress.Security.NonceVerification.Missing
        // phpcs:enable WordPress.Security.NonceVerification.Recommended
    }

    private function redirect_with_notice($order, $notice)
    {
        $url = add_query_arg(
            'parsigate_cardtocard_notice',
            $notice,
            $order->get_checkout_payment_url(true)
        );
        wp_safe_redirect($url);
        exit;
    }
}

new CardToCard();