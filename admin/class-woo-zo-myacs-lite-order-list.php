<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Render simple order-list guidance for Lite-only tools.
 */
class Woo_Zo_Myacs_Lite_Order_List
{
    /**
     * Output a helper notice for the order list screen.
     */
    public function render_tools_box()
    {
        echo '<div class="notice notice-info"><p>' . esc_html__('Close day is available from the MyACS Lite settings page.', 'woo-zo-myacs-lite') . '</p></div>';
    }
}
