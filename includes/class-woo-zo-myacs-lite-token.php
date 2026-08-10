<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Provide token access and validation helpers for signed actions.
 */
class Woo_Zo_Myacs_Lite_Token
{
    protected $options;

    /**
     * Store the options service used to fetch the saved token.
     */
    public function __construct($options)
    {
        $this->options = $options;
    }

    /**
     * Return the saved plugin token.
     */
    public function get_token()
    {
        return $this->options->get('token');
    }

    /**
     * Compare the supplied token against the stored value.
     */
    public function validate_token($token)
    {
        return hash_equals((string) $this->get_token(), (string) $token);
    }
}
