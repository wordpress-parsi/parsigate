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

    /**
     * Accept a receipt without marking a Card to Card order paid.
     *
     * The MySQL advisory lock serializes submissions across PHP workers.  Both
     * status and receipt filename are verified against persisted storage after
     * WooCommerce saves; the in-memory order can be stale on save-hook errors.
     */
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
            wp_die(esc_html__('You do not have permission to access this order.', 'parsigate'), '', ['response' => 403]);
        }

        global $wpdb;
        // Connection-local advisory lock, not a WordPress object-cache mutex.
        $lock_name = 'parsigate_ctc_' . md5(DB_NAME . ':' . $wpdb->prefix . ':' . $order_id);
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Named locks are stateful and must not be cached.
        $acquired = $wpdb->get_var($wpdb->prepare('SELECT GET_LOCK(%s, 0)', $lock_name));
        if ('1' !== (string) $acquired) {
            wp_die(esc_html__('Another receipt submission is in progress. Please try again.', 'parsigate'), '', ['response' => 409]);
        }

        $result = 'error';
        try {
            // Reload *after* taking the lock. A competing request can change
            // the status and the receipt meta before we enter this block.
            $order = wc_get_order($order_id);
            if (!$order || $this->gateway_id !== $order->get_payment_method()) {
                $result = 'invalid';
            } elseif (!$order->has_status(['pending', 'failed']) || $this->has_receipt($order)) {
                $result = 'conflict';
            } elseif (empty($_FILES['parsigate_cardtocard_receipt']) || !isset($_FILES['parsigate_cardtocard_receipt']['error'])) {
                $result = 'error';
            } else {
                $upload_error = (int) $_FILES['parsigate_cardtocard_receipt']['error'];
                $upload_size = isset($_FILES['parsigate_cardtocard_receipt']['size']) ? (int) $_FILES['parsigate_cardtocard_receipt']['size'] : 0;
                if (in_array($upload_error, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true) || $upload_size > $this->max_upload_bytes()) {
                    $result = 'size';
                } elseif (UPLOAD_ERR_OK !== $upload_error) {
                    $result = 'error';
                } elseif (!$this->prepare_receipts_dir()) {
                    $result = 'error';
                } else {
                    require_once ABSPATH . 'wp-admin/includes/file.php';
                    $tracked_file = '';
                    $expected_file = '';
                    $receipt_name = '';
                    $completed = false;
                    // Upstream's prefilter randomizes the filename at priority 20.
                    // Capture the final name after other prefilters, before file movement.
                    $capture_name = static function ($file) use (&$expected_file) {
                        if (is_array($file) && isset($file['name']) && is_string($file['name'])) {
                            $expected_file = wp_basename($file['name']);
                        }
                        return $file;
                    };
                    $capture_file = static function ($upload) use (&$tracked_file) {
                        if (is_array($upload) && isset($upload['file']) && is_string($upload['file'])) {
                            $tracked_file = $upload['file'];
                        }
                        return $upload;
                    };
                    add_filter('wp_handle_upload_prefilter', [$this, 'parsigate_limit_upload_size'], 20);
                    add_filter('wp_handle_upload_prefilter', $capture_name, 999999);
                    add_filter('upload_dir', [$this, 'receipts_upload_dir'], 99);
                    add_filter('wp_handle_upload', $capture_file, -100000);
                    try {
                        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Validated by the core upload handler and explicit MIME/image checks.
                        $upload = wp_handle_upload($_FILES['parsigate_cardtocard_receipt'], [
                            'test_form' => false,
                            'mimes' => $this->allowed_mimes,
                        ]);
                        if (!is_array($upload) || !empty($upload['error']) || empty($upload['file'])) {
                            $result = 'type';
                        } else {
                            $tracked_file = $upload['file'];
                            $receipt_name = wp_basename($tracked_file);
                            if (!$this->ctc_is_private_receipt_file($tracked_file)
                                || !preg_match('/^[A-Za-z0-9]{32}\.(jpe?g|jpe|png|webp)$/i', $receipt_name)
                                || !wp_getimagesize($tracked_file)) {
                                $result = 'type';
                            } else {
                                $order->update_meta_data(static::$receipt_file, $receipt_name);
                                // A submitted receipt is NOT an approved payment.
                                $order->set_status('on-hold', __('Payment receipt submitted; awaiting manual verification.', 'parsigate'));
                                $returned_id = $order->save();
                                $stored = $this->ctc_read_persisted_receipt_state($order_id);
                                if (absint($returned_id) !== $order_id || $stored['error']
                                    || 'wc-on-hold' !== $stored['status']
                                    || $receipt_name !== $stored['receipt']) {
                                    throw new \RuntimeException('Card to Card receipt was not persisted in the expected state.');
                                }
                                $completed = true;
                                $result = 'success';
                            }
                        }
                    } catch (\Throwable $error) {
                        // A save hook can throw *after* the DB transaction commits.
                        // Decide cleanup using the database, not the exception alone.
                        $result = 'error';
                    } finally {
                        remove_filter('wp_handle_upload', $capture_file, -100000);
                        remove_filter('upload_dir', [$this, 'receipts_upload_dir'], 99);
                        remove_filter('wp_handle_upload_prefilter', $capture_name, 999999);
                        remove_filter('wp_handle_upload_prefilter', [$this, 'parsigate_limit_upload_size'], 20);
                        if (!$completed) {
                            $stored = $this->ctc_read_persisted_receipt_state($order_id);
                            $tracked_name = $receipt_name;
                            if ('' === $tracked_name && '' !== $tracked_file) {
                                $tracked_name = wp_basename($tracked_file);
                            }
                            if ('' === $tracked_name) {
                                $tracked_name = $expected_file;
                            }
                            $committed = !$stored['error'] && 'wc-on-hold' === $stored['status']
                                && '' !== $tracked_name && $tracked_name === $stored['receipt'];
                            $safe_to_delete = !$stored['error']
                                && in_array($stored['status'], ['wc-pending', 'wc-failed'], true)
                                && '' === $stored['receipt'];
                            if ($committed) {
                                // A post-save hook failed, but the order and file are committed.
                                $completed = true;
                                $result = 'success';
                            } elseif ($safe_to_delete) {
                                $path = '' !== $tracked_file ? $tracked_file : $this->get_receipts_path() . '/' . $expected_file;
                                $this->ctc_delete_uncommitted_receipt_file($path);
                            } elseif (function_exists('wc_get_logger')) {
                                wc_get_logger()->warning(
                                    'Card to Card receipt upload has ambiguous persisted state; manual reconciliation required.',
                                    ['source' => 'parsigate', 'order_id' => $order_id]
                                );
                            }
                        }
                    }
                }
            }
        } finally {
            // Always release the same DB connection's advisory lock.
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- RELEASE_LOCK is stateful and must not be cached.
            $wpdb->get_var($wpdb->prepare('SELECT RELEASE_LOCK(%s)', $lock_name));
        }

        if ('invalid' === $result) {
            wp_die(esc_html__('Invalid order.', 'parsigate'));
        }
        if ('conflict' === $result) {
            wp_die(esc_html__('This order is not awaiting a payment receipt.', 'parsigate'), '', ['response' => 409]);
        }
        if ('success' !== $result) {
            $this->redirect_with_notice($order, $result);
        }
        wp_safe_redirect($order->get_checkout_order_received_url());
        exit;
    }

    /**
     * Read the persisted order status and receipt filename, bypassing object caches.
     * A WC_Order::save() return value alone does not prove the write succeeded.
     */
    private function ctc_read_persisted_receipt_state(int $order_id): array
    {
        global $wpdb;
        $hpos = class_exists('\\Automattic\\WooCommerce\\Utilities\\OrderUtil')
            && \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
        $status_table = $hpos ? $wpdb->prefix . 'wc_orders' : $wpdb->posts;
        $meta_table = $hpos ? $wpdb->prefix . 'wc_orders_meta' : $wpdb->postmeta;
        if (!preg_match('/^[A-Za-z0-9_]+$/D', $status_table)
            || !preg_match('/^[A-Za-z0-9_]+$/D', $meta_table)) {
            return ['status' => null, 'receipt' => '', 'error' => true];
        }
        if ($hpos) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Identifiers are validated; status must be read uncached.
            $status = $wpdb->get_var($wpdb->prepare("SELECT status FROM {$status_table} WHERE id = %d", $order_id));
            $status_error = (string) $wpdb->last_error;
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Validated table identifiers and parameterized values.
            $receipt = $wpdb->get_var($wpdb->prepare("SELECT meta_value FROM {$meta_table} WHERE order_id = %d AND meta_key = %s ORDER BY id DESC LIMIT 1", $order_id, static::$receipt_file));
            $receipt_error = (string) $wpdb->last_error;
        } else {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Core posts table; uncached status read required for rollback.
            $status = $wpdb->get_var($wpdb->prepare("SELECT post_status FROM {$status_table} WHERE ID = %d AND post_type = 'shop_order'", $order_id));
            $status_error = (string) $wpdb->last_error;
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Core postmeta table; values are parameterized.
            $receipt = $wpdb->get_var($wpdb->prepare("SELECT meta_value FROM {$meta_table} WHERE post_id = %d AND meta_key = %s ORDER BY meta_id DESC LIMIT 1", $order_id, static::$receipt_file));
            $receipt_error = (string) $wpdb->last_error;
        }
        return [
            'status' => $status,
            'receipt' => is_string($receipt) ? $receipt : '',
            'error' => '' !== $status_error || '' !== $receipt_error || null === $status,
        ];
    }

    /**
     * Confirm a path is an existing file inside the private receipts directory.
     */
    private function ctc_is_private_receipt_file(string $path): bool
    {
        $base = realpath($this->get_receipts_path());
        $file = realpath($path);
        if (false === $base || false === $file || !is_file($file)) {
            return false;
        }
        $base = rtrim(wp_normalize_path($base), '/');
        $file = wp_normalize_path($file);
        return 0 === strpos($file, $base . '/');
    }

    /**
     * Delete only the new file, never a path outside the private directory.
     */
    private function ctc_delete_uncommitted_receipt_file(string $path): void
    {
        if ($this->ctc_is_private_receipt_file($path)) {
            $verified_path = realpath($path);
            if (false !== $verified_path && $this->ctc_is_private_receipt_file($verified_path)) {
                wp_delete_file($verified_path);
            }
        } elseif (is_file($path) && function_exists('wc_get_logger')) {
            // Refuse to delete outside our private directory; log without a path.
            wc_get_logger()->warning('Card to Card upload path is outside the private receipts directory; manual review required.', ['source' => 'parsigate']);
        }
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