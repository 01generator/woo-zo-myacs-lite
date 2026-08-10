<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register and coordinate all admin-facing Lite plugin features.
 */
class Woo_Zo_Myacs_Lite_Admin
{
    protected $plugin_name;
    protected $version;
    protected $settings;
    protected $metabox;
    protected $ajax;
    protected $notices;
    protected $capabilities;
    protected $adapter;

    /**
     * Return the WooCommerce admin screen IDs supported for order editing.
     */
    protected function get_order_screen_ids()
    {
        $screens = array('shop_order');

        if (function_exists('wc_get_page_screen_id')) {
            $screens[] = wc_get_page_screen_id('shop-order');
        }

        return array_values(array_unique(array_filter($screens)));
    }

    /**
     * Return the WooCommerce admin screen IDs supported for order lists.
     */
    protected function get_order_list_screen_ids()
    {
        return array_values(array_unique(array_filter(array(
            'edit-shop_order',
            'woocommerce_page_wc-orders',
            'admin_page_wc-orders',
            function_exists('wc_get_page_screen_id') ? wc_get_page_screen_id('shop-order') : '',
            function_exists('wc_get_page_screen_id') ? wc_get_page_screen_id('shop_order') : '',
        ))));
    }

    /**
     * Build the admin module dependencies.
     */
    public function __construct($plugin_name, $version, $options, $repository, $order_meta, $pdf_manager, $token, $capabilities, $notices, $adapter)
    {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->settings = new Woo_Zo_Myacs_Lite_Settings($options, $pdf_manager);
        $this->metabox = new Woo_Zo_Myacs_Lite_Order_Metabox($repository);
        $this->ajax = new Woo_Zo_Myacs_Lite_Ajax($repository, $order_meta, $pdf_manager, $adapter, $options);
        $this->notices = $notices;
        $this->capabilities = $capabilities;
        $this->adapter = $adapter;
    }

    /**
     * Load admin assets on plugin pages, order screens and the updates screen.
     */
    public function enqueue_assets($hook)
    {
        if ('update-core.php' !== $hook && false === strpos((string) $hook, 'woocommerce_page_woo-zo-myacs-lite')) {
            $screen = get_current_screen();
            $allowed_screens = array_merge($this->get_order_screen_ids(), $this->get_order_list_screen_ids());
            if (!$screen || !in_array($screen->id, $allowed_screens, true)) {
                return;
            }
        }

        wp_enqueue_style($this->plugin_name, Woo_Zo_Myacs_Lite_URL . 'assets/css/admin.css', array(), $this->version);
        wp_enqueue_script($this->plugin_name, Woo_Zo_Myacs_Lite_URL . 'assets/js/admin.js', array('jquery'), $this->version, true);
        wp_localize_script($this->plugin_name, 'wooZoMyacsLite', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('woo_zo_myacs_lite_nonce'),
            'pluginFile' => plugin_basename(Woo_Zo_Myacs_Lite_FILE),
            'logoUrl' => Woo_Zo_Myacs_Lite_URL . 'assets/images/logo-128x128.png',
            'i18n' => array(
                'cancelTitle' => __('Cancel voucher', 'woo-zo-myacs-lite'),
                'cancelMessage' => __('Are you sure you want to cancel voucher #%s?', 'woo-zo-myacs-lite'),
                'cancelMessageEmpty' => __('Are you sure you want to cancel this voucher?', 'woo-zo-myacs-lite'),
                'confirmYes' => __('Yes', 'woo-zo-myacs-lite'),
                'confirmCancel' => __('Cancel', 'woo-zo-myacs-lite'),
                'closeAction' => __('Close', 'woo-zo-myacs-lite'),
                'closeDayTitle' => __('MyACS - Close the Day', 'woo-zo-myacs-lite'),
                'closeDayLoading' => __('Requesting the ACS pickup list...', 'woo-zo-myacs-lite'),
                'closeDaySuccess' => __('The ACS pickup list is ready.', 'woo-zo-myacs-lite'),
                'closeDayDownload' => __('Download pickup list', 'woo-zo-myacs-lite'),
                'requestFailed' => __('Request failed.', 'woo-zo-myacs-lite'),
            ),
        ));
    }

    /**
     * Register the WooCommerce submenu page for the Lite settings screen.
     */
    public function register_menu()
    {
        add_submenu_page(
            'woocommerce',
            __('MyACS Lite', 'woo-zo-myacs-lite'),
            __('MyACS Lite', 'woo-zo-myacs-lite'),
            'manage_woocommerce',
            'woo-zo-myacs-lite',
            array($this->settings, 'render_page')
        );
    }

    /**
     * Register the order-side shipment metabox for WooCommerce orders.
     */
    public function register_metabox()
    {
        foreach ($this->get_order_screen_ids() as $screen_id) {
            add_meta_box(
                'woo-zo-myacs-lite',
                __('MyACS Lite', 'woo-zo-myacs-lite'),
                array($this->metabox, 'render'),
                $screen_id,
                'side',
                'high'
            );
        }
    }

    /**
     * Render queued admin notices.
     */
    public function render_notices()
    {
        $this->notices->render();
    }

    /**
     * Render the close-day action button on the classic order list screen.
     */
    public function render_order_list_close_day_button_legacy()
    {
        global $typenow;

        if ('shop_order' !== $typenow) {
            return;
        }

        $this->render_order_list_close_day_button();
    }

    /**
     * Render the close-day action button on supported order list screens.
     */
    public function render_order_list_close_day_button()
    {
        if (!$this->capabilities->supports_close_day()) {
            return;
        }

        $url = wp_nonce_url(
            admin_url('admin-post.php?action=woo_zo_myacs_lite_close_day'),
            'woo_zo_myacs_lite_close_day'
        );

        echo '<a class="button button-secondary woo-zo-myacs-lite-close-day-button" href="' . esc_url($url) . '" rel="noopener noreferrer" style="margin-left:8px;">' .
            esc_html__('MyACS - Close the Day', 'woo-zo-myacs-lite') .
            '</a>';
    }

    /**
     * Handle the close-day request and redirect the browser to the generated list PDF.
     */
    public function handle_close_day_request()
    {
        if (!current_user_can('manage_woocommerce') && !current_user_can('edit_shop_orders')) {
            wp_die(esc_html__('Permission denied.', 'woo-zo-myacs-lite'));
        }

        check_admin_referer('woo_zo_myacs_lite_close_day');

        if (!$this->capabilities->supports_close_day()) {
            $this->notices->set_notice(__('Close day is not supported.', 'woo-zo-myacs-lite'), 'error');
            wp_safe_redirect($this->get_orders_list_url());
            exit;
        }

        $result = $this->adapter->close_day();
        if (empty($result['success']) || empty($result['url'])) {
            $this->notices->set_notice(
                !empty($result['message']) ? $result['message'] : __('The ACS pickup list could not be generated.', 'woo-zo-myacs-lite'),
                'error'
            );
            wp_safe_redirect($this->get_orders_list_url());
            exit;
        }

        wp_safe_redirect($result['url']);
        exit;
    }

    /**
     * Build the WooCommerce orders-list URL used for close-day fallbacks.
     */
    protected function get_orders_list_url()
    {
        if (function_exists('wc_get_page_screen_id') && class_exists('Automattic\\WooCommerce\\Internal\\DataStores\\Orders\\CustomOrdersTableController')) {
            $screen_id = wc_get_page_screen_id('shop-order');
            if ('woocommerce_page_wc-orders' === $screen_id) {
                return admin_url('admin.php?page=wc-orders');
            }
        }

        return admin_url('edit.php?post_type=shop_order');
    }

    /**
     * Add a quick settings link to the plugins list row.
     */
    public function add_action_links($links)
    {
        array_unshift($links, '<a href="' . esc_url(admin_url('admin.php?page=woo-zo-myacs-lite')) . '">' . esc_html__('Settings', 'woo-zo-myacs-lite') . '</a>');

        return $links;
    }

    /**
     * Proxy the order option save AJAX request.
     */
    public function ajax_save_options() { $this->ajax->save_options(); }

    /**
     * Proxy the create-and-print AJAX request.
     */
    public function ajax_create_print() { $this->ajax->create_print(); }

    /**
     * Proxy the cancel shipment AJAX request.
     */
    public function ajax_cancel() { $this->ajax->cancel(); }

    /**
     * Proxy the manual tracking AJAX request.
     */
    public function ajax_track() { $this->ajax->track(); }

    /**
     * Validate support for close day before proxying the AJAX request.
     */
    public function ajax_close_day()
    {
        if (!$this->capabilities->supports_close_day()) {
            wp_send_json_error(array('message' => __('Close day is not supported.', 'woo-zo-myacs-lite')));
        }
        $this->ajax->close_day();
    }

    /**
     * Proxy the generated PDF cleanup AJAX request.
     */
    public function ajax_clear_pdfs() { $this->ajax->clear_pdfs(); }
}
