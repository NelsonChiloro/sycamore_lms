<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Borrowed_repayements_model extends CI_Model
{

    public $table = 'borrowed_repayements';
    public $id = 'b_id';
    public $order = 'DESC';

    function __construct()
    {
        parent::__construct();
    }
	public function sum_interest_paid($from,$to)
	{
		$this->db->select('SUM(interest_paid) as interest_paid');
		$this->db->from('borrowed_repayements');

		// $this->db->join('lend_payments','lend_payments.borrower_loan_id=lend_borrower_loans.id');

		if ($from != "" && $to != "") {
			$this->db->where('date_of_repaymet BETWEEN "' . date('Y-m-d', strtotime($from)) . '" and "' . date('Y-m-d', strtotime($to)) . '"');

		}
		return $this->db->get()->row();

	}
		// get all
    function get_all()
    {
        $this->db->order_by($this->id, $this->order);
        return $this->db->get($this->table)->result();
    }
  function get_related($id)
    {
        $this->db->order_by($this->id, $this->order);
        $this->db->join('employees','employees.id=borrowed_repayements.paid_by');
        $this->db->where('borrowed_id',$id);
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
        $this->db->like('b_id', $q);
	$this->db->or_like('borrowed_id', $q);
	$this->db->or_like('interest_paid', $q);
	$this->db->or_like('principal_paid', $q);
	$this->db->or_like('paid_by', $q);

	$this->db->or_like('stamp', $q);
	$this->db->from($this->table);
        return $this->db->count_all_results();
    }

    // get data with limit and search
    function get_limit_data($limit, $start = 0, $q = NULL) {
        $this->db->order_by($this->id, $this->order);
        $this->db->like('b_id', $q);
	$this->db->or_like('borrowed_id', $q);
	$this->db->or_like('interest_paid', $q);
	$this->db->or_like('principal_paid', $q);
	$this->db->or_like('paid_by', $q);

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

