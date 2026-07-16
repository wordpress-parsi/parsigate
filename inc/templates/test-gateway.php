<?php
if (!defined('ABSPATH')) exit;
?>

<?php wp_print_styles(['parsigate-test-gateway']); ?>

<div class="container">
    <h1><?php esc_html_e("Test Gateway", "parsigate"); ?></h1>
    <p><?php esc_html_e("Please click the buttons below to perform the operation", "parsigate"); ?></p>
    <div class="buttons">
        <a href="<?php echo esc_html(add_query_arg(['status' => 'OK'], $callback_url)); ?>" class="btn btn-success">
            <?php esc_html_e("Successful Payment", "parsigate"); ?>
        </a>
        <a href="<?php echo esc_html(add_query_arg(['status' => 'NOK'], $callback_url)); ?>" class="btn btn-danger">
            <?php esc_html_e("Failed Payment", "parsigate"); ?>
        </a>
    </div>
</div>
