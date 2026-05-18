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

