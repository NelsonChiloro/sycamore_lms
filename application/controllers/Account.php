<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Account extends CI_Controller
{
    function __construct()
    {
        parent::__construct();
        $this->load->model('Account_model');
        $this->load->model('Loan_model');
        $this->load->model('Payement_schedules_model');
        $this->load->model('Transaction_model');
        $this->load->model('Tellering_model');
        $this->load->model('Vault_cashier_pends_model');
        $this->load->model('Cashier_vault_pends_model');
		$this->load->model('Individual_customers_model');
        $this->load->library('form_validation');
    }
	public function cash_transaction(){
		$tid="TR-S".rand(100,9999).date('Y').date('m').date('d');
		$result = array();
		$get_account = $this->Tellering_model->get_teller_account($this->session->userdata('user_id'));
		if(empty($get_account)){
			$result['status']= 'error';
			$result['message'] = "You are not authorized to do this transaction";
		}else{
			$teller_account = $get_account->account;

			$account = $this->input->post('account');
			$amount = $this->input->post('amount');
			$mode = $this->input->post('transaction_mode');
			$datee = $this->input->post('dateof');

			$res =	$this->Account_model->cash_transaction($teller_account,$account,$amount,$mode,$tid, $datee);
			if(!empty($res)){
				$result['status']= 'success';
				$result['data'] = array(
					'account'=>$account,
					'mode'=>$mode,
					'amount'=> number_format($amount,2),
					'transid'=>$tid,
					'reciept_no'=> '1',
					'dated'=>$datee,
                    'id'=>$res

				);
			}elseif($res=='teller'){
				$result['status']= 'error';
				$result['message'] = "Teller does not have enough fund, please request from vault";
			}elseif($res=='customer'){
				$result['status']= 'error';
				$result['message'] = "Customer does not have enough fund";
			}else{
				$result['status']= 'error';
				$result['message'] = "Internal errors";
			}
		}


		echo json_encode($result);
	}
	public function journal(){
    	$this->load->view('admin/header');
    	$this->load->view('account/journal');
    	$this->load->view('admin/footer');

	}
	public function accept_credit_teller($id){
		$row = $this->Vault_cashier_pends_model->get_by_id($id);

		if ($row) {
		$res=	$this->Account_model->vault_to_teller($row->vault_account,$row->teller_account,$row->amount);
		if($res == 'success'){
			$this->Vault_cashier_pends_model->update($id,array('status'=>'Approved','approved_by'=>$this->session->userdata('user_id')));
			$this->toaster->success('Success, Approval was successful');
			redirect($_SERVER['HTTP_REFERER']);
		}else{

			$this->toaster->error('Error,  Cashier does not have enough balance ,Approval was  not successful');
			redirect($_SERVER['HTTP_REFERER']);
		}

		} else {
			$this->toaster->error('error, record not found');
			redirect($_SERVER['HTTP_REFERER']);
		}

	}
	public function accept_credit_vault($id){
		$row = $this->Cashier_vault_pends_model->get_by_id($id);

		if ($row) {
		$res=	$this->Account_model->teller_to_vault($row->vault_account,$row->teller_account,$row->amount);
		if($res == 'success'){
			$this->Cashier_vault_pends_model->update($id,array('status'=>'Approved','approved_by'=>$this->session->userdata('user_id')));
			$this->toaster->success('Success, Approval was successful');
			redirect($_SERVER['HTTP_REFERER']);
		}else{

			$this->toaster->error('Error,  Cashier does not have enough balance ,Approval was  not successful');
			redirect($_SERVER['HTTP_REFERER']);
		}

		} else {
			$this->toaster->error('error, record not found');
			redirect($_SERVER['HTTP_REFERER']);
		}

	}
	public function credit_teller(){
		$vault_account = $this->input->post('vault_account');

		$account = $this->input->post('account');
		$amount = $this->input->post('amount');
		$user = $this->Tellering_model->get_mine($account);

		$data = array(
			'vault_account' => $this->input->post('vault_account',TRUE),
			'teller_account' => $account,
			'amount' => $amount,
			'creator' => $this->session->userdata('user_id'),
			'teller' => $user->teller,


		);
		$this->Vault_cashier_pends_model->insert($data);
//	 	$res =	$this->Accounts_model->post_transaction($vault_account,$account,$amount);
		$this->toaster->success('Success, transfer initiated');
		redirect($_SERVER['HTTP_REFERER']);
	}
	public function credit_vault(){
		$vault_account = $this->input->post('vault_account');

		$account = $this->input->post('account');
		$amount = $this->input->post('amount');
		$user = $this->Tellering_model->get_mine($account);

		$data = array(
			'vault_account' => $vault_account,
			'teller_account' => $account,
			'amount' => $amount,
			'creator' => $this->session->userdata('user_id'),
			'teller' => $user->teller,


		);
		$this->Cashier_vault_pends_model->insert($data);
//	 	$res =	$this->Accounts_model->post_transaction($vault_account,$account,$amount);
		$this->toaster->success('Success, transfer initiated');
		redirect($_SERVER['HTTP_REFERER']);
	}
	public function account_reconciliation(){
        $data['re'] = array();
        $this->load->view('admin/header');
        $this->load->view('account/reconciliation', $data);
        $this->load->view('admin/footer');
    }
	public function get_teller_transaction($id){
		$re = $this->Transaction_model->get_my_transaction($id);
        $tcredit = 0;
        $tdebit = 0;
		$output = '';
		$output .='<table class="table table-bordered">';
		$output .= '<thead class="bg-primary text-white">
						<tr>
						<td>Trans ID</td>
						<td>System Time</td>
						<td>Teller Account No</td>
						<td>Credit</td>
						<td>Debit</td>
						<td>Balance</td>
						
						<td>Action</td>
						</tr>
					</thead>
					<tbody>
					';
		if(!empty($re)){

			foreach ($re as $dd){
				$tcredit += $dd->credit1;
				$tdebit += $dd->debit1;
				$output.= '
				<tr>
				<td>'.$dd->transaction_id.'</td>
				<td>'.$dd->system_time.'</td>
				<td>'.$dd->teller_account.'</td>
				<td>'.$dd->credit1.'</td>
				<td>'.$dd->debit1.'</td>
				<td>'.$dd->balance1.'</td>
				
				<td><a href="'.base_url('account/print_receipt/').$dd->tid.'" target="_blank" > Print deposit receipt</a>
				<a href="'.base_url('account/print_loan_receipt/').$dd->tid.'"  target="_blank"> Print loan payment receipt</a></td>
				</tr>
				
				';
			}
		}

		$output.='
<tfoot>
<tr>
<td></td>
<td></td>
<td></td>
<td>'.number_format($tcredit,2).'</td>
<td>'.number_format($tdebit,2).'</td>
<td>'.number_format($tcredit-$tdebit,2).'</td>
<td></td>

</tr>
</tfoot>
</tbody>
</table>';

		echo $output;
	}
function  print_receipt ($id){
    $re = $this->Transaction_model->get_my_transaction_filter_one($id);
    $customer = $this->Loan_model->get_one($re->customer_account);
$data = array(
    'customer_account'=>$re->customer_account,
    'transaction_id'=>$re->transaction_id,
    'amount'=>$re->adebit,
    'date'=>$re->adate,
    'balance'=>$re->cbalance,
    'name'=>$customer->customer_name
);
    $this->load->view('account/print_receipt',$data);

}
function  print_loan_receipt ($id){



$nextdatevalue='';
    $re = $this->Transaction_model->get_my_transaction_filter_one($id);
    $customer = $this->Loan_model->get_one($re->customer_account);
    $next_date = $this->Loan_model->get_next($re->customer_account);
    if (is_array($next_date)) {
        // $next_date is an array, meaning 'return Array();' was executed
        // You can handle this case here
        $nextdatevalue='';
    } elseif (isset($next_date)) {
        // $next_date is set, meaning it holds a value from the function call
        // You can handle this case here
        $nextdatevalue=$next_date->payment_schedule;
    } else {
        // $next_date is neither an array nor set
        // You can handle this case here
        $nextdatevalue='';
    }
    $payments = $this->Payement_schedules_model->get_total($customer->loan_id);
    $data = array(
        'customer_account'=>$re->customer_account,
        'transaction_id'=>$re->transaction_id,
        'amount'=>$re->adebit,
        'total_amount'=>$payments->total_payment,
        'total_paid_amount'=>$payments->paid_amount,
        'date'=>$re->adate,
        'next_date'=> $nextdatevalue,
        'name'=>$customer->customer_name
    );
    $this->load->view('account/loan_balance_receipt',$data);


}
public function email_receipt($id){
    $re = $this->Transaction_model->get_my_transaction_filter_one($id);
    $customer = $this->Loan_model->get_one($re->customer_account);
    $next_date = $this->Loan_model->get_next($re->customer_account);
    $payments = $this->Payement_schedules_model->get_total($customer->loan_id);
    $data = array(
        'customer_account'=>$re->customer_account,
        'transaction_id'=>$re->transaction_id,
        'amount'=>$re->adebit,
        'total_amount'=>$payments->total_payment,
        'total_paid_amount'=>$payments->paid_amount,
        'date'=>$re->adate,
        'next_date'=>$next_date->payment_schedule,
        'name'=>$customer->customer_name
    );

    $this->load->library('Pdf');
    $html = $this->load->view('account/email_receipt', $data);
//    $this->pdf->createPDF($html, $data['name']." receipt report as on".date('Y-m-d'), true);
}
	public function get_teller_transaction_reconciliation(){
        $id = $this->input->post('account');
        $from = $this->input->post('from');
        $to = $this->input->post('to');
		$data['re'] = $this->Transaction_model->get_my_transaction_filter($id,$from, $to);
		if($this->input->post('submit')=="Export excel"){
$this->excel($data);
        }else {

            $this->load->view('admin/header');
            $this->load->view('account/reconciliation', $data);
            $this->load->view('admin/footer');
        }

	}
	function get_teller_balance($id){

		$get_account = $this->Tellering_model->get_teller_account($id);
		echo json_encode(array('status'=>'success','balance'=>$get_account->balance));
	}
    public function index()
    {
        $q = urldecode($this->input->get('q', TRUE));
        $start = intval($this->input->get('start'));
        
        if ($q <> '') {
            $config['base_url'] = base_url() . 'account/index.html?q=' . urlencode($q);
            $config['first_url'] = base_url() . 'account/index.html?q=' . urlencode($q);
        } else {
            $config['base_url'] = base_url() . 'account/index.html';
            $config['first_url'] = base_url() . 'account/index.html';
        }

        $config['per_page'] = 10;
        $config['page_query_string'] = TRUE;
        $config['total_rows'] = $this->Account_model->total_rows($q);
        $account = $this->Account_model->get_limit_data($config['per_page'], $start, $q);

        $this->load->library('pagination');
        $this->pagination->initialize($config);

        $data = array(
            'account_data' => $account,
            'q' => $q,
            'pagination' => $this->pagination->create_links(),
            'total_rows' => $config['total_rows'],
            'start' => $start,
        );
        $this->load->view('account/account_list', $data);
    }

    public function read($id) 
    {
        $row = $this->Account_model->get_by_id($id);
        if ($row) {
            $data = array(
		'account_id' => $row->account_id,
		'client_id' => $row->client_id,
		'account_number' => $row->account_number,
		'balance' => $row->balance,
		'account_type' => $row->account_type,
		'account_type_product' => $row->account_type_product,
		'added_by' => $row->added_by,
		'date_added' => $row->date_added,
	    );
            $this->load->view('account/account_read', $data);
        } else {
            $this->session->set_flashdata('message', 'Record Not Found');
            redirect(site_url('account'));
        }
    }
	public function savings(){
		$data['account_data'] =$this->Account_model->get_all();
		$menu_toggle['toggles'] = 46;
		$this->load->view('admin/header', $menu_toggle);
		$this->load->view('account/savings_accounts',$data);
		$this->load->view('admin/footer');
	}
	public function edit(){
		$data['account_data'] =$this->Account_model->get_all();
		$menu_toggle['toggles'] = 46;
		$this->load->view('admin/header', $menu_toggle);
		$this->load->view('account/savings_accounts_edit',$data);
		$this->load->view('admin/footer');
	}
	public function to_delete(){
		$data['account_data'] =$this->Account_model->get_all();
		$menu_toggle['toggles'] = 46;
		$this->load->view('admin/header', $menu_toggle);
		$this->load->view('account/savings_accounts_delete',$data);
		$this->load->view('admin/footer');
	}
	public function to_deactivate(){
		$data['account_data'] =$this->Account_model->get_all();
		$menu_toggle['toggles'] = 46;
		$this->load->view('admin/header', $menu_toggle);
		$this->load->view('account/savings_accounts_block',$data);
		$this->load->view('admin/footer');
	}
	function get_vv($id){
$d = $this->Account_model->get_account($id);
echo json_encode(array('status'=>'success','data'=>$d));
	}
	public function teller(){

		$this->load->view('admin/header');
		$this->load->view('account/cashier');
		$this->load->view('admin/footer');
	}
    public function create() 
    {
        $data = array(
            'button' => 'Create',
            'action' => site_url('account/create_action'),
	    'account_id' => set_value('account_id'),
	    'client_id' => set_value('client_id'),
	    'account_number' => set_value('account_number'),
	    'balance' => set_value('balance'),
	    'account_type' => set_value('account_type'),
	    'account_type_product' => set_value('account_type_product'),
	    'added_by' => set_value('added_by'),
	    'date_added' => set_value('date_added'),
			'customers' =>$this->Individual_customers_model->get_all_active()
	);
		$menu_toggle['toggles'] = 46;
		$this->load->view('admin/header', $menu_toggle);
        $this->load->view('account/account_form', $data);
		$this->load->view('admin/footer');
    }
	function search(){
		$res = array();
		$search_group = $this->input->post('agroup');
		$search_code = $this->input->post('searchcode');
		$search_by =  $this->input->post('search_by');
		$result = $this->Account_model->search($search_code,$search_by,$search_group);

		if(!empty($result)){
			$res['status']= 'success';
			$res['data'] = $result;
			$res['message'] = ' data found';
		}else{
			$res['status']= 'success';
			$res['data'] = "";
			$res['message'] = ' data not found';
		}
		echo json_encode($res);


	}
    public function create_action() 
    {
        $this->_rules();

        if ($this->form_validation->run() == FALSE) {
            $this->create();
        } else {
            $data = array(
		'client_id' => $this->input->post('client_id',TRUE),
		'account_number' => $this->input->post('account_number',TRUE),
		'balance' => 0,
		'account_type' => 1,
		'account_type_product' => $this->input->post('account_type_product',TRUE),
		'added_by' => $this->session->userdata('user_id'),

	    );

            $this->Account_model->insert($data);
            $this->session->set_flashdata('message', 'Create Record Success');
            redirect(site_url('account/savings'));
        }
    }
    
    public function update($id) 
    {
        $row = $this->Account_model->get_by_id($id);

        if ($row) {
            $data = array(
                'button' => 'Update',
                'action' => site_url('account/update_action'),
		'account_id' => set_value('account_id', $row->account_id),
		'client_id' => set_value('client_id', $row->client_id),
		'account_number' => set_value('account_number', $row->account_number),
		'balance' => set_value('balance', $row->balance),
		'account_type' => set_value('account_type', $row->account_type),
		'account_type_product' => set_value('account_type_product', $row->account_type_product),
		'added_by' => set_value('added_by', $row->added_by),
		'date_added' => set_value('date_added', $row->date_added),
	    );
            $this->load->view('account/account_form', $data);
        } else {
            $this->session->set_flashdata('message', 'Record Not Found');
            redirect(site_url('account/savings'));
        }
    }
    

    
    public function delete($id) 
    {
        $row = $this->Account_model->get_by_id($id);

        if ($row) {
            $this->Account_model->delete($id);
            $this->toaster->success('Delete Record Success');
            redirect($_SERVER['HTTP_REFERER']);
        } else {
            $this->toaster->error('Record Not Found');
            redirect($_SERVER['HTTP_REFERER']);
        }
    }
    public function block($id)
    {
        $row = $this->Account_model->get_by_id($id);

        if ($row) {
        	$data = array('account_status'=>"Closed");
            $this->Account_model->update($id, $data);
            $this->toaster->success('message', 'Delete Record Success');
            redirect($_SERVER['HTTP_REFERER']);
        } else {
            $this->toaster->error('Record Not Found');
			redirect($_SERVER['HTTP_REFERER']);
        }
    }public function unblock($id)
    {
        $row = $this->Account_model->get_by_id($id);

        if ($row) {
        	$data = array('account_status'=>"Active");
            $this->Account_model->update($id, $data);
            $this->toaster->success('message', 'Delete Record Success');
            redirect($_SERVER['HTTP_REFERER']);
        } else {
            $this->toaster->error('Record Not Found');
			redirect($_SERVER['HTTP_REFERER']);
        }
    }
    public function excel($edata)
    {
		
    }
    public function _rules() 
    {
	$this->form_validation->set_rules('client_id', 'client id', 'trim|required');
	$this->form_validation->set_rules('account_number', 'account number', 'trim|required');

	$this->form_validation->set_rules('account_type_product', 'account type product', 'trim|required');


	$this->form_validation->set_rules('account_id', 'account_id', 'trim');
	$this->form_validation->set_error_delimiters('<span class="text-danger">', '</span>');
    }
    public  function loan_transaction_reversal($id)
    {
        $transid ="TR-S" . rand(100, 9999) . date('Y') . date('m') . date('d');
        $re = $this->Transaction_model->get_my_transaction_filter_one($id);
        $collection_account = $this->Transaction_model->get_collection_account($re->transaction_id);
        $loan_account = $this->Transaction_model->get_loan_account($re->transaction_id);
        $teller_account = $this->Transaction_model->get_teller_account($re->transaction_id);
        $date = date('Y-m-d');

//        trans begin
//       $this->Account_model->transfer_funds($collection_account->account_number , $loan_account->account_number,$collection_account->credit, $transid);
//       $this->Account_model->cash_transaction($teller_account->account_number,$loan_account->account_number,$collection_account->credit,'withdraw',$transid, $date);
       
//        $logger = array(
//
//            'user_id' => $this->session->userdata('user_id'),
//            'activity' => 'Reversed loan payment, loan ID:' . ' ' . $loan_account->account_number . ' ' . ' Transaction number' . ' ' . $re->transaction_id ." reversal trans id".$transid.
//                ' ' . 'amount' . ' ' . $collection_account->credit,
//        );
//
//        log_activity($logger);
       $transactions = $this->db->select("*")->from('transactions')->where('ref',$re->transaction_id)->get()->result();
      foreach ($transactions as $transaction){
        $paynum= $this->db->get('payement_schedules')->where('transactions_id',$transaction->transaction_id);
        $final_amount = $paynum->paid_amount-$transaction->amount;
          $this->db->insert('transactions',$transaction);
      }
        exit();

       redirect ($_SERVER['HTTP_REFERER']);
    }

}

