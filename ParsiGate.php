<?php
/**
 * Plugin Name: Parsi Gate
 * Description: Persian Gateways for WordPress
 * Plugin URI:  https://wp-parsi.com
 * Version:     1.0.0
 * Author:      Parsidate Teams
 * Author URI:  https://wp-parsi.com
 * License:     MIT
 * Text Domain: parsigate
 * Domain Path: /languages
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Requires at least: 5.6
 * Requires PHP: 7.4
 * Requires Plugins: wp-parsidate
 */

class ParsiGate
{

    public static string $plugin_url;

    public static string $plugin_path;

    public static string $plugin_version;

    protected static ?ParsiGate $instance = null;

    public static function instance(): ?ParsiGate
    {
        null === self::$instance and self::$instance = new self;
        return self::$instance;
    }

    public function __construct()
    {
        // Setup Default constant
        $this->define_constants();

        // Activation Hook
        register_activation_hook(__FILE__, [$this, 'register_activation_hook']);
        register_deactivation_hook(__FILE__, [$this, 'register_deactivation_hook']);
        register_uninstall_hook(__FILE__, [__CLASS__, 'register_uninstall_hook']);

        // Plugin Loaded
        add_action('plugins_loaded', [$this, 'plugins_loaded'], 20);

        // i18n
        add_action('init', [$this, 'i18n']);

        // Wrap After ParsiDate
        add_action('wp_parsidate_addons_load', [$this, 'wpp_init']);
    }

    public function plugins_loaded()
    {
        if (!class_exists('WP_Parsidate')) {
            add_action('admin_notices', [$this, 'admin_notices']);
        }
    }

    public function admin_notices()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $plugin_url = 'https://wordpress.org/plugins/wp-parsidate/';
        $install_url = wp_nonce_url(admin_url('update.php?action=install-plugin&plugin=wp-parsidate'), 'install-plugin_wp-parsidate');
        $plugin_name = __('WP-Parsidate', 'parsigate');
        $download_text = __('Download it from WordPress', 'parsigate');
        $install_text = __('install it directly', 'parsigate');
        $download_link = sprintf(
            '<a href="%s" target="_blank">%s</a>',
            esc_url($plugin_url),
            esc_html($download_text)
        );
        $install_link = sprintf(
            '<a href="%s">%s</a>',
            esc_url($install_url),
            esc_html($install_text)
        );

        /* translators: 1: Plugin name in strong tag, 2: Download link, 3: Install link */
        $desc = sprintf(esc_html__('For proper functionality of your plugin, the %1$s plugin is required. Please %2$s or %3$s.', 'parsigate'), '<strong>' . esc_html($plugin_name) . '</strong>', esc_html($download_link), esc_html($install_link));
        echo '<div class="notice notice-error"><p>' . esc_html($desc) . '</p></div>';
    }

    public function wpp_init()
    {
        $this->includes();
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
        require_once self::$plugin_path . '/inc/WooCommerce.php';
        require_once self::$plugin_path . '/inc/WC_Gateway.php';
        require_once self::$plugin_path . '/inc/WC_Gateway_Block.php';

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

    public function i18n()
    {
        load_plugin_textdomain('parsigate', false, wp_normalize_path(self::$plugin_path . '/languages'));
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
