<?php
/**
 * Takes over the bank gateways that used to ship with Parsi Date
 *
 * Parsi Date (6.6+) had its own Parsian, Pasargad, Mellat and Melli gateways. They
 * use the same gateway IDs, and so the same WooCommerce option
 * (woocommerce_{id}_settings), as the ParsiGate ones. This class:
 *  - copies the few settings whose field names differ,
 *  - switches the gateway on in ParsiGate once its settings are complete,
 *  - reports it to Parsi Date (wp_parsidate_legacy_gateways_taken_over), which then
 *    stops loading its own copy, so the two never run side by side,
 *  - answers the old callback URLs (wc-api=wpp_wc_{id}_gateway) of payments that
 *    started before the switch.
 *
 * Loaded from the main plugin file, before Parsi Date asks for the list: WooCommerce
 * fires before_woocommerce_init on init/0, before wp_parsidate_addons_load.
 *
 * @package ParsiGate
 */

namespace ParsiGate;

if (!defined('ABSPATH')) exit;

class LegacyParsidateGateways
{
    public const GATEWAYS = ['parsian', 'pasargad', 'mellat', 'melli'];

    /**
     * Gateways already switched on once; never switched on again if the user turns them off
     */
    public const MIGRATED_OPTION = 'parsigate_legacy_gateways_migrated';

    /**
     * Settings the ParsiGate gateway needs to work
     */
    public const REQUIRED_SETTINGS = [
        'parsian' => ['merchant_id'],
        'pasargad' => ['terminal_id', 'username', 'password'],
        'mellat' => ['terminal', 'username', 'password'],
        'melli' => ['terminal_id', 'merchant_id', 'key'],
    ];

    /**
     * Parsi Date field => ParsiGate field. Mellat and Melli use the same names.
     */
    public const RENAMED_SETTINGS = [
        'parsian' => ['parsian_login_account' => 'merchant_id'],
        'pasargad' => ['pasargad_terminal_id' => 'terminal_id'],
    ];

    public function __construct()
    {
        add_action('init', [$this, 'migrate'], -5);
        add_filter('wp_parsidate_legacy_gateways_taken_over', [$this, 'taken_over']);

        foreach (self::GATEWAYS as $code) {
            add_action('woocommerce_api_wpp_wc_' . $code . '_gateway', [$this, 'handle_legacy_callback']);
        }
    }

    /**
     * Whether the installed Parsi Date steps aside for gateways ParsiGate took over
     *
     * Older Parsi Date versions always load their gateways; switching the ParsiGate
     * copy on there would show the same gateway twice.
     */
    public static function parsidate_supports_takeover(): bool
    {
        return method_exists('\WPParsidate\App\Integration\WooCommerce\WcGateways', 'getTakenOverGateways');
    }

    /**
     * Copies the renamed settings and switches complete gateways on in ParsiGate
     */
    public function migrate(): void
    {
        if (!class_exists('\WPParsidate\Settings\Settings') || !self::parsidate_supports_takeover()) {
            return;
        }

        $migrated = get_option(self::MIGRATED_OPTION, []);
        $migrated = is_array($migrated) ? $migrated : [];
        $changed = false;

        foreach (self::GATEWAYS as $code) {
            if (isset($migrated[$code]) || !self::is_enabled_in_parsidate($code)) {
                continue;
            }

            $settings = self::copy_renamed_settings($code);

            // Pasargad in ParsiGate uses the bank's new API, which needs a username and
            // password that Parsi Date never had. The user switches it on themselves
            // after getting them from the bank; Parsi Date's notice says so.
            if (!self::has_required_settings($code, $settings)) {
                continue;
            }

            \WPParsidate\Settings\Settings::save($code, true, 'parsigate');

            $migrated[$code] = time();
            $changed = true;
        }

        if ($changed) {
            update_option(self::MIGRATED_OPTION, $migrated, false);
        }
    }

    /**
     * Gateways Parsi Date must not load any more
     *
     * @param array $codes Gateway codes other plugins took over
     *
     * @return array Gateway codes
     */
    public function taken_over($codes): array
    {
        $codes = is_array($codes) ? $codes : [];

        foreach (self::GATEWAYS as $code) {
            if (self::is_enabled_in_parsigate($code)) {
                $codes[] = $code;
            }
        }

        return array_values(array_unique($codes));
    }

    /**
     * Answers the callback URL of a payment Parsi Date started
     */
    public function handle_legacy_callback(): void
    {
        $code = substr(current_action(), strlen('woocommerce_api_wpp_wc_'), -strlen('_gateway'));

        // Not taken over: Parsi Date still loads its gateway and answers itself.
        if (!in_array($code, self::GATEWAYS, true) || !self::is_enabled_in_parsigate($code)) {
            return;
        }

        // phpcs:disable WordPress.Security.NonceVerification.Recommended -- Bank callback request, it cannot carry a nonce.
        $order_id = isset($_GET['order_id']) ? absint(wp_unslash($_GET['order_id'])) : 0;
        $action = isset($_GET['action']) ? sanitize_text_field(wp_unslash($_GET['action'])) : '';
        // phpcs:enable WordPress.Security.NonceVerification.Recommended

        $order = $order_id > 0 ? wc_get_order($order_id) : false;

        if (!$order || $order->get_payment_method() !== $code) {
            wp_die(esc_html__('Invalid order id.', 'parsigate'), '', ['response' => 403]);
        }

        // Parsi Date's own "go to the bank" page (Mellat): the bank token lived in
        // Parsi Date's session, so start the payment again with ParsiGate.
        if ($action === 'redirect') {
            wp_safe_redirect($order->get_checkout_payment_url());
            exit;
        }

        // The old Pasargad API can not be verified with the new one.
        if ($code === 'pasargad') {
            $this->hold_legacy_pasargad_order($order);
            return;
        }

        $gateway = self::get_wc_gateway($code);

        if (!$gateway) {
            wp_die(esc_html__('Invalid order id.', 'parsigate'));
        }

        // Same bank, same callback fields; ParsiGate verifies it like its own.
        $gateway->handle_gateway_response();
    }

    /**
     * @param \WC_Order $order Order paid with the old Pasargad gateway of Parsi Date
     */
    private function hold_legacy_pasargad_order($order): void
    {
        if (!$order->is_paid()) {
            $order->update_status(
                'on-hold',
                'بازگشت از درگاه پاسارگاد پارسی‌دیت (نسخه‌ی قدیمی) پس از انتقال درگاه به پارسی‌گیت. پرداخت به‌صورت خودکار تأیید نشد؛ لطفاً وضعیت تراکنش را در پنل بانک پاسارگاد بررسی کنید.'
            );
        }

        wp_safe_redirect($order->get_checkout_order_received_url());
        exit;
    }

    /**
     * @return \ParsiGate\WC_Gateway|null ParsiGate's WooCommerce gateway
     */
    private static function get_wc_gateway(string $code)
    {
        if (function_exists('WC') && WC()->payment_gateways()) {
            $gateways = WC()->payment_gateways()->payment_gateways();

            if (isset($gateways[$code]) && $gateways[$code] instanceof WC_Gateway) {
                return $gateways[$code];
            }
        }

        if (!class_exists('\ParsiGate\WC_Gateway')) {
            return null;
        }

        $gateway = new WC_Gateway();
        $gateway->setup_gateway($code);

        return $gateway;
    }

    /**
     * Copies Parsi Date fields to their ParsiGate names, without overwriting
     *
     * @return array Gateway settings after the copy
     */
    private static function copy_renamed_settings(string $code): array
    {
        $option = 'woocommerce_' . $code . '_settings';
        $settings = get_option($option, []);
        $settings = is_array($settings) ? $settings : [];
        $changed = false;

        foreach (self::RENAMED_SETTINGS[$code] ?? [] as $from => $to) {
            if (empty($settings[$to]) && !empty($settings[$from])) {
                $settings[$to] = $settings[$from];
                $changed = true;
            }
        }

        if ($changed) {
            update_option($option, $settings);
        }

        return $settings;
    }

    private static function has_required_settings(string $code, array $settings): bool
    {
        foreach (self::REQUIRED_SETTINGS[$code] ?? [] as $key) {
            if (!isset($settings[$key]) || trim((string)$settings[$key]) === '') {
                return false;
            }
        }

        return true;
    }

    /**
     * Read straight from the option: this runs before ParsiGate loads its classes.
     */
    private static function is_enabled_in_parsigate(string $code): bool
    {
        $options = get_option('wp_parsidate_parsigate', []);

        return is_array($options) && !empty($options[$code]);
    }

    private static function is_enabled_in_parsidate(string $code): bool
    {
        $options = get_option('wp_parsidate_woocommerce', []);

        return is_array($options) && !empty($options[$code . '_gateway_enable']);
    }
}

new LegacyParsidateGateways();
