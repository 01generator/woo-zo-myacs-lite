<?php

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/class-woo-zo-myacs-lite-options.php';
require_once __DIR__ . '/class-woo-zo-myacs-lite-pdf-manager.php';

/**
 * Create the Lite database schema and default runtime resources on activation.
 */
class Woo_Zo_Myacs_Lite_Activator
{
    /**
     * Run the plugin activation tasks.
     */
    public static function activate()
    {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $table_name = $wpdb->prefix . 'Woo_Zo_Myacs_Lite';
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE {$table_name} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            id_order BIGINT UNSIGNED NOT NULL,
            reference TEXT NOT NULL,
            vouchers LONGTEXT NOT NULL,
            order_delivery_status TEXT NOT NULL,
            order_delivery_history LONGTEXT NOT NULL,
            weight DECIMAL(20,6) NOT NULL DEFAULT 0.000000,
            parcels INT NOT NULL DEFAULT 1,
            cod TINYINT(1) NOT NULL DEFAULT 0,
            comment LONGTEXT NOT NULL,
            price DECIMAL(20,6) NOT NULL DEFAULT 0.000000,
            sat TINYINT(1) NOT NULL DEFAULT 0,
            rec TINYINT(1) NOT NULL DEFAULT 0,
            return_voucher TINYINT(1) NOT NULL DEFAULT 0,
            extra_flags LONGTEXT NULL,
            date_created DATETIME NOT NULL,
            date_updated DATETIME NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY id_order (id_order)
        ) {$charset_collate};";

        dbDelta($sql);

        $options = new Woo_Zo_Myacs_Lite_Options();
        $options->ensure_defaults();

        $pdf_manager = new Woo_Zo_Myacs_Lite_Pdf_Manager();
        $pdf_manager->ensure_storage();
    }
}
