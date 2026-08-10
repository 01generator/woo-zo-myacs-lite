<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Synchronize key shipment values into WooCommerce order meta.
 */
class Woo_Zo_Myacs_Lite_Order_Meta
{
    /**
     * Save the main carrier reference on the order.
     */
    public function set_tracking_code($order_id, $tracking_code)
    {
        update_post_meta($order_id, '_woo_zo_myacs_reference', sanitize_text_field($tracking_code));
    }

    /**
     * Remove the stored carrier reference from the order.
     */
    public function clear_tracking_code($order_id)
    {
        delete_post_meta($order_id, '_woo_zo_myacs_reference');
    }

    /**
     * Save the last known tracking status and history summary on the order.
     */
    public function set_tracking_summary($order_id, $status, $history)
    {
        update_post_meta($order_id, '_woo_zo_myacs_tracking_status', sanitize_text_field($status));
        update_post_meta($order_id, '_woo_zo_myacs_tracking_history', sanitize_text_field($history));
    }
}
