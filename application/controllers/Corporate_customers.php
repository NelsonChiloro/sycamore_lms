<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Corporate_customers extends CI_Controller
{
    function __construct()
    {
        parent::__construct();
        $this->load->model('Corporate_customers_model');
        $this->load->model('Geo_countries_model');
        $this->load->model('Individual_customers_model');
        $this->load->model('Branches_model');
        $this->load->model('Authorized_signitories_model');
        $this->load->model('Chart_of_accounts_model');
        $this->load->model('Currency_model');
        $this->load->model('Accounts_model');
        $this->load->model('Entity_type_model');
        $this->load->library('form_validation');
    }

    public function index()
    {
        $q = urldecode($this->input->get('q', TRUE));
        $start = intval($this->input->get('start'));
        
        if ($q <> '') {
            $config['base_url'] = base_url() . 'corporate_customers/index?q=' . urlencode($q);
            $config['first_url'] = base_url() . 'corporate_customers/index?q=' . urlencode($q);
        } else {
            $config['base_url'] = base_url() . 'corporate_customers/index';
            $config['first_url'] = base_url() . 'corporate_customers/index';
        }

        $config['per_page'] = 10;
        $config['page_query_string'] = TRUE;
        $config['total_rows'] = $this->Corporate_customers_model->total_rows($q);
        $corporate_customers = $this->Corporate_customers_model->get_limit_data($config['per_page'], $start, $q);

        $this->load->library('pagination');
        $this->pagination->initialize($config);

        $data = array(
            'corporate_customers_data' => $corporate_customers,
            'q' => $q,
            'pagination' => $this->pagination->create_links(),
            'total_rows' => $config['total_rows'],
            'start' => $start,
        );
		$this->template->set('title', 'Core Banking |Corporate Customers');
		$this->template->load('template', 'contents' ,'corporate_customers/corporate_customers_list',$data);

    }

    public function read($id) 
    {
        $row = $this->Corporate_customers_model->get_by_id($id);
        if ($row) {
            $data = array(
		'id' => $row->id,
		'EntityName' => $row->EntityName,
		'DateOfIncorporation' => $row->DateOfIncorporation,
		'RegistrationNumber' => $row->RegistrationNumber,
		'EntityType' => $row->EntityType,
		'ClientId' => $row->ClientId,
		'TaxIdentificationNumber' => $row->TaxIdentificationNumber,
		'Country' => $row->Country,
		'Branch' => $row->Branch,
		'Status' => $row->Status,
		'DoesQualify' => $row->DoesQualify,
		'LastUpdatedOn' => $row->LastUpdatedOn,
		'CreatedOn' => $row->CreatedOn,
		'company_certificate' =>  $row->company_certificate,
		'tax_id_doc' =>  $row->tax_id_doc,
		'memo_doc' =>  $row->memo_doc,
	    );


			$this->template->set('title', 'Core Banking |Corporate Customers');
			$this->template->load('template', 'contents' ,'corporate_customers/corporate_customers_read',$data);
        } else {
            $this->session->set_flashdata('message', 'Record Not Found');
            redirect(site_url('corporate_customers'));
        }
    }

    public function create() 
    {
        $data = array(
            'button' => 'Create',
            'action' => site_url('corporate_customers/create_action'),
	    'id' => set_value('id'),
	    'EntityName' => set_value('EntityName'),
	    'DateOfIncorporation' => set_value('DateOfIncorporation'),
	    'RegistrationNumber' => set_value('RegistrationNumber'),
	    'EntityType' => set_value('EntityType'),
	    'ClientId' => set_value('ClientId'),
	    'TaxIdentificationNumber' => set_value('TaxIdentificationNumber'),
	    'Country' => set_value('Country'),
	    'Branch' => set_value('Branch'),
	    'Status' => set_value('Status'),
	    'DoesQualify' => set_value('DoesQualify'),
	    'LastUpdatedOn' => set_value('LastUpdatedOn'),
	    'CreatedOn' => set_value('CreatedOn'),
	    'company_certificate' => set_value('company_certificate'),
	    'tax_id_doc' => set_value('tax_id_doc'),
	    'memo_doc' => set_value('memo_doc'),
	);
		$this->template->set('title', 'Core Banking |Corporate Customers');
		$this->template->load('template', 'contents' ,'corporate_customers/corporate_customers_form',$data);
    }
    
    public function create_action() 
    {
		$dd = $this->Individual_customers_model->count_it();
		$d1 = $this->Corporate_customers_model->count_it();
		$clientid = (2010000)+($dd+$d1+1);
        $this->_rules();

        if ($this->form_validation->run() == FALSE) {
            $this->create();
        } else {
            $data = array(
		'EntityName' => $this->input->post('EntityName',TRUE),
		'DateOfIncorporation' => $this->input->post('DateOfIncorporation',TRUE),
		'RegistrationNumber' => $this->input->post('RegistrationNumber',TRUE),
		'EntityType' => $this->input->post('EntityType',TRUE),
		'ClientId' => $clientid,
		'TaxIdentificationNumber' => $this->input->post('TaxIdentificationNumber',TRUE),
		'Country' => $this->input->post('Country',TRUE),
		'Branch' => $this->input->post('Branch',TRUE),
				'company_certificate' => $this->input->post('company_certificate',TRUE),
				'tax_id_doc' => $this->input->post('tax_id_doc',TRUE),
				'memo_doc' => $this->input->post('memo_doc',TRUE),

	    );
			$logger2 = array(
				'auth_type' => 'corporate_customer_creation',
				'old_data' => json_encode($data),
				'new_data' => json_encode($data),
				'system_date' => $this->session->userdata('system_date'),
				'initiator' => $this->session->userdata('user_id')

			);
            $this->Corporate_customers_model->insert($data);
			auth_logger($logger2);
			$this->toaster->success('Success, Corporate customer was created  pending approval');
//            $this->Corporate_customers_model->insert($data);
//            $this->session->set_flashdata('message', 'Create Record Success');
            redirect(site_url('corporate_customers'));
        }
    }
    
    public function update($id) 
    {
        $row = $this->Corporate_customers_model->get_by_id($id);

        if ($row) {
            $data = array(
                'button' => 'Update',
                'action' => site_url('corporate_customers/update_action'),
		'id' => set_value('id', $row->id),
		'EntityName' => set_value('EntityName', $row->EntityName),
		'DateOfIncorporation' => set_value('DateOfIncorporation', $row->DateOfIncorporation),
		'RegistrationNumber' => set_value('RegistrationNumber', $row->RegistrationNumber),
		'EntityType' => set_value('EntityType', $row->EntityType),
		'ClientId' => set_value('ClientId', $row->ClientId),
		'TaxIdentificationNumber' => set_value('TaxIdentificationNumber', $row->TaxIdentificationNumber),
		'Country' => set_value('Country', $row->Country),
		'Branch' => set_value('Branch', $row->Branch),
				'company_certificate' => set_value('company_certificate', $row->company_certificate),
				'tax_id_doc' => set_value('tax_id_doc', $row->tax_id_doc),
				'memo_doc' => set_value('memo_doc', $row->memo_doc),
	    );
			$this->template->set('title', 'Core Banking |Corporate Customers');
			$this->template->load('template', 'contents' ,'corporate_customers/corporate_customers_form',$data);
        } else {
            $this->session->set_flashdata('message', 'Record Not Found');
            redirect(site_url('corporate_customers'));
        }
    }
    
    public function update_action() 
    {
        $this->_rules();

        if ($this->form_validation->run() == FALSE) {
            $this->update($this->input->post('id', TRUE));
        } else {
            $data = array(
		'EntityName' => $this->input->post('EntityName',TRUE),
		'DateOfIncorporation' => $this->input->post('DateOfIncorporation',TRUE),
		'RegistrationNumber' => $this->input->post('RegistrationNumber',TRUE),
		'EntityType' => $this->input->post('EntityType',TRUE),
		'ClientId' => $this->input->post('ClientId',TRUE),
		'TaxIdentificationNumber' => $this->input->post('TaxIdentificationNumber',TRUE),
		'Country' => $this->input->post('Country',TRUE),
		'Branch' => $this->input->post('Branch',TRUE),
		'company_certificate' => $this->input->post('company_certificate',TRUE),
		'tax_id_doc' => $this->input->post('tax_id_doc',TRUE),
		'memo_doc' => $this->input->post('memo_doc',TRUE),
	    );
			$row = $this->Corporate_customers_model->get_by_id($this->input->post('id', TRUE));

				$data2 = array(

					'EntityName' => $row->EntityName,
					'DateOfIncorporation' => $row->DateOfIncorporation,
					'RegistrationNumber' => $row->RegistrationNumber,
					'EntityType' => $row->EntityType,
					'ClientId' => $row->ClientId,
					'TaxIdentificationNumber' => $row->TaxIdentificationNumber,
					'Country' => $row->Country,
					'Branch' => $row->Branch,

					'company_certificate' =>  $row->company_certificate,
					'tax_id_doc' =>  $row->tax_id_doc,
					'memo_doc' =>  $row->memo_doc,
				);
			$logger2 = array(
				'identity'=>$this->input->post('id', TRUE),
				'auth_type' => 'corporate_customer_update',
				'old_data' => json_encode($data2),
				'new_data' => json_encode($data),
				'system_date' => $this->session->userdata('system_date'),
				'initiator' => $this->session->userdata('user_id')

			);
//            $this->Employees_model->insert($data);
			auth_logger($logger2);
			$this->toaster->success('Success, Corporate customer was edited  pending approval');
//            $this->Corporate_customers_model->update($this->input->post('id', TRUE), $data);
//            $this->session->set_flashdata('message', 'Update Record Success');
            redirect(site_url('corporate_customers'));
        }
    }
    
    public function delete($id) 
    {
        $row = $this->Corporate_customers_model->get_by_id($id);

        if ($row) {
            $this->Corporate_customers_model->delete($id);
            $this->session->set_flashdata('message', 'Delete Record Success');
            redirect(site_url('corporate_customers'));
        } else {
            $this->session->set_flashdata('message', 'Record Not Found');
            redirect(site_url('corporate_customers'));
        }
    }

    public function _rules() 
    {
	$this->form_validation->set_rules('EntityName', 'entityname', 'trim|required');
	$this->form_validation->set_rules('DateOfIncorporation', 'dateofincorporation', 'trim|required');
	$this->form_validation->set_rules('RegistrationNumber', 'registrationnumber', 'trim|required');
	$this->form_validation->set_rules('EntityType', 'entitytype', 'trim|required');

	$this->form_validation->set_rules('TaxIdentificationNumber', 'taxidentificationnumber', 'trim|required');
	$this->form_validation->set_rules('Country', 'country', 'trim|required');
	$this->form_validation->set_rules('Branch', 'branch', 'trim|required');




	$this->form_validation->set_rules('id', 'id', 'trim');
	$this->form_validation->set_error_delimiters('<span class="text-danger">', '</span>');
    }

}

