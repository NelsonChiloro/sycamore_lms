<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Individual_customers_model extends CI_Model
{

    public $table = 'individual_customers';
    public $id = 'id';
    public $order = 'DESC';

    function __construct()
    {
        parent::__construct();
    }

    // get all
    function get_all()
    {
        $this->db->order_by('individual_customers.id', $this->order);
//        $this->db->select('*,employees.Firstname as efname, employees.Lastname as elname')->from($this->table);
//
//        $this->db->join('employees','employees.id=individual_customers.added_by');
        return $this->db->get($this->table)->result();
    }
    function get_all2()
    {
		$this->db->order_by('individual_customers.id', 'ASC');
		$this->db->select('*,geo_countries.name as geoname,employees.Firstname as efname, employees.Lastname as elname,individual_customers.Firstname as cfname, individual_customers.Lastname as clname,individual_customers.Middlename as cmname, individual_customers.Gender as cgender,individual_customers.DateOfBirth as cdob, individual_customers.EmailAddress as cemail,individual_customers.id as cid')->from($this->table);

		$this->db->join('employees','employees.id=individual_customers.added_by');
		$this->db->join('geo_countries','geo_countries.code=individual_customers.Country','left');
        return $this->db->get()->result();
    }
    function get_filter($status,$user,$country,$branch,$gender, $from, $to)
    {

		$this->db->order_by('individual_customers.id', $this->order);
		$this->db->select('*,geo_countries.name as geoname,employees.Firstname as efname, employees.Lastname as elname,individual_customers.Firstname as cfname, individual_customers.Lastname as clname,individual_customers.Middlename as cmname, individual_customers.Gender as cgender,individual_customers.id as cid,individual_customers.DateOfBirth as cdob, individual_customers.EmailAddress as cemail')->from($this->table);


		$this->db->join('employees','employees.id=individual_customers.added_by');
		$this->db->join('geo_countries','geo_countries.code=individual_customers.Country','left');
		if($gender !="All"){
			$this->db->where('individual_customers.Gender',$gender);
		}
		if($status !="All"){
			$this->db->where('individual_customers.approval_status',$status);
		}
		if($user !="All"){
			$this->db->where('individual_customers.added_by',$user);
		}
		if($country !="All"){
			$this->db->where('individual_customers.Country',$country);
		}
        if($branch !="All"){
            $this->db->where('individual_customers.Branch',$branch);
        }
		if($from !="" && $to !=""){
			$this->db->where('CreatedOn BETWEEN "'. date('Y-m-d', strtotime($from)). '" and "'. date('Y-m-d', strtotime($to)).'"');
		}
        return $this->db->get()->result();

    }
    function get_all_active()
    {
        $this->db->order_by($this->id, $this->order);
		$this->db->where('approval_status','Approved');
        return $this->db->get($this->table)->result();
    }
    function get_selective($id)
    {
        $this->db->order_by($this->id, $this->order);
        $this->db->where('added_by',$id);
        return $this->db->get($this->table)->result();
    }   function get_status($id)
    {
        $this->db->order_by($this->id, $this->order);
        $this->db->where('approval_status',$id);
        return $this->db->get($this->table)->result();
    }

    // get data by id
    function get_by_id($id)
    {
        $this->db->where($this->id, $id);
        return $this->db->get($this->table)->row();
    }
    
    // get total rows
    function total_rows($q = NULL) {
    	$this->db->group_start();
        $this->db->like('id', $q);
	$this->db->or_like('ClientId', $q);
	$this->db->or_like('Title', $q);
	$this->db->or_like('Firstname', $q);
	$this->db->or_like('Middlename', $q);
	$this->db->or_like('Lastname', $q);
	$this->db->or_like('Gender', $q);
	$this->db->or_like('DateOfBirth', $q);
	$this->db->or_like('EmailAddress', $q);
	$this->db->or_like('PhoneNumber', $q);
	$this->db->or_like('AddressLine1', $q);
	$this->db->or_like('AddressLine2', $q);
	$this->db->or_like('AddressLine3', $q);
	$this->db->or_like('Province', $q);
	$this->db->or_like('City', $q);
	$this->db->or_like('Country', $q);
	$this->db->or_like('ResidentialStatus', $q);
	$this->db->or_like('Profession', $q);
	$this->db->or_like('SourceOfIncome', $q);
	$this->db->or_like('GrossMonthlyIncome', $q);
	$this->db->or_like('Branch', $q);
	$this->db->or_like('LastUpdatedOn', $q);
	$this->db->or_like('CreatedOn', $q);
	$this->db->group_end();
	$this->db->from($this->table)
		->where('approval_status','Approved');

        return $this->db->count_all_results();
    }
    function count_it() {

	$this->db->from($this->table);
        return $this->db->count_all_results();
    }
    function count_active($from,$to) {

	$this->db->from($this->table)->where('approval_status','Approved');
		if($from !="" && $to !=""){
			$this->db->where('CreatedOn BETWEEN "'. date('Y-m-d', strtotime($from)). '" and "'. date('Y-m-d', strtotime($to)).'"');

		}
        return $this->db->count_all_results();
    }
    function count_active_gender($from,$to, $gender) {

	$this->db->from($this->table)->where('approval_status','Approved')->where('Gender',$gender);
		if($from !="" && $to !=""){
			$this->db->where('CreatedOn BETWEEN "'. date('Y-m-d', strtotime($from)). '" and "'. date('Y-m-d', strtotime($to)).'"');

		}
        return $this->db->count_all_results();
    }

    // get data with limit and search
    function get_limit_data($limit, $start = 0, $q = NULL) {
        $this->db->order_by($this->id, $this->order);
        $this->db->select("*");
        $this->db->like('id', $q);
        $this->db->group_start();
	$this->db->or_like('ClientId', $q);
	$this->db->or_like('Title', $q);
	$this->db->or_like('Firstname', $q);
	$this->db->or_like('Middlename', $q);
	$this->db->or_like('Lastname', $q);
	$this->db->or_like('Gender', $q);
	$this->db->or_like('DateOfBirth', $q);
	$this->db->or_like('EmailAddress', $q);
	$this->db->or_like('PhoneNumber', $q);
	$this->db->or_like('AddressLine1', $q);
	$this->db->or_like('AddressLine2', $q);
	$this->db->or_like('AddressLine3', $q);
	$this->db->or_like('Province', $q);
	$this->db->or_like('City', $q);
	$this->db->or_like('Country', $q);
	$this->db->or_like('ResidentialStatus', $q);
	$this->db->or_like('Profession', $q);
	$this->db->or_like('SourceOfIncome', $q);
	$this->db->or_like('GrossMonthlyIncome', $q);
	$this->db->or_like('Branch', $q);
	$this->db->or_like('LastUpdatedOn', $q);
	$this->db->or_like('CreatedOn', $q);
	$this->db->group_end();
	$this->db->limit($limit, $start);
		$this->db->from($this->table)
			->where('approval_status','Approved');
        return $this->db->get()->result();
    }

    // insert data
    function insert($data)
    {
        $this->db->insert($this->table, $data);
        return $this->db->insert_id();
    }

    // update data
    function update($id, $data)
    {
        $this->db->where($this->id, $id);
        $this->db->update($this->table, $data);
    }
    function update2($id)
    {
        $this->db->where('ClientId', $id);
        $this->db->update($this->table,array('approval_status'=>'Approved'));
    }

    // delete data
    function delete($id)
    {
        $this->db->where($this->id, $id);
        $this->db->delete($this->table);
    }

}


