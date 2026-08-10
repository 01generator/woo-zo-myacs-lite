<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Manage generated PDFs stored in the WordPress uploads directory.
 */
class Woo_Zo_Myacs_Lite_Pdf_Manager
{
    /**
     * Resolve the plugin slug used for the uploads subdirectory.
     */
    protected function get_storage_slug()
    {
        if (defined('WP_ZO_COURIERFRAME_PRO_SLUG')) {
            return WP_ZO_COURIERFRAME_PRO_SLUG;
        }

        if (defined('Woo_Zo_Myacs_Lite_SLUG')) {
            return Woo_Zo_Myacs_Lite_SLUG;
        }

        return 'woo-zo-myacs-lite';
    }

    /**
     * Return the absolute storage directory for generated PDFs.
     */
    public function get_storage_dir()
    {
        $upload_dir = wp_upload_dir();

        return trailingslashit($upload_dir['basedir']) . $this->get_storage_slug() . '/';
    }

    /**
     * Return the public storage URL for generated PDFs.
     */
    public function get_storage_url()
    {
        $upload_dir = wp_upload_dir();

        return trailingslashit($upload_dir['baseurl']) . $this->get_storage_slug() . '/';
    }

    /**
     * Ensure the storage directory exists and contains a safety index file.
     */
    public function ensure_storage()
    {
        $dir = $this->get_storage_dir();
        if (!wp_mkdir_p($dir)) {
            return false;
        }

        $index = $dir . 'index.php';
        if (!file_exists($index)) {
            file_put_contents($index, "<?php\n// Silence is golden.\n");
        }

        return is_writable($dir);
    }

    /**
     * Save a PDF binary to the uploads directory and return its path and URL.
     */
    public function save_pdf($filename, $binary)
    {
        if (!$this->ensure_storage()) {
            return false;
        }

        $path = $this->get_storage_dir() . sanitize_file_name($filename);
        if (false === file_put_contents($path, $binary)) {
            return false;
        }

        return array(
            'path' => $path,
            'url'  => $this->get_storage_url() . basename($path),
        );
    }

    /**
     * Delete all stored PDF files and return the number of deleted files.
     */
    public function clear_all()
    {
        if (!$this->ensure_storage()) {
            return 0;
        }

        $deleted = 0;
        foreach (glob($this->get_storage_dir() . '*.pdf') as $file) {
            if (@unlink($file)) {
                $deleted++;
            }
        }

        return $deleted;
    }

    /**
     * Count the generated PDF files currently stored by the plugin.
     */
    public function count_files()
    {
        if (!$this->ensure_storage()) {
            return 0;
        }

        return count(glob($this->get_storage_dir() . '*.pdf'));
    }

    /**
     * Return a minimal placeholder PDF binary.
     */
    public function get_placeholder_pdf_binary($reference)
    {
        $reference = preg_replace('/[^A-Za-z0-9_-]/', '', (string) $reference);

        return "%PDF-1.3\n1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 300 200] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>\nendobj\n4 0 obj\n<< /Length 62 >>\nstream\nBT /F1 12 Tf 40 140 Td (" . $reference . ") Tj ET\nendstream\nendobj\n5 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\nxref\n0 6\n0000000000 65535 f \n0000000010 00000 n \n0000000063 00000 n \n0000000122 00000 n \n0000000249 00000 n \n0000000361 00000 n \ntrailer\n<< /Root 1 0 R /Size 6 >>\nstartxref\n431\n%%EOF";
    }
}
