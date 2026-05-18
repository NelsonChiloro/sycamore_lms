<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Loan_customer_first_drafr_model extends CI_Model
{

    public $table = 'loan_customer_first_drafr';
    public $id = 'id';
    public $order = 'DESC';

    function __construct()
    {
        parent::__construct();
    }
// insert customers and update customer ID
function insert_c(){
		$this->db->select("*")->from($this->table);
		$results = $this->db->get()->result();
		$count = 2;
		foreach ($results as $r){
			$clientid=	(2010000)+ $count;
			$data = array(
				'ClientId' => $clientid,
				'Title' => $r->Title,
				'Firstname' => $r->Firstname,
				'Middlename' => $r->Middlename,
				'Lastname' => $r->Lastname,
				'Gender' => $r->Gender,
				'DateOfBirth' => $r->DateOfBirth,
				'EmailAddress' => 'N/A',
				'PhoneNumber' => $r->PhoneNumber,
				'AddressLine1' => 'N/A',
				'AddressLine2' => 'N/A',
				'AddressLine3' => 'N/A',
				'village' => $r->Village,
				'Province' => $r->TA,
				'City' => $r->City,
				'Country' => 454,
				'ResidentialStatus' => $r->ResidentialStatus,
				'Profession' => $r->Profession,
				'SourceOfIncome' => $r->SourceOfIncome,
				'GrossMonthlyIncome' => $r->GrossMonthlyIncome,
				'Branch' => 6,
				'added_by' => 7

			);
			$this->db->insert('individual_customers',$data);
			$id = $this->db->insert_id();
			$this->db->where('id',$r->id)
			->update($this->table, array('customer_id'=> $id));
			$count++;
		}

}
function add_loan_products(){
	$this->db->group_by('loan_product ');
	$this->db->distinct()->select("*")->from($this->table);
	$results = $this->db->get()->result();
$c = 0;
	foreach ($results as $row){

		$data = array(

			'product_name' => $row->loan_product,
			'interest' => $row->loan_interest,
			'added_by' => 7,

		);

		$this->db->insert('loan_products',$data);
		$id = $this->db->insert_id();
		$this->db->where('loan_product',$row->loan_product)
			->update($this->table, array('loan_product_id'=> $id));
//$c++;
	}
	echo $c;
}
function add_groups(){
	$this->db->group_by('ClubName');
	$this->db->distinct()->select("*")->from($this->table);
	$results = $this->db->get()->result();
$c = 0;
	foreach ($results as $r){

		$data = array(
			'group_code' =>rand(100,9999),
			'group_name' => $r->ClubName,

			'branch' => 89,
			'group_description' => 'N/A',
			'file' => 'N/A',
			'group_added_by' => 7

		);
		$this->db->insert('groups',$data);
		$id = $this->db->insert_id();
		$this->db->where('ClubName',$r->ClubName)
			->update($this->table, array('club_id'=> $id));
//$c++;
	}
	echo $c;
}


function add_customer_to_group(){

	$this->db->select("*")->from($this->table);
	$results = $this->db->get()->result();
$c = 0;
	foreach ($results as $r){

		$data = array(

			'customer' => $r->customer_id,
			'group_id' => $r->club_id,


		);
		$this->db->insert('customer_groups',$data);
//		$id = $this->db->insert_id();
//		$this->db->where('ClubName',$r->ClubName)
//			->update($this->table, array('club_id'=> $id));
//$c++;
	}
	echo $c;
}
    // get all
    function get_all_active()
    {
        $this->db->order_by($this->id, $this->order);
		$this->db->where('loan_status','open');
        return $this->db->get($this->table)->result();
    } function get_all()
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
        $this->db->like('', $q);
	$this->db->or_like('Title', $q);
	$this->db->or_like('Firstname', $q);
	$this->db->or_like('Middlename', $q);
	$this->db->or_like('Lastname', $q);
	$this->db->or_like('Gender', $q);
	$this->db->or_like('DateOfBirth', $q);
	$this->db->or_like('PhoneNumber', $q);
	$this->db->or_like('Village', $q);
	$this->db->or_like('TA', $q);
	$this->db->or_like('ClubName', $q);
	$this->db->or_like('City', $q);
	$this->db->or_like('MarritalStatus', $q);
	$this->db->or_like('Country', $q);
	$this->db->or_like('ResidentialStatus', $q);
	$this->db->or_like('Profession', $q);
	$this->db->or_like('SourceOfIncome', $q);
	$this->db->or_like('GrossMonthlyIncome', $q);
	$this->db->or_like('CreatedOnCustomer', $q);
	$this->db->or_like('loan_number', $q);
	$this->db->or_like('loan_product', $q);
	$this->db->or_like('loan_effectve_date', $q);
	$this->db->or_like('loan_principal', $q);
	$this->db->or_like('loan_period', $q);
	$this->db->or_like('period_type', $q);
	$this->db->or_like('loan_interest', $q);
	$this->db->or_like('next_payment_number', $q);
	$this->db->or_like('loan_added_by', $q);
	$this->db->or_like('loan_status', $q);
	$this->db->or_like('loan_added_date', $q);
	$this->db->or_like('Totalrepaid', $q);
	$this->db->or_like('PrincipalPaid', $q);
	$this->db->or_like('InteresrPaid', $q);
	$this->db->from($this->table);
        return $this->db->count_all_results();
    }

    // get data with limit and search
    function get_limit_data($limit, $start = 0, $q = NULL) {
        $this->db->order_by($this->id, $this->order);
        $this->db->like('', $q);
	$this->db->or_like('Title', $q);
	$this->db->or_like('Firstname', $q);
	$this->db->or_like('Middlename', $q);
	$this->db->or_like('Lastname', $q);
	$this->db->or_like('Gender', $q);
	$this->db->or_like('DateOfBirth', $q);
	$this->db->or_like('PhoneNumber', $q);
	$this->db->or_like('Village', $q);
	$this->db->or_like('TA', $q);
	$this->db->or_like('ClubName', $q);
	$this->db->or_like('City', $q);
	$this->db->or_like('MarritalStatus', $q);
	$this->db->or_like('Country', $q);
	$this->db->or_like('ResidentialStatus', $q);
	$this->db->or_like('Profession', $q);
	$this->db->or_like('SourceOfIncome', $q);
	$this->db->or_like('GrossMonthlyIncome', $q);
	$this->db->or_like('CreatedOnCustomer', $q);
	$this->db->or_like('loan_number', $q);
	$this->db->or_like('loan_product', $q);
	$this->db->or_like('loan_effectve_date', $q);
	$this->db->or_like('loan_principal', $q);
	$this->db->or_like('loan_period', $q);
	$this->db->or_like('period_type', $q);
	$this->db->or_like('loan_interest', $q);
	$this->db->or_like('next_payment_number', $q);
	$this->db->or_like('loan_added_by', $q);
	$this->db->or_like('loan_status', $q);
	$this->db->or_like('loan_added_date', $q);
	$this->db->or_like('Totalrepaid', $q);
	$this->db->or_like('PrincipalPaid', $q);
	$this->db->or_like('InteresrPaid', $q);
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

/* End of file Loan_customer_first_drafr_model.php */
/* Location: ./application/models/Loan_customer_first_drafr_model.php */
/* Please DO NOT modify this information : */
/* Generated by Harviacode Codeigniter CRUD Generator 2022-08-24 20:59:58 */
/* http://harviacode.com */
