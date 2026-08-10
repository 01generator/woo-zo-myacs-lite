<?php

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/class-woo-zo-myacs-lite-loader.php';
require_once __DIR__ . '/class-woo-zo-myacs-lite-i18n.php';
require_once __DIR__ . '/class-woo-zo-myacs-lite-capabilities.php';
require_once __DIR__ . '/class-woo-zo-myacs-lite-options.php';
require_once __DIR__ . '/class-woo-zo-myacs-lite-repository.php';
require_once __DIR__ . '/class-woo-zo-myacs-lite-order-meta.php';
require_once __DIR__ . '/class-woo-zo-myacs-lite-pdf-manager.php';
require_once __DIR__ . '/class-woo-zo-myacs-lite-token.php';
require_once __DIR__ . '/class-woo-zo-myacs-lite-notices.php';
require_once __DIR__ . '/class-woo-zo-myacs-lite-acs-adapter.php';
require_once dirname(__DIR__) . '/admin/class-woo-zo-myacs-lite-settings.php';
require_once dirname(__DIR__) . '/admin/class-woo-zo-myacs-lite-order-metabox.php';
require_once dirname(__DIR__) . '/admin/class-woo-zo-myacs-lite-order-list.php';
require_once dirname(__DIR__) . '/admin/class-woo-zo-myacs-lite-ajax.php';
require_once dirname(__DIR__) . '/admin/class-woo-zo-myacs-lite-admin.php';

/**
 * Bootstrap and wire together the Lite plugin services.
 */
class Woo_Zo_Myacs_Lite
{
    protected $loader;

    /**
     * Initialize the hook loader and register the plugin modules.
     */
    public function __construct()
    {
        $this->loader = new Woo_Zo_Myacs_Lite_Loader();
        $this->set_locale();
        $this->define_admin_hooks();
    }

    /**
     * Register translation loading for the plugin text domain.
     */
    protected function set_locale()
    {
        $plugin_i18n = new Woo_Zo_Myacs_Lite_I18n();
        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
    }

    /**
     * Build the admin service graph and register all Lite admin hooks.
     */
    protected function define_admin_hooks()
    {
        $options = new Woo_Zo_Myacs_Lite_Options();
        $repository = new Woo_Zo_Myacs_Lite_Repository();
        $order_meta = new Woo_Zo_Myacs_Lite_Order_Meta();
        $pdf_manager = new Woo_Zo_Myacs_Lite_Pdf_Manager();
        $token = new Woo_Zo_Myacs_Lite_Token($options);
        $capabilities = new Woo_Zo_Myacs_Lite_Capabilities();
        $notices = new Woo_Zo_Myacs_Lite_Notices();
        $adapter = new Woo_Zo_Myacs_Lite_Acs_Adapter($options, $pdf_manager);

        $admin = new Woo_Zo_Myacs_Lite_Admin(
            Woo_Zo_Myacs_Lite_SLUG,
            Woo_Zo_Myacs_Lite_VERSION,
            $options,
            $repository,
            $order_meta,
            $pdf_manager,
            $token,
            $capabilities,
            $notices,
            $adapter
        );

        $this->loader->add_action('admin_enqueue_scripts', $admin, 'enqueue_assets');
        $this->loader->add_action('admin_menu', $admin, 'register_menu');
        $this->loader->add_action('add_meta_boxes', $admin, 'register_metabox');
        $this->loader->add_action('admin_notices', $admin, 'render_notices');
        $this->loader->add_action('restrict_manage_posts', $admin, 'render_order_list_close_day_button_legacy');
        $this->loader->add_action('woocommerce_order_list_table_restrict_manage_orders', $admin, 'render_order_list_close_day_button');
        $this->loader->add_filter('plugin_action_links_' . plugin_basename(Woo_Zo_Myacs_Lite_FILE), $admin, 'add_action_links');

        $this->loader->add_action('wp_ajax_woo_zo_myacs_lite_save_options', $admin, 'ajax_save_options');
        $this->loader->add_action('wp_ajax_woo_zo_myacs_lite_create_print', $admin, 'ajax_create_print');
        $this->loader->add_action('wp_ajax_woo_zo_myacs_lite_cancel', $admin, 'ajax_cancel');
        $this->loader->add_action('wp_ajax_woo_zo_myacs_lite_track', $admin, 'ajax_track');
        $this->loader->add_action('wp_ajax_woo_zo_myacs_lite_close_day', $admin, 'ajax_close_day');
        $this->loader->add_action('wp_ajax_woo_zo_myacs_lite_clear_pdfs', $admin, 'ajax_clear_pdfs');
        $this->loader->add_action('admin_post_woo_zo_myacs_lite_close_day', $admin, 'handle_close_day_request');
    }

    /**
     * Register the collected hooks with WordPress.
     */
    public function run()
    {
        $this->loader->run();
    }
}
