<?php

namespace ParsiGate\compatibility;

if (!defined('ABSPATH')) exit;

class CardToCard extends Base
{

    public string $gateway_id = 'cardtocard';

    /**
     * Legacy (<= 1.1.0) meta key: receipt stored as a public media library attachment.
     */
    public static string $attachment_id = '_cardtocard_receipt_image_id';

    /**
     * Receipt file name inside the protected receipts directory.
     */
    public static string $receipt_file = '_cardtocard_receipt_file';

    public static string $receipts_dir = 'parsigate-receipts';

    public static string $migration_option = 'parsigate_cardtocard_receipts_migrated';

    private array $allowed_mimes = [
        'jpg|jpeg|jpe' => 'image/jpeg',
        'png' => 'image/png',
        'webp' => 'image/webp',
    ];

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
        add_action('admin_post_parsigate_cardtocard_receipt', [$this, 'serve_receipt']);
        add_action('admin_init', [$this, 'migrate_legacy_receipts']);
    }

    public function parsigate_gateway_set_completed($return, $order, $transaction_id, $gateway_id, $gateway)
    {
        if (!$this->enable()) {
            return $return;
        }

        if ($gateway_id != $this->gateway_id) {
            return $return;
        }

        // Only orders still awaiting payment may (re)enter the receipt step.
        // Orders that are on-hold / processing / completed must never be reset to pending
        // by someone hitting the callback URL.
        if (!$order->has_status(['pending', 'failed'])) {
            wp_safe_redirect($order->get_checkout_order_received_url());
            exit;
        }

        if ($order->has_status('failed')) {
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
        if (!$order instanceof \WC_Order || $this->gateway_id !== $order->get_payment_method()) {
            return;
        }

        $this->maybe_migrate_order($order);

        $has_receipt = $this->has_receipt($order);
        if (!$has_receipt && !$this->enable()) {
            return;
        }

        echo '<div class="parsigate-cardtocard-admin-receipt">';
        echo '<p><strong>' . esc_html__('Deposit Image', 'parsigate') . '</strong></p>';

        if (!$has_receipt) {
            echo '<p>' . esc_html__('No receipt has been submitted for this order.', 'parsigate') . '</p>';
            echo '</div>';
            return;
        }

        $path = $this->get_receipt_path($order);
        if ($path) {
            $url = $this->get_receipt_view_url($order);
            echo '<a href="' . esc_url($url) . '" target="_blank" rel="noopener noreferrer" title="' . esc_attr__('View full image', 'parsigate') . '">';
            echo '<img src="' . esc_url($url) . '" alt="' . esc_attr__('Deposit Image', 'parsigate') . '" style="max-width:120px;height:auto;display:block;" />';
            echo '</a>';
        } else {
            // Legacy receipt that could not be moved to the protected directory.
            $attachment_id = absint($order->get_meta(static::$attachment_id, true));
            $thumb = $attachment_id ? wp_get_attachment_image($attachment_id, array(120, 120), false, array('style' => 'max-width:120px;height:auto;display:block;')) : '';
            $full = $attachment_id ? wp_get_attachment_url($attachment_id) : '';
            if ($thumb and $full) {
                echo '<a href="' . esc_url($full) . '" target="_blank" rel="noopener noreferrer" title="' . esc_attr__('View full image', 'parsigate') . '">';
                echo wp_kses_post($thumb);
                echo '</a>';
            }
        }

        echo '</div>';
    }

    public function delete_receipt_attachment($order_id, $order = null)
    {
        if (!$order instanceof \WC_Order) {
            $order = wc_get_order($order_id);
        }

        if (!$order) {
            return;
        }

        if ($order->get_payment_method() !== $this->gateway_id) {
            return;
        }

        $path = $this->get_receipt_path($order);
        if ($path) {
            wp_delete_file($path);
        }

        $attachment_id = absint($order->get_meta(static::$attachment_id, true));

        if ($attachment_id > 0 and get_post_type($attachment_id) === 'attachment') {
            wp_delete_attachment($attachment_id, true);
        }

        $order->delete_meta_data(static::$attachment_id);
        $order->delete_meta_data(static::$receipt_file);
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
        $max_size_mb = (float)($wc_settings['max_file_size'] ?? 2);
        if ($max_size_mb <= 0) {
            $max_size_mb = 2;
        }
        // Used by the template: truthy once a receipt has been submitted.
        $attachment_id = $this->has_receipt($order) ? 1 : 0;

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

        if ($this->has_receipt($order)) {
            $this->redirect_with_notice($order, 'already');
        }

        if (!$order->has_status(['pending', 'failed'])) {
            $this->redirect_with_notice($order, 'error');
        }

        if (empty($_FILES['parsigate_cardtocard_receipt']) || !isset($_FILES['parsigate_cardtocard_receipt']['error'])) {
            $this->redirect_with_notice($order, 'error');
        }

        $upload_error = (int)$_FILES['parsigate_cardtocard_receipt']['error'];
        $upload_size = isset($_FILES['parsigate_cardtocard_receipt']['size']) ? (int)$_FILES['parsigate_cardtocard_receipt']['size'] : 0;
        if (in_array($upload_error, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true) || $upload_size > $this->max_upload_bytes()) {
            $this->redirect_with_notice($order, 'size');
        }
        if (UPLOAD_ERR_OK !== $upload_error) {
            $this->redirect_with_notice($order, 'error');
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';

        if (!$this->prepare_receipts_dir()) {
            $this->redirect_with_notice($order, 'error');
        }

        add_filter('wp_handle_upload_prefilter', [$this, 'parsigate_limit_upload_size'], 20);
        add_filter('upload_dir', [$this, 'receipts_upload_dir'], 99);

        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- the file array is validated by wp_handle_upload().
        $upload = wp_handle_upload($_FILES['parsigate_cardtocard_receipt'], [
            'test_form' => false,
            'mimes' => $this->allowed_mimes,
        ]);

        remove_filter('upload_dir', [$this, 'receipts_upload_dir'], 99);
        remove_filter('wp_handle_upload_prefilter', [$this, 'parsigate_limit_upload_size'], 20);

        if (!is_array($upload) || !empty($upload['error']) || empty($upload['file'])) {
            $this->redirect_with_notice($order, 'type');
        }

        // Make sure the file really is an image.
        if (!wp_getimagesize($upload['file'])) {
            wp_delete_file($upload['file']);
            $this->redirect_with_notice($order, 'type');
        }

        $order->update_meta_data(static::$receipt_file, wp_basename($upload['file']));
        $order->save();
        $order->payment_complete();

        $return_url = $order->get_checkout_order_received_url();
        wp_safe_redirect($return_url);
        exit;
    }

    private function max_upload_bytes(): int
    {
        $wc_settings = $this->wc();
        $max_size_mb = (float)($wc_settings['max_file_size'] ?? 2);
        if ($max_size_mb <= 0) {
            $max_size_mb = 2;
        }

        return (int)($max_size_mb * 1024 * 1024);
    }

    public function parsigate_limit_upload_size($file)
    {
        if ($file['size'] > $this->max_upload_bytes()) {
            $file['error'] = __('File size exceeds the allowed limit.', 'parsigate');
            return $file;
        }

        // Unguessable file name; only the extension of the original name is kept.
        $ext = strtolower(pathinfo((string)$file['name'], PATHINFO_EXTENSION));
        $file['name'] = wp_generate_password(32, false) . ($ext ? '.' . preg_replace('/[^a-z0-9]/', '', $ext) : '');

        return $file;
    }

    /**
     * Send uploads of this request to the protected receipts directory.
     */
    public function receipts_upload_dir($uploads)
    {
        $uploads['subdir'] = '/' . static::$receipts_dir;
        $uploads['path'] = $uploads['basedir'] . $uploads['subdir'];
        $uploads['url'] = $uploads['baseurl'] . $uploads['subdir'];

        return $uploads;
    }

    /**
     * Stream a receipt image to users who can manage orders.
     */
    public function serve_receipt()
    {
        $order_id = isset($_GET['order_id']) ? absint($_GET['order_id']) : 0;

        if (!$order_id || !isset($_GET['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'parsigate_cardtocard_view_receipt_' . $order_id)) {
            wp_die(esc_html__('Invalid request.', 'parsigate'), '', ['response' => 403]);
        }

        if (!current_user_can('edit_shop_orders')) {
            wp_die(esc_html__('You do not have permission to access this order.', 'parsigate'), '', ['response' => 403]);
        }

        $order = wc_get_order($order_id);
        if (!$order || $this->gateway_id !== $order->get_payment_method()) {
            wp_die(esc_html__('Invalid order.', 'parsigate'), '', ['response' => 404]);
        }

        $this->maybe_migrate_order($order);

        $path = $this->get_receipt_path($order);
        if (!$path) {
            wp_die(esc_html__('No receipt has been submitted for this order.', 'parsigate'), '', ['response' => 404]);
        }

        $type = wp_check_filetype($path, $this->allowed_mimes);

        nocache_headers();
        header('Content-Type: ' . ($type['type'] ?: 'application/octet-stream'));
        header('Content-Length: ' . filesize($path));
        header('Content-Disposition: inline; filename="receipt-' . $order_id . '.' . ($type['ext'] ?: 'img') . '"');
        header('X-Content-Type-Options: nosniff');
        header('X-Robots-Tag: noindex, nofollow');

        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile -- streaming a binary image.
        readfile($path);
        exit;
    }

    /**
     * Move receipts uploaded by older versions (public media library attachments)
     * into the protected directory, a few orders per admin request.
     */
    public function migrate_legacy_receipts()
    {
        if (wp_doing_ajax() || '1' === get_option(static::$migration_option) || !current_user_can('edit_shop_orders') || !function_exists('wc_get_orders')) {
            return;
        }

        $batch = 20;
        $orders = wc_get_orders([
            'limit' => $batch,
            'type' => 'shop_order',
            'status' => array_keys(wc_get_order_statuses()),
            'payment_method' => $this->gateway_id,
            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- one-off migration in small batches.
            'meta_query' => [
                [
                    'key' => static::$attachment_id,
                    'compare' => 'EXISTS',
                ],
            ],
        ]);

        $failed = 0;
        foreach ($orders as $order) {
            if (!$this->maybe_migrate_order($order)) {
                $failed++;
            }
        }

        // Done when nothing (or only orders that cannot be migrated) is left.
        if (count($orders) < $batch || $failed === count($orders)) {
            update_option(static::$migration_option, '1', false);
        }
    }

    /**
     * Move one order's legacy attachment into the protected directory.
     *
     * @return bool True when the order has no legacy receipt left.
     */
    private function maybe_migrate_order($order): bool
    {
        $attachment_id = absint($order->get_meta(static::$attachment_id, true));
        if (!$attachment_id) {
            return true;
        }

        if ($this->get_receipt_path($order)) {
            // Already migrated; just clean up the public copy.
            if (get_post_type($attachment_id) === 'attachment') {
                wp_delete_attachment($attachment_id, true);
            }
            $order->delete_meta_data(static::$attachment_id);
            $order->save_meta_data();
            return true;
        }

        $source = get_attached_file($attachment_id);
        if (!$source || !file_exists($source) || !$this->prepare_receipts_dir()) {
            return false;
        }

        $ext = strtolower(pathinfo($source, PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'jpe', 'png', 'webp'], true)) {
            return false;
        }

        $name = wp_generate_password(32, false) . '.' . $ext;
        $target = $this->get_receipts_path() . '/' . $name;

        if (!copy($source, $target)) {
            return false;
        }

        $order->update_meta_data(static::$receipt_file, $name);
        $order->delete_meta_data(static::$attachment_id);
        $order->save_meta_data();

        // Removes the public original and all generated sizes.
        wp_delete_attachment($attachment_id, true);

        return true;
    }

    private function has_receipt($order): bool
    {
        return (bool)$order->get_meta(static::$receipt_file, true) || (bool)$order->get_meta(static::$attachment_id, true);
    }

    private function get_receipts_path(): string
    {
        $uploads = wp_upload_dir(null, false);
        return untrailingslashit($uploads['basedir']) . '/' . static::$receipts_dir;
    }

    /**
     * Absolute path of the order's protected receipt, or empty string.
     */
    private function get_receipt_path($order): string
    {
        $name = wp_basename((string)$order->get_meta(static::$receipt_file, true));
        if (!$name || !preg_match('/^[A-Za-z0-9]+\.(jpe?g|jpe|png|webp)$/i', $name)) {
            return '';
        }

        $path = $this->get_receipts_path() . '/' . $name;
        return is_file($path) ? $path : '';
    }

    private function get_receipt_view_url($order): string
    {
        return wp_nonce_url(
            add_query_arg(
                [
                    'action' => 'parsigate_cardtocard_receipt',
                    'order_id' => $order->get_id(),
                ],
                admin_url('admin-post.php')
            ),
            'parsigate_cardtocard_view_receipt_' . $order->get_id()
        );
    }

    /**
     * Create the receipts directory and block direct web access to it.
     */
    private function prepare_receipts_dir(): bool
    {
        $dir = $this->get_receipts_path();

        if (!wp_mkdir_p($dir)) {
            return false;
        }

        $files = [
            '.htaccess' => "Options -Indexes\n<IfModule mod_authz_core.c>\n    Require all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n    Order allow,deny\n    Deny from all\n</IfModule>\n",
            'index.php' => "<?php\n// Silence is golden.\n",
            'index.html' => '',
        ];

        foreach ($files as $file => $content) {
            if (!file_exists($dir . '/' . $file)) {
                // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- small protection files inside uploads.
                file_put_contents($dir . '/' . $file, $content);
            }
        }

        return true;
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