<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Group_loan_tracker extends CI_Controller
{
    function __construct()
    {
        parent::__construct();
        $this->load->model('Group_loan_tracker_model');
        $this->load->library('form_validation');
    }

    public function index()
    {
        $q = urldecode($this->input->get('q', TRUE));
        $start = intval($this->input->get('start'));
        
        if ($q <> '') {
            $config['base_url'] = base_url() . 'group_loan_tracker/index.html?q=' . urlencode($q);
            $config['first_url'] = base_url() . 'group_loan_tracker/index.html?q=' . urlencode($q);
        } else {
            $config['base_url'] = base_url() . 'group_loan_tracker/index.html';
            $config['first_url'] = base_url() . 'group_loan_tracker/index.html';
        }

        $config['per_page'] = 10;
        $config['page_query_string'] = TRUE;
        $config['total_rows'] = $this->Group_loan_tracker_model->total_rows($q);
        $group_loan_tracker = $this->Group_loan_tracker_model->get_limit_data($config['per_page'], $start, $q);

        $this->load->library('pagination');
        $this->pagination->initialize($config);

        $data = array(
            'group_loan_tracker_data' => $group_loan_tracker,
            'q' => $q,
            'pagination' => $this->pagination->create_links(),
            'total_rows' => $config['total_rows'],
            'start' => $start,
        );
        $this->load->view('group_loan_tracker/group_loan_tracker_list', $data);
    }

    public function read($id) 
    {
        $row = $this->Group_loan_tracker_model->get_by_id($id);
        if ($row) {
            $data = array(
		'group_loan_tracker_id' => $row->group_loan_tracker_id,
		'disbursement_id' => $row->disbursement_id,
		'group_id' => $row->group_id,
		'customer_id' => $row->customer_id,
		'amount' => $row->amount,
		'date_added' => $row->date_added,
	    );
            $this->load->view('group_loan_tracker/group_loan_tracker_read', $data);
        } else {
            $this->session->set_flashdata('message', 'Record Not Found');
            redirect(site_url('group_loan_tracker'));
        }
    }

    public function create() 
    {
        $data = array(
            'button' => 'Create',
            'action' => site_url('group_loan_tracker/create_action'),
	    'group_loan_tracker_id' => set_value('group_loan_tracker_id'),
	    'disbursement_id' => set_value('disbursement_id'),
	    'group_id' => set_value('group_id'),
	    'customer_id' => set_value('customer_id'),
	    'amount' => set_value('amount'),
	    'date_added' => set_value('date_added'),
	);
        $this->load->view('group_loan_tracker/group_loan_tracker_form', $data);
    }
    
    public function create_action() 
    {
        $this->_rules();

        if ($this->form_validation->run() == FALSE) {
            $this->create();
        } else {
            $data = array(
		'disbursement_id' => $this->input->post('disbursement_id',TRUE),
		'group_id' => $this->input->post('group_id',TRUE),
		'customer_id' => $this->input->post('customer_id',TRUE),
		'amount' => $this->input->post('amount',TRUE),
		'date_added' => $this->input->post('date_added',TRUE),
	    );

            $this->Group_loan_tracker_model->insert($data);
            $this->session->set_flashdata('message', 'Create Record Success');
            redirect(site_url('group_loan_tracker'));
        }
    }
    
    public function update($id) 
    {
        $row = $this->Group_loan_tracker_model->get_by_id($id);

        if ($row) {
            $data = array(
                'button' => 'Update',
                'action' => site_url('group_loan_tracker/update_action'),
		'group_loan_tracker_id' => set_value('group_loan_tracker_id', $row->group_loan_tracker_id),
		'disbursement_id' => set_value('disbursement_id', $row->disbursement_id),
		'group_id' => set_value('group_id', $row->group_id),
		'customer_id' => set_value('customer_id', $row->customer_id),
		'amount' => set_value('amount', $row->amount),
		'date_added' => set_value('date_added', $row->date_added),
	    );
            $this->load->view('group_loan_tracker/group_loan_tracker_form', $data);
        } else {
            $this->session->set_flashdata('message', 'Record Not Found');
            redirect(site_url('group_loan_tracker'));
        }
    }
    
    public function update_action() 
    {
        $this->_rules();

        if ($this->form_validation->run() == FALSE) {
            $this->update($this->input->post('group_loan_tracker_id', TRUE));
        } else {
            $data = array(
		'disbursement_id' => $this->input->post('disbursement_id',TRUE),
		'group_id' => $this->input->post('group_id',TRUE),
		'customer_id' => $this->input->post('customer_id',TRUE),
		'amount' => $this->input->post('amount',TRUE),
		'date_added' => $this->input->post('date_added',TRUE),
	    );

            $this->Group_loan_tracker_model->update($this->input->post('group_loan_tracker_id', TRUE), $data);
            $this->session->set_flashdata('message', 'Update Record Success');
            redirect(site_url('group_loan_tracker'));
        }
    }
    
    public function delete($id) 
    {
        $row = $this->Group_loan_tracker_model->get_by_id($id);

        if ($row) {
            $this->Group_loan_tracker_model->delete($id);
            $this->session->set_flashdata('message', 'Delete Record Success');
            redirect(site_url('group_loan_tracker'));
        } else {
            $this->session->set_flashdata('message', 'Record Not Found');
            redirect(site_url('group_loan_tracker'));
        }
    }

    public function _rules() 
    {
	$this->form_validation->set_rules('disbursement_id', 'disbursement id', 'trim|required');
	$this->form_validation->set_rules('group_id', 'group id', 'trim|required');
	$this->form_validation->set_rules('customer_id', 'customer id', 'trim|required');
	$this->form_validation->set_rules('amount', 'amount', 'trim|required|numeric');
	$this->form_validation->set_rules('date_added', 'date added', 'trim|required');

	$this->form_validation->set_rules('group_loan_tracker_id', 'group_loan_tracker_id', 'trim');
	$this->form_validation->set_error_delimiters('<span class="text-danger">', '</span>');
    }

}

/* End of file Group_loan_tracker.php */
/* Location: ./application/controllers/Group_loan_tracker.php */
/* Please DO NOT modify this information : */
/* Generated by Harviacode Codeigniter CRUD Generator 2022-03-13 21:19:44 */
/* http://harviacode.com */