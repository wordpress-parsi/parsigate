<?php
/**
 * Plugin Name: Parsi Gate
 * Description: A Plugin For starter WordPress
 * Plugin URI:  https://site.com
 * Version:     1.0.0
 * Author:      plugin
 * Author URI:  https://site.com
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
        add_action('plugins_loaded', [$this, 'plugins_loaded'], 20);
        add_action('wpp_init', [$this, 'wpp_init']);
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
        echo '<div class="notice notice-error"><p>'
            . sprintf(
            /* translators: 1: Plugin name in strong tag, 2: Download link, 3: Install link */
                __('For proper functionality of your plugin, the %1$s plugin is required. Please %2$s or %3$s.', 'parsigate'),
                '<strong>' . esc_html($plugin_name) . '</strong>',
                $download_link,
                $install_link
            )
            . '</p></div>';
    }

    public function wpp_init()
    {
        $this->define_constants();
        $this->includes();
        $this->init_hooks();
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
        require_once self::$plugin_path . '/inc/Option.php';
        require_once self::$plugin_path . '/inc/Log.php';
        require_once self::$plugin_path . '/inc/Gateways.php';

        // Gateways
        require_once self::$plugin_path . '/inc/gateways/Base.php';
        require_once self::$plugin_path . '/inc/gateways/ZarinPal.php';

        // WooCommerce
        require_once self::$plugin_path . '/inc/woocommerce/Gateways.php';
    }

    public function init_hooks()
    {

        load_plugin_textdomain('parsigate', false, wp_normalize_path(self::$plugin_path . '/languages'));
        register_activation_hook(__FILE__, [$this, 'register_activation_hook']);
        register_deactivation_hook(__FILE__, [$this, 'register_deactivation_hook']);
        register_uninstall_hook(__FILE__, [__CLASS__, 'register_uninstall_hook']);
    }

    public function register_activation_hook()
    {
    }

    public function register_deactivation_hook()
    {
    }

    public static function register_uninstall_hook()
    {
    }

}

$GLOBALS['ParsiGate'] = ParsiGate::instance();