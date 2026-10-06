<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Currency extends CI_Controller
{
    function __construct()
    {
        parent::__construct();
        $this->load->model('Currency_model');
        $this->load->library('form_validation');
    }

    public function index()
    {
        $q = urldecode($this->input->get('q', TRUE));
        $start = intval($this->input->get('start'));
        
        if ($q <> '') {
            $config['base_url'] = base_url() . 'currency/index.html?q=' . urlencode($q);
            $config['first_url'] = base_url() . 'currency/index.html?q=' . urlencode($q);
        } else {
            $config['base_url'] = base_url() . 'currency/index.html';
            $config['first_url'] = base_url() . 'currency/index.html';
        }

        $config['per_page'] = 10;
        $config['page_query_string'] = TRUE;
        $config['total_rows'] = $this->Currency_model->total_rows($q);
        $currency = $this->Currency_model->get_limit_data($config['per_page'], $start, $q);

        $this->load->library('pagination');
        $this->pagination->initialize($config);

        $data = array(
            'currency_data' => $currency,
            'q' => $q,
            'pagination' => $this->pagination->create_links(),
            'total_rows' => $config['total_rows'],
            'start' => $start,
        );
        $this->load->view('currency/currency_list', $data);
    }

    public function read($id) 
    {
        $row = $this->Currency_model->get_by_id($id);
        if ($row) {
            $data = array(
		'id' => $row->id,
		'name' => $row->name,
		'symbol' => $row->symbol,
		'code' => $row->code,
		'is_local' => $row->is_local,
	    );
            $this->load->view('currency/currency_read', $data);
        } else {
            $this->session->set_flashdata('message', 'Record Not Found');
            redirect(site_url('currency'));
        }
    }

    public function create() 
    {
        $data = array(
            'button' => 'Create',
            'action' => site_url('currency/create_action'),
	    'id' => set_value('id'),
	    'name' => set_value('name'),
	    'symbol' => set_value('symbol'),
	    'code' => set_value('code'),
	    'is_local' => set_value('is_local'),
	);
        $this->load->view('currency/currency_form', $data);
    }
    
    public function create_action() 
    {
        $this->_rules();

        if ($this->form_validation->run() == FALSE) {
            $this->create();
        } else {
            $data = array(
		'name' => $this->input->post('name',TRUE),
		'symbol' => $this->input->post('symbol',TRUE),
		'code' => $this->input->post('code',TRUE),
		'is_local' => $this->input->post('is_local',TRUE),
	    );

            $this->Currency_model->insert($data);
            $this->session->set_flashdata('message', 'Create Record Success');
            redirect(site_url('currency'));
        }
    }
    
    public function update($id) 
    {
        $row = $this->Currency_model->get_by_id($id);

        if ($row) {
            $data = array(
                'button' => 'Update',
                'action' => site_url('currency/update_action'),
		'id' => set_value('id', $row->id),
		'name' => set_value('name', $row->name),
		'symbol' => set_value('symbol', $row->symbol),
		'code' => set_value('code', $row->code),
		'is_local' => set_value('is_local', $row->is_local),
	    );
            $this->load->view('currency/currency_form', $data);
        } else {
            $this->session->set_flashdata('message', 'Record Not Found');
            redirect(site_url('currency'));
        }
    }
    
    public function update_action() 
    {
        $this->_rules();

        if ($this->form_validation->run() == FALSE) {
            $this->update($this->input->post('id', TRUE));
        } else {
            $data = array(
		'name' => $this->input->post('name',TRUE),
		'symbol' => $this->input->post('symbol',TRUE),
		'code' => $this->input->post('code',TRUE),
		'is_local' => $this->input->post('is_local',TRUE),
	    );

            $this->Currency_model->update($this->input->post('id', TRUE), $data);
            $this->session->set_flashdata('message', 'Update Record Success');
            redirect(site_url('currency'));
        }
    }
    
    public function delete($id) 
    {
        $row = $this->Currency_model->get_by_id($id);

        if ($row) {
            $this->Currency_model->delete($id);
            $this->session->set_flashdata('message', 'Delete Record Success');
            redirect(site_url('currency'));
        } else {
            $this->session->set_flashdata('message', 'Record Not Found');
            redirect(site_url('currency'));
        }
    }

    public function _rules() 
    {
	$this->form_validation->set_rules('name', 'name', 'trim|required');
	$this->form_validation->set_rules('symbol', 'symbol', 'trim|required');
	$this->form_validation->set_rules('code', 'code', 'trim|required');
	$this->form_validation->set_rules('is_local', 'is local', 'trim|required');

	$this->form_validation->set_rules('id', 'id', 'trim');
	$this->form_validation->set_error_delimiters('<span class="text-danger">', '</span>');
    }

}

/* End of file Currency.php */
/* Location: ./application/controllers/Currency.php */
/* Please DO NOT modify this information : */
/* Generated by Harviacode Codeigniter CRUD Generator 2021-10-14 06:27:32 */
/* http://harviacode.com */