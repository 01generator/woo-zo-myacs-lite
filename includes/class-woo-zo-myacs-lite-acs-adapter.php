<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handle the ACS web service requests used by the Lite MyACS workflow.
 */
class Woo_Zo_Myacs_Lite_Acs_Adapter
{
    protected $options;
    protected $pdf_manager;

    /**
     * Store settings and PDF services used by the ACS adapter.
     */
    public function __construct($options, $pdf_manager)
    {
        $this->options = $options;
        $this->pdf_manager = $pdf_manager;
    }

    /**
     * Build the shared ACS credential payload.
     */
    protected function get_acs_credentials()
    {
        return array(
            'Company_ID'       => $this->options->get('company_id'),
            'Company_Password' => $this->options->get('company_password'),
            'User_ID'          => $this->options->get('api_username'),
            'User_Password'    => $this->options->get('api_password'),
        );
    }

    /**
     * Ensure the required ACS credentials are present before a request.
     */
    protected function credentials_are_configured()
    {
        $credentials = $this->get_acs_credentials();

        return !empty($credentials['Company_ID'])
            && !empty($credentials['Company_Password'])
            && !empty($credentials['User_ID'])
            && !empty($credentials['User_Password'])
            && !empty($this->options->get('api_key'));
    }

    /**
     * Detect whether a WordPress HTTP error is a timeout that should be retried once.
     */
    protected function is_timeout_error($error)
    {
        if (!is_wp_error($error)) {
            return false;
        }

        $message = strtolower($error->get_error_message());

        return false !== strpos($message, 'timed out')
            || false !== strpos($message, 'timeout')
            || false !== strpos($message, 'curl error 28');
    }

    /**
     * Execute the ACS HTTP request with a longer timeout and one retry on timeout errors.
     */
    protected function perform_request($body)
    {
        $request_args = array(
            'timeout'     => 60,
            'redirection' => 3,
            'httpversion' => '1.1',
            'headers'     => array(
                'acsapikey'    => $this->options->get('api_key'),
                'content-type' => 'application/json',
            ),
            'body'        => wp_json_encode($body),
        );

        $response = wp_remote_post('https://webservices.acscourier.net/ACSRestServices/api/ACSAutoRest', $request_args);
        if ($this->is_timeout_error($response)) {
            $response = wp_remote_post('https://webservices.acscourier.net/ACSRestServices/api/ACSAutoRest', $request_args);
        }

        return $response;
    }

    /**
     * Send a JSON request to the ACS REST endpoint.
     */
    protected function request($alias, array $parameters)
    {
        if (!$this->credentials_are_configured()) {
            return array(
                'success' => false,
                'message' => __('ACS credentials are not configured yet.', 'woo-zo-myacs-lite'),
            );
        }

        $body = array(
            'ACSAlias'           => $alias,
            'ACSInputParameters' => array_merge($this->get_acs_credentials(), $parameters),
        );

        $response = $this->perform_request($body);

        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => $response->get_error_message(),
            );
        }

        $payload = json_decode(wp_remote_retrieve_body($response));
        if (!$payload) {
            return array(
                'success' => false,
                'message' => __('Invalid response received from ACS.', 'woo-zo-myacs-lite'),
            );
        }

        $message = $this->extract_error_message($payload);
        if ($message) {
            return array(
                'success' => false,
                'message' => $message,
                'data'    => $payload,
            );
        }

        return array(
            'success' => true,
            'data'    => $payload,
        );
    }

    /**
     * Extract an ACS error message from the response payload when available.
     */
    protected function extract_error_message($payload)
    {
        if (!empty($payload->ACSExecutionErrorMessage)) {
            return (string) $payload->ACSExecutionErrorMessage;
        }

        if (!empty($payload->ACSOutputResponce->ACSValueOutput[0]->Error_Message)) {
            return (string) $payload->ACSOutputResponce->ACSValueOutput[0]->Error_Message;
        }

        return '';
    }

    /**
     * Read the ACS object output as a normalized array.
     */
    protected function get_object_output($payload)
    {
        if (empty($payload->ACSOutputResponce->ACSValueOutput[0]->ACSObjectOutput)) {
            return array();
        }

        $output = $payload->ACSOutputResponce->ACSValueOutput[0]->ACSObjectOutput;

        if (is_array($output)) {
            return $output;
        }

        return array($output);
    }

    /**
     * Normalize the ACS PDF payload into a binary PDF string.
     */
    protected function decode_pdf_payload($pdf_data)
    {
        if (!is_string($pdf_data) || '' === $pdf_data) {
            return false;
        }

        $pdf_data = trim($pdf_data);
        if (0 === strpos($pdf_data, '%PDF')) {
            return $pdf_data;
        }

        $normalized = preg_replace('/\s+/', '', $pdf_data);
        $decoded = base64_decode($normalized, true);
        if (false !== $decoded && 0 === strpos($decoded, '%PDF')) {
            return $decoded;
        }

        $decoded = base64_decode($normalized, false);
        if (false !== $decoded && 0 === strpos($decoded, '%PDF')) {
            return $decoded;
        }

        return false;
    }

    /**
     * Return the shared ACS pickup-list request parameters.
     */
    protected function get_close_day_parameters()
    {
        return array(
            'Language'            => 'GR',
            'Pickup_Date'         => gmdate('Y-m-d'),
            'MyData'              => null,
            'Vouchers_To_Include' => null,
            'Vouchers_To_Exclude' => null,
        );
    }

    /**
     * Extract unprinted voucher numbers from a failed ACS pickup-list response.
     */
    protected function extract_unprinted_vouchers($payload)
    {
        if (empty($payload->ACSOutputResponce->ACSTableOutput)) {
            return array();
        }

        $table_outputs = $payload->ACSOutputResponce->ACSTableOutput;
        if (!is_array($table_outputs)) {
            $table_outputs = array($table_outputs);
        }

        $vouchers = array();

        foreach ($table_outputs as $table_output) {
            if (empty($table_output->Table_Data)) {
                continue;
            }

            $rows = $table_output->Table_Data;
            if (!is_array($rows)) {
                $rows = array($rows);
            }

            foreach ($rows as $row) {
                if (!is_object($row)) {
                    continue;
                }

                foreach (array('Unprinted_Vouchers', 'Voucher_No', 'VoucherNo') as $field) {
                    if (empty($row->{$field})) {
                        continue;
                    }

                    $parts = preg_split('/\s*,\s*/', (string) $row->{$field});
                    foreach ((array) $parts as $part) {
                        $part = sanitize_text_field(trim((string) $part));
                        if ('' !== $part) {
                            $vouchers[] = $part;
                        }
                    }
                }
            }
        }

        return array_values(array_unique($vouchers));
    }

    /**
     * Cancel unprinted ACS vouchers, clear saved references, and reset order meta.
     */
    protected function clear_unprinted_vouchers(array $vouchers)
    {
        $vouchers = array_values(array_unique(array_filter(array_map('sanitize_text_field', $vouchers))));
        if (empty($vouchers)) {
            return array(
                'success' => true,
            );
        }

        $repository = new Woo_Zo_Myacs_Lite_Repository();
        $order_meta = new Woo_Zo_Myacs_Lite_Order_Meta();

        foreach ($vouchers as $voucher) {
            $deleted = $this->request(
                'ACS_Delete_Voucher',
                array(
                    'Language'   => null,
                    'Voucher_No' => $voucher,
                )
            );

            if (empty($deleted['success'])) {
                return $deleted;
            }

            $order_ids = $repository->clear_reference_by_value($voucher);
            foreach ($order_ids as $order_id) {
                $order_meta->clear_tracking_code($order_id);
                $order_meta->set_tracking_summary($order_id, '', '');
            }
        }

        return array(
            'success' => true,
        );
    }

    /**
     * Extract the ACS pickup-list PDF from the response and store it locally.
     */
    protected function save_close_day_pdf($payload)
    {
        $objects = $this->get_object_output($payload);
        if (empty($objects) && !empty($payload->ACSOutputResponce->ACSValueOutput[0]->PDFData)) {
            $objects = array((object) array(
                'PDFData' => $payload->ACSOutputResponce->ACSValueOutput[0]->PDFData,
            ));
        }

        foreach ($objects as $object) {
            $pdf_data = '';

            if (is_object($object) && !empty($object->PDFData)) {
                $pdf_data = (string) $object->PDFData;
            } elseif (is_string($object)) {
                $pdf_data = $object;
            }

            if ('' === $pdf_data) {
                continue;
            }

            $binary = $this->decode_pdf_payload($pdf_data);
            if (false === $binary) {
                continue;
            }

            $saved = $this->pdf_manager->save_pdf('myacs-lite-close-day-' . gmdate('Y-m-d-H-i-s') . '.pdf', $binary);
            if ($saved) {
                $saved['success'] = true;

                return $saved;
            }
        }

        $pickup_list_no = $this->extract_pickup_list_number($payload);
        if ('' !== $pickup_list_no) {
            return array(
                'success'            => true,
                'path'               => 'pickup-list-' . sanitize_file_name($pickup_list_no) . '.pdf',
                'url'                => $this->build_pickup_list_url($pickup_list_no),
                'manifest_reference' => $pickup_list_no,
            );
        }

        return array(
            'success' => false,
            'message' => __('ACS did not return a printable pickup list.', 'woo-zo-myacs-lite'),
        );
    }

    /**
     * Extract the ACS pickup-list number from the pickup-list response.
     */
    protected function extract_pickup_list_number($payload)
    {
        if (!empty($payload->ACSOutputResponce->ACSValueOutput[0]->PickupList_No)) {
            return sanitize_text_field((string) $payload->ACSOutputResponce->ACSValueOutput[0]->PickupList_No);
        }

        $objects = $this->get_object_output($payload);
        foreach ($objects as $object) {
            if (!is_object($object)) {
                continue;
            }

            foreach (array('PickupList_No', 'PickupListNo', 'MassNumber') as $field) {
                if (!empty($object->{$field})) {
                    return sanitize_text_field((string) $object->{$field});
                }
            }
        }

        return '';
    }

    /**
     * Build the legacy ACS pickup-list URL for a returned list number.
     */
    protected function build_pickup_list_url($pickup_list_no)
    {
        $query = array(
            'MainID'    => $this->options->get('company_id'),
            'MainPass'  => $this->options->get('company_password'),
            'UserID'    => $this->options->get('api_username'),
            'UserPass'  => $this->options->get('api_password'),
            'MassNumber'=> $pickup_list_no,
            'DateParal' => gmdate('Y-m-d'),
        );

        return 'https://acs-eud2.acscourier.net/Eshops/getlist.aspx?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
    }

    /**
     * Try to locate the returned voucher number from the ACS response.
     */
    protected function extract_reference($payload)
    {
        if (!empty($payload->ACSOutputResponce->ACSValueOutput[0]->Voucher_No)) {
            return sanitize_text_field((string) $payload->ACSOutputResponce->ACSValueOutput[0]->Voucher_No);
        }

        $objects = $this->get_object_output($payload);
        if (empty($objects)) {
            return '';
        }

        foreach ($objects as $object) {
            if (!is_object($object)) {
                continue;
            }

            foreach (array('Voucher_No', 'VoucherNo', 'Voucber_No', 'Shipment_Number', 'ShipmentNumber') as $field) {
                if (!empty($object->{$field})) {
                    return sanitize_text_field((string) $object->{$field});
                }
            }
        }

        return '';
    }

    /**
     * Build the ACS delivery product flags expected by the ACS voucher API.
     */
    protected function build_delivery_products($country, array $row)
    {
        $products = array();

        if ('CY' === strtoupper((string) $country)) {
            $products[] = 'P2P';
        }

        if (!empty($row['cod'])) {
            $products[] = 'COD';
        }

        if (!empty($row['rec'])) {
            $products[] = 'REC';
        }

        if (!empty($row['sat'])) {
            $products[] = 'SAT';
        }

        if (!empty($row['return_voucher'])) {
            $products[] = 'RDO';
        }

        return implode(',', array_unique($products));
    }

    /**
     * Split a free-form address into street name and number.
     */
    protected function split_address($address)
    {
        $address = trim((string) $address);
        if ($address === '') {
            return array('', '');
        }

        preg_match('/^(.*?)(?:\s+(\d+[A-Za-z0-9\/-]*))?$/u', $address, $matches);

        return array(
            trim($matches[1] ?? $address),
            trim($matches[2] ?? ''),
        );
    }

    /**
     * Normalize the shipment note before sending it to ACS.
     */
    protected function normalize_delivery_notes($comment)
    {
        $comment = wp_strip_all_tags((string) $comment);
        $comment = preg_replace('/[\r\n\t]+/', ' ', $comment);
        $comment = preg_replace('/\s{2,}/', ' ', $comment);
        $comment = trim((string) $comment);

        if (function_exists('mb_substr')) {
            $comment = mb_substr($comment, 0, 120);
        } else {
            $comment = substr($comment, 0, 120);
        }

        return $comment;
    }

    /**
     * Calculate the order weight when no manual override exists.
     */
    protected function calculate_order_weight($order)
    {
        $total_weight = 0.0;

        foreach ($order->get_items() as $item) {
            if (!method_exists($item, 'get_product')) {
                continue;
            }

            $product = $item->get_product();
            if (!$product) {
                continue;
            }

            $item_weight = (float) $product->get_weight();
            $total_weight += max(0.0, $item_weight) * max(1, (int) $item->get_quantity());
        }

        return $total_weight > 0 ? $total_weight : 0.5;
    }

    /**
     * Build the ACS voucher payload from a WooCommerce order.
     */
    protected function build_create_payload($order_id)
    {
        $order = wc_get_order($order_id);
        if (!$order) {
            return array(
                'success' => false,
                'message' => __('The order could not be loaded.', 'woo-zo-myacs-lite'),
            );
        }

        $repository = new Woo_Zo_Myacs_Lite_Repository();
        $row = $repository->ensure_order_row($order_id);

        $first_name = $order->get_shipping_first_name() ? $order->get_shipping_first_name() : $order->get_billing_first_name();
        $last_name = $order->get_shipping_last_name() ? $order->get_shipping_last_name() : $order->get_billing_last_name();
        $address_1 = $order->get_shipping_address_1() ? $order->get_shipping_address_1() : $order->get_billing_address_1();
        $address_2 = $order->get_shipping_address_2() ? $order->get_shipping_address_2() : $order->get_billing_address_2();
        $city = $order->get_shipping_city() ? $order->get_shipping_city() : $order->get_billing_city();
        $postcode = $order->get_shipping_postcode() ? $order->get_shipping_postcode() : $order->get_billing_postcode();
        $country = $order->get_shipping_country() ? $order->get_shipping_country() : $order->get_billing_country();
        $phone = $order->get_billing_phone();
        $email = $order->get_billing_email();

        list($recipient_address, $recipient_number) = $this->split_address(trim($address_1 . ' ' . $address_2));

        $weight = !empty($row['weight']) ? max(0.1, (float) $row['weight']) : $this->calculate_order_weight($order);
        $cod_amount = !empty($row['cod']) ? (float) $order->get_total() : 0;
        $comment = !empty($row['comment']) ? $row['comment'] : $order->get_customer_note();
        $comment = $this->normalize_delivery_notes($comment);
        $reference_key_2 = $order->get_customer_id() ? (string) $order->get_customer_id() : null;
        $delivery_products = $this->build_delivery_products($country, $row);

        return array(
            'success' => true,
            'data'    => array(
                'Pickup_Date'                  => gmdate('Y-m-d'),
                'Sender'                       => $this->options->get('company_name', get_bloginfo('name')),
                'Recipient_Name'               => trim($last_name . ' ' . $first_name),
                'Recipient_Address'            => $recipient_address,
                'Recipient_Address_Number'     => $recipient_number,
                'Recipient_Zipcode'            => $postcode,
                'Recipient_Region'             => $city,
                'Recipient_Phone'              => $phone,
                'Recipient_Cell_Phone'         => $phone,
                'Recipient_Floor'              => null,
                'Recipient_Company_Name'       => null,
                'Recipient_Country'            => $country,
                'Acs_Station_Destination'      => null,
                'Acs_Station_Branch_Destination' => '1',
                'Billing_Code'                 => $this->options->get('billing_code'),
                'Charge_Type'                  => '2',
                'Cost_Center_Code'             => null,
                'Item_Quantity'                => max(1, (int) $row['parcels']),
                'Weight'                       => $weight,
                'Dimension_X_In_Cm'            => null,
                'Dimension_Y_in_Cm'            => null,
                'Dimension_Z_in_Cm'            => null,
                'Cod_Ammount'                  => $cod_amount,
                'Cod_Payment_Way'              => 0,
                'Acs_Delivery_Products'        => $delivery_products,
                'Insurance_Ammount'            => null,
                'Delivery_Notes'               => $comment,
                'Appointment_Until_Time'       => null,
                'Recipient_Email'              => $email ? $email : null,
                'Reference_Key1'               => (string) $order_id,
                'Reference_Key2'               => $reference_key_2,
                'With_Return_Voucher'          => !empty($row['return_voucher']) ? 1 : 0,
                'Language'                     => null,
                'Content_Type_ID'              => null,
            ),
        );
    }

    /**
     * Create a real ACS shipment for the given order.
     */
    public function create_shipment($order_id)
    {
        $payload = $this->build_create_payload($order_id);
        if (empty($payload['success'])) {
            return $payload;
        }

        $response = $this->request('ACS_Create_Voucher', $payload['data']);
        if (empty($response['success'])) {
            return $response;
        }

        $reference = $this->extract_reference($response['data']);
        if ($reference === '') {
            return array(
                'success' => false,
                'message' => __('ACS did not return a voucher number.', 'woo-zo-myacs-lite'),
            );
        }

        return array(
            'success'   => true,
            'reference' => $reference,
            'vouchers'  => array(),
            'message'   => __('Voucher created successfully.', 'woo-zo-myacs-lite'),
        );
    }

    /**
     * Generate the printable ACS voucher PDF for the provided reference.
     */
    public function print_voucher($reference)
    {
        if (empty($reference)) {
            return array(
                'success' => false,
                'message' => __('There is no reference to print.', 'woo-zo-myacs-lite'),
            );
        }

        $response = $this->request(
            'ACS_Print_Voucher',
            array(
                'Language'       => null,
                'Voucher_No'     => (string) $reference,
                'Print_Type'     => 'a4' === $this->options->get('print_template', 'thermal') ? '2' : '1',
                'Start_Position' => '1',
            )
        );

        if (empty($response['success'])) {
            return $response;
        }

        $objects = $this->get_object_output($response['data']);
        if (empty($objects) && !empty($response['data']->ACSOutputResponce->ACSValueOutput[0]->PDFData)) {
            $objects = array((object) array(
                'PDFData' => $response['data']->ACSOutputResponce->ACSValueOutput[0]->PDFData,
            ));
        }

        if (empty($objects)) {
            return array(
                'success' => false,
                'message' => __('ACS did not return printable PDF data.', 'woo-zo-myacs-lite'),
            );
        }

        foreach ($objects as $object) {
            $pdf_data = '';

            if (is_object($object)) {
                if (!empty($object->PDFData)) {
                    $pdf_data = (string) $object->PDFData;
                }
            } elseif (is_string($object)) {
                $pdf_data = $object;
            }

            if ('' === $pdf_data) {
                continue;
            }

            $binary = $this->decode_pdf_payload($pdf_data);
            if (false === $binary) {
                continue;
            }

            $saved = $this->pdf_manager->save_pdf('myacs-lite-' . sanitize_file_name((string) $reference) . '.pdf', $binary);
            if (!$saved) {
                return array(
                    'success' => false,
                    'message' => __('The voucher PDF file could not be written.', 'woo-zo-myacs-lite'),
                );
            }

            $saved['success'] = true;

            return $saved;
        }

        return array(
            'success' => false,
            'message' => __('ACS returned invalid PDF data.', 'woo-zo-myacs-lite'),
        );
    }

    /**
     * Cancel the ACS shipment for the provided voucher number.
     */
    public function cancel_shipment($reference)
    {
        if (empty($reference)) {
            return array(
                'success' => false,
                'message' => __('There is no reference to cancel.', 'woo-zo-myacs-lite'),
            );
        }

        return $this->request(
            'ACS_Delete_Voucher',
            array(
                'Language'   => null,
                'Voucher_No' => (string) $reference,
            )
        );
    }

    /**
     * Return a manual tracking notice until the dedicated ACS tracking API is wired.
     */
    public function track_shipment($reference)
    {
        if (empty($reference)) {
            return array(
                'success' => false,
                'message' => __('There is no reference to track.', 'woo-zo-myacs-lite'),
            );
        }

        $response = $this->request(
            'ACS_Trackingsummary',
            array(
                'Language'   => null,
                'Voucher_No' => (string) $reference,
            )
        );

        if (empty($response['success'])) {
            return $response;
        }

        if (!empty($response['data']->ACSOutputResponce->ACSTableOutput->Table_Data[0])) {
            $row = $response['data']->ACSOutputResponce->ACSTableOutput->Table_Data[0];

            $delivered = !empty($row->delivery_flag);
            $status = $delivered
                ? __('Delivered', 'woo-zo-myacs-lite')
                : __('NOT Delivered.', 'woo-zo-myacs-lite');
            $history = !empty($row->delivery_info)
                ? sanitize_text_field((string) $row->delivery_info)
                : __('No tracking details were returned by ACS.', 'woo-zo-myacs-lite');

            return array(
                'success' => true,
                'status'  => $status,
                'history' => $history,
            );
        }

        if (!empty($response['data']->ACSOutputResponce->ACSValueOutput[0]->Error_Message)) {
            return array(
                'success' => false,
                'message' => sanitize_text_field((string) $response['data']->ACSOutputResponce->ACSValueOutput[0]->Error_Message),
            );
        }

        return array(
            'success' => false,
            'message' => __('ACS did not return tracking data for this voucher.', 'woo-zo-myacs-lite'),
        );
    }

    /**
     * Issue the ACS close-day pickup list PDF.
     */
    public function close_day()
    {
        $parameters = $this->get_close_day_parameters();
        $response = $this->request('ACS_Issue_Pickup_List', $parameters);

        if (empty($response['success'])) {
            $unprinted_vouchers = !empty($response['data'])
                ? $this->extract_unprinted_vouchers($response['data'])
                : array();

            if (empty($unprinted_vouchers)) {
                return $response;
            }

            $cleanup = $this->clear_unprinted_vouchers($unprinted_vouchers);
            if (empty($cleanup['success'])) {
                return $cleanup;
            }

            $response = $this->request('ACS_Issue_Pickup_List', $parameters);
            if (empty($response['success'])) {
                return $response;
            }
        }

        return $this->save_close_day_pdf($response['data']);
    }
}
