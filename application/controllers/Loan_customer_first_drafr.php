<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Loan_customer_first_drafr extends CI_Controller
{
    function __construct()
    {
        parent::__construct();
        $this->load->model('Loan_customer_first_drafr_model');
        $this->load->library('form_validation');
    }

    public function index()
    {
        $q = urldecode($this->input->get('q', TRUE));
        $start = intval($this->input->get('start'));
        
        if ($q <> '') {
            $config['base_url'] = base_url() . 'loan_customer_first_drafr/index.html?q=' . urlencode($q);
            $config['first_url'] = base_url() . 'loan_customer_first_drafr/index.html?q=' . urlencode($q);
        } else {
            $config['base_url'] = base_url() . 'loan_customer_first_drafr/index.html';
            $config['first_url'] = base_url() . 'loan_customer_first_drafr/index.html';
        }

        $config['per_page'] = 10;
        $config['page_query_string'] = TRUE;
        $config['total_rows'] = $this->Loan_customer_first_drafr_model->total_rows($q);
        $loan_customer_first_drafr = $this->Loan_customer_first_drafr_model->get_limit_data($config['per_page'], $start, $q);

        $this->load->library('pagination');
        $this->pagination->initialize($config);

        $data = array(
            'loan_customer_first_drafr_data' => $loan_customer_first_drafr,
            'q' => $q,
            'pagination' => $this->pagination->create_links(),
            'total_rows' => $config['total_rows'],
            'start' => $start,
        );
        $this->load->view('loan_customer_first_drafr/loan_customer_first_drafr_list', $data);
    }

    public function read($id) 
    {
        $row = $this->Loan_customer_first_drafr_model->get_by_id($id);
        if ($row) {
            $data = array(
		'Title' => $row->Title,
		'Firstname' => $row->Firstname,
		'Middlename' => $row->Middlename,
		'Lastname' => $row->Lastname,
		'Gender' => $row->Gender,
		'DateOfBirth' => $row->DateOfBirth,
		'PhoneNumber' => $row->PhoneNumber,
		'Village' => $row->Village,
		'TA' => $row->TA,
		'ClubName' => $row->ClubName,
		'City' => $row->City,
		'MarritalStatus' => $row->MarritalStatus,
		'Country' => $row->Country,
		'ResidentialStatus' => $row->ResidentialStatus,
		'Profession' => $row->Profession,
		'SourceOfIncome' => $row->SourceOfIncome,
		'GrossMonthlyIncome' => $row->GrossMonthlyIncome,
		'CreatedOnCustomer' => $row->CreatedOnCustomer,
		'loan_number' => $row->loan_number,
		'loan_product' => $row->loan_product,
		'loan_effectve_date' => $row->loan_effectve_date,
		'loan_principal' => $row->loan_principal,
		'loan_period' => $row->loan_period,
		'period_type' => $row->period_type,
		'loan_interest' => $row->loan_interest,
		'next_payment_number' => $row->next_payment_number,
		'loan_added_by' => $row->loan_added_by,
		'loan_status' => $row->loan_status,
		'loan_added_date' => $row->loan_added_date,
		'Totalrepaid' => $row->Totalrepaid,
		'PrincipalPaid' => $row->PrincipalPaid,
		'InteresrPaid' => $row->InteresrPaid,
	    );
            $this->load->view('loan_customer_first_drafr/loan_customer_first_drafr_read', $data);
        } else {
            $this->session->set_flashdata('message', 'Record Not Found');
            redirect(site_url('loan_customer_first_drafr'));
        }
    }

    public function create() 
    {
        $data = array(
            'button' => 'Create',
            'action' => site_url('loan_customer_first_drafr/create_action'),
	    'Title' => set_value('Title'),
	    'Firstname' => set_value('Firstname'),
	    'Middlename' => set_value('Middlename'),
	    'Lastname' => set_value('Lastname'),
	    'Gender' => set_value('Gender'),
	    'DateOfBirth' => set_value('DateOfBirth'),
	    'PhoneNumber' => set_value('PhoneNumber'),
	    'Village' => set_value('Village'),
	    'TA' => set_value('TA'),
	    'ClubName' => set_value('ClubName'),
	    'City' => set_value('City'),
	    'MarritalStatus' => set_value('MarritalStatus'),
	    'Country' => set_value('Country'),
	    'ResidentialStatus' => set_value('ResidentialStatus'),
	    'Profession' => set_value('Profession'),
	    'SourceOfIncome' => set_value('SourceOfIncome'),
	    'GrossMonthlyIncome' => set_value('GrossMonthlyIncome'),
	    'CreatedOnCustomer' => set_value('CreatedOnCustomer'),
	    'loan_number' => set_value('loan_number'),
	    'loan_product' => set_value('loan_product'),
	    'loan_effectve_date' => set_value('loan_effectve_date'),
	    'loan_principal' => set_value('loan_principal'),
	    'loan_period' => set_value('loan_period'),
	    'period_type' => set_value('period_type'),
	    'loan_interest' => set_value('loan_interest'),
	    'next_payment_number' => set_value('next_payment_number'),
	    'loan_added_by' => set_value('loan_added_by'),
	    'loan_status' => set_value('loan_status'),
	    'loan_added_date' => set_value('loan_added_date'),
	    'Totalrepaid' => set_value('Totalrepaid'),
	    'PrincipalPaid' => set_value('PrincipalPaid'),
	    'InteresrPaid' => set_value('InteresrPaid'),
	);
        $this->load->view('loan_customer_first_drafr/loan_customer_first_drafr_form', $data);
    }
    
    public function create_action() 
    {
        $this->_rules();

        if ($this->form_validation->run() == FALSE) {
            $this->create();
        } else {
            $data = array(
		'Title' => $this->input->post('Title',TRUE),
		'Firstname' => $this->input->post('Firstname',TRUE),
		'Middlename' => $this->input->post('Middlename',TRUE),
		'Lastname' => $this->input->post('Lastname',TRUE),
		'Gender' => $this->input->post('Gender',TRUE),
		'DateOfBirth' => $this->input->post('DateOfBirth',TRUE),
		'PhoneNumber' => $this->input->post('PhoneNumber',TRUE),
		'Village' => $this->input->post('Village',TRUE),
		'TA' => $this->input->post('TA',TRUE),
		'ClubName' => $this->input->post('ClubName',TRUE),
		'City' => $this->input->post('City',TRUE),
		'MarritalStatus' => $this->input->post('MarritalStatus',TRUE),
		'Country' => $this->input->post('Country',TRUE),
		'ResidentialStatus' => $this->input->post('ResidentialStatus',TRUE),
		'Profession' => $this->input->post('Profession',TRUE),
		'SourceOfIncome' => $this->input->post('SourceOfIncome',TRUE),
		'GrossMonthlyIncome' => $this->input->post('GrossMonthlyIncome',TRUE),
		'CreatedOnCustomer' => $this->input->post('CreatedOnCustomer',TRUE),
		'loan_number' => $this->input->post('loan_number',TRUE),
		'loan_product' => $this->input->post('loan_product',TRUE),
		'loan_effectve_date' => $this->input->post('loan_effectve_date',TRUE),
		'loan_principal' => $this->input->post('loan_principal',TRUE),
		'loan_period' => $this->input->post('loan_period',TRUE),
		'period_type' => $this->input->post('period_type',TRUE),
		'loan_interest' => $this->input->post('loan_interest',TRUE),
		'next_payment_number' => $this->input->post('next_payment_number',TRUE),
		'loan_added_by' => $this->input->post('loan_added_by',TRUE),
		'loan_status' => $this->input->post('loan_status',TRUE),
		'loan_added_date' => $this->input->post('loan_added_date',TRUE),
		'Totalrepaid' => $this->input->post('Totalrepaid',TRUE),
		'PrincipalPaid' => $this->input->post('PrincipalPaid',TRUE),
		'InteresrPaid' => $this->input->post('InteresrPaid',TRUE),
	    );

            $this->Loan_customer_first_drafr_model->insert($data);
            $this->session->set_flashdata('message', 'Create Record Success');
            redirect(site_url('loan_customer_first_drafr'));
        }
    }
    
    public function update($id) 
    {
        $row = $this->Loan_customer_first_drafr_model->get_by_id($id);

        if ($row) {
            $data = array(
                'button' => 'Update',
                'action' => site_url('loan_customer_first_drafr/update_action'),
		'Title' => set_value('Title', $row->Title),
		'Firstname' => set_value('Firstname', $row->Firstname),
		'Middlename' => set_value('Middlename', $row->Middlename),
		'Lastname' => set_value('Lastname', $row->Lastname),
		'Gender' => set_value('Gender', $row->Gender),
		'DateOfBirth' => set_value('DateOfBirth', $row->DateOfBirth),
		'PhoneNumber' => set_value('PhoneNumber', $row->PhoneNumber),
		'Village' => set_value('Village', $row->Village),
		'TA' => set_value('TA', $row->TA),
		'ClubName' => set_value('ClubName', $row->ClubName),
		'City' => set_value('City', $row->City),
		'MarritalStatus' => set_value('MarritalStatus', $row->MarritalStatus),
		'Country' => set_value('Country', $row->Country),
		'ResidentialStatus' => set_value('ResidentialStatus', $row->ResidentialStatus),
		'Profession' => set_value('Profession', $row->Profession),
		'SourceOfIncome' => set_value('SourceOfIncome', $row->SourceOfIncome),
		'GrossMonthlyIncome' => set_value('GrossMonthlyIncome', $row->GrossMonthlyIncome),
		'CreatedOnCustomer' => set_value('CreatedOnCustomer', $row->CreatedOnCustomer),
		'loan_number' => set_value('loan_number', $row->loan_number),
		'loan_product' => set_value('loan_product', $row->loan_product),
		'loan_effectve_date' => set_value('loan_effectve_date', $row->loan_effectve_date),
		'loan_principal' => set_value('loan_principal', $row->loan_principal),
		'loan_period' => set_value('loan_period', $row->loan_period),
		'period_type' => set_value('period_type', $row->period_type),
		'loan_interest' => set_value('loan_interest', $row->loan_interest),
		'next_payment_number' => set_value('next_payment_number', $row->next_payment_number),
		'loan_added_by' => set_value('loan_added_by', $row->loan_added_by),
		'loan_status' => set_value('loan_status', $row->loan_status),
		'loan_added_date' => set_value('loan_added_date', $row->loan_added_date),
		'Totalrepaid' => set_value('Totalrepaid', $row->Totalrepaid),
		'PrincipalPaid' => set_value('PrincipalPaid', $row->PrincipalPaid),
		'InteresrPaid' => set_value('InteresrPaid', $row->InteresrPaid),
	    );
            $this->load->view('loan_customer_first_drafr/loan_customer_first_drafr_form', $data);
        } else {
            $this->session->set_flashdata('message', 'Record Not Found');
            redirect(site_url('loan_customer_first_drafr'));
        }
    }
    
    public function update_action() 
    {
        $this->_rules();

        if ($this->form_validation->run() == FALSE) {
            $this->update($this->input->post('', TRUE));
        } else {
            $data = array(
		'Title' => $this->input->post('Title',TRUE),
		'Firstname' => $this->input->post('Firstname',TRUE),
		'Middlename' => $this->input->post('Middlename',TRUE),
		'Lastname' => $this->input->post('Lastname',TRUE),
		'Gender' => $this->input->post('Gender',TRUE),
		'DateOfBirth' => $this->input->post('DateOfBirth',TRUE),
		'PhoneNumber' => $this->input->post('PhoneNumber',TRUE),
		'Village' => $this->input->post('Village',TRUE),
		'TA' => $this->input->post('TA',TRUE),
		'ClubName' => $this->input->post('ClubName',TRUE),
		'City' => $this->input->post('City',TRUE),
		'MarritalStatus' => $this->input->post('MarritalStatus',TRUE),
		'Country' => $this->input->post('Country',TRUE),
		'ResidentialStatus' => $this->input->post('ResidentialStatus',TRUE),
		'Profession' => $this->input->post('Profession',TRUE),
		'SourceOfIncome' => $this->input->post('SourceOfIncome',TRUE),
		'GrossMonthlyIncome' => $this->input->post('GrossMonthlyIncome',TRUE),
		'CreatedOnCustomer' => $this->input->post('CreatedOnCustomer',TRUE),
		'loan_number' => $this->input->post('loan_number',TRUE),
		'loan_product' => $this->input->post('loan_product',TRUE),
		'loan_effectve_date' => $this->input->post('loan_effectve_date',TRUE),
		'loan_principal' => $this->input->post('loan_principal',TRUE),
		'loan_period' => $this->input->post('loan_period',TRUE),
		'period_type' => $this->input->post('period_type',TRUE),
		'loan_interest' => $this->input->post('loan_interest',TRUE),
		'next_payment_number' => $this->input->post('next_payment_number',TRUE),
		'loan_added_by' => $this->input->post('loan_added_by',TRUE),
		'loan_status' => $this->input->post('loan_status',TRUE),
		'loan_added_date' => $this->input->post('loan_added_date',TRUE),
		'Totalrepaid' => $this->input->post('Totalrepaid',TRUE),
		'PrincipalPaid' => $this->input->post('PrincipalPaid',TRUE),
		'InteresrPaid' => $this->input->post('InteresrPaid',TRUE),
	    );

            $this->Loan_customer_first_drafr_model->update($this->input->post('', TRUE), $data);
            $this->session->set_flashdata('message', 'Update Record Success');
            redirect(site_url('loan_customer_first_drafr'));
        }
    }
    
    public function delete($id) 
    {
        $row = $this->Loan_customer_first_drafr_model->get_by_id($id);

        if ($row) {
            $this->Loan_customer_first_drafr_model->delete($id);
            $this->session->set_flashdata('message', 'Delete Record Success');
            redirect(site_url('loan_customer_first_drafr'));
        } else {
            $this->session->set_flashdata('message', 'Record Not Found');
            redirect(site_url('loan_customer_first_drafr'));
        }
    }

    public function _rules() 
    {
	$this->form_validation->set_rules('Title', 'title', 'trim|required');
	$this->form_validation->set_rules('Firstname', 'firstname', 'trim|required');
	$this->form_validation->set_rules('Middlename', 'middlename', 'trim|required');
	$this->form_validation->set_rules('Lastname', 'lastname', 'trim|required');
	$this->form_validation->set_rules('Gender', 'gender', 'trim|required');
	$this->form_validation->set_rules('DateOfBirth', 'dateofbirth', 'trim|required');
	$this->form_validation->set_rules('PhoneNumber', 'phonenumber', 'trim|required');
	$this->form_validation->set_rules('Village', 'village', 'trim|required');
	$this->form_validation->set_rules('TA', 'ta', 'trim|required');
	$this->form_validation->set_rules('ClubName', 'clubname', 'trim|required');
	$this->form_validation->set_rules('City', 'city', 'trim|required');
	$this->form_validation->set_rules('MarritalStatus', 'marritalstatus', 'trim|required');
	$this->form_validation->set_rules('Country', 'country', 'trim|required');
	$this->form_validation->set_rules('ResidentialStatus', 'residentialstatus', 'trim|required');
	$this->form_validation->set_rules('Profession', 'profession', 'trim|required');
	$this->form_validation->set_rules('SourceOfIncome', 'sourceofincome', 'trim|required');
	$this->form_validation->set_rules('GrossMonthlyIncome', 'grossmonthlyincome', 'trim|required');
	$this->form_validation->set_rules('CreatedOnCustomer', 'createdoncustomer', 'trim|required');
	$this->form_validation->set_rules('loan_number', 'loan number', 'trim|required');
	$this->form_validation->set_rules('loan_product', 'loan product', 'trim|required');
	$this->form_validation->set_rules('loan_effectve_date', 'loan effectve date', 'trim|required');
	$this->form_validation->set_rules('loan_principal', 'loan principal', 'trim|required');
	$this->form_validation->set_rules('loan_period', 'loan period', 'trim|required');
	$this->form_validation->set_rules('period_type', 'period type', 'trim|required');
	$this->form_validation->set_rules('loan_interest', 'loan interest', 'trim|required');
	$this->form_validation->set_rules('next_payment_number', 'next payment number', 'trim|required');
	$this->form_validation->set_rules('loan_added_by', 'loan added by', 'trim|required');
	$this->form_validation->set_rules('loan_status', 'loan status', 'trim|required');
	$this->form_validation->set_rules('loan_added_date', 'loan added date', 'trim|required');
	$this->form_validation->set_rules('Totalrepaid', 'totalrepaid', 'trim|required');
	$this->form_validation->set_rules('PrincipalPaid', 'principalpaid', 'trim|required');
	$this->form_validation->set_rules('InteresrPaid', 'interesrpaid', 'trim|required');

	$this->form_validation->set_rules('', '', 'trim');
	$this->form_validation->set_error_delimiters('<span class="text-danger">', '</span>');
    }

}

/* End of file Loan_customer_first_drafr.php */
/* Location: ./application/controllers/Loan_customer_first_drafr.php */
/* Please DO NOT modify this information : */
/* Generated by Harviacode Codeigniter CRUD Generator 2022-08-24 20:59:58 */
/* http://harviacode.com */
