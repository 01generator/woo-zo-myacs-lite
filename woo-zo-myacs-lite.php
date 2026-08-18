<?php
/**
 * Plugin Name:       MyACS Lite
 * Plugin URI:        https://github.com/01generator/woo-zo-myacs-lite
 * Description:       Initial MyACS Lite WooCommerce carrier plugin scaffold for voucher creation, printing, canceling, and manual tracking.
 * Version:           0.1.17
 * Author:            01generator
 * Author URI:        https://01generator.com
 * License:           GPL-2.0+
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       woo-zo-myacs-lite
 * Domain Path:       /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

define('Woo_Zo_Myacs_Lite_VERSION', '0.1.17');
define('Woo_Zo_Myacs_Lite_SLUG', 'woo-zo-myacs-lite');
define('Woo_Zo_Myacs_Lite_FILE', __FILE__);
define('Woo_Zo_Myacs_Lite_PATH', plugin_dir_path(__FILE__));
define('Woo_Zo_Myacs_Lite_URL', plugin_dir_url(__FILE__));
define('Woo_Zo_Myacs_Lite_PRO_URL_EN', 'https://01generator.com/wordpress-plugins/woocommerce-plugins/greek-woocommerce-plugins/myacs-pro-for-woocommerce');
define('Woo_Zo_Myacs_Lite_PRO_URL_EL', 'https://01generator.com/el/wordpress-plugins/woocommerce-plugins/ellinika-woocommerce-plugins/myacs-pro-for-woocommerce');

/**
 * Declare compatibility with WooCommerce High-Performance Order Storage.
 */
add_action('before_woocommerce_init', function () {
    if (class_exists('Automattic\\WooCommerce\\Utilities\\FeaturesUtil')) {
        Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
            'custom_order_tables',
            Woo_Zo_Myacs_Lite_FILE,
            true
        );
    }
});

/**
 * Return the localized public product URL for MyACS Pro.
 *
 * @return string
 */
function woo_zo_myacs_lite_get_pro_url()
{
    $locale = function_exists('determine_locale') ? determine_locale() : get_locale();

    return strpos($locale, 'el') === 0
        ? Woo_Zo_Myacs_Lite_PRO_URL_EL
        : Woo_Zo_Myacs_Lite_PRO_URL_EN;
}

$Woo_Zo_Myacs_Lite_autoload = Woo_Zo_Myacs_Lite_PATH . 'vendor/autoload.php';
if (file_exists($Woo_Zo_Myacs_Lite_autoload)) {
    require_once $Woo_Zo_Myacs_Lite_autoload;
}

require_once Woo_Zo_Myacs_Lite_PATH . 'includes/class-woo-zo-myacs-lite-activator.php';
require_once Woo_Zo_Myacs_Lite_PATH . 'includes/class-woo-zo-myacs-lite-deactivator.php';
require_once Woo_Zo_Myacs_Lite_PATH . 'includes/class-woo-zo-myacs-lite.php';

register_activation_hook(__FILE__, array('Woo_Zo_Myacs_Lite_Activator', 'activate'));
register_deactivation_hook(__FILE__, array('Woo_Zo_Myacs_Lite_Deactivator', 'deactivate'));

/**
 * Bootstrap the MyACS Lite plugin instance.
 *
 * @return void
 */
function run_Woo_Zo_Myacs_Lite()
{
    $plugin = new Woo_Zo_Myacs_Lite();
    $plugin->run();
}

run_Woo_Zo_Myacs_Lite();
