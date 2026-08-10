<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Persist short-lived admin notices for later rendering.
 */
class Woo_Zo_Myacs_Lite_Notices
{
    const TRANSIENT_KEY = 'Woo_Zo_Myacs_Lite_notice';

    /**
     * Queue a notice message in a transient for the next admin page load.
     */
    public function set_notice($message, $type = 'success')
    {
        set_transient(self::TRANSIENT_KEY, array('message' => $message, 'type' => $type), 60);
    }

    /**
     * Render and clear the queued admin notice, if one exists.
     */
    public function render()
    {
        $notice = get_transient(self::TRANSIENT_KEY);
        if (empty($notice['message'])) {
            return;
        }

        delete_transient(self::TRANSIENT_KEY);
        $class = ('error' === $notice['type']) ? 'notice notice-error' : 'notice notice-success';

        echo '<div class="' . esc_attr($class) . ' is-dismissible"><p>' . esc_html($notice['message']) . '</p></div>';
    }
}
