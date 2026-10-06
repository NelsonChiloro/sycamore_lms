<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Internal_accounts extends CI_Controller
{
    function __construct()
    {
        parent::__construct();
        $this->load->model('Internal_accounts_model');
        $this->load->model('Account_model');
		$this->load->model('User_access_model');
        $this->load->model('Tellering_model');
        $this->load->library('form_validation');
    }

    public function index()
    {


        $data = array(
            'internal_accounts_data' => $this->Internal_accounts_model->get_all(),

        );
		$menu_toggle['toggles'] = 47;
		$this->load->view('admin/header', $menu_toggle);
        $this->load->view('internal_accounts/internal_accounts_list', $data);
		$this->load->view('admin/footer');
    }
    public function todelete()
    {


        $data = array(
            'internal_accounts_data' => $this->Internal_accounts_model->get_all(),

        );
		$menu_toggle['toggles'] = 47;
		$this->load->view('admin/header', $menu_toggle);
        $this->load->view('internal_accounts/internal_accounts_delete', $data);
		$this->load->view('admin/footer');
    }
    public function configure_accounts()
    {


        $data = array(
            'teller' => $this->Account_model->get_teller_accounts(),
            'all_cash' => $this->Account_model->get_cash_accounts(),
            'all_user' => $this->User_access_model->get_all(),

        );
        $this->load->view('admin/header');
        $this->load->view('internal_accounts/tellering', $data);
		$this->load->view('admin/footer');
    }
    public function configure_income()
    {


        $data = array(
            'teller' => $this->Account_model->get_teller_accounts(),
            'all_cash' => $this->Account_model->get_internalaccounts(),
            'all_user' => $this->User_access_model->get_all(),

        );
        $this->load->view('admin/header');
        $this->load->view('internal_accounts/income_config', $data);
		$this->load->view('admin/footer');
    }

    public function read($id) 
    {
        $row = $this->Internal_accounts_model->get_by_id($id);
        if ($row) {
            $data = array(
		'internal_account_id' => $row->internal_account_id,
		'account_name' => $row->account_name,
		'is_cash_account' => $row->is_cash_account,
		'adde_by' => $row->adde_by,
		'date_created' => $row->date_created,
	    );
            $this->load->view('internal_accounts/internal_accounts_read', $data);
        } else {
            $this->session->set_flashdata('message', 'Record Not Found');
            redirect(site_url('internal_accounts'));
        }
    }

    public function create() 
    {
        $data = array(
            'button' => 'Create',
            'action' => site_url('internal_accounts/create_action'),
	    'internal_account_id' => set_value('internal_account_id'),
	    'account_name' => set_value('account_name'),
	    'is_cash_account' => set_value('is_cash_account'),
	    'adde_by' => set_value('adde_by'),
	    'date_created' => set_value('date_created'),
	    'account_desc' => set_value('account_desc'),
	);
		$this->load->view('admin/header');
        $this->load->view('internal_accounts/create_form', $data);
		$this->load->view('admin/footer');
    }
    
    public function create_action() 
    {
        $this->_rules();

        if ($this->form_validation->run() == FALSE) {
            $this->create();
        } else {
            $data = array(
		'account_name' => $this->input->post('account_name',TRUE),
		'is_cash_account' => $this->input->post('is_cash_account',TRUE),
		'account_desc' => $this->input->post('account_desc',TRUE),
		'adde_by' => $this->session->userdata('user_id')

	    );

          $insert_id =  $this->Internal_accounts_model->insert($data);

			$data = array(
				'client_id' => $insert_id,
				'account_number' => $this->input->post('account_number',TRUE),
				'balance' => 0,
				'account_type' => 3,
				'account_type_product' => $insert_id,
				'is_teller' =>  $this->input->post('is_cashier_account',TRUE),
				'added_by' => $this->session->userdata('user_id'),

			);

			$this->Account_model->insert($data);
            $this->session->set_flashdata('message', 'Create Record Success');
            redirect(site_url('internal_accounts'));
        }
    }
    
    public function update($id) 
    {
        $row = $this->Internal_accounts_model->get_by_id($id);

        if ($row) {
            $data = array(
                'button' => 'Update',
                'action' => site_url('internal_accounts/update_action'),
		'internal_account_id' => set_value('internal_account_id', $row->internal_account_id),
		'account_name' => set_value('account_name', $row->account_name),
		'is_cash_account' => set_value('is_cash_account', $row->is_cash_account),
		'adde_by' => set_value('adde_by', $row->adde_by),
		'date_created' => set_value('date_created', $row->date_created),
	    );
            $this->load->view('internal_accounts/internal_accounts_form', $data);
        } else {
            $this->session->set_flashdata('message', 'Record Not Found');
            redirect(site_url('internal_accounts'));
        }
    }
    function update_vault(){

    	$this->Account_model->update_vault($this->input->post('account_number'));
    	$this->toaster->success('The vault assigned');
    	redirect($_SERVER['HTTP_REFERER']);
	}
    
    public function update_action() 
    {
        $this->_rules();

        if ($this->form_validation->run() == FALSE) {
            $this->update($this->input->post('internal_account_id', TRUE));
        } else {
            $data = array(
		'account_name' => $this->input->post('account_name',TRUE),
		'is_cash_account' => $this->input->post('is_cash_account',TRUE),
		'adde_by' => $this->input->post('adde_by',TRUE),
		'date_created' => $this->input->post('date_created',TRUE),
	    );

            $this->Internal_accounts_model->update($this->input->post('internal_account_id', TRUE), $data);
            $this->session->set_flashdata('message', 'Update Record Success');
            redirect(site_url('internal_accounts'));
        }
    }
    
    public function delete($id) 
    {
        $row = $this->Internal_accounts_model->get_by_id($id);

        if ($row) {
            $this->Internal_accounts_model->delete($id);
            $this->session->set_flashdata('message', 'Delete Record Success');
            redirect(site_url('internal_accounts'));
        } else {
            $this->session->set_flashdata('message', 'Record Not Found');
            redirect(site_url('internal_accounts'));
        }
    }

    public function _rules() 
    {
	$this->form_validation->set_rules('account_name', 'account name', 'trim|required');
	$this->form_validation->set_rules('is_cash_account', 'is cash account', 'trim|required');


	$this->form_validation->set_rules('internal_account_id', 'internal_account_id', 'trim');
	$this->form_validation->set_error_delimiters('<span class="text-danger">', '</span>');
    }

}


