<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Financial_year extends CI_Controller
{
    function __construct()
    {
        parent::__construct();
        $this->load->model('Financial_year_model');
        $this->load->library('form_validation');
    }

    public function index()
    {
        $q = urldecode($this->input->get('q', TRUE));
        $start = intval($this->input->get('start'));
        
        if ($q <> '') {
            $config['base_url'] = base_url() . 'financial_year/index.html?q=' . urlencode($q);
            $config['first_url'] = base_url() . 'financial_year/index.html?q=' . urlencode($q);
        } else {
            $config['base_url'] = base_url() . 'financial_year/index.html';
            $config['first_url'] = base_url() . 'financial_year/index.html';
        }

        $config['per_page'] = 10;
        $config['page_query_string'] = TRUE;
        $config['total_rows'] = $this->Financial_year_model->total_rows($q);
        $financial_year = $this->Financial_year_model->get_limit_data($config['per_page'], $start, $q);

        $this->load->library('pagination');
        $this->pagination->initialize($config);

        $data = array(
            'financial_year_data' => $financial_year,
            'q' => $q,
            'pagination' => $this->pagination->create_links(),
            'total_rows' => $config['total_rows'],
            'start' => $start,
        );
        $this->load->view('financial_year/financial_year_list', $data);
    }

    public function read($id) 
    {
        $row = $this->Financial_year_model->get_by_id($id);
        if ($row) {
            $data = array(
		'fyid' => $row->fyid,
		'year_start' => $row->year_start,
		'year_end' => $row->year_end,
		'status' => $row->status,
	    );
            $this->load->view('financial_year/financial_year_read', $data);
        } else {
            $this->session->set_flashdata('message', 'Record Not Found');
            redirect(site_url('financial_year'));
        }
    }

    public function create() 
    {
        $data = array(
            'button' => 'Create',
            'action' => site_url('financial_year/create_action'),
	    'fyid' => set_value('fyid'),
	    'year_start' => set_value('year_start'),
	    'year_end' => set_value('year_end'),
	    'status' => set_value('status'),
	);
        $this->load->view('financial_year/financial_year_form', $data);
    }
    
    public function create_action() 
    {
        $this->_rules();

        if ($this->form_validation->run() == FALSE) {
            $this->create();
        } else {
            $data = array(
		'year_start' => $this->input->post('year_start',TRUE),
		'year_end' => $this->input->post('year_end',TRUE),
		'status' => $this->input->post('status',TRUE),
	    );

            $this->Financial_year_model->insert($data);
            $this->session->set_flashdata('message', 'Create Record Success');
            redirect(site_url('financial_year'));
        }
    }
    
    public function update($id) 
    {
        $row = $this->Financial_year_model->get_by_id($id);

        if ($row) {
            $data = array(
                'button' => 'Update',
                'action' => site_url('financial_year/update_action'),
		'fyid' => set_value('fyid', $row->fyid),
		'year_start' => set_value('year_start', $row->year_start),
		'year_end' => set_value('year_end', $row->year_end),
		'status' => set_value('status', $row->status),
	    );
            $this->load->view('financial_year/financial_year_form', $data);
        } else {
            $this->session->set_flashdata('message', 'Record Not Found');
            redirect(site_url('financial_year'));
        }
    }
    
    public function update_action() 
    {
        $this->_rules();

        if ($this->form_validation->run() == FALSE) {
            $this->update($this->input->post('fyid', TRUE));
        } else {
            $data = array(
		'year_start' => $this->input->post('year_start',TRUE),
		'year_end' => $this->input->post('year_end',TRUE),
		'status' => $this->input->post('status',TRUE),
	    );

            $this->Financial_year_model->update($this->input->post('fyid', TRUE), $data);
            $this->session->set_flashdata('message', 'Update Record Success');
            redirect(site_url('financial_year'));
        }
    }
    
    public function delete($id) 
    {
        $row = $this->Financial_year_model->get_by_id($id);

        if ($row) {
            $this->Financial_year_model->delete($id);
            $this->session->set_flashdata('message', 'Delete Record Success');
            redirect(site_url('financial_year'));
        } else {
            $this->session->set_flashdata('message', 'Record Not Found');
            redirect(site_url('financial_year'));
        }
    }

    public function _rules() 
    {
	$this->form_validation->set_rules('year_start', 'year start', 'trim|required');
	$this->form_validation->set_rules('year_end', 'year end', 'trim|required');
	$this->form_validation->set_rules('status', 'status', 'trim|required');

	$this->form_validation->set_rules('fyid', 'fyid', 'trim');
	$this->form_validation->set_error_delimiters('<span class="text-danger">', '</span>');
    }

}

/* End of file Financial_year.php */
/* Location: ./application/controllers/Financial_year.php */
/* Please DO NOT modify this information : */
/* Generated by Harviacode Codeigniter CRUD Generator 2021-10-14 06:27:32 */
/* http://harviacode.com */