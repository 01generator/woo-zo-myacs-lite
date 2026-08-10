<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Render and process the Lite plugin settings page.
 */
class Woo_Zo_Myacs_Lite_Settings
{
    protected $options;
    protected $pdf_manager;

    /**
     * Store the option and PDF management services used by the settings screen.
     */
    public function __construct($options, $pdf_manager)
    {
        $this->options = $options;
        $this->pdf_manager = $pdf_manager;
    }

    /**
     * Process submitted settings and render the full settings page UI.
     */
    public function render_page()
    {
        if (!current_user_can('manage_woocommerce')) {
            return;
        }

        if (isset($_POST['woo_zo_myacs_lite_save_settings']) && check_admin_referer('woo_zo_myacs_lite_save_settings')) {
            $this->options->set('company_name', sanitize_text_field(wp_unslash($_POST['company_name'] ?? '')));
            $this->options->set('api_username', sanitize_text_field(wp_unslash($_POST['api_username'] ?? '')));
            $this->options->set('api_password', sanitize_text_field(wp_unslash($_POST['api_password'] ?? '')));
            $this->options->set('api_key', sanitize_text_field(wp_unslash($_POST['api_key'] ?? '')));
            $this->options->set('company_id', sanitize_text_field(wp_unslash($_POST['company_id'] ?? '')));
            $this->options->set('company_password', sanitize_text_field(wp_unslash($_POST['company_password'] ?? '')));
            $this->options->set('billing_code', sanitize_text_field(wp_unslash($_POST['billing_code'] ?? '')));
            $this->options->set('print_template', sanitize_text_field(wp_unslash($_POST['print_template'] ?? 'thermal')));
            echo '<div class="notice notice-success"><p>' . esc_html__('Settings saved.', 'woo-zo-myacs-lite') . '</p></div>';
        }

        $settings = $this->options->all();
        $logo_url = Woo_Zo_Myacs_Lite_URL . 'assets/images/logo-256x256.png';
        $save_icon = Woo_Zo_Myacs_Lite_URL . 'assets/images/save.svg';
        $info_icon = Woo_Zo_Myacs_Lite_URL . 'assets/images/info-circle.svg';
        ?>
        <div class="wrap woocommerce">
            <div class="wp-zo-cfl-header">
                <img src="<?php echo esc_url($logo_url); ?>" alt="<?php esc_attr_e('MyACS Lite', 'woo-zo-myacs-lite'); ?>" class="wp-zo-cfl-logo">
                <div class="wp-zo-cfl-header-copy">
                    <h1><?php esc_html_e('MyACS Lite', 'woo-zo-myacs-lite'); ?></h1>
                    <p><?php esc_html_e('Single-order shipment workflow for WooCommerce with a clean upgrade path to the full Pro feature set.', 'woo-zo-myacs-lite'); ?></p>
                </div>
            </div>
            <form method="post">
                <?php wp_nonce_field('woo_zo_myacs_lite_save_settings'); ?>
                <div class="wp-zo-cfl-settings-panels">
                    <div class="wp-zo-cfl-panel">
                        <div class="wp-zo-cfl-panel-header">
                            <h2><?php esc_html_e('Credentials', 'woo-zo-myacs-lite'); ?></h2>
                            <p><?php esc_html_e('Store the ACS web service credentials required to create, print and cancel vouchers.', 'woo-zo-myacs-lite'); ?></p>
                        </div>
                        <table class="form-table">
                            <tr><th><label for="company_name"><?php esc_html_e('Company Name', 'woo-zo-myacs-lite'); ?></label></th><td><input class="regular-text" type="text" name="company_name" id="company_name" value="<?php echo esc_attr($settings['company_name']); ?>"></td></tr>
                            <tr><th><label for="api_username"><?php esc_html_e('API Username', 'woo-zo-myacs-lite'); ?></label></th><td><input class="regular-text" type="text" name="api_username" id="api_username" value="<?php echo esc_attr($settings['api_username']); ?>"></td></tr>
                            <tr><th><label for="api_password"><?php esc_html_e('API Password', 'woo-zo-myacs-lite'); ?></label></th><td><input class="regular-text" type="text" name="api_password" id="api_password" value="<?php echo esc_attr($settings['api_password']); ?>"></td></tr>
                            <tr><th><label for="api_key"><?php esc_html_e('API Key', 'woo-zo-myacs-lite'); ?></label></th><td><input class="regular-text" type="text" name="api_key" id="api_key" value="<?php echo esc_attr($settings['api_key']); ?>"></td></tr>
                            <tr><th><label for="company_id"><?php esc_html_e('Company ID', 'woo-zo-myacs-lite'); ?></label></th><td><input class="regular-text" type="text" name="company_id" id="company_id" value="<?php echo esc_attr($settings['company_id']); ?>"></td></tr>
                            <tr><th><label for="company_password"><?php esc_html_e('Company Password', 'woo-zo-myacs-lite'); ?></label></th><td><input class="regular-text" type="text" name="company_password" id="company_password" value="<?php echo esc_attr($settings['company_password']); ?>"></td></tr>
                            <tr><th><label for="billing_code"><?php esc_html_e('Billing Code', 'woo-zo-myacs-lite'); ?></label></th><td><input class="regular-text" type="text" name="billing_code" id="billing_code" value="<?php echo esc_attr($settings['billing_code']); ?>"></td></tr>
                        </table>
                    </div>

                    <div class="wp-zo-cfl-panel">
                        <div class="wp-zo-cfl-panel-header">
                            <h2><?php esc_html_e('Voucher Settings', 'woo-zo-myacs-lite'); ?></h2>
                            <p><?php esc_html_e('Configure the single-order voucher tools and the ACS print template used when generating labels.', 'woo-zo-myacs-lite'); ?></p>
                        </div>
                        <table class="form-table">
                            <tr><th><label for="print_template"><?php esc_html_e('Print Template', 'woo-zo-myacs-lite'); ?></label></th><td><select name="print_template" id="print_template"><option value="thermal" <?php selected($settings['print_template'], 'thermal'); ?>><?php esc_html_e('Thermal', 'woo-zo-myacs-lite'); ?></option><option value="a4" <?php selected($settings['print_template'], 'a4'); ?>><?php esc_html_e('A4', 'woo-zo-myacs-lite'); ?></option></select></td></tr>
                        </table>
                    </div>

                    <div class="wp-zo-cfl-panel">
                        <div class="wp-zo-cfl-panel-header">
                            <h2><?php esc_html_e('PDF Settings', 'woo-zo-myacs-lite'); ?></h2>
                            <p><?php esc_html_e('Manage the locally generated voucher PDFs stored in the WordPress uploads folder.', 'woo-zo-myacs-lite'); ?></p>
                        </div>
                        <table class="form-table">
                            <tr><th><?php esc_html_e('Generated PDFs', 'woo-zo-myacs-lite'); ?></th><td><p><?php echo esc_html(sprintf(__('Stored files: %d', 'woo-zo-myacs-lite'), $this->pdf_manager->count_files())); ?></p><p><button type="button" class="button" id="wp-zo-cfl-clear-pdfs"><?php esc_html_e('Clear Generated PDFs', 'woo-zo-myacs-lite'); ?></button></p></td></tr>
                        </table>
                    </div>

                    <div class="wp-zo-cfl-panel wp-zo-cfl-panel-accent">
                        <div class="wp-zo-cfl-panel-header">
                            <h2><?php esc_html_e('Lite vs Pro', 'woo-zo-myacs-lite'); ?></h2>
                            <p><?php esc_html_e('The Lite version already covers the single-order workflow. Use this table to see exactly what expands in Pro.', 'woo-zo-myacs-lite'); ?></p>
                        </div>
                        <div class="wp-zo-cfl-panel-body">
                            <table class="wp-zo-cfl-pro-table">
                                <thead>
                                    <tr>
                                        <th><?php esc_html_e('Feature / Function', 'woo-zo-myacs-lite'); ?></th>
                                        <th><?php esc_html_e('Lite', 'woo-zo-myacs-lite'); ?></th>
                                        <th><?php esc_html_e('Pro', 'woo-zo-myacs-lite'); ?></th>
                                        <th><?php esc_html_e('Description', 'woo-zo-myacs-lite'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><?php esc_html_e('Create & Print Voucher', 'woo-zo-myacs-lite'); ?></td>
                                        <td><?php esc_html_e('Yes', 'woo-zo-myacs-lite'); ?></td>
                                        <td><?php esc_html_e('Yes', 'woo-zo-myacs-lite'); ?></td>
                                        <td><?php esc_html_e('Create a shipment from the order screen and open the generated PDF label in a new tab for immediate printing.', 'woo-zo-myacs-lite'); ?></td>
                                    </tr>
                                    <tr>
                                        <td><?php esc_html_e('Cancel Voucher', 'woo-zo-myacs-lite'); ?></td>
                                        <td><?php esc_html_e('Yes', 'woo-zo-myacs-lite'); ?></td>
                                        <td><?php esc_html_e('Yes', 'woo-zo-myacs-lite'); ?></td>
                                        <td><?php esc_html_e('Cancel a previously created shipment directly from the WooCommerce order page.', 'woo-zo-myacs-lite'); ?></td>
                                    </tr>
                                    <tr>
                                        <td><?php esc_html_e('Manual Tracking', 'woo-zo-myacs-lite'); ?></td>
                                        <td><?php esc_html_e('Yes', 'woo-zo-myacs-lite'); ?></td>
                                        <td><?php esc_html_e('Yes', 'woo-zo-myacs-lite'); ?></td>
                                        <td><?php esc_html_e('Request and store the latest carrier tracking message manually from the order page.', 'woo-zo-myacs-lite'); ?></td>
                                    </tr>
                                    <tr>
                                        <td><?php esc_html_e('Order Page Shipment Options', 'woo-zo-myacs-lite'); ?></td>
                                        <td><?php esc_html_e('Yes', 'woo-zo-myacs-lite'); ?></td>
                                        <td><?php esc_html_e('Yes', 'woo-zo-myacs-lite'); ?></td>
                                        <td><?php esc_html_e('Edit COD, parcels, weight, comment and other supported carrier options inline on each order.', 'woo-zo-myacs-lite'); ?></td>
                                    </tr>
                                    <tr>
                                        <td><?php esc_html_e('Close Day', 'woo-zo-myacs-lite'); ?></td>
                                        <td><?php esc_html_e('Yes*', 'woo-zo-myacs-lite'); ?></td>
                                        <td><?php esc_html_e('Yes*', 'woo-zo-myacs-lite'); ?></td>
                                        <td><?php esc_html_e('Available when the carrier API supports it. The plugin will only show it when supported.', 'woo-zo-myacs-lite'); ?></td>
                                    </tr>
                                    <tr>
                                        <td><?php esc_html_e('Print Template Choice', 'woo-zo-myacs-lite'); ?></td>
                                        <td><?php esc_html_e('Yes', 'woo-zo-myacs-lite'); ?></td>
                                        <td><?php esc_html_e('Yes', 'woo-zo-myacs-lite'); ?></td>
                                        <td><?php esc_html_e('Select the label output format, such as thermal or A4, from the plugin settings.', 'woo-zo-myacs-lite'); ?></td>
                                    </tr>
                                    <tr>
                                        <td><?php esc_html_e('Manual PDF Cleanup', 'woo-zo-myacs-lite'); ?></td>
                                        <td><?php esc_html_e('Yes', 'woo-zo-myacs-lite'); ?></td>
                                        <td><?php esc_html_e('Yes', 'woo-zo-myacs-lite'); ?></td>
                                        <td><?php esc_html_e('Delete all locally stored generated PDFs from the settings page whenever you want.', 'woo-zo-myacs-lite'); ?></td>
                                    </tr>
                                    <tr>
                                        <td><?php esc_html_e('Mass Printing', 'woo-zo-myacs-lite'); ?></td>
                                        <td><?php esc_html_e('No', 'woo-zo-myacs-lite'); ?></td>
                                        <td><?php esc_html_e('Yes', 'woo-zo-myacs-lite'); ?></td>
                                        <td><?php esc_html_e('Print multiple selected orders in batches and generate one final merged PDF file.', 'woo-zo-myacs-lite'); ?></td>
                                    </tr>
                                    <tr>
                                        <td><?php esc_html_e('CRON Tracking', 'woo-zo-myacs-lite'); ?></td>
                                        <td><?php esc_html_e('No', 'woo-zo-myacs-lite'); ?></td>
                                        <td><?php esc_html_e('Yes', 'woo-zo-myacs-lite'); ?></td>
                                        <td><?php esc_html_e('Run scheduled tracking updates through a tokenized endpoint and optional server cron job.', 'woo-zo-myacs-lite'); ?></td>
                                    </tr>
                                    <tr>
                                        <td><?php esc_html_e('Automatic Status Changes', 'woo-zo-myacs-lite'); ?></td>
                                        <td><?php esc_html_e('No', 'woo-zo-myacs-lite'); ?></td>
                                        <td><?php esc_html_e('Yes', 'woo-zo-myacs-lite'); ?></td>
                                        <td><?php esc_html_e('Update WooCommerce order statuses automatically when shipments are created, delivered, or marked late.', 'woo-zo-myacs-lite'); ?></td>
                                    </tr>
                                    <tr>
                                        <td><?php esc_html_e('Customer Emails', 'woo-zo-myacs-lite'); ?></td>
                                        <td><?php esc_html_e('No', 'woo-zo-myacs-lite'); ?></td>
                                        <td><?php esc_html_e('Yes', 'woo-zo-myacs-lite'); ?></td>
                                        <td><?php esc_html_e('Send in-transit and thank-you emails using WooCommerce email classes and automation rules.', 'woo-zo-myacs-lite'); ?></td>
                                    </tr>
                                    <tr>
                                        <td><?php esc_html_e('Automatic PDF Cleanup', 'woo-zo-myacs-lite'); ?></td>
                                        <td><?php esc_html_e('Manual only', 'woo-zo-myacs-lite'); ?></td>
                                        <td><?php esc_html_e('Automatic + Manual', 'woo-zo-myacs-lite'); ?></td>
                                        <td><?php esc_html_e('Delete old generated PDF files automatically after the number of days you configure.', 'woo-zo-myacs-lite'); ?></td>
                                    </tr>
                                </tbody>
                            </table>
                            <div class="wp-zo-cfl-upgrade-note wp-zo-cfl-upgrade-note-settings">
                                <p class="wp-zo-cfl-upgrade-note-main">
                                    <img src="<?php echo esc_url($info_icon); ?>" alt="" aria-hidden="true" class="wp-zo-cfl-button-icon">
                                    <span><?php esc_html_e('Upgrade now and send automated email with the voucher number. Track your orders via CRON and mass print multiple vouchers in a few clicks.', 'woo-zo-myacs-lite'); ?></span>
                                </p>
                                <p class="wp-zo-cfl-upgrade-note-link">
                                    <a href="<?php echo esc_url(woo_zo_myacs_lite_get_pro_url()); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e('You can find the Pro version here.', 'woo-zo-myacs-lite'); ?></a>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <p><button type="submit" class="button button-primary wp-zo-cfl-settings-submit" name="woo_zo_myacs_lite_save_settings" value="1"><img src="<?php echo esc_url($save_icon); ?>" alt="" aria-hidden="true" class="wp-zo-cfl-button-icon"> <span><?php esc_html_e('Save Settings', 'woo-zo-myacs-lite'); ?></span></button></p>
            </form>
            <div id="wp-zo-cfl-settings-message"></div>
        </div>
        <?php
    }
}
