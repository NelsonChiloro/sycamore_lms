<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Tellering extends CI_Controller
{
    function __construct()
    {
        parent::__construct();
        $this->load->model('Tellering_model');
        $this->load->model('Account_model');
        $this->load->model('Transaction_model');
        $this->load->model('Transactions_model');
        $this->load->model('Loan_model');
        $this->load->model('Payement_schedules_model');
        $this->load->model('Vault_cashier_pends_model');
        $this->load->model('Cashier_vault_pends_model');
        $this->load->library('form_validation');
    }
    public function track_transaction()
    {

        $loannumber = $this->input->get('loannumber');

        $search = $this->input->get('search');

        // If a loan number is provided, filter by it; otherwise get all transactions
        if(!empty($loannumber)){
            $loan_data = $this->Transaction_model->track_transactions($loannumber);
        } else {
            $loan_data = $this->Transaction_model->track_all_transactions();
        }
        $data['loan_data'] = $loan_data;

        if($search=='pdf'){
            $this->load->library('Pdf');
            $html = $this->load->view('reports/transactions_pdf', $data,true);
            $this->pdf->createPDF($html, "transactions report as on".date('Y-m-d'), true,'A4','landscape');
        }elseif($search=='excel'){

        }else {
            $this->load->view('admin/header');
            $this->load->view('tellering/transactions', $data);
            $this->load->view('admin/footer');
        }
    }
    public function generate_loan_deposits_report()
    {
        $from = $this->input->post('from');
        $to = $this->input->post('to');

        // Initialize cURL session
        $ch = curl_init();

        // Set the URL of the Node.js endpoint through the shared helper
        $url = report_service_url('generate-report-loan-deposits');

        // Prepare the data to be sent
        $data = [
            "report_type" => "LOAN_DEPOSITS",
            "user" => $this->session->userdata('Firstname')." ".$this->session->userdata('Lastname'),
            "user_id" => $this->session->userdata('user_id'),
            "from" => $from,
            "to" => $to
        ];

        // Convert the data array to JSON
        $jsonData = json_encode($data);

        // Set cURL options
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($jsonData)
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);

        // Execute the cURL request
        $response = curl_exec($ch);

        // Check for errors
        if (curl_errno($ch)) {
            $this->toaster->error('Error: ' . curl_error($ch));
        } else {
            $this->toaster->success('Success! Loan Deposits Report is being processed. You may do other things and come back to check progress.');
            redirect(site_url('report'));
        }

        // Close the cURL session
        curl_close($ch);
    }

    public function generate_track_transactions_report()
    {
        $from = trim((string)$this->input->post('from', TRUE));
        $to = trim((string)$this->input->post('to', TRUE));
        $customer_name = trim((string)$this->input->post('customer_name', TRUE));
        $loan_number = trim((string)$this->input->post('loan_number', TRUE));
        $transaction_type = trim((string)$this->input->post('transaction_type', TRUE));

        $ch = curl_init();
        $url = report_service_url('generate-report-track-transactions');

        $data = [
            'report_type' => 'TRACK_TRANSACTIONS',
            'user' => $this->session->userdata('Firstname') . " " . $this->session->userdata('Lastname'),
            'user_id' => $this->session->userdata('user_id'),
            'from' => $from,
            'to' => $to,
            'customer_name' => $customer_name,
            'loan_number' => $loan_number,
            'transaction_type' => $transaction_type,
        ];

        $jsonData = json_encode($data);

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($jsonData)
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            $this->toaster->error('Error: ' . curl_error($ch));
        } else {
            $this->toaster->success('Success! Transaction tracking report is being processed. You may do other things and come back to check progress.');
            redirect(site_url('report'));
        }

        curl_close($ch);
    }

    public function track_transactions_view()
    {

        $should_fetch = $this->input->get('run', TRUE) === '1';

        $filters = array(
            'from' => trim((string)$this->input->get('from', TRUE)),
            'to' => trim((string)$this->input->get('to', TRUE)),
            'customer_name' => trim((string)$this->input->get('customer_name', TRUE)),
            'loan_number' => trim((string)$this->input->get('loan_number', TRUE)),
            'transaction_type' => trim((string)$this->input->get('transaction_type', TRUE)),
        );

        $loan_payment_loan = null;
        $loan_payment_rows = array();
        $loan_payment_message = '';

        if ($should_fetch && $filters['loan_number'] !== '') {
            $loan_payment_loan = $this->Loan_model->get_one($filters['loan_number']);

            $loan_payment_rows = $this->Transaction_model->track_transactions($filters['loan_number']);

            if (empty($loan_payment_rows)) {
                $loan_payment_message = 'No loan account transactions were found for the selected loan number.';
            }
        }

        $data = array(
            'filters' => $filters,
            'should_fetch' => $should_fetch,
            'transaction_types' => $this->db->order_by('name', 'ASC')->get('transaction_type')->result(),
            'transactions_data' => $should_fetch ? $this->Transactions_model->get_tracked_transactions($filters) : array(),
            'loan_payment_loan' => $loan_payment_loan,
            'loan_payment_rows' => $loan_payment_rows,
            'loan_payment_message' => $loan_payment_message,
        );

        $this->load->view('admin/header');
        $this->load->view('tellering/transactions_view', $data);
        $this->load->view('admin/footer');

    }
    public function index()
    {
        $q = urldecode($this->input->get('q', TRUE));
        $start = intval($this->input->get('start'));
        
        if ($q <> '') {
            $config['base_url'] = base_url() . 'tellering/index.html?q=' . urlencode($q);
            $config['first_url'] = base_url() . 'tellering/index.html?q=' . urlencode($q);
        } else {
            $config['base_url'] = base_url() . 'tellering/index.html';
            $config['first_url'] = base_url() . 'tellering/index.html';
        }

        $config['per_page'] = 10;
        $config['page_query_string'] = TRUE;
        $config['total_rows'] = $this->Tellering_model->total_rows($q);
        $tellering = $this->Tellering_model->get_limit_data($config['per_page'], $start, $q);

        $this->load->library('pagination');
        $this->pagination->initialize($config);

        $data = array(
            'tellering_data' => $tellering,
            'q' => $q,
            'pagination' => $this->pagination->create_links(),
            'total_rows' => $config['total_rows'],
            'start' => $start,
        );
        $this->load->view('tellering/tellering_list', $data);
    }

    public function read($id) 
    {
        $row = $this->Tellering_model->get_by_id($id);
        if ($row) {
            $data = array(
		'id' => $row->id,
		'teller' => $row->teller,
		'account' => $row->account,
		'date_time' => $row->date_time,
	    );
            $this->load->view('tellering/tellering_read', $data);
        } else {
            $this->session->set_flashdata('message', 'Record Not Found');
            redirect(site_url('tellering'));
        }
    }

    public function create() 
    {
        $data = array(
            'button' => 'Create',
            'action' => site_url('tellering/create_action'),
	    'id' => set_value('id'),
	    'teller' => set_value('teller'),
	    'account' => set_value('account'),
	    'date_time' => set_value('date_time'),
	);
        $this->load->view('tellering/tellering_form', $data);
    }

	public function create_action()
	{
		$this->_rules();

		if ($this->form_validation->run() == FALSE) {
			redirect($_SERVER['HTTP_REFERER']);
		} else {
			$data = array(
				'teller' => $this->input->post('teller',TRUE),
				'account' => $this->input->post('account',TRUE),

			);

			$this->Tellering_model->insert($data);
			$this->toaster->success('Teller Assigned to an account successfully');
			redirect($_SERVER['HTTP_REFERER']);
		}
	}
    
    public function update($id) 
    {
        $row = $this->Tellering_model->get_by_id($id);

        if ($row) {
            $data = array(
                'button' => 'Update',
                'action' => site_url('tellering/update_action'),
		'id' => set_value('id', $row->id),
		'teller' => set_value('teller', $row->teller),
		'account' => set_value('account', $row->account),
		'date_time' => set_value('date_time', $row->date_time),
	    );
            $this->load->view('tellering/tellering_form', $data);
        } else {
            $this->session->set_flashdata('message', 'Record Not Found');
            redirect(site_url('tellering'));
        }
    }
    
    public function update_action() 
    {
        $this->_rules();

        if ($this->form_validation->run() == FALSE) {
            $this->update($this->input->post('id', TRUE));
        } else {
            $data = array(
		'teller' => $this->input->post('teller',TRUE),
		'account' => $this->input->post('account',TRUE),
		'date_time' => $this->input->post('date_time',TRUE),
	    );

            $this->Tellering_model->update($this->input->post('id', TRUE), $data);
            $this->session->set_flashdata('message', 'Update Record Success');
            redirect(site_url('tellering'));
        }
    }
    
    public function delete($id) 
    {
        $row = $this->Tellering_model->get_by_id($id);

        if ($row) {
            $this->Tellering_model->delete($id);
            $this->session->set_flashdata('message', 'Delete Record Success');
            redirect(site_url('tellering'));
        } else {
            $this->session->set_flashdata('message', 'Record Not Found');
            redirect(site_url('tellering'));
        }
    }
	public function move_to_teller(){
    	$this->load->view('admin/header');
    	$this->load->view('tellering/move_to_teller');
    	$this->load->view('admin/footer');

	}
	public function move_to_vault(){
    	$this->load->view('admin/header');
    	$this->load->view('tellering/move_to_vault');
    	$this->load->view('admin/footer');

	}
    public function _rules() 
    {
	$this->form_validation->set_rules('teller', 'teller', 'trim|required');
	$this->form_validation->set_rules('account', 'account', 'trim|required');


	$this->form_validation->set_rules('id', 'id', 'trim');
	$this->form_validation->set_error_delimiters('<span class="text-danger">', '</span>');
    }

}

