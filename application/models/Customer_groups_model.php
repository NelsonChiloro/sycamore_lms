<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Customer_groups_model extends CI_Model
{

    public $table = 'customer_groups';
    public $id = 'customer_group_id';
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
    function get_members($id)
    {
        $this->db->order_by($this->id, $this->order);
        $this->db->select('*')->from($this->table)->join('individual_customers','individual_customers.id=customer_groups.customer')
			->where('group_id',$id);
        return $this->db->get()->result();
    }

    /**
     * Get groups that an individual customer belongs to
     * @param int $customer_id individual_customers.id
     * @return array of group objects with group_name, group_code
     */
    function get_groups_by_customer($customer_id)
    {
        $this->db->select('groups.group_id, groups.group_name, groups.group_code, customer_groups.date_joined');
        $this->db->from($this->table);
        $this->db->join('groups', 'groups.group_id = customer_groups.group_id');
        $this->db->where('customer_groups.customer', $customer_id);
        return $this->db->get()->result();
    }
    function check($id,$customer)
    {

        $this->db->select('*')->from($this->table)
			->where('group_id',$id)
			->where('customer',$customer);
        return $this->db->get()->row();
    }
	function add_members($arr,$data){
		if($arr){
			$already_assigned = array();
			$has_active_loans = array();
			
			for($i=0;$i <count($arr);$i++){
				// Get customer name for error messages
				$customer = $this->db->select('Firstname, Lastname')
					->from('individual_customers')
					->where('id', $arr[$i])
					->get()->row();
				
				$customer_name = $customer ? $customer->Firstname . ' ' . $customer->Lastname : 'Customer ID: ' . $arr[$i];
				
				// Check if customer is already in any group
				$existing_group = $this->db->select('cg.*, g.group_name')
					->from('customer_groups cg')
					->join('groups g', 'g.group_id = cg.group_id')
					->where('cg.customer', $arr[$i])
					->get()->row();
				
				if($existing_group) {
					$already_assigned[] = $customer_name . ' (already in group: ' . $existing_group->group_name . ')';
					continue; // Skip this customer
				}
				
				// Check if customer has an active individual loan
				$active_loan = $this->db->select('loan_id, loan_principal')
					->from('loan')
					->where('loan_customer', $arr[$i])
					->where('customer_type', 'individual')
					->where('loan_status', 'ACTIVE')
					->get()->row();
				
				if($active_loan) {
					$has_active_loans[] = $customer_name . ' (has active individual loan: MK ' . number_format($active_loan->loan_principal, 2) . ')';
					continue; // Skip this customer
				}

				$menu=array(
					'group_id'=>$data,
					'customer'=>$arr[$i],
				);

				$this->db->insert($this->table,$menu);
			}
			
			// Return information about validation failures
			$errors = array();
			if(!empty($already_assigned)) {
				$errors['already_assigned'] = $already_assigned;
			}
			if(!empty($has_active_loans)) {
				$errors['has_active_loans'] = $has_active_loans;
			}
			
			if(!empty($errors)) {
				return array_merge(array('success' => false), $errors);
			}
			
			return array('success' => true);
		}
		
		return array('success' => false, 'error' => 'No members provided');
	}
	function update_members($arr,$data){
    	$this->db->where('group_id',$data)->delete($this->table);
		if($arr){
			$already_assigned = array();
			$has_active_loans = array();

			for($i=0;$i <count($arr);$i++){
				// Get customer name for error messages
				$customer = $this->db->select('Firstname, Lastname')
					->from('individual_customers')
					->where('id', $arr[$i])
					->get()->row();
				
				$customer_name = $customer ? $customer->Firstname . ' ' . $customer->Lastname : 'Customer ID: ' . $arr[$i];
				
				// Check if customer is already in any OTHER group (not the current one being updated)
				$existing_group = $this->db->select('cg.*, g.group_name')
					->from('customer_groups cg')
					->join('groups g', 'g.group_id = cg.group_id')
					->where('cg.customer', $arr[$i])
					->where('cg.group_id !=', $data) // Exclude current group
					->get()->row();
				
				if($existing_group) {
					$already_assigned[] = $customer_name . ' (already in group: ' . $existing_group->group_name . ')';
					continue; // Skip this customer
				}
				
				// Check if customer has an active individual loan
				$active_loan = $this->db->select('loan_id, loan_principal')
					->from('loan')
					->where('loan_customer', $arr[$i])
					->where('customer_type', 'individual')
					->where('loan_status', 'ACTIVE')
					->get()->row();
				
				if($active_loan) {
					$has_active_loans[] = $customer_name . ' (has active individual loan: MK ' . number_format($active_loan->loan_principal, 2) . ')';
					continue; // Skip this customer
				}

				$menu=array(
					'group_id'=>$data,
					'customer'=>$arr[$i],

				);

				$this->db->insert($this->table,$menu);
			}
			
			// Return information about validation failures
			$errors = array();
			if(!empty($already_assigned)) {
				$errors['already_assigned'] = $already_assigned;
			}
			if(!empty($has_active_loans)) {
				$errors['has_active_loans'] = $has_active_loans;
			}
			
			if(!empty($errors)) {
				return array_merge(array('success' => false), $errors);
			}
			
			return array('success' => true);
		}
		
		return array('success' => true); // No members to add is OK for updates
	}

    // get data by id
    function get_by_id($id)
    {
        $this->db->where($this->id, $id);
        return $this->db->get($this->table)->row();
    }
    
    // get total rows
    function total_rows($q = NULL) {
        $this->db->like('customer_group_id', $q);
	$this->db->or_like('customer', $q);
	$this->db->or_like('group_id', $q);
	$this->db->or_like('date_joined', $q);
	$this->db->from($this->table);
        return $this->db->count_all_results();
    }

    // get data with limit and search
    function get_limit_data($limit, $start = 0, $q = NULL) {
        $this->db->order_by($this->id, $this->order);
        $this->db->like('customer_group_id', $q);
	$this->db->or_like('customer', $q);
	$this->db->or_like('group_id', $q);
	$this->db->or_like('date_joined', $q);
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

/* End of file Customer_groups_model.php */
/* Location: ./application/models/Customer_groups_model.php */
/* Please DO NOT modify this information : */
/* Generated by Harviacode Codeigniter CRUD Generator 2021-12-23 06:07:11 */
/* http://harviacode.com */
