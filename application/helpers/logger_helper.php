<?php
function log_activity($data){

	$ci =& get_instance();
	$ci->load->database();
	$ci->load->model('Activity_logger_model');

	$sql=$ci->db->insert('activity_logger',$data);


}
function log_crud($data){

	$ci =& get_instance();
	$ci->load->database();
	$ci->load->model('Crud_logger_model');
	$sql=$ci->db->insert('crud_logger',$data);

}
function auth_logger($data){

    $ci =& get_instance();
    $ci->load->database();

    $sql=$ci->db->insert('approval_edits',$data);

}

/**
 * Format a value for approval preview tables (scalars, arrays, objects).
 */
function approval_preview_cell($value)
{
    if ($value === null || $value === '') {
        return '&mdash;';
    }

    if (is_scalar($value)) {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }

    $json = json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        return htmlspecialchars(print_r($value, true), ENT_QUOTES, 'UTF-8');
    }

    return '<pre class="mb-0 small" style="white-space:pre-wrap;max-height:220px;overflow:auto;">'
        . htmlspecialchars($json, ENT_QUOTES, 'UTF-8')
        . '</pre>';
}

/**
 * Detect group batch edit payload from decoded approval JSON.
 */
function approval_is_group_batch_payload($info)
{
    return is_object($info)
        && !empty($info->batch)
        && isset($info->members)
        && is_array($info->members);
}

