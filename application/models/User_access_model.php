<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class User_access_model extends CI_Model
{

    public $table = 'user_access';
    public $id = 'Employee';
    public $order = 'DESC';

    function __construct()
    {
        parent::__construct();
    }
    public function logout_all(){
    	$this->db->where('Employee !=',$this->session->userdata('user_id'))
			->update($this->table,array('is_logged_in'=>'No'));
	}
	public function logout_a(){
    	$this->db->update($this->table,array('is_logged_in'=>'No'));
	}
	function login_user($username,$password){
		$this->db->select("*");
		$this->db->from($this->table);
		$this->db->join('employees','employees.id=user_access.Employee');
		$this->db->join('roles','roles.id=employees.Role');
		$this->db->where('AccessCode',$username);
		$this->db->where('Password',$password);
		$this->db->where('status','AUTHORIZED');
		return $this->db->get()->row_array();
	}
	function check_user($user){
		$this->db->select("*");
		$this->db->from($this->table);
		$this->db->join('employees','employees.id=user_access.Employee');
		$this->db->join('roles','roles.id=employees.Role');
		$this->db->where('Employee',$user);
		return $this->db->get()->row();
	}
    // get all
    function get_all()
    {
        $this->db->order_by($this->id, $this->order);
		$this->db->join('employees','employees.id=user_access.Employee');
        return $this->db->get($this->table)->result();
    }

    // get data by id
    function get_by_id($id)
    {
        $this->db->where($this->id, $id);
        return $this->db->get($this->table)->row();
    }

    // get total rows
    function count_logged() {
    $this->db->select("*");
	$this->db->from($this->table)
		->where('is_logged_in','Yes');
	return $this->db->count_all_results();
    } function total_rows($q = NULL) {
        $this->db->like('AccessCode', $q);
	$this->db->or_like('Password', $q);
	$this->db->or_like('Employee', $q);
	$this->db->or_like('Status', $q);
	$this->db->from($this->table);
	$this->db->join('employees','employees.id=user_access.Employee');
        return $this->db->count_all_results();
    }

    // get data with limit and search
    function get_limit_data($limit, $start = 0, $q = NULL) {
        $this->db->order_by($this->id, $this->order);
        $this->db->like('AccessCode', $q);
	$this->db->or_like('Password', $q);
	$this->db->or_like('Employee', $q);
	$this->db->or_like('Status', $q);
		$this->db->from($this->table);
		$this->db->join('employees','employees.id=user_access.Employee');
	$this->db->limit($limit, $start);
        return $this->db->get()->result();
    }

    // insert data
    function insert($data)
    {
        $this->db->insert($this->table, $data);
    }

    // update data
    function update($id, $data)
    {
        $this->db->where($this->id, $id);
        $this->db->update($this->table, $data);
    }
 function update_auth($id, $data)
    {
        $this->db->where('Employee', $id);
        $this->db->update($this->table, $data);
    }

    // delete data
    function delete($id)
    {
        $this->db->where($this->id, $id);
        $this->db->delete($this->table);
    }

}

