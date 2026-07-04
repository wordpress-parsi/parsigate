<?php

namespace ParsiGate\CustomTable;


use ParsiGate\Gateways;
use WPParsidate\Addons\ParsiGateOption\ParsiGateOption;

class LogAdminPage extends Page
{

    public \WP_List_Table $admin_list_table;

    public static int $ListTablePerPage = 50;

    public function __construct()
    {
        // Add Menu
        add_action('admin_menu', array($this, 'admin_menu'));

        // Set Screen Option (Admin List Table)
        add_filter('set-screen-option', array($this, 'set_screen_option'), 10, 3);

        // Custom JS
        add_action('admin_enqueue_scripts', array($this, 'admin_assets'));

        // Search Box
        add_filter('parsigate_admin_post_type_search_box_fields', [$this, 'search_box_field']);
        add_action('admin_footer', [$this, 'search_box_template']);

        // Handler
        add_action("admin_init", array($this, 'handler'));

        // Admin Notices
        add_action('admin_notices', [$this, 'admin_notices'], 30);
    }

    /**
     * @return Log|string
     */
    public static function model()
    {
        return Log::class;
    }

    public static function page_slug(): string
    {
        return (static::model())::slug();
    }

    public function admin_menu(): void
    {
        if (!ParsiGateOption::enable_log()) {
            return;
        }

        $hook = add_management_page(
            (static::model())::title(),
            (static::model())::title(),
            apply_filters('parsigate_log_menu_permission', 'manage_options'),
            static::page_slug(),
            [$this, 'page']
        );

        add_action("load-$hook", array($this, 'screen_option'));
    }

    public function admin_assets(): void
    {
        if (!static::is_page()) {
            return;
        }

        // Load On Admin List Table
        if (empty(static::screen())) {

            // Add Thickbox
            add_thickbox();

            // Add Json Browse
            wp_enqueue_style('parsigate-json-browser', \ParsiGate::$plugin_url . '/assets/json-browse/json-browse.css', array(), \ParsiGate::$plugin_version, 'all');
            wp_enqueue_script('parsigate-json-browser', \ParsiGate::$plugin_url . '/assets/json-browse/json-browse.js', array('jquery'), \ParsiGate::$plugin_version, false);
        }
    }

    public static function set_screen_option($status, $option, $value)
    {
        // this filter run when saved
        if ($option == 'wp_' . static::page_slug() . '_per_page') {
            if ((int)$value < 1) {
                $value = static::$ListTablePerPage;
            }
        }

        return $value;
    }

    public static function is_page(): bool
    {
        global $pagenow;

        // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
        return ($pagenow == "tools.php" and isset($_GET['page']) and $_GET['page'] == static::page_slug());
    }

    public static function url($args = []): string
    {
        return add_query_arg(array_replace([
            'page' => static::page_slug()
        ], $args), admin_url('tools.php'));
    }

    public function screen_option(): void
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
        if (static::is_page() and empty($_GET['screen'])) {

            // Set Screen Option
            add_screen_option('per_page', array(
                'label' => 'تعداد در هر صفحه',
                'default' => 50,
                'option' => 'wp_' . static::page_slug() . '_per_page' // user_meta_name
            ));

            // Load WP_List_Table
            $this->admin_list_table = new LogListTable();
            $this->admin_list_table->prepare_items();
        }
    }

    public function handler(): void
    {
        // if (static::is_page()) {}
    }

    public function admin_notices(): void
    {
        if (static::is_page() and !empty(static::screen())) {

            $flashMessage = Message::get();
            if (!empty($flashMessage)) {
                Message::admin_notice($flashMessage['data'], $flashMessage['type']);
            }
        }
    }

    public function page(): void
    {

        // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
        if (!isset($_GET['screen'])) {

            $table = $this->admin_list_table;
            $title = (static::model())::title();
            $buttons = [];
            include \ParsiGate::$plugin_path . '/inc/custom-table/' . str_ireplace("_", "-", (static::model())::slug()) . '/views/list-table.php';
        }
    }

    public function search_box_template(): void
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
        if (static::is_page() and empty($_GET['screen'])) {
            include \ParsiGate::$plugin_path . '/inc/custom-table/' . str_ireplace("_", "-", (static::model())::slug()) . '/views/search-box.php';
        }
    }

    public function search_box_field($list)
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
        if (static::is_page() and empty($_GET['screen'])) {

            $list = [
                'ID' => __('ID', 'parsigate'),
                'gateway' => array(
                    'title' => __('Gateway', 'parsigate'),
                    'type' => 'select',
                    'choices' => Gateways::choices()
                ),
                'type' => array(
                    'title' => __('Type', 'parsigate'),
                    'type' => 'select',
                    'choices' => (static::model())::get_type_list()
                ),
                'code' => __('Status Code', 'parsigate')
            ];
        }

        return $list;
    }

}

new LogAdminPage();