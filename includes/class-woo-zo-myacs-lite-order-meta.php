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
        $value = sanitize_text_field($tracking_code);
        $order = function_exists('wc_get_order') ? wc_get_order($order_id) : false;

        if (!$order instanceof WC_Order) {
            return;
        }

        $order->update_meta_data('_woo_zo_myacs_reference', $value);
        $order->save_meta_data();
    }

    /**
     * Remove the stored carrier reference from the order.
     */
    public function clear_tracking_code($order_id)
    {
        $order = function_exists('wc_get_order') ? wc_get_order($order_id) : false;

        if (!$order instanceof WC_Order) {
            return;
        }

        $order->delete_meta_data('_woo_zo_myacs_reference');
        $order->save_meta_data();
    }

    /**
     * Save the last known tracking status and history summary on the order.
     */
    public function set_tracking_summary($order_id, $status, $history)
    {
        $status = sanitize_text_field($status);
        $history = sanitize_text_field($history);
        $order = function_exists('wc_get_order') ? wc_get_order($order_id) : false;

        if (!$order instanceof WC_Order) {
            return;
        }

        $order->update_meta_data('_woo_zo_myacs_tracking_status', $status);
        $order->update_meta_data('_woo_zo_myacs_tracking_history', $history);
        $order->save_meta_data();
    }
}
