<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handle admin-side AJAX requests for the Lite shipment workflow.
 */
class Woo_Zo_Myacs_Lite_Ajax
{
    protected $repository;
    protected $order_meta;
    protected $pdf_manager;
    protected $adapter;
    protected $options;

    /**
     * Store the services used by the AJAX actions.
     */
    public function __construct($repository, $order_meta, $pdf_manager, $adapter, $options)
    {
        $this->repository = $repository;
        $this->order_meta = $order_meta;
        $this->pdf_manager = $pdf_manager;
        $this->adapter = $adapter;
        $this->options = $options;
    }

    /**
     * Verify current-user permissions and validate the shared AJAX nonce.
     */
    protected function check_request()
    {
        if (!current_user_can('edit_shop_orders') && !current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'woo-zo-myacs-lite')), 403);
        }

        check_ajax_referer('woo_zo_myacs_lite_nonce', 'nonce');
    }

    /**
     * Persist inline order option changes from the metabox.
     */
    public function save_options()
    {
        $this->check_request();
        $order_id = absint($_POST['order_id'] ?? 0);
        $field = sanitize_key($_POST['field'] ?? '');
        $value = wp_unslash($_POST['value'] ?? '');

        $this->repository->ensure_order_row($order_id);
        $saved = $this->repository->save_order_options($order_id, array($field => $value));

        if (!$saved) {
            wp_send_json_error(array('message' => __('Nothing was saved.', 'woo-zo-myacs-lite')));
        }

        $labels = array(
            'cod' => __('COD option saved.', 'woo-zo-myacs-lite'),
            'parcels' => __('Parcels saved.', 'woo-zo-myacs-lite'),
            'weight' => __('Weight saved.', 'woo-zo-myacs-lite'),
            'comment' => __('Comment saved.', 'woo-zo-myacs-lite'),
            'sat' => __('Saturday delivery option saved.', 'woo-zo-myacs-lite'),
            'rec' => __('Reception delivery option saved.', 'woo-zo-myacs-lite'),
            'return_voucher' => __('Return voucher option saved.', 'woo-zo-myacs-lite'),
        );

        wp_send_json_success(array('message' => isset($labels[$field]) ? $labels[$field] : __('Saved.', 'woo-zo-myacs-lite')));
    }

    /**
     * Create a shipment if needed, then generate and return the voucher PDF.
     */
    public function create_print()
    {
        $this->check_request();
        $order_id = absint($_POST['order_id'] ?? 0);
        $row = $this->repository->ensure_order_row($order_id);
        $reference = $row['reference'];
        $is_reprint = !empty($reference);

        if (empty($reference)) {
            $result = $this->adapter->create_shipment($order_id);
            if (empty($result['success'])) {
                wp_send_json_error(array('message' => $result['message']));
            }

            $reference = $result['reference'];
            $vouchers = !empty($result['vouchers']) ? implode(' | ', $result['vouchers']) : '';
            $this->repository->save_reference($order_id, $reference, $vouchers);
            $this->order_meta->set_tracking_code($order_id, $reference);
        }

        $printed = $this->adapter->print_voucher($reference);
        if (empty($printed['success'])) {
            wp_send_json_error(array('message' => !empty($printed['message']) ? $printed['message'] : __('The voucher PDF could not be generated.', 'woo-zo-myacs-lite')));
        }

        wp_send_json_success(array(
            'message'   => $is_reprint ? __('Voucher reprinted.', 'woo-zo-myacs-lite') : __('Voucher created.', 'woo-zo-myacs-lite'),
            'reference' => $reference,
            'pdf_url'   => $printed['url'],
        ));
    }

    /**
     * Cancel the stored shipment reference for the selected order.
     */
    public function cancel()
    {
        $this->check_request();
        $order_id = absint($_POST['order_id'] ?? 0);
        $row = $this->repository->ensure_order_row($order_id);
        $result = $this->adapter->cancel_shipment($row['reference']);

        if (empty($result['success'])) {
            wp_send_json_error(array('message' => $result['message']));
        }

        $this->repository->clear_reference($order_id);
        $this->order_meta->clear_tracking_code($order_id);
        wp_send_json_success(array('message' => __('Voucher canceled.', 'woo-zo-myacs-lite')));
    }

    /**
     * Request the latest tracking message and save it for the order.
     */
    public function track()
    {
        $this->check_request();
        $order_id = absint($_POST['order_id'] ?? 0);
        $row = $this->repository->ensure_order_row($order_id);
        $result = $this->adapter->track_shipment($row['reference']);

        if (empty($result['success'])) {
            wp_send_json_error(array('message' => $result['message']));
        }

        $this->repository->save_tracking($order_id, $result['status'], $result['history']);
        $this->order_meta->set_tracking_summary($order_id, $result['status'], $result['history']);
        wp_send_json_success(array(
            'message' => trim($result['status'] . ' - ' . $result['history']),
            'status'  => $result['status'],
            'history' => $result['history'],
        ));
    }

    /**
     * Execute the close-day routine and store the execution timestamp.
     */
    public function close_day()
    {
        $this->check_request();
        $result = $this->adapter->close_day();

        if (empty($result['success'])) {
            wp_send_json_error(array('message' => $result['message']));
        }

        $this->options->set('last_close_day', current_time('mysql'));
        wp_send_json_success(array(
            'message'      => !empty($result['message']) ? $result['message'] : __('Close day completed.', 'woo-zo-myacs-lite'),
            'document_url' => $result['url'],
        ));
    }

    /**
     * Delete all generated PDFs stored in the Lite uploads directory.
     */
    public function clear_pdfs()
    {
        $this->check_request();
        $deleted = $this->pdf_manager->clear_all();
        $this->options->set('last_pdf_clear', current_time('mysql'));

        wp_send_json_success(array(
            'message'       => __('Generated PDFs were deleted.', 'woo-zo-myacs-lite'),
            'deleted_count' => $deleted,
        ));
    }
}
