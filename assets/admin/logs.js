jQuery(document).ready(function ($) {

    jQuery(document).on("click", "[data-pg-log-show-json]", function (e) {
        e.preventDefault();
        let id = jQuery(this).attr("data-pg-log-show-json");
        $("div[id='" + id + "']").show();
    });

    jQuery(document).on("click", ".pg-log-json-pre-area__close", function (e) {
        e.preventDefault();
        jQuery(".pg-log-json-pre-area").hide();
    });

    /* Search Box */
    var parsigateData = window.parsigateAdminData || {};
    var searchInputId = parsigateData.searchInputId || 'pg_log-search-input';
    var optionsHtml = parsigateData.optionsHtml || '';
    var currentSearchValue = parsigateData.currentSearchValue || '';

    // Add Field To Search Box
    $('input#' + searchInputId).attr('autocomplete', 'off');
    $('<select name="search-type" data-current-value="' + currentSearchValue + '">' + optionsHtml + '</select>')
        .prependTo($('p.search-box'));

    // Handle Select Search
    $(document).on('change', 'select[name=search-type]', function (e) {
        e.preventDefault();
        _wp_list_table_search_box_form();
    });

    // Handle Search Box Form
    function _wp_list_table_search_box_form(current_value) {
        current_value = current_value || '';

        var opt_selected = $('select[name=search-type] option:selected');
        var option_type = opt_selected.attr('data-type');
        var default_search_input = '<input type="search" id="' + searchInputId + '" name="s" value="' + current_value + '" autocomplete="off">';
        var post_search_input = $('#' + searchInputId);

        switch (option_type) {
            case 'select':
                var option_choices = JSON.parse(opt_selected.attr('data-selected'));
                var opt_list = '<select id="' + searchInputId + '" name="s">';
                $.each(option_choices, function (key, val) {
                    var selected = '';
                    if (current_value.length > 0 && key == current_value) {
                        selected = ' selected';
                    }
                    opt_list += '<option value="' + key + '"' + selected + '>' + val + '</option>';
                });
                opt_list += '</select>';
                post_search_input.replaceWith(opt_list);
                break;

            case 'persian-datepicker':
                $('#' + searchInputId).replaceWith(default_search_input);
                var DatePickerID = 'picker-' + new Date().valueOf();
                $('#' + searchInputId).attr('data-persian-datepicker-id', DatePickerID);

                var persian_datepicker_arg = {
                    cellWidth: 38,
                    cellHeight: 38,
                    fontSize: 14,
                    formatDate: 'YYYY-0M-0D',
                    onSelect: function () {
                        var jdate = $('input[data-persian-datepicker-id=' + DatePickerID + ']').attr('data-jdate');
                        jQuery('input[data-persian-datepicker-id=' + DatePickerID + ']').val(jdate);
                    }
                };

                if (current_value.length > 0) {
                    var exp = current_value.split('-');
                    persian_datepicker_arg['selectedDate'] = exp[0] + '/' + exp[1] + '/' + exp[2];
                }

                jQuery('input[data-persian-datepicker-id=' + DatePickerID + ']').persianDatepicker(persian_datepicker_arg);
                break;

            case 'text':
                var TagName = $('#' + searchInputId).prop('tagName');
                var this_value = '';
                if (TagName == 'input') {
                    this_value = $('#' + searchInputId).val();
                }

                $('#' + searchInputId).replaceWith(default_search_input);

                if (TagName == 'input' && typeof this_value !== 'undefined') {
                    $('#' + searchInputId).val(this_value);
                }
                break;
        }

        // Show After Render
        $('#' + searchInputId).show();
    }

    // Run in Load Page
    var current_value = $('select[name=search-type]').attr('data-current-value');
    _wp_list_table_search_box_form(current_value);

});