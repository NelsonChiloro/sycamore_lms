<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Vault_cashier_pends_model extends CI_Model
{

    public $table = 'vault_cashier_pends';
    public $id = 'cvpid';
    public $order = 'DESC';

    function __construct()
    {
        parent::__construct();
    }
	function get_cash()
	{
		$this->db->order_by($this->id, $this->order);
		$this->db->select("*,vault_cashier_pends.system_date As sd")
			->from($this->table)
			->join('employees','employees.id =vault_cashier_pends.creator ')
			->where('vault_cashier_pends.status','Initiated');
		return $this->db->get()->result();
	}
	function get_my_cash($id)
	{
		$this->db->order_by($this->id, $this->order);
		$this->db->select("*,vault_cashier_pends.system_date As sd")
			->from($this->table)
			->join('employees','employees.id =vault_cashier_pends.creator ')
			->where('teller',$id);

		return $this->db->get()->result();
	}
    // get all
    function get_all()
    {
        $this->db->order_by($this->id, $this->order);
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
        $this->db->like('cvpid', $q);
	$this->db->or_like('vault_account', $q);
	$this->db->or_like('teller_account', $q);
	$this->db->or_like('amount', $q);
	$this->db->or_like('creator', $q);
	$this->db->or_like('teller', $q);
	$this->db->or_like('system_date', $q);
	$this->db->or_like('status', $q);
	$this->db->or_like('stamp', $q);
	$this->db->from($this->table);
        return $this->db->count_all_results();
    }

    // get data with limit and search
    function get_limit_data($limit, $start = 0, $q = NULL) {
        $this->db->order_by($this->id, $this->order);
        $this->db->like('cvpid', $q);
	$this->db->or_like('vault_account', $q);
	$this->db->or_like('teller_account', $q);
	$this->db->or_like('amount', $q);
	$this->db->or_like('creator', $q);
	$this->db->or_like('teller', $q);
	$this->db->or_like('system_date', $q);
	$this->db->or_like('status', $q);
	$this->db->or_like('stamp', $q);
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

