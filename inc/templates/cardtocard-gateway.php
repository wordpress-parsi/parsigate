<?php
if (!defined('ABSPATH')) exit;
?>

<section class="parsigate-cardtocard-receipt">
    <h2><?php echo esc_html__('Card to Card Payment Information', 'parsigate'); ?></h2>
    <?php
    if (!empty($message)) {
        echo wp_kses_post($message);
    }
    ?>

    <?php
    if (!empty($wc_settings['instructions'])) {
        ?>
        <div class="parsigate-cardtocard-instructions">
            <?php echo wp_kses_post(wpautop(do_shortcode($wc_settings['instructions']))); ?>
        </div>
        <?php
    }
    ?>

    <table class="shop_table shop_table_responsive">
        <?php
        $parsigate_cardtocard_lists = [
            __('Full Name / Company Name', 'parsigate') => $wc_settings['account_name'],
            __('Bank Account Number', 'parsigate') => $wc_settings['bank_account'],
            __('IBAN', 'parsigate') => $wc_settings['iban'],
            __('Card Number', 'parsigate') => $wc_settings['card_number'],
            __('Bank Name', 'parsigate') => $wc_settings['bank_name'],
        ];

        foreach ($parsigate_cardtocard_lists as $parsigate_cardtocard_item_label => $parsigate_cardtocard_item_value) {
            if ('' === trim((string)$parsigate_cardtocard_item_value)) {
                continue;
            }
            ?>

            <tr>
                <th><?php echo esc_html($parsigate_cardtocard_item_label); ?></th>
                <td data-title="<?php echo esc_attr($parsigate_cardtocard_item_label); ?>"><?php echo esc_html($parsigate_cardtocard_item_value); ?></td>
            </tr>

            <?php
        }
        ?>
    </table>

    <?php
    if ($attachment_id) {
        ?>

        <br />
        <div class="woocommerce-message">
            <?php echo wp_kses_post(wpautop(do_shortcode($wc_settings['success_message']))); ?>
        </div>

        <?php
    } else {
        ?>

        <form method="post"
              action="<?php echo esc_url($action); ?>"
              enctype="multipart/form-data"
              class="parsigate-cardtocard-upload-form">
            <p>
                <label for="parsigate_cardtocard_receipt">
                    <strong>
                        <?php esc_html_e('Payment Receipt Image', 'parsigate'); ?>
                    </strong>
                </label>
                <br>
                <input type="file"
                       id="parsigate_cardtocard_receipt"
                       name="parsigate_cardtocard_receipt"
                       accept="image/jpeg,image/png,image/webp" required>
            </p>

            <p class="description">
                <?php
                printf(
                /* translators: %s: maximum file size in megabytes */
                    esc_html__('Maximum file size: %s MB. Allowed formats: JPG, PNG, and WebP.', 'parsigate'),
                    esc_html($max_size_mb)
                );
                ?>
            </p>

            <?php wp_nonce_field('parsigate_cardtocard_upload_receipt_' . $order->get_id(), 'parsigate_cardtocard_nonce'); ?>

            <input type="hidden"
                   name="parsigate_cardtocard_order_id"
                   value="<?php echo esc_attr($order->get_id()); ?>">

            <button type="submit" class="button alt">
                <?php esc_html_e('Submit Payment Receipt', 'parsigate'); ?>
            </button>
        </form>
        <?php
    }
    ?>
</section>