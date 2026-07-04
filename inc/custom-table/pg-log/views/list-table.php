<?php
if (!defined('ABSPATH')) exit;
?>
<div class="wrap">
    <h1 class="wp-heading-inline">
        <?php echo esc_html($title); ?>
    </h1>
    <?php
    if (!empty($buttons)) {
        foreach ($buttons as $parsigate_btn) {
            ?>
            <a href="<?php echo esc_url($parsigate_btn['link']); ?>"
               class="page-title-action"><?php echo esc_html($parsigate_btn['name']); ?></a>
            <?php
        }
    }
    ?>
    <hr class="wp-header-end">
    <div id="poststuff">
        <div id="post-body" class="metabox-holder columns">
            <div>
                <div class="meta-box-sortables ui-sortable">
                    <?php $table->views(); ?>
                    <form method="post" action="<?php echo esc_url(remove_query_arg(array('alert'))); ?>">
                        <?php
                        $table->search_box(__("Search", "parsigate"), '');
                        $table->display();
                        ?>
                    </form>
                </div>
            </div>
        </div>
        <br class="clear">
    </div>
</div>

<style>
    .pg-log-column-status {
        display: inline-block;
        background: #e3e3e3;
        padding: 8px;
        border: 1px solid #e3e3e3;
        border-radius: 5px;
        cursor: default;
    }

    .pg-log-column-status-success {
        background: #139f3f;
        border: 1px solid #139f3f;
        color: #fff;
    }

    .pg-log-column-status-error {
        background: #d63638;
        border: 1px solid #d63638;
        color: #fff;
    }

    .pg-log-json-pre-area {
        position: fixed;
        background: #e3e3e3bf;
        width: 100%;
        height: 100%;
        z-index: 99999;
        top: 0;
        right: 0;
        text-align: center;
        padding-top: 5%;
        display: none;
    }

    .pg-log-json-pre-area pre {
        width: 50%;
        height: 400px;
        background: #fff;
        direction: ltr;
        text-align: left;
        margin: 0 auto;
        padding: 40px;
        overflow-y: scroll;
    }

    .pg-log-json-pre-area__close {
        margin-top: 20px;
    }

    .pg-log-admin-list-top {
        margin: 60px 60px 30px 60px;
        background: #f6f7f7;
        border: 1px solid #e3e3e3;
        padding: 10px 40px 25px 40px;
    }

    .pg-log-admin-list-top__close-button {
        text-align: left;
        margin-top: 10px;
    }

    .pg-log-admin-list-top__close-button span {
        font-size: 30px;
        color: #8f8f8f;
        cursor: pointer;
    }
</style>
<script>
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
    });
</script>