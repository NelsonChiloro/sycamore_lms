<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Payement_schedules extends CI_Controller
{
    function __construct()
    {
        parent::__construct();
        $this->load->model('Payement_schedules_model');
        $this->load->library('form_validation');
    }

    public function index()
    {
        $q = urldecode($this->input->get('q', TRUE));
        $start = intval($this->input->get('start'));
        
        if ($q <> '') {
            $config['base_url'] = base_url() . 'payement_schedules/index.html?q=' . urlencode($q);
            $config['first_url'] = base_url() . 'payement_schedules/index.html?q=' . urlencode($q);
        } else {
            $config['base_url'] = base_url() . 'payement_schedules/index.html';
            $config['first_url'] = base_url() . 'payement_schedules/index.html';
        }

        $config['per_page'] = 10;
        $config['page_query_string'] = TRUE;
        $config['total_rows'] = $this->Payement_schedules_model->total_rows($q);
        $payement_schedules = $this->Payement_schedules_model->get_limit_data($config['per_page'], $start, $q);

        $this->load->library('pagination');
        $this->pagination->initialize($config);

        $data = array(
            'payement_schedules_data' => $payement_schedules,
            'q' => $q,
            'pagination' => $this->pagination->create_links(),
            'total_rows' => $config['total_rows'],
            'start' => $start,
        );
        $this->load->view('payement_schedules/payement_schedules_list', $data);
    }

    public function read($id) 
    {
        $row = $this->Payement_schedules_model->get_by_id($id);
        if ($row) {
            $data = array(
		'id' => $row->id,
		'customer' => $row->customer,
		'loan_id' => $row->loan_id,
		'payment_schedule' => $row->payment_schedule,
		'payment_number' => $row->payment_number,
		'amount' => $row->amount,
		'principal' => $row->principal,
		'interest' => $row->interest,
		'paid_amount' => $row->paid_amount,
		'loan_balance' => $row->loan_balance,
		'status' => $row->status,
		'loan_date' => $row->loan_date,
		'paid_date' => $row->paid_date,
		'marked_due' => $row->marked_due,
		'marked_due_date' => $row->marked_due_date,
	    );
            $this->load->view('payement_schedules/payement_schedules_read', $data);
        } else {
            $this->session->set_flashdata('message', 'Record Not Found');
            redirect(site_url('payement_schedules'));
        }
    }

    public function create() 
    {
        $data = array(
            'button' => 'Create',
            'action' => site_url('payement_schedules/create_action'),
	    'id' => set_value('id'),
	    'customer' => set_value('customer'),
	    'loan_id' => set_value('loan_id'),
	    'payment_schedule' => set_value('payment_schedule'),
	    'payment_number' => set_value('payment_number'),
	    'amount' => set_value('amount'),
	    'principal' => set_value('principal'),
	    'interest' => set_value('interest'),
	    'paid_amount' => set_value('paid_amount'),
	    'loan_balance' => set_value('loan_balance'),
	    'status' => set_value('status'),
	    'loan_date' => set_value('loan_date'),
	    'paid_date' => set_value('paid_date'),
	    'marked_due' => set_value('marked_due'),
	    'marked_due_date' => set_value('marked_due_date'),
	);
        $this->load->view('payement_schedules/payement_schedules_form', $data);
    }
    
    public function create_action() 
    {
        $this->_rules();

        if ($this->form_validation->run() == FALSE) {
            $this->create();
        } else {
            $data = array(
		'id' => $this->input->post('id',TRUE),
		'customer' => $this->input->post('customer',TRUE),
		'loan_id' => $this->input->post('loan_id',TRUE),
		'payment_schedule' => $this->input->post('payment_schedule',TRUE),
		'payment_number' => $this->input->post('payment_number',TRUE),
		'amount' => $this->input->post('amount',TRUE),
		'principal' => $this->input->post('principal',TRUE),
		'interest' => $this->input->post('interest',TRUE),
		'paid_amount' => $this->input->post('paid_amount',TRUE),
		'loan_balance' => $this->input->post('loan_balance',TRUE),
		'status' => $this->input->post('status',TRUE),
		'loan_date' => $this->input->post('loan_date',TRUE),
		'paid_date' => $this->input->post('paid_date',TRUE),
		'marked_due' => $this->input->post('marked_due',TRUE),
		'marked_due_date' => $this->input->post('marked_due_date',TRUE),
	    );

            $this->Payement_schedules_model->insert($data);
            $this->session->set_flashdata('message', 'Create Record Success');
            redirect(site_url('payement_schedules'));
        }
    }
    
    public function update($id) 
    {
        $row = $this->Payement_schedules_model->get_by_id($id);

        if ($row) {
            $data = array(
                'button' => 'Update',
                'action' => site_url('payement_schedules/update_action'),
		'id' => set_value('id', $row->id),
		'customer' => set_value('customer', $row->customer),
		'loan_id' => set_value('loan_id', $row->loan_id),
		'payment_schedule' => set_value('payment_schedule', $row->payment_schedule),
		'payment_number' => set_value('payment_number', $row->payment_number),
		'amount' => set_value('amount', $row->amount),
		'principal' => set_value('principal', $row->principal),
		'interest' => set_value('interest', $row->interest),
		'paid_amount' => set_value('paid_amount', $row->paid_amount),
		'loan_balance' => set_value('loan_balance', $row->loan_balance),
		'status' => set_value('status', $row->status),
		'loan_date' => set_value('loan_date', $row->loan_date),
		'paid_date' => set_value('paid_date', $row->paid_date),
		'marked_due' => set_value('marked_due', $row->marked_due),
		'marked_due_date' => set_value('marked_due_date', $row->marked_due_date),
	    );
            $this->load->view('payement_schedules/payement_schedules_form', $data);
        } else {
            $this->session->set_flashdata('message', 'Record Not Found');
            redirect(site_url('payement_schedules'));
        }
    }
    
    public function update_action() 
    {
        $this->_rules();

        if ($this->form_validation->run() == FALSE) {
            $this->update($this->input->post('', TRUE));
        } else {
            $data = array(
		'id' => $this->input->post('id',TRUE),
		'customer' => $this->input->post('customer',TRUE),
		'loan_id' => $this->input->post('loan_id',TRUE),
		'payment_schedule' => $this->input->post('payment_schedule',TRUE),
		'payment_number' => $this->input->post('payment_number',TRUE),
		'amount' => $this->input->post('amount',TRUE),
		'principal' => $this->input->post('principal',TRUE),
		'interest' => $this->input->post('interest',TRUE),
		'paid_amount' => $this->input->post('paid_amount',TRUE),
		'loan_balance' => $this->input->post('loan_balance',TRUE),
		'status' => $this->input->post('status',TRUE),
		'loan_date' => $this->input->post('loan_date',TRUE),
		'paid_date' => $this->input->post('paid_date',TRUE),
		'marked_due' => $this->input->post('marked_due',TRUE),
		'marked_due_date' => $this->input->post('marked_due_date',TRUE),
	    );

            $this->Payement_schedules_model->update($this->input->post('', TRUE), $data);
            $this->session->set_flashdata('message', 'Update Record Success');
            redirect(site_url('payement_schedules'));
        }
    }
    
    public function delete($id) 
    {
        $row = $this->Payement_schedules_model->get_by_id($id);

        if ($row) {
            $this->Payement_schedules_model->delete($id);
            $this->session->set_flashdata('message', 'Delete Record Success');
            redirect(site_url('payement_schedules'));
        } else {
            $this->session->set_flashdata('message', 'Record Not Found');
            redirect(site_url('payement_schedules'));
        }
    }

    public function _rules() 
    {
	$this->form_validation->set_rules('id', 'id', 'trim|required');
	$this->form_validation->set_rules('customer', 'customer', 'trim|required');
	$this->form_validation->set_rules('loan_id', 'loan id', 'trim|required');
	$this->form_validation->set_rules('payment_schedule', 'payment schedule', 'trim|required');
	$this->form_validation->set_rules('payment_number', 'payment number', 'trim|required');
	$this->form_validation->set_rules('amount', 'amount', 'trim|required');
	$this->form_validation->set_rules('principal', 'principal', 'trim|required');
	$this->form_validation->set_rules('interest', 'interest', 'trim|required');
	$this->form_validation->set_rules('paid_amount', 'paid amount', 'trim|required');
	$this->form_validation->set_rules('loan_balance', 'loan balance', 'trim|required|numeric');
	$this->form_validation->set_rules('status', 'status', 'trim|required');
	$this->form_validation->set_rules('loan_date', 'loan date', 'trim|required');
	$this->form_validation->set_rules('paid_date', 'paid date', 'trim|required');
	$this->form_validation->set_rules('marked_due', 'marked due', 'trim|required');
	$this->form_validation->set_rules('marked_due_date', 'marked due date', 'trim|required');

	$this->form_validation->set_rules('', '', 'trim');
	$this->form_validation->set_error_delimiters('<span class="text-danger">', '</span>');
    }

}
