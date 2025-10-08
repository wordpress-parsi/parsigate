<?php
// Check Load Persian DatePicker If Exist
$has_persian_datepicker = false;
$search_fields = apply_filters('admin_post_type_search_box_fields', array());

// Get Search Input ID
global $post_type, $pagenow;
if (in_array($pagenow, ["admin.php", "edit.php", "tools.php"]) and !empty($_GET['page'])) {
    $search_input_id = trim($_GET['page']) . '-search-input';
} elseif (!empty($post_type)) {
    $search_input_id = 'post-search-input';
} else {
    $search_input_id = 'user-search-input';
}
?>
<script>
    jQuery(document).ready(function ($) {
        // Add Field To Search Box [@see https://developer.wordpress.org/reference/classes/wp_list_table/search_box/]
        $("input#<?php echo $search_input_id; ?>").attr('autocomplete', 'off');
        $(`<select name="search-type" data-current-value="<?php if (isset($_REQUEST['s']) and !empty($_REQUEST['s'])) {
            echo trim($_REQUEST['s']);
        } ?>">
        <?php
        $search_fields = apply_filters('admin_post_type_search_box_fields', array());
        foreach ($search_fields as $name => $value) {

        // Check Value Type
        $type = 'text';
        if (isset($value['type'])) {
            $type = $value['type'];
        }

        // $Selected Data
        $choices = '';
        if (isset($value['choices']) and is_array($value['choices']) and !empty($value['choices'])) {
            $choices = json_encode($value['choices'], JSON_NUMERIC_CHECK);
        }

        // Check Title
        if (is_array($value)) {
            $title = $value['title'];
        } else {
            $title = $value;
        }
        ?>
            <option <?php if(!empty($choices)) { ?> data-selected='<?php echo $choices; ?>' <?php } ?> data-type="<?php echo $type; ?>" value="<?php echo $name; ?>" <?php if (isset($_REQUEST['search-type'])) {
            selected($_REQUEST['search-type'], $name);
        } ?>><?php echo $title; ?></option>
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
            let default_search_input = `<input type="search" id="<?php echo $search_input_id; ?>" name="s" value="` + current_value + `" autocomplete="off">`;
            let post_search_input = $("#<?php echo $search_input_id; ?>");

            switch (option_type) {
                case "select":
                    let option_choices = JSON.parse(opt_selected.attr("data-selected"));
                    let opt_list = `<select id="<?php echo $search_input_id; ?>" name="s">`;
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
                    $("#<?php echo $search_input_id; ?>").replaceWith(default_search_input);
                    let DatePickerID = "picker-" + new Date().valueOf();
                    $("#<?php echo $search_input_id; ?>").attr("data-persian-datepicker-id", DatePickerID);
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
                    let TagName = $("#<?php echo $search_input_id; ?>").prop("tagName");
                    if (TagName == "input") {
                        let this_value = $("#<?php echo $search_input_id; ?>").val();
                    }
                    $("#<?php echo $search_input_id; ?>").replaceWith(default_search_input);
                    if (TagName == "input" && typeof this_value !== 'undefined') {
                        $("#<?php echo $search_input_id; ?>").val(this_value);
                    }
                    break;
            }

            // Show After Render
            $("#<?php echo $search_input_id; ?>").show();
        }

        // Run in Load Page
        let current_value = $("select[name=search-type]").attr('data-current-value');
        _wp_list_table_search_box_form(current_value);
    });
</script>
<style>
    #<?php echo $search_input_id; ?>  {  display: none  ;  }

    .search-box select[name="s"], .tablenav .search-plugins select[name="s"], .tagsdiv .newtag {
        float: right;
        margin: 1px 0 0 4px;
    }

    .search-box input[name="s"], .tablenav .search-plugins input[name="s"], .tagsdiv .newtag {
        margin: 1px 0 0 4px;
    }
</style>