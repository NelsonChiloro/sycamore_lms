<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Savings_products extends CI_Controller
{
    function __construct()
    {
        parent::__construct();
        $this->load->model('Savings_products_model');
        $this->load->library('form_validation');
    }

    public function index()
    {
        $q = urldecode($this->input->get('q', TRUE));


        $data = array(
            'savings_products_data' => $this->Savings_products_model->get_all(),

        );


		$menu_toggle['toggles'] = 46;
		$this->load->view('admin/header', $menu_toggle);
		$this->load->view('savings_products/savings_products_list', $data);
		$this->load->view('admin/footer');
    }
    public function edit()
    {
        $q = urldecode($this->input->get('q', TRUE));


        $data = array(
            'savings_products_data' => $this->Savings_products_model->get_all(),

        );


		$menu_toggle['toggles'] = 46;
		$this->load->view('admin/header', $menu_toggle);
		$this->load->view('savings_products/savings_products_edit', $data);
		$this->load->view('admin/footer');
    }
    public function to_delete()
    {
        $q = urldecode($this->input->get('q', TRUE));


        $data = array(
            'savings_products_data' => $this->Savings_products_model->get_all(),

        );


		$menu_toggle['toggles'] = 46;
		$this->load->view('admin/header', $menu_toggle);
		$this->load->view('savings_products/savings_products_delete', $data);
		$this->load->view('admin/footer');
    }

    public function read($id) 
    {
        $row = $this->Savings_products_model->get_by_id($id);
        if ($row) {
            $data = array(
		'saviings_product_id' => $row->saviings_product_id,
		'name' => $row->name,
		'added_by' => $row->added_by,
		'date_added' => $row->date_added,
		'interest_per_anum' => $row->interest_per_anum,
		'interest_method' => $row->interest_method,
		'interest_posting_frequency' => $row->interest_posting_frequency,
		'when_to_post' => $row->when_to_post,
		'minimum_balance_for_interest' => $row->minimum_balance_for_interest,
		'minimum_balance_withdrawal' => $row->minimum_balance_withdrawal,
		'overdraft' => $row->overdraft,
	    );
            $this->load->view('savings_products/savings_products_read', $data);
        } else {
            $this->session->set_flashdata('message', 'Record Not Found');
            redirect(site_url('savings_products'));
        }
    }

    public function create() 
    {
        $data = array(
            'button' => 'Create',
            'action' => site_url('savings_products/create_action'),
	    'saviings_product_id' => set_value('saviings_product_id'),
	    'name' => set_value('name'),
	    'added_by' => set_value('added_by'),
	    'date_added' => set_value('date_added'),
	    'interest_per_anum' => set_value('interest_per_anum'),
	    'interest_method' => set_value('interest_method'),
	    'interest_posting_frequency' => set_value('interest_posting_frequency'),
	    'when_to_post' => set_value('when_to_post'),
	    'minimum_balance_for_interest' => set_value('minimum_balance_for_interest'),
	    'minimum_balance_withdrawal' => set_value('minimum_balance_withdrawal'),
	    'overdraft' => set_value('overdraft'),
	);
		$menu_toggle['toggles'] = 46;
		$this->load->view('admin/header', $menu_toggle);
        $this->load->view('savings_products/savings_products_form', $data);
		$this->load->view('admin/footer');
    }
    
    public function create_action() 
    {
        $this->_rules();

        if ($this->form_validation->run() == FALSE) {
            $this->create();
        } else {
            $data = array(
		'name' => $this->input->post('name',TRUE),
		'added_by' => $this->session->userdata('user_id'),

		'interest_per_anum' => $this->input->post('interest_per_anum',TRUE),
		'interest_method' => $this->input->post('interest_method',TRUE),
		'interest_posting_frequency' => $this->input->post('interest_posting_frequency',TRUE),
		'when_to_post' => $this->input->post('when_to_post',TRUE),
		'minimum_balance_for_interest' => $this->input->post('minimum_balance_for_interest',TRUE),
		'minimum_balance_withdrawal' => $this->input->post('minimum_balance_withdrawal',TRUE),
		'overdraft' => $this->input->post('overdraft',TRUE),
	    );

            $this->Savings_products_model->insert($data);
            $this->session->set_flashdata('message', 'Create Record Success');
            redirect(site_url('savings_products'));
        }
    }
    
    public function update($id) 
    {
        $row = $this->Savings_products_model->get_by_id($id);

        if ($row) {
            $data = array(
                'button' => 'Update',
                'action' => site_url('savings_products/update_action'),
		'saviings_product_id' => set_value('saviings_product_id', $row->saviings_product_id),
		'name' => set_value('name', $row->name),
		'added_by' => set_value('added_by', $row->added_by),
		'date_added' => set_value('date_added', $row->date_added),
		'interest_per_anum' => set_value('interest_per_anum', $row->interest_per_anum),
		'interest_method' => set_value('interest_method', $row->interest_method),
		'interest_posting_frequency' => set_value('interest_posting_frequency', $row->interest_posting_frequency),
		'when_to_post' => set_value('when_to_post', $row->when_to_post),
		'minimum_balance_for_interest' => set_value('minimum_balance_for_interest', $row->minimum_balance_for_interest),
		'minimum_balance_withdrawal' => set_value('minimum_balance_withdrawal', $row->minimum_balance_withdrawal),
		'overdraft' => set_value('overdraft', $row->overdraft),
	    );
			$menu_toggle['toggles'] = 46;
			$this->load->view('admin/header', $menu_toggle);
            $this->load->view('savings_products/savings_products_form', $data);
			$this->load->view('admin/footer');
        } else {
            $this->session->set_flashdata('message', 'Record Not Found');
            redirect(site_url('savings_products'));
        }
    }
    
    public function update_action() 
    {
        $this->_rules();

        if ($this->form_validation->run() == FALSE) {
            $this->update($this->input->post('saviings_product_id', TRUE));
        } else {
            $data = array(
		'name' => $this->input->post('name',TRUE),
		'added_by' => $this->input->post('added_by',TRUE),
		'date_added' => $this->input->post('date_added',TRUE),
		'interest_per_anum' => $this->input->post('interest_per_anum',TRUE),
		'interest_method' => $this->input->post('interest_method',TRUE),
		'interest_posting_frequency' => $this->input->post('interest_posting_frequency',TRUE),
		'when_to_post' => $this->input->post('when_to_post',TRUE),
		'minimum_balance_for_interest' => $this->input->post('minimum_balance_for_interest',TRUE),
		'minimum_balance_withdrawal' => $this->input->post('minimum_balance_withdrawal',TRUE),
		'overdraft' => $this->input->post('overdraft',TRUE),
	    );

            $this->Savings_products_model->update($this->input->post('saviings_product_id', TRUE), $data);
            $this->session->set_flashdata('message', 'Update Record Success');
            redirect(site_url('savings_products'));
        }
    }
    
    public function delete($id) 
    {
        $row = $this->Savings_products_model->get_by_id($id);

        if ($row) {
            $this->Savings_products_model->delete($id);
            $this->session->set_flashdata('message', 'Delete Record Success');
            redirect(site_url('savings_products'));
        } else {
            $this->session->set_flashdata('message', 'Record Not Found');
            redirect(site_url('savings_products'));
        }
    }

    public function _rules() 
    {
	$this->form_validation->set_rules('name', 'name', 'trim|required');

	$this->form_validation->set_rules('overdraft', 'overdraft', 'trim|required');

	$this->form_validation->set_rules('saviings_product_id', 'saviings_product_id', 'trim');
	$this->form_validation->set_error_delimiters('<span class="text-danger">', '</span>');
    }

}

