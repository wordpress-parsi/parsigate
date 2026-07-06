<?php
/**
 * Plugin Name: Parsigate
 * Description: Persian Gateways for WooCommerce/WordPress
 * Version:     1.0.0
 * Author:      Parsidate Teams
 * Author URI:  https://wp-parsi.com
 * Text Domain: parsigate
 * Domain Path: /languages
 * License:     GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 5.6
 * Requires PHP: 7.4
 * Tested up to: 7.0
 */

if (!defined('ABSPATH')) exit;

class ParsiGate
{

    public static string $plugin_url;

    public static string $plugin_path;

    public static string $plugin_version;

    public static string $plugin_basename;

    protected static ?ParsiGate $instance = null;

    public static function instance(): ?ParsiGate
    {
        null === self::$instance and self::$instance = new self;
        return self::$instance;
    }

    public function __construct()
    {
        // Define
        $this->define_constants();

        // Activation Hook
        register_activation_hook(__FILE__, [$this, 'register_activation_hook']);
        register_deactivation_hook(__FILE__, [$this, 'register_deactivation_hook']);
        register_uninstall_hook(__FILE__, [__CLASS__, 'register_uninstall_hook']);

        // Plugin Loaded
        add_action('plugins_loaded', [$this, 'plugins_loaded'], 20);

        // Wrap After ParsiDate
        add_action('wp_parsidate_addons_load', [$this, 'wpp_init']);
    }

    public function required_plugins(): array
    {
        return [
            'parsidate' => [
                'name' => 'WP-Parsidate',
                'slug' => 'wp-parsidate',
                'class' => '\WPParsidate\WP_Parsidate',
                'plugin_url' => 'https://wordpress.org/plugins/wp-parsidate/',
            ],
            'woocommerce' => [
                'name' => 'WooCommerce',
                'slug' => 'woocommerce',
                'class' => 'WooCommerce',
                'plugin_url' => 'https://wordpress.org/plugins/woocommerce/',
            ]
        ];
    }

    public function plugins_loaded()
    {

        if (!$this->is_woocommerce_active() || !$this->is_parsidate_active()) {
            add_action('admin_notices', [$this, 'admin_notices']);
        }
    }

    private function is_plugin_active(string $slug): bool
    {
        $plugins = $this->required_plugins();

        if (!isset($plugins[$slug])) {
            return false;
        }

        return class_exists($plugins[$slug]['class']);
    }

    public function is_parsidate_active(): bool
    {
        return $this->is_plugin_active('parsidate');
    }

    public function is_woocommerce_active(): bool
    {
        return $this->is_plugin_active('woocommerce');
    }

    public function admin_notices()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        foreach ($this->required_plugins() as $slug => $plugin) {
            if ($this->is_plugin_active($slug)) {
                continue;
            }

            $install_url = wp_nonce_url(
                admin_url('update.php?action=install-plugin&plugin=' . $plugin['slug']),
                'install-plugin_' . $plugin['slug']
            );

            $download_button = sprintf(
                '<a href="%s" target="_blank" class="button button-secondary">%s %s</a>',
                esc_url($plugin['plugin_url']),
                '<span class="dashicons dashicons-wordpress"></span>',
                __('Download from WordPress', 'parsigate')
            );

            $install_button = sprintf(
                '<a href="%s" class="button button-primary">%s %s</a>',
                esc_url($install_url),
                '<span class="dashicons dashicons-admin-plugins"></span>',
                __('Install directly', 'parsigate')
            );


            $line1 = sprintf(
                // translators: %1$s is the plugin name (Parsigate), %2$s is the required plugin name
                __('%1$s plugin requires %2$s plugin to function properly.', 'parsigate'),
                '<strong>' . __('Parsigate', 'parsigate') . '</strong>',
                '<strong>' . esc_html($plugin['name']) . '</strong>'
            );


            $line2 = sprintf(
                // translators: %s is the required plugin name
                __('Please install and activate %s plugin first to access all features.', 'parsigate'),
                '<strong>' . esc_html($plugin['name']) . '</strong>'
            );

            // translators: %1$s is the download button, %2$s is the install button
            $message = sprintf(
                '<p>%s</p>
            <p>%s</p>
            <p style="margin-top: 20px;">%s %s</p>',
                $line1,
                $line2,
                $download_button,
                $install_button
            );

            echo '<div class="notice notice-error is-dismissible">' . wp_kses_post($message) . '</div>';
        }
    }

    public function wpp_init()
    {
        // Check required plugin
        if (!$this->is_woocommerce_active() || !$this->is_parsidate_active()) {
            return;
        }

        // include files
        $this->includes();

        // parsigate has loaded
        do_action('parsigate_loaded');
    }

    public function define_constants()
    {
        if (!function_exists('get_plugin_data')) {
            require_once(ABSPATH . 'wp-admin/includes/plugin.php');
        }
        $plugin_data = get_plugin_data(__FILE__, true, false);

        self::$plugin_version = $plugin_data['Version'];
        self::$plugin_url = plugins_url('', __FILE__);
        self::$plugin_path = plugin_dir_path(__FILE__);
        self::$plugin_basename = plugin_basename(__FILE__);
    }

    public function includes()
    {
        // Main
        require_once self::$plugin_path . '/inc/Utility.php';
        require_once self::$plugin_path . '/inc/Gateways.php';
        require_once self::$plugin_path . '/inc/Option.php';
        require_once self::$plugin_path . '/inc/Gateway.php';
        require_once self::$plugin_path . '/inc/Tokens.php';

        // Gateways
        require_once self::$plugin_path . '/inc/gateways/Base.php';
        require_once self::$plugin_path . '/inc/gateways/ZarinPal.php';
        require_once self::$plugin_path . '/inc/gateways/AqayePardakht.php';
        require_once self::$plugin_path . '/inc/gateways/AsanPardakht.php';
        require_once self::$plugin_path . '/inc/gateways/Azkivam.php';
        require_once self::$plugin_path . '/inc/gateways/DigiPay.php';
        require_once self::$plugin_path . '/inc/gateways/IranKish.php';
        require_once self::$plugin_path . '/inc/gateways/Mellat.php';
        require_once self::$plugin_path . '/inc/gateways/Melli.php';
        require_once self::$plugin_path . '/inc/gateways/Parsian.php';
        require_once self::$plugin_path . '/inc/gateways/Pasargad.php';
        require_once self::$plugin_path . '/inc/gateways/PayPing.php';
        require_once self::$plugin_path . '/inc/gateways/Saderat.php';
        require_once self::$plugin_path . '/inc/gateways/Sep.php';
        require_once self::$plugin_path . '/inc/gateways/Shepa.php';
        require_once self::$plugin_path . '/inc/gateways/SnappPay.php';
        require_once self::$plugin_path . '/inc/gateways/Tara.php';
        require_once self::$plugin_path . '/inc/gateways/Zibal.php';
        require_once self::$plugin_path . '/inc/gateways/Test.php';

        // WooCommerce
        if ($this->is_woocommerce_active()) {

            require_once self::$plugin_path . '/inc/WooCommerce.php';
            require_once self::$plugin_path . '/inc/WC_Gateway.php';
            require_once self::$plugin_path . '/inc/WC_Gateway_Block.php';
        }

        // Custom Table
        if (is_admin() and !class_exists('WP_List_Table')) {
            require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
        }
        require_once dirname(__FILE__) . '/inc/custom-table/Message.php';
        require_once dirname(__FILE__) . '/inc/custom-table/Base.php';
        require_once dirname(__FILE__) . '/inc/custom-table/Page.php';
        require_once dirname(__FILE__) . '/inc/custom-table/pg-log/Log.php';
        if (is_admin()) {
            require_once dirname(__FILE__) . '/inc/custom-table/pg-log/LogAdminPage.php';
            require_once dirname(__FILE__) . '/inc/custom-table/pg-log/LogListTable.php';
        }
    }

    public function register_activation_hook()
    {
        global $wpdb;

        // Load DB delta
        if (!function_exists('dbDelta')) {
            require(ABSPATH . 'wp-admin/includes/upgrade.php');
        }

        // Charset Collate
        $collate = $wpdb->get_charset_collate();

        // Create Log Table
        $table_name = $wpdb->prefix . 'pg_log';
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            `ID` BIGINT(48) NOT NULL AUTO_INCREMENT,
            `gateway` VARCHAR(150) NOT NULL,
            `url` TEXT NULL,
            `type` BIGINT(48) NOT NULL DEFAULT '1',
            `code` BIGINT(4) NOT NULL DEFAULT '200',
            `header` TEXT NULL,
            `body` TEXT NULL,
            `response` TEXT NULL,
            `meta` TEXT NULL,
            `created_at` DATETIME NOT NULL,
            PRIMARY KEY (`ID`),
            INDEX gateway (`gateway`),
            INDEX gateway_code (`gateway`, `code`)
            ) ENGINE = InnoDB {$collate};";
        dbDelta($sql);
    }

    public function register_deactivation_hook()
    {
    }

    public static function register_uninstall_hook()
    {
        global $wpdb;

        // Delete gateway tokens option
        delete_option('pg_gateway_tokens');

        // Delete gateway log table
        $table_name = esc_sql($wpdb->prefix . 'pg_log');

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
        $wpdb->query($wpdb->prepare("DROP TABLE IF EXISTS %s", $table_name));
    }

}

$GLOBALS['ParsiGate'] = ParsiGate::instance();
