<?php
// phpcs:disable WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
$parsigate_has_persian_datepicker = false;
$parsigate_search_fields = apply_filters('parsigate_admin_post_type_search_box_fields', array());

// Get Search Input ID
global $post_type, $pagenow;
if (in_array($pagenow, ["admin.php", "edit.php", "tools.php"]) and !empty($_GET['page'])) {
    $parsigate_search_input_id = sanitize_text_field(wp_unslash($_GET['page'])) . '-search-input';
} elseif (!empty($post_type)) {
    $parsigate_search_input_id = 'post-search-input';
} else {
    $parsigate_search_input_id = 'user-search-input';
}
?>
<script>
    jQuery(document).ready(function ($) {
        // Add Field To Search Box [@see https://developer.wordpress.org/reference/classes/wp_list_table/search_box/]
        $("input#<?php echo esc_html($parsigate_search_input_id); ?>").attr('autocomplete', 'off');
        $(`<select name="search-type" data-current-value="<?php if (isset($_REQUEST['s']) and !empty($_REQUEST['s'])) {
            echo esc_html(sanitize_text_field(wp_unslash($_REQUEST['s'])));
        } ?>">
        <?php
        $parsigate_search_fields = apply_filters('parsigate_admin_post_type_search_box_fields', array());
        foreach ($parsigate_search_fields as $parsigate_name => $parsigate_value_array) {

        // Check Value Type
        $type = 'text';
        if (isset($parsigate_value_array['type'])) {
            $type = $parsigate_value_array['type'];
        }

        // $Selected Data
        $parsigate_choices = '';
        if (isset($parsigate_value_array['choices']) and is_array($parsigate_value_array['choices']) and !empty($parsigate_value_array['choices'])) {
            $parsigate_choices = json_encode($parsigate_value_array['choices'], JSON_NUMERIC_CHECK);
        }

        // Check Title
        if (is_array($parsigate_value_array)) {
            $title = $parsigate_value_array['title'];
        } else {
            $title = $parsigate_value_array;
        }
        ?>
            <option <?php if(!empty($parsigate_choices)) { ?> data-selected='<?php echo esc_html( $parsigate_choices ); ?>' <?php } ?> data-type="<?php echo esc_html( $type ); ?>" value="<?php  echo esc_html( $parsigate_name ); ?>" <?php if (isset($_REQUEST['search-type'])) {
            selected( sanitize_text_field(wp_unslash($_REQUEST['search-type'])) , $parsigate_name);
        } ?>><?php echo esc_html($title); ?></option>
            <?php
        }
        ?></select>`).prependTo($("p.search-box"));

        // Handle Select Search
        $(document).on("change", "select[name=search-type]", function (e) {
            e.preventDefault();
            _wp_list_table_search_box_form();
        });

        // Handle Search Box Form
        function _wp_list_table_search_box_form(current_value = '') {
            let opt_selected = $('select[name=search-type] option:selected');
            let option_type = opt_selected.attr('data-type');
            let default_search_input = `<input type="search" id="<?php echo esc_html($parsigate_search_input_id); ?>" name="s" value="` + current_value + `" autocomplete="off">`;
            let post_search_input = $("#<?php echo esc_html($parsigate_search_input_id); ?>");

            switch (option_type) {
                case "select":
                    let option_choices = JSON.parse(opt_selected.attr("data-selected"));
                    let opt_list = `<select id="<?php echo esc_html($parsigate_search_input_id); ?>" name="s">`;
                    Object.entries(option_choices).forEach(([key, val]) => {
                        let selected = '';
                        if (current_value.length > 0 && key == current_value) {
                            selected = ' selected';
                        }
                        opt_list += `<option value="${key}" ${selected}>${val}</option>`;
                    });
                    opt_list += `</select>`;
                    post_search_input.replaceWith(opt_list);
                    break;
                case "persian-datepicker":
                    $("#<?php echo esc_html($parsigate_search_input_id); ?>").replaceWith(default_search_input);
                    let DatePickerID = "picker-" + new Date().valueOf();
                    $("#<?php echo esc_html($parsigate_search_input_id); ?>").attr("data-persian-datepicker-id", DatePickerID);
                    let persian_datepicker_arg = {
                        cellWidth: 38,
                        cellHeight: 38,
                        fontSize: 14,
                        formatDate: "YYYY-0M-0D",
                        onSelect: function () {
                            let jdate = $("input[data-persian-datepicker-id=" + DatePickerID + "]").attr("data-jdate");
                            jQuery("input[data-persian-datepicker-id=" + DatePickerID + "]").val(jdate);
                        }
                    };
                    if (current_value.length > 0) {
                        let exp = current_value.split("-");
                        persian_datepicker_arg['selectedDate'] = exp[0] + '/' + exp[1] + '/' + exp[2];
                    }
                    jQuery("input[data-persian-datepicker-id=" + DatePickerID + "]").persianDatepicker(persian_datepicker_arg);
                    break;
                case "text":
                    let TagName = $("#<?php echo esc_html($parsigate_search_input_id) ?>").prop("tagName");
                    if (TagName == "input") {
                        let this_value = $("#<?php echo esc_html($parsigate_search_input_id); ?>").val();
                    }
                    $("#<?php  echo esc_html($parsigate_search_input_id); ?>").replaceWith(default_search_input);
                    if (TagName == "input" && typeof this_value !== 'undefined') {
                        $("#<?php echo esc_html($parsigate_search_input_id); ?>").val(this_value);
                    }
                    break;
            }

            // Show After Render
            $("#<?php echo esc_html($parsigate_search_input_id); ?>").show();
        }

        // Run in Load Page
        let current_value = $("select[name=search-type]").attr('data-current-value');
        _wp_list_table_search_box_form(current_value);
    });
</script>
<style>
    #<?php echo esc_html($parsigate_search_input_id); ?>
    {
      display: none;
    }

    .search-box select[name="s"], .tablenav .search-plugins select[name="s"], .tagsdiv .newtag {
        float: right;
        margin: 1px 0 0 4px;
    }

    .search-box input[name="s"], .tablenav .search-plugins input[name="s"], .tagsdiv .newtag {
        margin: 1px 0 0 4px;
    }
</style>