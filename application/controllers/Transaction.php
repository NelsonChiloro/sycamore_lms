<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Transaction extends CI_Controller
{
    function __construct()
    {
        parent::__construct();
        $this->load->model('Transaction_model');
        $this->load->library('form_validation');
    }

    public function index()
    {
        $q = urldecode($this->input->get('q', TRUE));
        $start = intval($this->input->get('start'));
        
        if ($q <> '') {
            $config['base_url'] = base_url() . 'transaction/index.html?q=' . urlencode($q);
            $config['first_url'] = base_url() . 'transaction/index.html?q=' . urlencode($q);
        } else {
            $config['base_url'] = base_url() . 'transaction/index.html';
            $config['first_url'] = base_url() . 'transaction/index.html';
        }

        $config['per_page'] = 10;
        $config['page_query_string'] = TRUE;
        $config['total_rows'] = $this->Transaction_model->total_rows($q);
        $transaction = $this->Transaction_model->get_limit_data($config['per_page'], $start, $q);

        $this->load->library('pagination');
        $this->pagination->initialize($config);

        $data = array(
            'transaction_data' => $transaction,
            'q' => $q,
            'pagination' => $this->pagination->create_links(),
            'total_rows' => $config['total_rows'],
            'start' => $start,
        );
        $this->load->view('transaction/transaction_list', $data);
    }

    public function read($id) 
    {
        $row = $this->Transaction_model->get_by_id($id);
        if ($row) {
            $data = array(
		'id' => $row->id,
		'account_number' => $row->account_number,
		'transaction_id' => $row->transaction_id,
		'credit' => $row->credit,
		'debit' => $row->debit,
		'balance' => $row->balance,
		'system_time' => $row->system_time,
		'server_time' => $row->server_time,
	    );
            $this->load->view('transaction/transaction_read', $data);
        } else {
            $this->session->set_flashdata('message', 'Record Not Found');
            redirect(site_url('transaction'));
        }
    }

    public function create() 
    {
        $data = array(
            'button' => 'Create',
            'action' => site_url('transaction/create_action'),
	    'id' => set_value('id'),
	    'account_number' => set_value('account_number'),
	    'transaction_id' => set_value('transaction_id'),
	    'credit' => set_value('credit'),
	    'debit' => set_value('debit'),
	    'balance' => set_value('balance'),
	    'system_time' => set_value('system_time'),
	    'server_time' => set_value('server_time'),
	);
        $this->load->view('transaction/transaction_form', $data);
    }
    
    public function create_action() 
    {
        $this->_rules();

        if ($this->form_validation->run() == FALSE) {
            $this->create();
        } else {
            $data = array(
		'account_number' => $this->input->post('account_number',TRUE),
		'transaction_id' => $this->input->post('transaction_id',TRUE),
		'credit' => $this->input->post('credit',TRUE),
		'debit' => $this->input->post('debit',TRUE),
		'balance' => $this->input->post('balance',TRUE),
		'system_time' => $this->input->post('system_time',TRUE),
		'server_time' => $this->input->post('server_time',TRUE),
	    );

            $this->Transaction_model->insert($data);
            $this->session->set_flashdata('message', 'Create Record Success');
            redirect(site_url('transaction'));
        }
    }
    
    public function update($id) 
    {
        $row = $this->Transaction_model->get_by_id($id);

        if ($row) {
            $data = array(
                'button' => 'Update',
                'action' => site_url('transaction/update_action'),
		'id' => set_value('id', $row->id),
		'account_number' => set_value('account_number', $row->account_number),
		'transaction_id' => set_value('transaction_id', $row->transaction_id),
		'credit' => set_value('credit', $row->credit),
		'debit' => set_value('debit', $row->debit),
		'balance' => set_value('balance', $row->balance),
		'system_time' => set_value('system_time', $row->system_time),
		'server_time' => set_value('server_time', $row->server_time),
	    );
            $this->load->view('transaction/transaction_form', $data);
        } else {
            $this->session->set_flashdata('message', 'Record Not Found');
            redirect(site_url('transaction'));
        }
    }
    
    public function update_action() 
    {
        $this->_rules();

        if ($this->form_validation->run() == FALSE) {
            $this->update($this->input->post('id', TRUE));
        } else {
            $data = array(
		'account_number' => $this->input->post('account_number',TRUE),
		'transaction_id' => $this->input->post('transaction_id',TRUE),
		'credit' => $this->input->post('credit',TRUE),
		'debit' => $this->input->post('debit',TRUE),
		'balance' => $this->input->post('balance',TRUE),
		'system_time' => $this->input->post('system_time',TRUE),
		'server_time' => $this->input->post('server_time',TRUE),
	    );

            $this->Transaction_model->update($this->input->post('id', TRUE), $data);
            $this->session->set_flashdata('message', 'Update Record Success');
            redirect(site_url('transaction'));
        }
    }
    
    public function delete($id) 
    {
        $row = $this->Transaction_model->get_by_id($id);

        if ($row) {
            $this->Transaction_model->delete($id);
            $this->session->set_flashdata('message', 'Delete Record Success');
            redirect(site_url('transaction'));
        } else {
            $this->session->set_flashdata('message', 'Record Not Found');
            redirect(site_url('transaction'));
        }
    }

    public function _rules() 
    {
	$this->form_validation->set_rules('account_number', 'account number', 'trim|required');
	$this->form_validation->set_rules('transaction_id', 'transaction id', 'trim|required');
	$this->form_validation->set_rules('credit', 'credit', 'trim|required|numeric');
	$this->form_validation->set_rules('debit', 'debit', 'trim|required|numeric');
	$this->form_validation->set_rules('balance', 'balance', 'trim|required|numeric');
	$this->form_validation->set_rules('system_time', 'system time', 'trim|required');
	$this->form_validation->set_rules('server_time', 'server time', 'trim|required');

	$this->form_validation->set_rules('id', 'id', 'trim');
	$this->form_validation->set_error_delimiters('<span class="text-danger">', '</span>');
    }

}

/* End of file Transaction.php */
/* Location: ./application/controllers/Transaction.php */
/* Please DO NOT modify this information : */
/* Generated by Harviacode Codeigniter CRUD Generator 2022-03-31 21:06:36 */
/* http://harviacode.com */