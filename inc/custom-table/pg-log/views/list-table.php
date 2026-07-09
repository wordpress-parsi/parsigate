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
