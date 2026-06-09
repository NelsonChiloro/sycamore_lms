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

/**
 * Resolve the logged-in employee/user id from session (with fallbacks).
 */
function current_session_user_id()
{
	$ci =& get_instance();
	$keys = array('user_id', 'Employee', 'id', 'employee_id');
	foreach ($keys as $key) {
		$val = $ci->session->userdata($key);
		if ($val !== null && $val !== '' && (int) $val > 0) {
			return (int) $val;
		}
	}

	$username = $ci->session->userdata('username');
	if ($username !== null && $username !== '') {
		$row = $ci->db->select('Employee')
			->from('user_access')
			->where('AccessCode', $username)
			->limit(1)
			->get()
			->row();
		if ($row && !empty($row->Employee) && (int) $row->Employee > 0) {
			return (int) $row->Employee;
		}
	}

	return 0;
}

function auth_logger($data){

    $ci =& get_instance();
    $ci->load->database();

    if (empty($data['Initiated_by'])) {
        $data['Initiated_by'] = current_session_user_id();
    } else {
        $data['Initiated_by'] = (int) $data['Initiated_by'];
    }

    if (empty($data['Initiated_by'])) {
        log_message('error', 'auth_logger: Initiated_by is missing — session user not resolved.');
        return false;
    }

    return $ci->db->insert('approval_edits', $data);

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

