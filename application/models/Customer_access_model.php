<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Customer_access_model extends CI_Model
{

    public $table = 'customer_access';
    public $id = 'id';
    public $order = 'DESC';

    function __construct()
    {
        parent::__construct();
    }

    // get all
    function get_all()
    {
        $this->db->order_by($this->id, $this->order);
        return $this->db->get($this->table)->result();
    }
    function get_all_by_status($status)
    {
        $this->db->order_by($this->id, $this->order);
        $this->db->where('status',$status);
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
	$this->db->or_like('customer_id', $q);
	$this->db->or_like('phone_number', $q);
	$this->db->or_like('created_by', $q);
	$this->db->or_like('approved_by', $q);
	$this->db->or_like('denied_by', $q);
	$this->db->or_like('status', $q);
	$this->db->or_like('stamp', $q);
	$this->db->from($this->table);
        return $this->db->count_all_results();
    }

    // get data with limit and search
    function get_limit_data($limit, $start = 0, $q = NULL) {
        $this->db->order_by($this->id, $this->order);
        $this->db->like('id', $q);
	$this->db->or_like('customer_id', $q);
	$this->db->or_like('phone_number', $q);
	$this->db->or_like('created_by', $q);
	$this->db->or_like('approved_by', $q);
	$this->db->or_like('denied_by', $q);
	$this->db->or_like('status', $q);
	$this->db->or_like('stamp', $q);
	$this->db->limit($limit, $start);
        return $this->db->get($this->table)->result();
    }
    function login($email, $password)
    {
        $this->db->select('*');
        $this->db->from($this->table);
        $this->db->join('individual_customers','individual_customers.id=customer_access.customer_id');

        $this->db->where('customer_access.phone_number', $email);
        $this->db->where('customer_access.password', md5($password));
        $this->db->where('customer_access.status', 'Active');

        $q= $this->db->get()->row();

        if(empty($q)){
            return array('status' => 204,'message' => 'Username or password not correct.');
        } else {

            $id  = $q->phone_number;

            $last_login = date('Y-m-d H:i:s');
            $token = sha1(substr( md5(rand()), 0, 7));
            $expired_at = date("Y-m-d H:i:s", strtotime('+12 hours'));
            $this->db->trans_start();
            // $this->db->where('id',$id)->update('users',array('last_login' => $last_login));
            $this->db->insert('users_authentication',array('users_id' => $id,'token' => $token,'expired_at' => $expired_at));
            if ($this->db->trans_status() === FALSE){
                $this->db->trans_rollback();
                return array('status' => 500,'message' => 'Internal server error.');
            } else {
                $this->db->trans_commit();
                return array('status' => 200,'message' => 'Successfully login.','id' => $id, 'token' => $token);
            }

        }

    }
    // insert data
    function insert($data)
    {
        $this->db->where('customer_id', $data['customer_id']);
        $this->db->delete($this->table);
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


