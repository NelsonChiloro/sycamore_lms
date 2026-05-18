<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Transaction_model extends CI_Model
{

    public $table = 'transaction';
    public $id = 'id';
    public $order = 'DESC';

    function __construct()
    {
        parent::__construct();
    }
    function track_transactions($id)
    {
        $this->db->where('transaction.account_number', $id);
        $this->db->where('transaction_type !=', 'other');
        return $this->db->get($this->table)->result();
    }
    function track_all_transactions()
    {
        $this->db->select('transaction.*');
        $this->db->from($this->table);
        $this->db->join('loan', 'loan.loan_number = transaction.account_number');
        $this->db->where('transaction_type !=', 'other');
        $this->db->order_by('transaction.account_number', 'ASC');
        $this->db->order_by('transaction.system_time', 'DESC');
        return $this->db->get()->result();
    }
    // get all
    function get_all()
    {
        $this->db->order_by($this->id, $this->order);
        return $this->db->get($this->table)->result();
    }
    public function get_my_transaction($account){
        $sql = "SELECT 
                t1.transaction_id AS transaction_id, 
                t1.system_time AS system_time, 
                t1.account_number AS teller_account, 
                t1.credit AS credit1, 
                t1.debit AS debit1, 
                t1.balance AS balance1, 
                t2.account_number AS customer_account
            FROM 
                transaction t1
            JOIN 
                transaction t2 ON t1.transaction_id = t2.transaction_id AND t1.account_number != t2.account_number
            WHERE 
                t1.account_number = '{$account}'";

        return $this->db->query($sql)->result();
    }
	public function get_my_transaction2($account){
		$cdate = date('Y-m-d');
		$sql = "SELECT 
   t1.id AS tid, 
    t1.transaction_id as transaction_id, 
    t1.system_time as system_time, 
    t1.account_number AS teller_account, 
    t1.credit AS credit1, 
    t1.debit AS debit1, 
    t1.balance AS balance1
  
    
FROM 
    transaction t1

WHERE 
    t1.account_number = '{$account}' AND DATE (A.server_time)='{$cdate}'";
//		$sql = "SELECT A.transaction_id AS transaction_id, A.system_time, A.account_number AS teller_account,A.credit,A.debit,A.balance,B.account_number As customer_account FROM transaction A , transaction B WHERE A.account_number <> B.account_number AND A.transaction_id = B.transaction_id AND A.account_number = '{$account}' AND DATE (A.server_time)='{$cdate}' ORDER BY A.id DESC ";
		return	$this->db->query($sql)->result();
//		return $this->db->get()->result();
	}
    public function get_my_transaction_filter($account, $from, $to){
        $this->db->select('t1.id AS tid, t1.transaction_id, t1.system_time, t1.account_number AS teller_account, t1.credit AS credit1, t1.debit AS debit1, t1.balance AS balance1');
        $this->db->from('transaction t1');
        $this->db->where('t1.account_number', $account);
        if($from !="" && $to !=""){
            $this->db->where('t1.system_time BETWEEN "'. date('Y-m-d', strtotime($from)). '" and "'. date('Y-m-d', strtotime($to)).'"');

        }
        $this->db->order_by('t1.system_time', 'DESC');

        return $this->db->get()->result();
    }
    public function get_my_transaction_filter_one($id){

        $sql = "SELECT A.debit as adebit,B.system_time as adate, B.balance as cbalance,A.transaction_id AS transaction_id, A.system_time, A.account_number AS teller_account,A.credit,A.debit,A.balance,B.account_number As customer_account FROM transaction A , transaction B WHERE A.account_number <> B.account_number AND A.transaction_id = B.transaction_id AND A.id = '{$id}'  ORDER BY A.id DESC ";
        return	$this->db->query($sql)->row();

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
	$this->db->or_like('account_number', $q);
	$this->db->or_like('transaction_id', $q);
	$this->db->or_like('credit', $q);
	$this->db->or_like('debit', $q);
	$this->db->or_like('balance', $q);
	$this->db->or_like('system_time', $q);
	$this->db->or_like('server_time', $q);
	$this->db->from($this->table);
        return $this->db->count_all_results();
    }

    // get data with limit and search
    function get_limit_data($limit, $start = 0, $q = NULL) {
        $this->db->order_by($this->id, $this->order);
        $this->db->like('id', $q);
	$this->db->or_like('account_number', $q);
	$this->db->or_like('transaction_id', $q);
	$this->db->or_like('credit', $q);
	$this->db->or_like('debit', $q);
	$this->db->or_like('balance', $q);
	$this->db->or_like('system_time', $q);
	$this->db->or_like('server_time', $q);
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
    public function get_collection_account($ref)
    {
        $this->db->from($this->table)
            ->join('account','account.account_number=transaction.account_number')
            ->where('transaction.transaction_id',$ref)
            ->where('account.collection_account','Yes');
        return $this->db->get()->row();

    }   public function get_teller_account($ref)
    {
        $this->db->from($this->table)
            ->join('account','account.account_number=transaction.account_number')
            ->where('transaction.transaction_id',$ref)
            ->where('account.is_teller','Yes');
        return $this->db->get()->row();

    }
    public function get_loan_account($ref)
    {
        $this->db->from($this->table)
            ->join('account','account.account_number=transaction.account_number')
            ->where('transaction.transaction_id',$ref)
            ->where('account.account_type',2 );
        return $this->db->get()->row();

    }

}


