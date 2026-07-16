<?php

namespace ParsiGate\CustomTable;

if (!defined('ABSPATH')) exit;

class LogListTable extends \WP_List_Table
{
    public static array $query_search = [
        's',
        'search-type',
    ];

    public static array $query_filter = [
        'ID',
        'gateway',
        'type',
        'code'
    ];

    /**
     * @return Log|string
     */
    public static function model()
    {
        return Log::class;
    }

    /**
     * @return LogAdminPage|string
     */
    public static function pageClass()
    {
        return LogAdminPage::class;
    }

    public function __construct()
    {
        parent::__construct(array(
            'singular' => 'wp-' . (static::model())::slug(),
            'plural' => 'wp-' . (static::model())::slug(),
            'ajax' => false
        ));

        // Fixed Params
        $this->sanitize_query_link();
    }

    public function sanitize_query_link(): void
    {
        foreach (array_merge(static::$query_filter, static::$query_search) as $key) {
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            if (isset($_REQUEST[$key])) {
                // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                $_GET[$key] = sanitize_text_field(wp_unslash($_REQUEST[$key]));
            }
        }
    }

    public function url($args = []): string
    {
        // Setup Default Params
        foreach (array_merge(static::$query_filter, static::$query_search) as $key) {
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            if (isset($_REQUEST[$key])) {
                // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                $args[$key] = sanitize_text_field(wp_unslash($_REQUEST[$key]));
            }
        }

        // Return
        return add_query_arg($args, remove_query_arg(['action', '_wpnonce', 'del']));
    }

    public function prepare_items(): void
    {

        //Column Option
        $this->_column_headers = $this->get_column_info();

        //Process Bulk and Row Action
        $this->process_bulk_action();

        //Prepare Data
        $per_page = $this->get_items_per_page('wp_' . (static::pageClass())::page_slug() . '_per_page', (static::pageClass())::$ListTablePerPage);
        $current_page = $this->get_pagenum();
        $total_items = self::record_count();

        //Create Pagination
        $this->set_pagination_args(array(
            'total_items' => $total_items,
            'per_page' => $per_page
        ));

        //return items
        $this->items = self::get_lists($per_page, $current_page);
    }

    public static function get_lists($per_page = 10, $page_number = 1)
    {
        global $wpdb;

        $tbl = (static::model())::table();
        $sql = "SELECT * FROM `$tbl`";

        // Where conditional
        $conditional = self::conditional_sql();
        if (!empty($conditional)) {
            $sql .= ' WHERE ' . implode(' AND ', $conditional);
        }

        // Check Order By
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if (!empty($_REQUEST['orderby'])) {

            // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $sql .= ' ORDER BY ' . esc_sql(sanitize_text_field(wp_unslash($_REQUEST['orderby'])));
        } else {
            $sql .= ' ORDER BY `' . (static::model())::primary_key() . '`';
        }

        // Check Order
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $sql .= !empty($_REQUEST['order']) ? ' ' . esc_sql(sanitize_text_field(wp_unslash($_REQUEST['order']))) : ' DESC';
        $sql .= " LIMIT $per_page";
        $sql .= ' OFFSET ' . ($page_number - 1) * $per_page;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
        return array_map([static::model(), 'prepare'], $wpdb->get_results($sql, ARRAY_A));
    }

    public static function record_count($condition = true): ?string
    {
        global $wpdb;
        $tbl = (static::model())::table();
        $sql = "SELECT COUNT(*) FROM `$tbl`";

        // Where conditional
        if ($condition) {

            $conditional = self::conditional_sql();
            if (!empty($conditional)) {
                $sql .= ' WHERE ' . implode(' AND ', $conditional);
            }
        }

        $cache_key = 'db_var_' . md5($sql);
        $cached = wp_cache_get($cache_key, 'db_var');
        if ($cached !== false) {
            return $cached;
        }

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
        $result = $wpdb->get_var($sql);
        wp_cache_set($cache_key, $result, 'db_var', 3600);

        return $result;
    }

    public static function conditional_sql(): array
    {
        // phpcs:disable WordPress.Security.NonceVerification.Recommended
        $where = [];

        // Check Search
        if (isset($_REQUEST['search-type']) and !empty($_REQUEST['search-type']) and isset($_REQUEST['s']) and !empty($_REQUEST['s'])) {

            // Set Request URL
            if (isset($_SERVER['REQUEST_URI'])) {

                $_SERVER['REQUEST_URI'] = add_query_arg([
                    'search-type' => sanitize_text_field(wp_unslash($_REQUEST['search-type'])),
                    's' => sanitize_text_field(wp_unslash($_REQUEST['s'])),
                ], sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'])));
            }

            // Get search Input
            $search = sanitize_text_field(wp_unslash($_REQUEST['s']));

            // Setup Case Switch
            switch (strtolower(sanitize_text_field(wp_unslash($_REQUEST['search-type'])))) {

                case "ID":
                    $explodeIds = array_filter(array_map('trim', explode(",", $search)));
                    $where[] = "`" . (static::model())::primary_key() . "` IN ('" . implode("','", $explodeIds) . "')";
                    break;

                default:
                    $where[] = "`" . sanitize_text_field(wp_unslash($_REQUEST['search-type'])) . "` = '{$search}'";
                    break;
            }
        }

        // Setup Filter Query
        foreach (self::$query_filter as $key) {
            if (isset($_REQUEST[$key]) and $_REQUEST[$key] != '') {
                $search = sanitize_text_field(wp_unslash($_REQUEST[$key]));
                $where[] = "`$key` = '{$search}'";
            }
        }

        // phpcs:enable WordPress.Security.NonceVerification.Recommended
        return $where;
    }

    public static function delete_action($id): array
    {
        return (static::model())::delete($id);
    }

    public function no_items(): void
    {
        esc_html_e('Empty gateways log list.', 'parsigate');
    }

    public function get_columns(): array
    {
        return [
            'cb' => '<input type="checkbox" />',
            'ID' => __('ID', 'parsigate'),
            'gateway' => __('Gateway', 'parsigate'),
            'status_code' => __('Status Code', 'parsigate'),
            'url' => __('Url', 'parsigate'),
            'type' => __('Type', 'parsigate'),
            'request' => __('Request', 'parsigate'),
            'created_at' => __('Created at', 'parsigate')
        ];
    }

    public function column_cb($item)
    {
        return sprintf(
            '<input type="checkbox" name="bulk-' . (static::model())::primary_key() . '[]" value="%s" />',
            $item[(static::model())::primary_key()]
        );
    }

    public function column_default($item, $column_name): string
    {
        // Default unknown Column Value
        $unknown = '<span aria-hidden="true">—</span><span class="screen-reader-text">_</span>';

        switch ($column_name) {
            case 'ID':

                $actions['trash'] = '<a onclick="return confirm(\'' . __('Are you sure?', 'parsigate') . '\')"
                    href="' . $this->url(array(
                        'page' => (static::pageClass())::page_slug(),
                        'action' => 'delete',
                        '_wpnonce' => wp_create_nonce('delete_action_nonce'),
                        'ID' => $item[(static::model())::primary_key()]
                    )) . '">' . __('Delete', 'parsigate') . '</a>';

                return $item[(static::model())::primary_key()] . $this->row_actions($actions);
                break;

            case 'gateway':
                if (isset($item['gateway_object']['title']) and !empty($item['gateway_object']['title'])) {
                    return $item['gateway_object']['title'];
                }

                return $unknown;
                break;

            case 'status_code':
                if (empty($item['code'])) {
                    return $unknown;
                }

                $class = 'none';
                if (in_array(substr($item['code'], 0, 1), ['2'])) {
                    $class = 'success';
                }
                if (in_array(substr($item['code'], 0, 1), ['4', '5'])) {
                    $class = 'error';
                }
                return '<span class="pg-log-column-status pg-log-column-status-' . $class . '">' . trim($item['code']) . '</span>';
                break;

            case 'created_at':
                if (empty($item[$column_name])) {
                    return $unknown;
                }

                $text = parsidate("Y-m-d", strtotime($item[$column_name]), "eng");
                $text .= '<br />';
                $text .= parsidate("H:i:s", strtotime($item[$column_name]), "eng");
                $text .= '<br />';
                $text .= '<span style="color: #b1b1b1;">' . human_time_diff(strtotime($item[$column_name]), current_time('timestamp')) . ' ' . __('ago', 'parsigate') . ' </span>';
                return $text;
                break;

            case 'type':

                $typeName = (static::model())::get_type_name($item['type']);
                return '<span>' . strtoupper($typeName) . '</span>';
                break;

            case 'url':
                if (empty($item['url'])) {
                    return $unknown;
                }

                return '<span dir="ltr" style="text-align: left; font-size: 12px !important;">' . $item['url'] . '</span>';
                break;

            case 'request':

                $text = __('Send: ', 'parsigate');
                if (empty($item['body'])) {

                    $text .= '_';
                } else {

                    $text .= '<div class="pg-log-json-pre-area" id="body_' . absint($item['ID']) . '">
                            <pre class="json-body"></pre>
                            <div class="pg-log-json-pre-area__close">
                                <a href="#" class="button">
                                    ' . esc_html__('Close', 'parsigate') . '
                                </a>
                            </div>
                        </div>';

                    $inline_script = 'jQuery(document).ready(function () {
                                        jQuery("#body_' . absint($item['ID']) . ' pre").jsonBrowse(' . wp_json_encode($item['body'], JSON_PRETTY_PRINT) . ', {
                                            collapsed: false,
                                            withQuotes: false
                                        });
                                    });';
                    wp_add_inline_script('parsigate-logs', $inline_script);

                    $text .= '<a href="" data-pg-log-show-json="body_' . $item['ID'] . '">' . __('Show', 'parsigate') . '</a>';
                }

                $text .= '<br />';
                if (!empty($item['header'])) {

                    $text .= __('Header: ', 'parsigate');

                    $text .= '<div class="pg-log-json-pre-area" id="header_' . absint($item['ID']) . '">
                            <pre class="json-body"></pre>
                            <div class="pg-log-json-pre-area__close">
                                <a href="#" class="button">
                                    ' . esc_html__('Close', 'parsigate') . '
                                </a>
                            </div>
                        </div>';

                    $inline_script = 'jQuery(document).ready(function () {
                                jQuery("#header_' . absint($item['ID']) . ' pre").jsonBrowse(' . wp_json_encode($item['header'], JSON_PRETTY_PRINT) . ', {
                                    collapsed: false,
                                    withQuotes: false
                                });
                            });';
                    wp_add_inline_script('parsigate-logs', $inline_script);

                    $text .= '<a href="" data-pg-log-show-json="header_' . $item['ID'] . '">' . __('Show', 'parsigate') . '</a>';
                    $text .= '<br />';
                }

                $text .= __('Response: ', 'parsigate');
                if (empty($item['response'])) {

                    $text .= '_';
                } else {

                    $text .= '<div class="pg-log-json-pre-area" id="response_' . absint($item['ID']) . '">
                            <pre class="json-body"></pre>
                            <div class="pg-log-json-pre-area__close">
                                <a href="#" class="button">
                                    ' . esc_html__('Close', 'parsigate') . '
                                </a>
                            </div>
                        </div>';

                    $inline_script = 'jQuery(document).ready(function () {
                                    jQuery("#response_' . absint($item['ID']) . ' pre").jsonBrowse(' . wp_json_encode($item['response'], JSON_PRETTY_PRINT) . ', {
                                        collapsed: false,
                                        withQuotes: false
                                    });
                                });';
                    wp_add_inline_script('parsigate-logs', $inline_script);
                    $text .= '<a href="" data-pg-log-show-json="response_' . $item['ID'] . '">' . __('Show', 'parsigate') . '</a>';
                }

                return $text;
                break;

            default:
                return $unknown;
        }
    }

    public function get_sortable_columns(): array
    {
        return [
            'ID' => array('ID', true),
            'type' => array('type', false),
            'gateway' => array('gateway', false),
        ];
    }

    protected function get_views(): array
    {

        $views = [];
        $class = ' class="current"';
        $all_url = (static::pageClass())::url();
        $views['all'] = "<a href='{$all_url}' {$class}>" . __("All", "parsigate") . " <span class=\"count\">(" . number_format(static::record_count()) . ")</span></a>";

        return $views;
    }

    public function extra_tablenav($which): void
    {
        if ($which == "top") {
            //
        }
    }

    public function get_bulk_actions(): array
    {
        return array(
            'bulk-delete' => __('Delete', 'parsigate'),
        );
    }

    public function search_box($text, $input_id): void
    {
        // phpcs:disable WordPress.Security.NonceVerification.Recommended
        if (empty($_REQUEST['s']) && !$this->has_items()) {
            return;
        }

        $input_id = (empty($input_id) ? (static::pageClass())::page_slug() : $input_id) . '-search-input';

        if (!empty($_REQUEST['orderby'])) {
            echo '<input type="hidden" name="orderby" value="' . esc_attr(sanitize_text_field(wp_unslash($_REQUEST['orderby']))) . '" />';
        }
        if (!empty($_REQUEST['order'])) {
            echo '<input type="hidden" name="order" value="' . esc_attr(sanitize_text_field(wp_unslash($_REQUEST['order']))) . '" />';
        }
        ?>

        <p class="search-box">
            <label class="screen-reader-text" for="<?php echo esc_attr($input_id); ?>"><?php echo esc_html($text); ?>
                :</label>
            <input type="search" placeholder="جستجو ..." id="<?php echo esc_attr($input_id); ?>" name="s"
                   value="<?php _admin_search_query(); ?>" autocomplete="off"/>
            <?php submit_button($text, 'button', false, false, array('id' => 'search-submit')); ?>
        </p>

        <?php
        // phpcs:enable WordPress.Security.NonceVerification.Recommended
    }

    public function process_bulk_action(): void
    {

        // Row Action `Delete`
        if ('delete' === $this->current_action()) {

            $nonce = (isset($_REQUEST['_wpnonce']) ? sanitize_text_field(wp_unslash($_REQUEST['_wpnonce'])) : '');
            if (!wp_verify_nonce($nonce, 'delete_action_nonce')) {

                wp_die(esc_html__("You are not Permission for this action.", "parsigate"));
            } else {

                $deleteItem = (isset($_REQUEST['ID']) ? self::delete_action(absint($_REQUEST['ID'])) : '');
                $_REQUEST['ID'] = '';
                Message::admin_notice_handler(
                    'success',
                    __("Items deleted.", "parsigate"),
                    [
                        'action',
                        '_wpnonce',
                        (static::model())::primary_key()
                    ]
                );
            }
        }

        // Bulk Action `Delete`
        if (isset($_POST['action']) && $_POST['action'] == 'bulk-delete') {

            $item_ids = (isset($_POST['bulk-ID']) ? sanitize_text_field(wp_unslash($_POST['bulk-ID'])) : '');
            if (is_array($item_ids) and count($item_ids) > 0) {
                $logs = [];
                foreach ($item_ids as $id) {
                    // Delete Items
                    $deleteItem = self::delete_action($id);

                    /* translators: %d: Number of items to delete */
                    $logs[] = sprintf(__('Delete %d Id Items', 'parsigate'), $id);
                }

                Message::admin_notice_handler(
                    'success',
                    implode("<br />", $logs),
                    [
                        'action',
                        '_wpnonce'
                    ]
                );
            }
        }
    }
}