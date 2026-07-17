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

    }

    public function url($args = []): string
    {
        // Setup Default Params
        foreach (array_merge(static::$query_filter, static::$query_search) as $key) {

            // phpcs:disable WordPress.Security.NonceVerification
            if (isset($_REQUEST[$key])) {
                $args[$key] = sanitize_text_field(wp_unslash($_REQUEST[$key]));
            }
            // phpcs:enable WordPress.Security.NonceVerification
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

        $table = (static::model())::table();

        $sql = "SELECT * FROM `{$table}`";

        $condition = self::conditional_sql();

        if (!empty($condition['where'])) {
            $sql .= ' WHERE ' . implode(' AND ', $condition['where']);
        }

        $allowed_orderby = array_unique(
            array_merge(
                self::$query_filter,
                [(static::model())::primary_key()]
            )
        );

        // phpcs:disable WordPress.Security.NonceVerification

        $orderby = !empty($_REQUEST['orderby'])
            ? sanitize_key(wp_unslash($_REQUEST['orderby']))
            : (static::model())::primary_key();

        if (!in_array($orderby, $allowed_orderby, true)) {
            $orderby = (static::model())::primary_key();
        }

        $order = !empty($_REQUEST['order'])
            ? strtoupper(sanitize_text_field(wp_unslash($_REQUEST['order'])))
            : 'DESC';

        $order = ('ASC' === $order) ? 'ASC' : 'DESC';

        // phpcs:enable WordPress.Security.NonceVerification

        $sql .= " ORDER BY `{$orderby}` {$order}";
        $sql .= ' LIMIT %d OFFSET %d';

        $args = $condition['args'];
        $args[] = absint($per_page);
        $args[] = max(0, (absint($page_number) - 1) * absint($per_page));

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        $prepared = $wpdb->prepare($sql, $args);

        return array_map(
            [static::model(), 'prepare'],
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
            $wpdb->get_results($prepared, ARRAY_A)
        );
    }

    public static function record_count($condition = true): ?string
    {
        global $wpdb;

        $table = (static::model())::table();

        $sql = "SELECT COUNT(*) FROM `{$table}`";
        $args = [];

        if ($condition) {

            $conditional = self::conditional_sql();

            if (!empty($conditional['where'])) {
                $sql .= ' WHERE ' . implode(' AND ', $conditional['where']);
                $args = $conditional['args'];
            }
        }

        if (!empty($args)) {
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
            $sql = $wpdb->prepare($sql, $args);
        }

        $cache_key = 'db_var_' . md5($sql);

        $cached = wp_cache_get($cache_key, 'db_var');
        if (false !== $cached) {
            return $cached;
        }

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.NotPrepared
        $result = $wpdb->get_var($sql);

        wp_cache_set($cache_key, $result, 'db_var', HOUR_IN_SECONDS);

        return $result;
    }

    public static function conditional_sql(): array
    {
        $where = [];
        $args = [];

        // phpcs:disable WordPress.Security.NonceVerification.Recommended

        if (
            !empty($_REQUEST['search-type']) &&
            !empty($_REQUEST['s'])
        ) {

            $search_type = sanitize_key(wp_unslash($_REQUEST['search-type']));
            $search = sanitize_text_field(wp_unslash($_REQUEST['s']));

            if (isset($_SERVER['REQUEST_URI'])) {
                $_SERVER['REQUEST_URI'] = add_query_arg(
                    [
                        'search-type' => $search_type,
                        's' => $search,
                    ],
                    sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI']))
                );
            }

            $allowed_columns = array_unique(
                array_merge(
                    self::$query_filter,
                    [(static::model())::primary_key()]
                )
            );

            if ('ID' === strtoupper($search_type)) {

                $ids = array_filter(
                    array_map(
                        'absint',
                        explode(',', $search)
                    )
                );

                if (!empty($ids)) {
                    $placeholders = implode(',', array_fill(0, count($ids), '%d'));

                    $where[] = '`' . (static::model())::primary_key() . "` IN ($placeholders)";
                    $args = array_merge($args, $ids);
                }
            } elseif (in_array($search_type, $allowed_columns, true)) {

                $where[] = "`{$search_type}` = %s";
                $args[] = $search;
            }
        }

        foreach (self::$query_filter as $key) {

            if (
                isset($_REQUEST[$key]) &&
                '' !== $_REQUEST[$key]
            ) {

                $where[] = "`{$key}` = %s";
                $args[] = sanitize_text_field(wp_unslash($_REQUEST[$key]));
            }
        }

        // phpcs:enable WordPress.Security.NonceVerification.Recommended

        return [
            'where' => $where,
            'args' => $args,
        ];
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
                        '_wpnonce' => wp_create_nonce('parsigate_delete_action'),
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
            if (!wp_verify_nonce($nonce, 'parsigate_delete_action')) {

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

            check_admin_referer('bulk-' . $this->_args['plural']);

            $item_ids = isset($_POST['bulk-ID'])
                ? array_map('absint', (array)wp_unslash($_POST['bulk-ID']))
                : [];

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