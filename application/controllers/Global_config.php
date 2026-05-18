<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Global_config extends CI_Controller
{
    function __construct()
    {
        parent::__construct();
        $this->load->model('Global_config_model');
        $this->load->library('form_validation');
    }

    public function index()
    {
        $q = urldecode($this->input->get('q', TRUE));
        $start = intval($this->input->get('start'));
        
        if ($q <> '') {
            $config['base_url'] = base_url() . 'global_config/index.html?q=' . urlencode($q);
            $config['first_url'] = base_url() . 'global_config/index.html?q=' . urlencode($q);
        } else {
            $config['base_url'] = base_url() . 'global_config/index.html';
            $config['first_url'] = base_url() . 'global_config/index.html';
        }

        $config['per_page'] = 10;
        $config['page_query_string'] = TRUE;
        $config['total_rows'] = $this->Global_config_model->total_rows($q);
        $global_config = $this->Global_config_model->get_limit_data($config['per_page'], $start, $q);

        $this->load->library('pagination');
        $this->pagination->initialize($config);

        $data = array(
            'global_config_data' => $global_config,
            'q' => $q,
            'pagination' => $this->pagination->create_links(),
            'total_rows' => $config['total_rows'],
            'start' => $start,
        );
        $this->load->view('global_config/global_config_list', $data);
    }

    public function read($id) 
    {
        $row = $this->Global_config_model->get_by_id($id);
        if ($row) {
            $data = array(
		'id' => $row->id,
		'repayment_automatic' => $row->repayment_automatic,
		'cron_path' => $row->cron_path,
	    );
            $this->load->view('global_config/global_config_read', $data);
        } else {
            $this->session->set_flashdata('message', 'Record Not Found');
            redirect(site_url('global_config'));
        }
    }

    public function create() 
    {
        $data = array(
            'button' => 'Create',
            'action' => site_url('global_config/create_action'),
	    'id' => set_value('id'),
	    'repayment_automatic' => set_value('repayment_automatic'),
	    'cron_path' => set_value('cron_path'),
	);
        $this->load->view('global_config/global_config_form', $data);
    }
    
    public function create_action() 
    {
        $this->_rules();

        if ($this->form_validation->run() == FALSE) {
            $this->create();
        } else {
            $data = array(
		'repayment_automatic' => $this->input->post('repayment_automatic',TRUE),
		'cron_path' => $this->input->post('cron_path',TRUE),
	    );

            $this->Global_config_model->insert($data);
            $this->session->set_flashdata('message', 'Create Record Success');
            redirect(site_url('global_config'));
        }
    }
    
    public function update($id) 
    {
        $row = $this->Global_config_model->get_by_id($id);

        if ($row) {
            $data = array(
                'button' => 'Update',
                'action' => site_url('global_config/update_action'),
		'id' => set_value('id', $row->id),
		'repayment_automatic' => set_value('repayment_automatic', $row->repayment_automatic),
		'cron_path' => set_value('cron_path', $row->cron_path),
	    );
			$menu_toggle['toggles'] = 52;
			$this->load->view('admin/header', $menu_toggle);
            $this->load->view('global_config/global_config_form', $data);
			$this->load->view('admin/footer');
        } else {
            $this->session->set_flashdata('message', 'Record Not Found');
            redirect($_SERVER['HTTP_REFERER']);
        }
    }
    
    public function update_action() 
    {
        $this->_rules();

        if ($this->form_validation->run() == FALSE) {
            $this->update($this->input->post('id', TRUE));
        } else {
            $data = array(
		'repayment_automatic' => $this->input->post('repayment_automatic',TRUE),
		'cron_path' => $this->input->post('cron_path',TRUE),
	    );

            $this->Global_config_model->update($this->input->post('id', TRUE), $data);
            $this->toaster->success('Update  Success');
			redirect($_SERVER['HTTP_REFERER']);
        }
    }
    
    public function delete($id) 
    {
        $row = $this->Global_config_model->get_by_id($id);

        if ($row) {
            $this->Global_config_model->delete($id);
            $this->session->set_flashdata('message', 'Delete Record Success');
            redirect(site_url('global_config'));
        } else {
            $this->session->set_flashdata('message', 'Record Not Found');
            redirect(site_url('global_config'));
        }
    }

    public function _rules() 
    {
	$this->form_validation->set_rules('repayment_automatic', 'repayment automatic', 'trim|required');
	$this->form_validation->set_rules('cron_path', 'cron path', 'trim|required');

	$this->form_validation->set_rules('id', 'id', 'trim');
	$this->form_validation->set_error_delimiters('<span class="text-danger">', '</span>');
    }

}


