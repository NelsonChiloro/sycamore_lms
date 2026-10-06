<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Sdate_logger extends CI_Controller
{
    function __construct()
    {
        parent::__construct();
        $this->load->model('Sdate_logger_model');
        $this->load->library('form_validation');
    }

    public function index()
    {
        $q = urldecode($this->input->get('q', TRUE));
        $start = intval($this->input->get('start'));
        
        if ($q <> '') {
            $config['base_url'] = base_url() . 'sdate_logger/index.html?q=' . urlencode($q);
            $config['first_url'] = base_url() . 'sdate_logger/index.html?q=' . urlencode($q);
        } else {
            $config['base_url'] = base_url() . 'sdate_logger/index.html';
            $config['first_url'] = base_url() . 'sdate_logger/index.html';
        }

        $config['per_page'] = 10;
        $config['page_query_string'] = TRUE;
        $config['total_rows'] = $this->Sdate_logger_model->total_rows($q);
        $sdate_logger = $this->Sdate_logger_model->get_limit_data($config['per_page'], $start, $q);

        $this->load->library('pagination');
        $this->pagination->initialize($config);

        $data = array(
            'sdate_logger_data' => $sdate_logger,
            'q' => $q,
            'pagination' => $this->pagination->create_links(),
            'total_rows' => $config['total_rows'],
            'start' => $start,
        );
        $this->load->view('sdate_logger/sdate_logger_list', $data);
    }

    public function read($id) 
    {
        $row = $this->Sdate_logger_model->get_by_id($id);
        if ($row) {
            $data = array(
		'id' => $row->id,
		'table_name' => $row->table_name,
		'crud' => $row->crud,
		'server_time' => $row->server_time,
		'system_date' => $row->system_date,
	    );
            $this->load->view('sdate_logger/sdate_logger_read', $data);
        } else {
            $this->session->set_flashdata('message', 'Record Not Found');
            redirect(site_url('sdate_logger'));
        }
    }

    public function create() 
    {
        $data = array(
            'button' => 'Create',
            'action' => site_url('sdate_logger/create_action'),
	    'id' => set_value('id'),
	    'table_name' => set_value('table_name'),
	    'crud' => set_value('crud'),
	    'server_time' => set_value('server_time'),
	    'system_date' => set_value('system_date'),
	);
        $this->load->view('sdate_logger/sdate_logger_form', $data);
    }
    
    public function create_action() 
    {
        $this->_rules();

        if ($this->form_validation->run() == FALSE) {
            $this->create();
        } else {
            $data = array(
		'table_name' => $this->input->post('table_name',TRUE),
		'crud' => $this->input->post('crud',TRUE),
		'server_time' => $this->input->post('server_time',TRUE),
		'system_date' => $this->input->post('system_date',TRUE),
	    );

            $this->Sdate_logger_model->insert($data);
            $this->session->set_flashdata('message', 'Create Record Success');
            redirect(site_url('sdate_logger'));
        }
    }
    
    public function update($id) 
    {
        $row = $this->Sdate_logger_model->get_by_id($id);

        if ($row) {
            $data = array(
                'button' => 'Update',
                'action' => site_url('sdate_logger/update_action'),
		'id' => set_value('id', $row->id),
		'table_name' => set_value('table_name', $row->table_name),
		'crud' => set_value('crud', $row->crud),
		'server_time' => set_value('server_time', $row->server_time),
		'system_date' => set_value('system_date', $row->system_date),
	    );
            $this->load->view('sdate_logger/sdate_logger_form', $data);
        } else {
            $this->session->set_flashdata('message', 'Record Not Found');
            redirect(site_url('sdate_logger'));
        }
    }
    
    public function update_action() 
    {
        $this->_rules();

        if ($this->form_validation->run() == FALSE) {
            $this->update($this->input->post('id', TRUE));
        } else {
            $data = array(
		'table_name' => $this->input->post('table_name',TRUE),
		'crud' => $this->input->post('crud',TRUE),
		'server_time' => $this->input->post('server_time',TRUE),
		'system_date' => $this->input->post('system_date',TRUE),
	    );

            $this->Sdate_logger_model->update($this->input->post('id', TRUE), $data);
            $this->session->set_flashdata('message', 'Update Record Success');
            redirect(site_url('sdate_logger'));
        }
    }
    
    public function delete($id) 
    {
        $row = $this->Sdate_logger_model->get_by_id($id);

        if ($row) {
            $this->Sdate_logger_model->delete($id);
            $this->session->set_flashdata('message', 'Delete Record Success');
            redirect(site_url('sdate_logger'));
        } else {
            $this->session->set_flashdata('message', 'Record Not Found');
            redirect(site_url('sdate_logger'));
        }
    }

    public function _rules() 
    {
	$this->form_validation->set_rules('table_name', 'table name', 'trim|required');
	$this->form_validation->set_rules('crud', 'crud', 'trim|required');
	$this->form_validation->set_rules('server_time', 'server time', 'trim|required');
	$this->form_validation->set_rules('system_date', 'system date', 'trim|required');

	$this->form_validation->set_rules('id', 'id', 'trim');
	$this->form_validation->set_error_delimiters('<span class="text-danger">', '</span>');
    }

}

/* End of file Sdate_logger.php */
/* Location: ./application/controllers/Sdate_logger.php */
/* Please DO NOT modify this information : */
/* Generated by Harviacode Codeigniter CRUD Generator 2021-10-14 06:27:32 */
/* http://harviacode.com */