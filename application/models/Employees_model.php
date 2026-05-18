<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Employees_model extends CI_Model
{

    public $table = 'employees';
    public $id = 'id';
    public $order = 'DESC';

    function __construct()
    {
        parent::__construct();
    }

    // get all
    function get_all()
    {
        $this->db->order_by('employees.id', $this->order);
        $this->db->select("*,employees.id as empid");
        $this->db->join('roles','roles.id=employees.Role');
        return $this->db->get($this->table)->result();
    }
 function count_active($from,$to)
    {

        $this->db->select("*")->from($this->table);
		if($from !="" && $to !=""){
			$this->db->where('CreatedOn BETWEEN "'. date('Y-m-d', strtotime($from)). '" and "'. date('Y-m-d', strtotime($to)).'"');

		}
        return $this->db->count_all_results();
    }    // get all
    function get_allb()
    {
        $this->db->order_by('employees.id', $this->order);
        $this->db->select("*,employees.id as empid");
        $this->db->join('roles','roles.id=employees.Role');
        return $this->db->get($this->table)->result();
    }

    // get data by id
    function get_by_id($id)
    {
    	$this->db->select("*,employees.id as empid");
		$this->db->join('roles','roles.id=employees.Role');
        $this->db->where('employees.id', $id);
        return $this->db->get($this->table)->row();
    }
    
    // get total rows
    function total_rows($q = NULL) {
        $this->db->like('id', $q);
	$this->db->or_like('Firstname', $q);
	$this->db->or_like('Middlename', $q);
	$this->db->or_like('Lastname', $q);
	$this->db->or_like('Gender', $q);
	$this->db->or_like('DateOfBirth', $q);
	$this->db->or_like('EmailAddress', $q);
	$this->db->or_like('PhoneNumber', $q);
	$this->db->or_like('AddressLine1', $q);
	$this->db->or_like('AddressLine2', $q);
	$this->db->or_like('Province', $q);
	$this->db->or_like('City', $q);
	$this->db->or_like('Country', $q);
	$this->db->or_like('Role', $q);
	$this->db->or_like('BranchCode', $q);
	$this->db->or_like('Branch', $q);
	$this->db->or_like('EmploymentStatus', $q);
	$this->db->or_like('LastUpdatedOn', $q);
	$this->db->or_like('CreatedOn', $q);
	$this->db->or_like('system_date', $q);
	$this->db->from($this->table);
        return $this->db->count_all_results();
    }

    // get data with limit and search
    function get_limit_data($limit, $start = 0, $q = NULL) {
        $this->db->order_by($this->id, $this->order);
        $this->db->like('id', $q);
	$this->db->or_like('Firstname', $q);
	$this->db->or_like('Middlename', $q);
	$this->db->or_like('Lastname', $q);
	$this->db->or_like('Gender', $q);
	$this->db->or_like('DateOfBirth', $q);
	$this->db->or_like('EmailAddress', $q);
	$this->db->or_like('PhoneNumber', $q);
	$this->db->or_like('AddressLine1', $q);
	$this->db->or_like('AddressLine2', $q);
	$this->db->or_like('Province', $q);
	$this->db->or_like('City', $q);
	$this->db->or_like('Country', $q);
	$this->db->or_like('Role', $q);
	$this->db->or_like('BranchCode', $q);
	$this->db->or_like('Branch', $q);
	$this->db->or_like('EmploymentStatus', $q);
	$this->db->or_like('LastUpdatedOn', $q);
	$this->db->or_like('CreatedOn', $q);
	$this->db->or_like('system_date', $q);
	$this->db->limit($limit, $start);
        return $this->db->get($this->table)->result();
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

    // delete data
    function delete($id)
    {
        $this->db->where($this->id, $id);
        $this->db->delete($this->table);
    }

}

