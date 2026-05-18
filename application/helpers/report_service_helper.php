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