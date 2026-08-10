<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Describe which workflow capabilities are enabled in the Lite edition.
 */
class Woo_Zo_Myacs_Lite_Capabilities
{
    /**
     * Lite does not support mass printing.
     */
    public function supports_mass_print()
    {
        return false;
    }

    /**
     * Lite does not support scheduled cron automation.
     */
    public function supports_cron()
    {
        return false;
    }

    /**
     * Lite does not change WooCommerce statuses automatically.
     */
    public function supports_auto_status_change()
    {
        return false;
    }

    /**
     * Lite does not include thank-you email automation.
     */
    public function supports_thank_you_email()
    {
        return false;
    }

    /**
     * Lite does not expose the dedicated send-email button.
     */
    public function supports_send_email_button()
    {
        return false;
    }

    /**
     * Keep close-day support enabled for the MyACS plugin.
     */
    public function supports_close_day()
    {
        return true;
    }

    /**
     * Lite supports manual tracking requests from the order screen.
     */
    public function supports_tracking()
    {
        return true;
    }
}
