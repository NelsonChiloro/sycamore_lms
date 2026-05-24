<?php defined('BASEPATH') OR exit('No direct script access allowed');

if (!function_exists('report_service_base_url')) {
    function report_service_base_url()
    {
        $ci =& get_instance();
        $configured_url = $ci->config->item('report_service_base_url');

        if (!$configured_url) {
            $configured_url = 'http://localhost:4500';
        }

        return rtrim($configured_url, '/');
    }
}

if (!function_exists('report_service_url')) {
    function report_service_url($path = '')
    {
        $normalized_path = ltrim((string) $path, '/');

        if ($normalized_path === '') {
            return report_service_base_url();
        }

        return report_service_base_url() . '/' . $normalized_path;
    }
}

if (!function_exists('report_preview_url')) {
    /**
     * Resolve a report download_link to a browser preview URL (HTML in bulk_report/reports).
     */
    function report_preview_url($download_link)
    {
        if (empty($download_link)) {
            return null;
        }

        $previewLink = str_replace('\\', '/', (string) $download_link);

        if (preg_match('#^https?://#i', $previewLink)) {
            return $previewLink;
        }

        if (preg_match('#^[A-Za-z]:/#', $previewLink) === 1 || strpos($previewLink, '/') === 0) {
            $previewLink = 'bulk_report/reports/' . basename($previewLink);
        } else {
            $previewLink = preg_replace('#^/?bulk_report/#', 'bulk_report/', $previewLink);
            if (strpos($previewLink, 'bulk_report/') !== 0) {
                $previewLink = 'bulk_report/' . ltrim($previewLink, '/');
            }
        }

        return base_url($previewLink);
    }
}