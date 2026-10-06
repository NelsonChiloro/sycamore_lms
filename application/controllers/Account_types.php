<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Account_types extends CI_Controller
{
    function __construct()
    {
        parent::__construct();
        $this->load->model('Account_types_model');
        $this->load->library('form_validation');
    }

    public function index()
    {
        $q = urldecode($this->input->get('q', TRUE));
        $start = intval($this->input->get('start'));
        
        if ($q <> '') {
            $config['base_url'] = base_url() . 'account_types/index.html?q=' . urlencode($q);
            $config['first_url'] = base_url() . 'account_types/index.html?q=' . urlencode($q);
        } else {
            $config['base_url'] = base_url() . 'account_types/index.html';
            $config['first_url'] = base_url() . 'account_types/index.html';
        }

        $config['per_page'] = 10;
        $config['page_query_string'] = TRUE;
        $config['total_rows'] = $this->Account_types_model->total_rows($q);
        $account_types = $this->Account_types_model->get_limit_data($config['per_page'], $start, $q);

        $this->load->library('pagination');
        $this->pagination->initialize($config);

        $data = array(
            'account_types_data' => $this->Account_types_model->get_all(),
            'q' => $q,
            'pagination' => $this->pagination->create_links(),
            'total_rows' => $config['total_rows'],
            'start' => $start,
        );
        $this->load->view('admin/header');
        $this->load->view('account_types/account_types_list', $data);
        $this->load->view('admin/footer');
    }

    public function read($id) 
    {
        $row = $this->Account_types_model->get_by_id($id);
        if ($row) {
            $data = array(
		'account_type_id' => $row->account_type_id,
		'account_type_name' => $row->account_type_name,
		'added_by' => $row->added_by,
		'date_added' => $row->date_added,
	    );
            $this->load->view('account_types/account_types_read', $data);
        } else {
            $this->session->set_flashdata('message', 'Record Not Found');
            redirect(site_url('account_types'));
        }
    }

    public function create() 
    {
        $data = array(
            'button' => 'Create',
            'action' => site_url('account_types/create_action'),
	    'account_type_id' => set_value('account_type_id'),
	    'account_type_name' => set_value('account_type_name'),
	    'added_by' => set_value('added_by'),
	    'date_added' => set_value('date_added'),
	);
        $this->load->view('admin/header');
        $this->load->view('account_types/account_types_form', $data);
        $this->load->view('admin/footer');
    }
    
    public function create_action() 
    {
        $this->_rules();

        if ($this->form_validation->run() == FALSE) {
            $this->create();
        } else {
            $data = array(
		'account_type_name' => $this->input->post('account_type_name',TRUE),
		'added_by' =>  $this->session->userdata('user_id'),

	    );

            $this->Account_types_model->insert($data);
            $this->session->set_flashdata('message', 'Create Record Success');
            redirect(site_url('account_types'));
        }
    }
    
    public function update($id) 
    {
        $row = $this->Account_types_model->get_by_id($id);

        if ($row) {
            $data = array(
                'button' => 'Update',
                'action' => site_url('account_types/update_action'),
		'account_type_id' => set_value('account_type_id', $row->account_type_id),
		'account_type_name' => set_value('account_type_name', $row->account_type_name),
		'added_by' => set_value('added_by', $row->added_by),
		'date_added' => set_value('date_added', $row->date_added),
	    );
            $this->load->view('admin/header');
            $this->load->view('account_types/account_types_form', $data);
            $this->load->view('admin/footer');
        } else {
            $this->session->set_flashdata('message', 'Record Not Found');
            redirect(site_url('account_types'));
        }
    }
    
    public function update_action() 
    {
        $this->_rules();

        if ($this->form_validation->run() == FALSE) {
            $this->update($this->input->post('account_type_id', TRUE));
        } else {
            $data = array(
		'account_type_name' => $this->input->post('account_type_name',TRUE),
		'added_by' => $this->input->post('added_by',TRUE),
		'date_added' => $this->input->post('date_added',TRUE),
	    );

            $this->Account_types_model->update($this->input->post('account_type_id', TRUE), $data);
            $this->session->set_flashdata('message', 'Update Record Success');
            redirect(site_url('account_types'));
        }
    }
    
    public function delete($id) 
    {
        $row = $this->Account_types_model->get_by_id($id);

        if ($row) {
            $this->Account_types_model->delete($id);
            $this->session->set_flashdata('message', 'Delete Record Success');
            redirect(site_url('account_types'));
        } else {
            $this->session->set_flashdata('message', 'Record Not Found');
            redirect(site_url('account_types'));
        }
    }

    public function _rules() 
    {
	$this->form_validation->set_rules('account_type_name', 'account type name', 'trim|required');


	$this->form_validation->set_rules('account_type_id', 'account_type_id', 'trim');
	$this->form_validation->set_error_delimiters('<span class="text-danger">', '</span>');
    }

}
