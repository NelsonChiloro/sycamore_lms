<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Tellering_model extends CI_Model
{

    public $table = 'tellering';
    public $id = 'id';
    public $order = 'DESC';

    function __construct()
    {
        parent::__construct();
    }
	public function get_mine($account){
		$this->db->from($this->table);
		$this->db->join('employees','employees.id=tellering.teller');
		$this->db->where('account', $account);
		return $this->db->get()->row();
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
        $this->db->like('id', $q);
	$this->db->or_like('teller', $q);
	$this->db->or_like('account', $q);
	$this->db->or_like('date_time', $q);
	$this->db->from($this->table);
        return $this->db->count_all_results();
    }

    // get data with limit and search
    function get_limit_data($limit, $start = 0, $q = NULL) {
        $this->db->order_by($this->id, $this->order);
        $this->db->like('id', $q);
	$this->db->or_like('teller', $q);
	$this->db->or_like('account', $q);
	$this->db->or_like('date_time', $q);
	$this->db->limit($limit, $start);
        return $this->db->get($this->table)->result();
    }

    // insert data
    function insert($data)
    {
		$this->db->where('account', $data['account']);
		$this->db->or_where('teller', $data['teller']);
		$this->db->delete($this->table);
        $this->db->insert($this->table, $data);
    }
	public function get_all2(){
		$this->db->select("*");
		$this->db->from($this->table);
		$this->db->join('employees','employees.id=tellering.teller');
		$this->db->join('account','account.account_number=tellering.account');
		$this->db->join('internal_accounts','internal_accounts.internal_account_id=account.account_type_product');
		return $this->db->get()->result();
	}
	public function get_teller_account($account){
		$this->db->from($this->table);
		$this->db->join('employees','employees.id=tellering.teller');
		$this->db->join('account','account.account_number=tellering.account');
		$this->db->where('tellering.teller', $account);
		return $this->db->get()->row();
	}
	public function get_teller_account1(){
		$this->db->from($this->table);
		$this->db->join('account','account.account_number=tellering.account');
		$this->db->where('tellering.is_internal', 'Yes');
		$row = $this->db->get()->row();
		if (!empty($row)) return $row;
		// Fallback: use first available teller if no internal teller configured
		$this->db->from($this->table);
		$this->db->join('account','account.account_number=tellering.account');
		$this->db->limit(1);
		return $this->db->get()->row();
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

