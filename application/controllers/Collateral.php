<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Collateral extends CI_Controller
{
    function __construct()
    {
        parent::__construct();
        $this->load->model('Collateral_model');
        $this->load->library('form_validation');
    }

    public function index()
    {
        $q = urldecode($this->input->get('q', TRUE));
        $start = intval($this->input->get('start'));
        
        if ($q <> '') {
            $config['base_url'] = base_url() . 'collateral/index.html?q=' . urlencode($q);
            $config['first_url'] = base_url() . 'collateral/index.html?q=' . urlencode($q);
        } else {
            $config['base_url'] = base_url() . 'collateral/index.html';
            $config['first_url'] = base_url() . 'collateral/index.html';
        }

        $config['per_page'] = 10;
        $config['page_query_string'] = TRUE;
        $config['total_rows'] = $this->Collateral_model->total_rows($q);
        $collateral = $this->Collateral_model->get_limit_data($config['per_page'], $start, $q);

        $this->load->library('pagination');
        $this->pagination->initialize($config);

        $data = array(
            'collateral_data' => $collateral,
            'q' => $q,
            'pagination' => $this->pagination->create_links(),
            'total_rows' => $config['total_rows'],
            'start' => $start,
        );
        $this->load->view('collateral/collateral_list', $data);
    }

    public function read($id) 
    {
        $row = $this->Collateral_model->get_by_id($id);
        if ($row) {
            $data = array(
		'collateral_id' => $row->collateral_id,
		'loan_id' => $row->loan_id,
		'collateral_name' => $row->collateral_name,
		'collateral_type' => $row->collateral_type,
		'serial' => $row->serial,
		'estimated_price' => $row->estimated_price,
		'attachement' => $row->attachement,
		'description' => $row->description,
		'date_added' => $row->date_added,
		'added_by' => $row->added_by,
	    );
            $this->load->view('collateral/collateral_read', $data);
        } else {
            $this->session->set_flashdata('message', 'Record Not Found');
            redirect(site_url('collateral'));
        }
    }

    public function create() 
    {
        $data = array(
            'button' => 'Create',
            'action' => site_url('collateral/create_action'),
	    'collateral_id' => set_value('collateral_id'),
	    'loan_id' => set_value('loan_id'),
	    'collateral_name' => set_value('collateral_name'),
	    'collateral_type' => set_value('collateral_type'),
	    'serial' => set_value('serial'),
	    'estimated_price' => set_value('estimated_price'),
	    'attachement' => set_value('attachement'),
	    'description' => set_value('description'),
	    'date_added' => set_value('date_added'),
	    'added_by' => set_value('added_by'),
	);
        $this->load->view('collateral/collateral_form', $data);
    }
    
    public function create_action() 
    {
        $this->_rules();

        if ($this->form_validation->run() == FALSE) {
            $this->create();
        } else {
            $data = array(
		'loan_id' => $this->input->post('loan_id',TRUE),
		'collateral_name' => $this->input->post('collateral_name',TRUE),
		'collateral_type' => $this->input->post('collateral_type',TRUE),
		'serial' => $this->input->post('serial',TRUE),
		'estimated_price' => $this->input->post('estimated_price',TRUE),
		'attachement' => $this->input->post('attachement',TRUE),
		'description' => $this->input->post('description',TRUE),
		'date_added' => $this->input->post('date_added',TRUE),
		'added_by' => $this->input->post('added_by',TRUE),
	    );

            $this->Collateral_model->insert($data);
            $this->session->set_flashdata('message', 'Create Record Success');
            redirect(site_url('collateral'));
        }
    }
    
    public function update($id) 
    {
        $row = $this->Collateral_model->get_by_id($id);

        if ($row) {
            $data = array(
                'button' => 'Update',
                'action' => site_url('collateral/update_action'),
		'collateral_id' => set_value('collateral_id', $row->collateral_id),
		'loan_id' => set_value('loan_id', $row->loan_id),
		'collateral_name' => set_value('collateral_name', $row->collateral_name),
		'collateral_type' => set_value('collateral_type', $row->collateral_type),
		'serial' => set_value('serial', $row->serial),
		'estimated_price' => set_value('estimated_price', $row->estimated_price),
		'attachement' => set_value('attachement', $row->attachement),
		'description' => set_value('description', $row->description),
		'date_added' => set_value('date_added', $row->date_added),
		'added_by' => set_value('added_by', $row->added_by),
	    );
            $this->load->view('collateral/collateral_form', $data);
        } else {
            $this->session->set_flashdata('message', 'Record Not Found');
            redirect(site_url('collateral'));
        }
    }
    
    public function update_action() 
    {
        $this->_rules();

        if ($this->form_validation->run() == FALSE) {
            $this->update($this->input->post('collateral_id', TRUE));
        } else {
            $data = array(
		'loan_id' => $this->input->post('loan_id',TRUE),
		'collateral_name' => $this->input->post('collateral_name',TRUE),
		'collateral_type' => $this->input->post('collateral_type',TRUE),
		'serial' => $this->input->post('serial',TRUE),
		'estimated_price' => $this->input->post('estimated_price',TRUE),
		'attachement' => $this->input->post('attachement',TRUE),
		'description' => $this->input->post('description',TRUE),
		'date_added' => $this->input->post('date_added',TRUE),
		'added_by' => $this->input->post('added_by',TRUE),
	    );

            $this->Collateral_model->update($this->input->post('collateral_id', TRUE), $data);
            $this->session->set_flashdata('message', 'Update Record Success');
            redirect(site_url('collateral'));
        }
    }
    
    public function delete($id) 
    {
        $row = $this->Collateral_model->get_by_id($id);

        if ($row) {
            $this->Collateral_model->delete($id);
            $this->session->set_flashdata('message', 'Delete Record Success');
            redirect(site_url('collateral'));
        } else {
            $this->session->set_flashdata('message', 'Record Not Found');
            redirect(site_url('collateral'));
        }
    }

    public function _rules() 
    {
	$this->form_validation->set_rules('loan_id', 'loan id', 'trim|required');
	$this->form_validation->set_rules('collateral_name', 'collateral name', 'trim|required');
	$this->form_validation->set_rules('collateral_type', 'collateral type', 'trim|required');
	$this->form_validation->set_rules('serial', 'serial', 'trim|required');
	$this->form_validation->set_rules('estimated_price', 'estimated price', 'trim|required|numeric');
	$this->form_validation->set_rules('attachement', 'attachement', 'trim|required');
	$this->form_validation->set_rules('description', 'description', 'trim|required');
	$this->form_validation->set_rules('date_added', 'date added', 'trim|required');
	$this->form_validation->set_rules('added_by', 'added by', 'trim|required');

	$this->form_validation->set_rules('collateral_id', 'collateral_id', 'trim');
	$this->form_validation->set_error_delimiters('<span class="text-danger">', '</span>');
    }

}

/* End of file Collateral.php */
/* Location: ./application/controllers/Collateral.php */
/* Please DO NOT modify this information : */
/* Generated by Harviacode Codeigniter CRUD Generator 2022-11-22 16:53:03 */
/* http://harviacode.com */