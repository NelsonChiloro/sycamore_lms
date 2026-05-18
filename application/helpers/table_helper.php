<?php
function check_exist_in_table($table,$field,$key){
	$ci =& get_instance();
	$ci->load->database();
//	$ci->load->model('Dbc_users_model');

	$sql="SELECT * FROM $table WHERE $field='$key'";
	return $query = $ci->db->query($sql)->row();

}
function delete_record($table,$field,$key){
	$ci =& get_instance();
	$ci->load->database();
//	$ci->load->model('Dbc_users_model');

	$sql="DELETE FROM $table WHERE $field='$key'";
	return $query = $ci->db->query($sql);

}
function check_day_start(){
	$ci =& get_instance();
	$ci->load->database();
//	$ci->load->model('Dbc_users_model');

	$sql="SELECT * FROM day_end_start WHERE id ='1'";
	return $query = $ci->db->query($sql)->row();

}
function delete_wrong_loans(){
	$ci =& get_instance();
	$ci->load->database();
//	$ci->load->model('Dbc_users_model');

	$sql="SELECT * FROM `loan` JOIN all_indi ON loan.loan_customer = all_indi.customer_id WHERE all_indi.rerun = 'Yes' ";
	return $query = $ci->db->query($sql)->result();

}
function check_logged_in($id){
	$ci =& get_instance();
	$ci->load->database();
//	$ci->load->model('Dbc_users_model');

	$sql="SELECT * FROM user_access WHERE Employee ='$id'";
	return $query = $ci->db->query($sql)->row();

}
function GeraHash($qtd){
//Under the string $Caracteres you write all the characters you want to be used to randomly generate the code.
	$Caracteres = 'ABCDEFGHIJKLMOPQRSTUVXWYZ0123456789';
	$QuantidadeCaracteres = strlen($Caracteres);
	$QuantidadeCaracteres--;

	$Hash=NULL;
	for($x=1;$x<=$qtd;$x++){
		$Posicao = rand(0,$QuantidadeCaracteres);
		$Hash .= substr($Caracteres,$Posicao,1);
	}

	return $Hash;
}
function get_user_details($data){
	$ci =& get_instance();
	$ci->load->database();
	$ci->load->model('User_access_model');

	$sql=$ci->User_access_model->check_user($data);
	return $sql;
}
function check_pass($pass,$key){
	$ci =& get_instance();
	$ci->load->database();
//	$ci->load->model('Dbc_users_model');

	$sql="SELECT * FROM user WHERE id='$key' AND password ='$pass'";
	return $query = $ci->db->query($sql)->row();

}
function get_all_table_with_key($table,$field,$key){
	$ci =& get_instance();
	$ci->load->database();
//	$ci->load->model('Dbc_users_model');

	$sql="SELECT * FROM $table WHERE $field='$key'";
	return $query = $ci->db->query($sql)->result();
}
function get_all_table($table){
	$ci =& get_instance();
	$ci->load->database();
//	$ci->load->model('Dbc_users_model');

	$sql="SELECT * FROM $table ";
	return $query = $ci->db->query($sql)->result();
}

function check_ip($ip){
	$ci =& get_instance();
	$ci->load->database();
//	$ci->load->model('Dbc_users_model');

	$sql="SELECT * FROM site_visitors WHERE ip='$ip' AND date(date) = CURDATE() ";
	return $query = $ci->db->query($sql)->row();

}

function insert_visitor($data){

	$ci =& get_instance();
	$ci->load->database();
	$ci->load->model('Site_visitors_model');

	$sql=$ci->db->insert('site_visitors',$data);


}
