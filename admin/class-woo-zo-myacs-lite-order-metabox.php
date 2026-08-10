<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Render the WooCommerce order metabox for the Lite shipment workflow.
 */
class Woo_Zo_Myacs_Lite_Order_Metabox
{
    protected $repository;
    protected $show_upgrade_note;

    /**
     * Store the repository used to load and initialize order rows.
     */
    public function __construct($repository, $show_upgrade_note = true)
    {
        $this->repository = $repository;
        $this->show_upgrade_note = (bool) $show_upgrade_note;
    }

    /**
     * Output the complete order metabox markup and saved values.
     */
    public function render($post)
    {
        $order_id = 0;
        if ($post instanceof WP_Post) {
            $order_id = (int) $post->ID;
        } elseif (is_object($post) && method_exists($post, 'get_id')) {
            $order_id = (int) $post->get_id();
        } elseif (is_object($post) && isset($post->ID)) {
            $order_id = (int) $post->ID;
        }

        if ($order_id <= 0) {
            echo '<p>' . esc_html__('Order context could not be resolved.', 'woo-zo-myacs-lite') . '</p>';

            return;
        }

        $row = $this->repository->ensure_order_row($order_id);
        $print_icon = plugins_url('../assets/images/print.svg', __FILE__);
        $track_icon = plugins_url('../assets/images/barcode-alt.svg', __FILE__);
        $cancel_icon = plugins_url('../assets/images/trash-alt.svg', __FILE__);
        $comment_icon = plugins_url('../assets/images/pencil-alt.svg', __FILE__);
        $info_icon = plugins_url('../assets/images/info-circle.svg', __FILE__);
        ?>
        <div id="woo-zo-myacs-lite-metabox" data-plugin="woo-zo-myacs-lite" data-order-id="<?php echo esc_attr($order_id); ?>">
            <div class="wp-zo-cfl-summary">
                <div class="wp-zo-cfl-summary-row">
                    <span class="wp-zo-cfl-summary-label"><?php esc_html_e('Reference', 'woo-zo-myacs-lite'); ?></span>
                    <span class="wp-zo-cfl-reference-wrap">
                        <?php if (!empty($row['reference'])) : ?>
                            <a class="wp-zo-cfl-reference-link wp-zo-cfl-reference" href="<?php echo esc_url('https://a.acssp.gr/track/?k=etr:' . rawurlencode($row['reference'])); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html($row['reference']); ?></a>
                        <?php else : ?>
                            <span class="wp-zo-cfl-reference"></span>
                        <?php endif; ?>
                    </span>
                </div>
                <div class="wp-zo-cfl-summary-row">
                    <span class="wp-zo-cfl-summary-label"><?php esc_html_e('Tracking Status', 'woo-zo-myacs-lite'); ?></span>
                    <span class="wp-zo-cfl-tracking"><?php echo esc_html(trim($row['order_delivery_status'] . ' ' . $row['order_delivery_history'])); ?></span>
                </div>
            </div>

            <div class="wp-zo-cfl-field-stack">
                <label class="wp-zo-cfl-checkline"><input type="checkbox" class="wp-zo-cfl-field" data-field="cod" <?php checked((int) $row['cod'], 1); ?>> <?php esc_html_e('COD', 'woo-zo-myacs-lite'); ?></label>

                <div class="wp-zo-cfl-inline-grid">
                    <label class="wp-zo-cfl-field-box">
                        <span class="wp-zo-cfl-field-label"><?php esc_html_e('Parcels', 'woo-zo-myacs-lite'); ?></span>
                        <input type="number" min="1" class="small-text wp-zo-cfl-field" data-field="parcels" value="<?php echo esc_attr((int) $row['parcels']); ?>">
                    </label>
                    <label class="wp-zo-cfl-field-box">
                        <span class="wp-zo-cfl-field-label"><?php esc_html_e('Weight', 'woo-zo-myacs-lite'); ?></span>
                        <input type="text" class="small-text wp-zo-cfl-field" data-field="weight" value="<?php echo esc_attr($row['weight']); ?>">
                    </label>
                </div>

                <label class="wp-zo-cfl-checkline"><input type="checkbox" class="wp-zo-cfl-field" data-field="sat" <?php checked((int) $row['sat'], 1); ?>> <?php esc_html_e('Saturday Delivery', 'woo-zo-myacs-lite'); ?></label>
                <label class="wp-zo-cfl-checkline"><input type="checkbox" class="wp-zo-cfl-field" data-field="rec" <?php checked((int) $row['rec'], 1); ?>> <?php esc_html_e('Reception Delivery', 'woo-zo-myacs-lite'); ?></label>
                <label class="wp-zo-cfl-checkline"><input type="checkbox" class="wp-zo-cfl-field" data-field="return_voucher" <?php checked((int) $row['return_voucher'], 1); ?>> <?php esc_html_e('Return Voucher', 'woo-zo-myacs-lite'); ?></label>

                <label class="wp-zo-cfl-comment-field" for="wp-zo-cfl-comment">
                    <span class="wp-zo-cfl-field-label wp-zo-cfl-field-label-icon">
                        <img src="<?php echo esc_url($comment_icon); ?>" alt="" aria-hidden="true" class="wp-zo-cfl-button-icon">
                        <?php esc_html_e('Comment', 'woo-zo-myacs-lite'); ?>
                    </span>
                    <textarea id="wp-zo-cfl-comment" class="widefat wp-zo-cfl-field" data-field="comment"><?php echo esc_textarea($row['comment']); ?></textarea>
                </label>
            </div>

            <div class="wp-zo-cfl-actions">
                <button type="button" class="button button-primary wp-zo-cfl-action wp-zo-cfl-action-primary" data-action="create_print">
                    <img src="<?php echo esc_url($print_icon); ?>" alt="" aria-hidden="true" class="wp-zo-cfl-button-icon">
                    <span><?php esc_html_e('Create & Print Voucher', 'woo-zo-myacs-lite'); ?></span>
                </button>
                <div class="wp-zo-cfl-actions-secondary">
                    <button type="button" class="button wp-zo-cfl-action wp-zo-cfl-action-track" data-action="track">
                        <img src="<?php echo esc_url($track_icon); ?>" alt="" aria-hidden="true" class="wp-zo-cfl-button-icon">
                        <span><?php esc_html_e('Track Voucher', 'woo-zo-myacs-lite'); ?></span>
                    </button>
                    <button type="button" class="button wp-zo-cfl-action wp-zo-cfl-action-cancel" data-action="cancel">
                        <img src="<?php echo esc_url($cancel_icon); ?>" alt="" aria-hidden="true" class="wp-zo-cfl-button-icon">
                        <span><?php esc_html_e('Cancel Voucher', 'woo-zo-myacs-lite'); ?></span>
                    </button>
                </div>
            </div>
            <?php if ($this->show_upgrade_note) : ?>
                <div class="wp-zo-cfl-upgrade-note">
                    <p class="wp-zo-cfl-upgrade-note-main">
                        <img src="<?php echo esc_url($info_icon); ?>" alt="" aria-hidden="true" class="wp-zo-cfl-button-icon">
                        <span><?php esc_html_e('Upgrade now and send automated email with the voucher number. Track your orders via CRON and mass print multiple vouchers in a few clicks.', 'woo-zo-myacs-lite'); ?></span>
                    </p>
                    <p class="wp-zo-cfl-upgrade-note-link">
                        <a href="<?php echo esc_url(woo_zo_myacs_lite_get_pro_url()); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e('You can find the Pro version here.', 'woo-zo-myacs-lite'); ?></a>
                    </p>
                </div>
            <?php endif; ?>
            <div class="wp-zo-cfl-message"></div>
        </div>
        <?php
    }
}
