<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Transactions extends CI_Controller
{
    function __construct()
    {
        parent::__construct();
        $this->load->model('Transactions_model');
        $this->load->model('Payement_schedules_model');
        $this->load->model('Loan_model');
        $this->load->model('Account_model');
        $this->load->library('form_validation');
    }

    public function index()
    {
        $q = urldecode($this->input->get('q', TRUE));
        $start = intval($this->input->get('start'));
        
        if ($q <> '') {
            $config['base_url'] = base_url() . 'transactions/index.html?q=' . urlencode($q);
            $config['first_url'] = base_url() . 'transactions/index.html?q=' . urlencode($q);
        } else {
            $config['base_url'] = base_url() . 'transactions/index.html';
            $config['first_url'] = base_url() . 'transactions/index.html';
        }

        $config['per_page'] = 10;
        $config['page_query_string'] = TRUE;
        $config['total_rows'] = $this->Transactions_model->total_rows($q);
        $transactions = $this->Transactions_model->get_limit_data($config['per_page'], $start, $q);

        $this->load->library('pagination');
        $this->pagination->initialize($config);

        $data = array(
            'transactions_data' => $transactions,
            'q' => $q,
            'pagination' => $this->pagination->create_links(),
            'total_rows' => $config['total_rows'],
            'start' => $start,
        );
        $this->load->view('transactions/transactions_list', $data);
    }

    public function read($id) 
    {
        $row = $this->Transactions_model->get_by_id($id);
        if ($row) {
            $data = array(
		'transaction_id' => $row->transaction_id,
		'ref' => $row->ref,
		'loan_id' => $row->loan_id,
		'amount' => $row->amount,
		'payment_number' => $row->payment_number,
		'date_stamp' => $row->date_stamp,
	    );
            $this->load->view('transactions/transactions_read', $data);
        } else {
            $this->session->set_flashdata('message', 'Record Not Found');
            redirect(site_url('transactions'));
        }
    }

    public function create() 
    {
        $data = array(
            'button' => 'Create',
            'action' => site_url('transactions/create_action'),
	    'transaction_id' => set_value('transaction_id'),
	    'ref' => set_value('ref'),
	    'loan_id' => set_value('loan_id'),
	    'amount' => set_value('amount'),
	    'payment_number' => set_value('payment_number'),
	    'date_stamp' => set_value('date_stamp'),
	);
        $this->load->view('transactions/transactions_form', $data);
    }
    
    public function create_action() 
    {
        $tid="TR-S".rand(100,9999).date('Y').date('m').date('d');
        $loan_account = check_exist_in_table('loan','loan_id',$this->input->post('loan_id', TRUE));
        $check = $this->Account_model->get_account($loan_account->loan_number);
        if($check->balance > $this->input->post('amount', TRUE)) {

            $do_transactions = $this->Account_model->transfer_funds($loan_account->loan_number, 'khbkk', $this->input->post('amount', TRUE), $tid);

            $data = array(
                'ref' => "GF." . date('Y') . date('m') . date('d') . '.' . rand(100, 999),
                'loan_id' => $this->input->post('loan_id', TRUE),
                'amount' => $this->input->post('amount', TRUE),
                'transaction_type' => 1,
                'payment_number' => 0,
                'added_by' => $this->session->userdata('user_id')

            );

            $this->Transactions_model->insert($data);
            $this->toaster->success('Success, transactions was added');
            redirect($_SERVER['HTTP_REFERER']);
        }else{
            $this->toaster->error('Error, transactions  failed loan account does not have enough funds deposit first');
            redirect($_SERVER['HTTP_REFERER']);
        }

    }    public function create_expense()
    {

            $data = array(
		'ref' => "GF.".date('Y').date('m').date('d').'.'.rand(100,999),
		'loan_id' => $this->input->post('loan_id',TRUE),
		'amount' => $this->input->post('amount',TRUE),
		'transaction_type' => 4,
		'payment_number' => 0,
		'description' => $this->input->post('description',TRUE),
		'added_by' => $this->session->userdata('user_id')

	    			   );

            $this->Transactions_model->insert($data);
			$this->toaster->success('Success, transactions was added');
			redirect($_SERVER['HTTP_REFERER']);

    }
	public function download($id)
	{
		$row = $this->Transactions_model->get_by_id($id);
		if ($row) {

			$save =file_get_contents(base_url()."uploads/".$row->payment_proof);
			$this->load->helper('download');
			force_download("Payment_ref-".$row->ref.".JPEG", $save);

		} else {

			redirect(base_url());
		}
	}
    function statement(){
    	$data['data'] = array();
		$menu_toggle['toggles'] = 41;
		$this->load->view('admin/header',$menu_toggle);
		$this->load->view('transactions/transactions',$data);
		$this->load->view('admin/footer');
	}
	function expenses(){

		$search = $this->input->get('search');
		$from = $this->input->get('from');
		$to = $this->input->get('to');
		if($search=='filter'){
			$data['data'] = $this->Transactions_model->get_expenses($from,$to);
			$this->load->view('admin/header');
			$this->load->view('transactions/expenses',$data);
			$this->load->view('admin/footer');
		}else{
			$data['data'] = $this->Transactions_model->get_expenses($from,$to);
			$this->load->view('admin/header');
			$this->load->view('transactions/expenses',$data);
			$this->load->view('admin/footer');
		}

	}
	function search(){
    	$this->session->set_userdata('lid',$this->input->get('loan_id'));
    	$results['data'] = $this->Transactions_model->search2($this->input->get('loan_id'));
		$menu_toggle['toggles'] = 41;
		$this->load->view('admin/header', $menu_toggle);
		$this->load->view('transactions/transactions',$results);
		$this->load->view('admin/footer');
	}
	function report(){
		$row = $this->Loan_model->get_by_id($this->input->get('loan_id'));
		$payments = $this->Payement_schedules_model->get_all_by_id($row->loan_id);
		$maturity_date = $this->Payement_schedules_model->get_last_payment($row->loan_id);
		$first_payment = $this->Payement_schedules_model->get_first_payment($row->loan_id);
		$trans = $this->Transactions_model->search($this->input->get('loan_id'));
        $datadeposits = $this->Transactions_model->search2($this->input->get('loan_id'));
        if($row->customer_type=='individual'){
            $inddata=get_by_id('individual_customers','id', $row->loan_customer);
          
            $customer=$inddata->Firstname." ".$inddata->Lastname;
        }
        else{
            $groupdata=get_by_id('groups','group_id', $row->loan_customer);
            $customer=$groupdata->group_name." ".$groupdata->group_code;
        }

		$data = array(
			'loan_id' => $row->loan_id,
			'transa' => $trans,
			'datadeposits'=>$datadeposits,
			'maturity_date' => $maturity_date->payment_schedule,
			'maturity_pay' => $maturity_date->amount,
			'first_payment' => $first_payment->amount,
			'first_payment_date' => $first_payment->payment_schedule,
			'loan_number' => $row->loan_number,
			'loan_product' => $row->product_name,
			'loan_customer' => $customer,
			'customer_id' => $row->id,
			'loan_date' => $row->loan_date,
			'loan_principal' => $row->loan_principal,
			'loan_period' => $row->loan_period,
			'period_type' => $row->period_type,
			'loan_interest' => $row->loan_interest,
			'loan_amount_total' => $row->loan_amount_total,
			'loan_amount_term' => $row->loan_amount_term,
			'next_payment_id' => $row->next_payment_id,
			'loan_added_by' => $row->loan_added_by,
			'loan_approved_by' => $row->loan_approved_by,
			'loan_status' => $row->loan_status,
			'loan_added_date' => $row->loan_added_date,
			'payments'=>$payments
		);
		$this->load->library('Pdf');
		$html = $this->load->view('transactions/print_transactions', $data,true);
		$this->pdf->createPDF($html, $data['loan_customer']." loan report as on".date('Y-m-d'), true);

	}
    
    public function update($id) 
    {
        $row = $this->Transactions_model->get_by_id($id);

        if ($row) {
            $data = array(
                'button' => 'Update',
                'action' => site_url('transactions/update_action'),
		'transaction_id' => set_value('transaction_id', $row->transaction_id),
		'ref' => set_value('ref', $row->ref),
		'loan_id' => set_value('loan_id', $row->loan_id),
		'amount' => set_value('amount', $row->amount),
		'payment_number' => set_value('payment_number', $row->payment_number),
		'date_stamp' => set_value('date_stamp', $row->date_stamp),
	    );
            $this->load->view('transactions/transactions_form', $data);
        } else {
            $this->session->set_flashdata('message', 'Record Not Found');
            redirect(site_url('transactions'));
        }
    }
    
    public function update_action() 
    {
        $this->_rules();

        if ($this->form_validation->run() == FALSE) {
            $this->update($this->input->post('transaction_id', TRUE));
        } else {
            $data = array(
		'ref' => $this->input->post('ref',TRUE),
		'loan_id' => $this->input->post('loan_id',TRUE),
		'amount' => $this->input->post('amount',TRUE),
		'payment_number' => $this->input->post('payment_number',TRUE),
		'date_stamp' => $this->input->post('date_stamp',TRUE),
	    );

            $this->Transactions_model->update($this->input->post('transaction_id', TRUE), $data);
            $this->session->set_flashdata('message', 'Update Record Success');
            redirect(site_url('transactions'));
        }
    }
    
    public function delete($id) 
    {
        $row = $this->Transactions_model->get_by_id($id);

        if ($row) {
            $this->Transactions_model->delete($id);
            $this->toaster->success('Success!, your expense has been deleted');
			redirect($_SERVER['HTTP_REFERER']);
        } else {
            $this->session->set_flashdata('message', 'Record Not Found');
            redirect($_SERVER['HTTP_REFERER']);
        }
    }

    public function _rules() 
    {
	$this->form_validation->set_rules('ref', 'ref', 'trim|required');
	$this->form_validation->set_rules('loan_id', 'loan id', 'trim|required');
	$this->form_validation->set_rules('amount', 'amount', 'trim|required');
	$this->form_validation->set_rules('payment_number', 'payment number', 'trim|required');
	$this->form_validation->set_rules('date_stamp', 'date stamp', 'trim|required');

	$this->form_validation->set_rules('transaction_id', 'transaction_id', 'trim');
	$this->form_validation->set_error_delimiters('<span class="text-danger">', '</span>');
    }

}

