<?php
require APPPATH . '/libraries/CreatorJwt.php';


class Customer extends CI_Controller
{
	function __construct()
	{

		parent::__construct();

		$this->objOfJwt = new CreatorJwt();

		$this->load->model('Agent_model', 'Agent');
		$this->load->model('Check_pin_counter_model');
		$this->load->model('Account_model');
		$this->load->model('Transaction_reversal_requests_model');

		$this->load->model('Airtime_purchase_logger_model');
		$this->load->model('System_users_model');
		$this->load->model('Merchant_sms_logger_model');
		$this->load->model('Transactions_model');
		$this->load->model('Customer_verification_model');
		$this->load->model('Favourites_model');
		$this->load->model('Security_questions_model');
		$this->load->model('Customer_model');
		$this->load->model('Kyc_id_model');
		$this->load->model('Customer_security_questions_responses_model', 'security_questions');
		$this->load->model('Airtime_purchase_logger_model');
		$this->load->library('form_validation');
		$this->load->helper('string');
	}

	public function melo()
	{
		$msg = 'hellom wolrd';
		try {
			mail("ceo@techsoft-mw.com", "My subject", $msg);
			echo "mail  sent";
		} catch (Exception $e) {
			echo "mail not sent";
		}
	}



	public function test(){

		$phones = $this->Customer_model->getphone();

		$data['phones'] = $phones;
		$data['chart_data'] = $this->Customer_model->get_chart_data();
		// load view

		$this->template->set('title', 'Customer');
		$this->template->load('new_template', 'contents' ,'customer/customer_view', $data);
//        $this->load->view('customer/customer_view',$data);

	}


	public function userList(){
		// POST data
		$postData = $this->input->post();

		// Get data
		$data = $this->Customer_model->getUsers($postData);

		echo json_encode($data);

	}





//reset password
	public function reset_customer_pass($phone){

		$password = rand(1000, 9999);
		$data = array(
			'auth_key'=>md5($password)
		);

		$this->Customer_model->update($phone,$data);

		$concat_message='Dear Customer your new password is:'.$password;
		send_sms($phone,$concat_message);
		print_r($password);
		$this->toastr->success( 'Update Record Success');
		redirect(site_url('customer'));
	}


//reset password
	public function reset_customer_pin($phone){

		$pin = rand(1000, 9999);
		$data=array(
			'pin'=>md5($pin)
		);

		$this->Account_model->update_account_status($phone,$data);
		$concat_message='Dear Customer your new pin is:'.$pin;
		send_sms($phone,$concat_message);

		$this->toastr->success( 'Update Record Success');
		redirect(site_url('customer'));
	}




	//changing customer account status
	public function block_account_status($phone)
	{
		$data = array(
			'account_status' => 'BLOCKED'
		);
		$this->Account_model->update_account_status($phone, $data);

		$this->toastr->success('User has been blocked');
		redirect(site_url('customer'));

	}
	//changing customer account status
	public function activate_account_status($phone){
		$data=array(
			'account_status'=>'ACTIVE'
		);
		$this->Account_model->update_account_status($phone,$data);

		$this->toastr->success('User Activated successfully');
		redirect(site_url('customer'));

	}
	public function view_report()
	{
		$this->template->set('title', 'Customer');
		$this->template->load('new_template', 'contents' ,'customer/report_view');

	}

	function test3(){
		echo $this->Transactions_model->sum_moneyout("+265994099461");
	}

	public function  get_gender(){
		$phones = $this->Customer_model->getphone();

		$data['phones'] = $phones;

		$data['chart_data'] = $this->Customer_model->get_chart_data();


		$this->template->set('title', 'Customer');
		$this->template->load('new_template', 'contents' ,'customer/pie_chart_view', $data);
	}

	public function gender_report(){
		if($this->input->post('submit')) {

			$data = array(
				'gender'=>$this->input->post('gender'),


			);

			$data['chart_data'] = $this->Customer_model->get_chart_data();
			$data['customer_data']=$this->Customer_model->users_gender_report($data);

			$this->template->set('title', 'Customer');
			$this->template->load('new_template', 'contents' ,'customer/customer_gender', $data);
		}
		else
		{
			$this->get_gender();
		}

	}


	public function index()
	{
		$q = $this->input->get('q', TRUE);
		$start = intval($this->input->get('start'));

		if ($q <> '') {
			$config['base_url'] = base_url() . 'customer/index?q=' . urlencode($q);
			$config['first_url'] = base_url() . 'customer/index?q=' . urlencode($q);
		} else {
			$config['base_url'] = base_url() . 'customer/index';
			$config['first_url'] = base_url() . 'customer/index';
		}

		$config['per_page'] = 10;
		$config['page_query_string'] = TRUE;
		$config['total_rows'] = $this->Customer_model->total_rows($q);
		$customer = $this->Customer_model->get_limit_data($config['per_page'], $start, $q);

		$this->load->library('pagination');
		$this->pagination->initialize($config);

		$data = array(
			'customer_data' => $customer,
			'q' => $q,
			'pagination' => $this->pagination->create_links(),
			'total_rows' => $config['total_rows'],
			'start' => $start,
		);
		//$this->load->view('customer/customer_list', $data);
		$this->template->set('title', 'Customer');
		$this->template->load('new_template', 'contents' ,'customer/customer_list', $data);
	}

	public function enquiries()
	{
		$q = $this->input->get('q', TRUE);
		$start = intval($this->input->get('start'));

		if ($q <> '') {
			$config['base_url'] = base_url() . 'customer/index?q=' . urlencode($q);
			$config['first_url'] = base_url() . 'customer/index?q=' . urlencode($q);
		} else {
			$config['base_url'] = base_url() . 'customer/index';
			$config['first_url'] = base_url() . 'customer/index';
		}

		$config['per_page'] = 10;
		$config['page_query_string'] = TRUE;
		$config['total_rows'] = $this->Customer_model->total_rows($q);
		$customer = $this->Customer_model->get_limit_data($config['per_page'], $start, $q);

		$this->load->library('pagination');
		$this->pagination->initialize($config);

		$data = array(
			'customer_data' => $customer,
			'q' => $q,
			'pagination' => $this->pagination->create_links(),
			'total_rows' => $config['total_rows'],
			'start' => $start,
		);
		//$this->load->view('customer/customer_list', $data);
		$this->template->set('title', 'Customer');
		$this->template->load('new_template', 'contents' ,'customer/enquiries_view', $data);
	}
	public function kyc_approval()
	{
		$q = $this->input->get('q', TRUE);


		$start = intval($this->input->get('start'));

		if ($q <> '') {
			$config['base_url'] = base_url() . 'customer/kyc_approval?q=' . urlencode($q);
			$config['first_url'] = base_url() . 'customer/kyc_approval?q=' . urlencode($q);
		} else {
			$config['base_url'] = base_url() . 'customer/kyc_approval';
			$config['first_url'] = base_url() . 'customer/kyc_approval';
		}

		$config['per_page'] = 10;
		$config['page_query_string'] = TRUE;
		$config['total_rows'] = $this->Customer_model->total_rows_kyc($q);
		$customer = $this->Customer_model->get_limit_data_kyc($config['per_page'], $start, $q);

		$this->load->library('pagination');
		$this->pagination->initialize($config);

		$data = array(
			'customer_data' => $customer,
			'q' => $q,
			'pagination' => $this->pagination->create_links(),
			'total_rows' => $config['total_rows'],
			'start' => $start,
		);
		//$this->load->view('customer/customer_list', $data);
		$this->template->set('title', 'Customer');
		$this->template->load('new_template', 'contents' ,'customer/kyc_approval', $data);
	}

	public function kyc_approved()
	{
		$q = $this->input->get('q', TRUE);


		$start = intval($this->input->get('start'));

		if ($q <> '') {
			$config['base_url'] = base_url() . 'customer/kyc_approved?q=' . urlencode($q);
			$config['first_url'] = base_url() . 'customer/kyc_approved?q=' . urlencode($q);
		} else {
			$config['base_url'] = base_url() . 'customer/kyc_approved';
			$config['first_url'] = base_url() . 'customer/kyc_approved';
		}

		$config['per_page'] = 10;
		$config['page_query_string'] = TRUE;
		$config['total_rows'] = $this->Customer_model->total_rows_kyc_approved($q);
		$customer = $this->Customer_model->get_limit_data_kyc_approved($config['per_page'], $start, $q);

		$this->load->library('pagination');
		$this->pagination->initialize($config);

		$data = array(
			'customer_data' => $customer,
			'q' => $q,
			'pagination' => $this->pagination->create_links(),
			'total_rows' => $config['total_rows'],
			'start' => $start,
		);
		//$this->load->view('customer/customer_list', $data);
		$this->template->set('title', 'Customer');
		$this->template->load('new_template', 'contents' ,'customer/kyc_approved', $data);
	}

	public function kyc_rejected()
	{
		$q = $this->input->get('q', TRUE);


		$start = intval($this->input->get('start'));

		if ($q <> '') {
			$config['base_url'] = base_url() . 'customer/kyc_rejected?q=' . urlencode($q);
			$config['first_url'] = base_url() . 'customer/kyc_rejected?q=' . urlencode($q);
		} else {
			$config['base_url'] = base_url() . 'customer/kyc_rejected';
			$config['first_url'] = base_url() . 'customer/kyc_rejecte';
		}

		$config['per_page'] = 10;
		$config['page_query_string'] = TRUE;
		$config['total_rows'] = $this->Customer_model->total_rows_kyc($q);
		$customer = $this->Customer_model->get_limit_data_kyc_rejected($config['per_page'], $start, $q);

		$this->load->library('pagination');
		$this->pagination->initialize($config);

		$data = array(
			'customer_data' => $customer,
			'q' => $q,
			'pagination' => $this->pagination->create_links(),
			'total_rows' => $config['total_rows'],
			'start' => $start,
		);
		//$this->load->view('customer/customer_list', $data);
		$this->template->set('title', 'Customer');
		$this->template->load('new_template', 'contents' ,'customer/kyc_rejected', $data);
	}

	public function get_transacted(){
		$from = $this->input->post('from');
		$to= $this->input->post('to');

		$q = $this->input->get('q', TRUE);
		$start = intval($this->input->get('start'));

		if ($q <> '') {
			$config['base_url'] = base_url() . 'customer/get_transacted?q=' . urlencode($q);
			$config['first_url'] = base_url() . 'customer/get_transacted?q=' . urlencode($q);
		} else {
			$config['base_url'] = base_url() . 'customer/get_transacted';
			$config['first_url'] = base_url() . 'customer/get_transacted';
		}

		$config['per_page'] = 10;
		$config['page_query_string'] = TRUE;
		$config['total_rows'] = $this->Customer_model->get_transacted_count($from,$to);
		$customer = $this->Customer_model->get_transacted($config['per_page'], $start ,$from,$to);

		$this->load->library('pagination');
		$this->pagination->initialize($config);


		$data = array(
			'customer_data' => $customer,
			'q' => $q,
			'from'=>$from,
			'to'=>$to,
			'pagination' => $this->pagination->create_links(),
			'total_rows' => $config['total_rows'],
			'start' => $start,
		);


		$this->template->set('title', 'Customer');
		$this->template->load('new_template', 'contents' ,'reports/transacted_customers', $data);

	}
	public function get_non_kyc_customers()
	{
		$q = urldecode($this->input->get('q', TRUE));
		$start = intval($this->input->get('start'));

		if ($q <> '') {
			$config['base_url'] = base_url() . 'customer/get_non_kyc_customers?q=' . urlencode($q);
			$config['first_url'] = base_url() . 'customer/get_non_kyc_customers?q=' . urlencode($q);
		} else {
			$config['base_url'] = base_url() . 'customer/get_non_kyc_customers';
			$config['first_url'] = base_url() . 'customer/get_non_kyc_customers';
		}

		$config['per_page'] = 10;
		$config['page_query_string'] = TRUE;
		$config['total_rowss'] = $this->Customer_model->total_rowss($q);
		$customer = $this->Customer_model->get_limit_dataa($config['per_page'], $start, $q);

		$this->load->library('pagination');
		$this->pagination->initialize($config);


		$data = array(
			'customer_data' => $customer,
			'q' => $q,
			'paginations' => $this->pagination->create_links(),
			'total_rowss' => $config['total_rowss'],
			'start' => $start,
		);
		//$this->load->view('customer/customer_list', $data);
		$this->template->set('title', 'Customer');
		$this->template->load('new_template', 'contents' ,'customer/customer_list_non_kyc', $data);
	}


	public function read($id)
	{
		$row = $this->Customer_model->get_by_id($id);
		if ($row) {
			$data = array(

				'firstname' => $row->firstname,
				'middlename' => $row->middlename,
				'lastname' => $row->lastname,
				'gender' => $row->gender,
				'dob' => $row->dob,
				'email_address' => $row->email_address,
				'phone_number' => $row->phone_number,
				'auth_key' => $row->auth_key,
				'stamp' => $row->stamp,
				'image_url' => $row->image_url,
				'id_number' => $row->id_number,
				'issue_date' => $row->issue_date,
				'expiry_date' => $row->expiry_date,
				'image' => $row->image_url,
				'institution_type' => $row->institution_type,
				'institution_name' => $row->institution_name,
				'utility_bill' => $row->utility_bill,
				'map' => $row->map,
				'kyc_added_by' => $row->kyc_added_by,
				'user_type_added_kyc' => $row->user_type_added_kyc

			);

//            $this->load->view('customer/customer_read', $data);
			$this->template->set('title', 'Customer');
			$this->template->load('new_template', 'contents' ,'customer/customer_read', $data);
		} else {
			$this->session->set_flashdata('message', 'Record Not Found');
			redirect(site_url('customer'));
		}


	}
	function read_new(){
		$res = array();
		$user= $_POST['User'];

		$row = $this->Customer_model->get_by_id('+'.$user);
		if (!empty($row)) {
			$data = array(

				'firstname' => $row->firstname,
				'middlename' => $row->middlename,
				'lastname' => $row->lastname,
				'gender' => $row->gender,
				'dob' => $row->dob,
				'email_address' => $row->email_address,
				'phone_number' => $row->phone_number,
				'auth_key' => $row->auth_key,
				'stamp' => $row->stamp,
				'image_url' => $row->image_url,
				'id_number' => $row->id_number,
				'issue_date' => $row->issue_date,
				'expiry_date' => $row->expiry_date,
				'image' => $row->image_url,
				'institution_type' => $row->institution_type,
				'institution_name' => $row->institution_name,
				'utility_bill' => $row->utility_bill,
				'map' => $row->map,
				'kyc_added_by' => $row->kyc_added_by,
				'user_type_added_kyc' => $row->user_type_added_kyc

			);

			// $res['message'] = 'Data retrieved ';
			$this->load->view('customer/customer_read',$data);
//            $this->template->set('title', 'Customer');
//            $this->template->load('new_template', ',modal_content' ,'customer/customer_read', $data);

		} else {

			$res['status'] = 'error';
			$res['message'] = 'No data retrieved ';
		}


		echo json_encode($res);

	}


	public function create()
	{
		$data = array(
			'button' => 'Create',
			'action' => site_url('customer/create_action'),
			'kyc' => set_value('kyc'),
			'firstname' => set_value('firstname'),
			'middlename' => set_value('middlename'),
			'lastname' => set_value('lastname'),
			'gender' => set_value('gender'),
			'dob' => set_value('dob'),
			'email_address' => set_value('email_address'),
			'phone_number' => set_value('phone_number'),
			'auth_key' => set_value('auth_key'),
			'stamp' => set_value('stamp'),
			'image_url' => set_value('image_url'),
		);
		//$this->load->view('customer/customer_form', $data);
		$this->template->set('title', 'Customer');
		$this->template->load('new_template', 'contents' ,'customer/customer_form', $data);
	}

	public function  approve($id){
		$this->Customer_model->approve($id);
		$this->toastr->success( 'Approval was Successful');
		redirect('customer/kyc_approval');
	}

	public function  approve_kyc($id){
		$this->Customer_model->approve($id);
		$this->toastr->success( 'Reject was Successful');
		redirect('customer/kyc_rejected');
	}

	public function  reject($id){
		$this->Customer_model->reject($id);
		$this->toastr->success( 'Approval was Successful');
		redirect('customer/kyc_approval');
	}

	public function  reject_kyc($id){
		$this->Customer_model->reject($id);
		$this->toastr->success( 'Reject was Successful');
		redirect('customer/kyc_approved');
	}

	public function create_action(){
		$this->_rules();
		if ($this->form_validation->run() == FALSE) {
			$this->create();
		} else {
			$data = array(
				'kyc' => $this->input->post('kyc',TRUE),
				'firstname' => $this->input->post('firstname',TRUE),
				'middlename' => $this->input->post('middlename',TRUE),
				'lastname' => $this->input->post('lastname',TRUE),
				'gender' => $this->input->post('gender',TRUE),
				'dob' => $this->input->post('dob',TRUE),
				'email_address' => $this->input->post('email_address',TRUE),
				'phone_number' => $this->input->post('phone',TRUE),
				'auth_key' => $this->input->post('auth_key',TRUE),
				// 'stamp' => $this->input->post('stamp',TRUE),
				'image_url' => $this->input->post('image_url',TRUE),
			);
			$this->Customer_model->insert($data);
			$this->session->set_flashdata('message', 'Create Record Success');
			redirect(site_url('customer'));
		}
	}

	public function ussd_register(){
		$phoneCode = rand(1000, 9999);
		$channel = isset($_POST['channel']) ? $_POST['channel']:'ussd';
		//Creating json response array of possible outcomes
		$res = array();
		//receiving post data and keeping them in array
		$data = array(
			'kyc'           =>  '000000',
			'firstname'     =>  $this->input->post('fname',TRUE),
			'lastname'      =>  $this->input->post('lname',TRUE),
			'gender'        =>  $this->input->post('gender',TRUE),
			'phone_number'  =>  preg_replace('/\s/', '', $this->input->post('phone',TRUE)),
			'auth_key'      =>  uniqid('',true),
			'image_url'     =>  uniqid('',true),
			'channel'       =>  $channel,
			'district_id'   =>  $this->input->post('district',TRUE),
		);
		if($channel==='app'){
			$data['source_of_income'] = $_POST['income'];
			$data['monthly_earning']  = $_POST['earning'];
		}
		$account=array(
			'primary_account'   =>  preg_replace('/\s/', '', $this->input->post('phone',TRUE)),
			'account_number'    =>  preg_replace('/\s/', '', $this->input->post('phone',TRUE)),
			'pin'               =>  md5('000000')
		);
		$vericode=array(
			'account_number'=>preg_replace('/\s/', '', $this->input->post('phone',TRUE)),
			'code'=>$phoneCode
		);
		$concat_message = 'Your KakuPay Confirmation number  is '.$phoneCode.'. Dial *3008# and enter the code to activate your account';
		//checkin if account is created or not
		if($data['firstname']!="" && $data['lastname'] !="" && $data['gender']!="" && $data['district_id']!=""){
			if(!preg_match ('/^([a-zA-Z]+)$/', $data['firstname']) && !preg_match ('/^([a-zA-Z]+)$/', $data['lastname']) )
			{
				$res['status']='error';
				$res['message']='Sorry ,name can only have characters';
			}else{
				$result = $this->Customer_model->insert($data,$account,$vericode);
				if($result){
					$logger = array(
						'user_type' =>  'CUSTOMER',
						'user_id'   =>  $data['phone_number'],
						'activity'  =>  'Registered account'
					);
					log_activity($logger);
					try{
						if($channel!='app')
							send_sms(preg_replace('/\s/', '', $this->input->post('phone',TRUE)),$concat_message);
						$res['status']='success';
						$res['message']='Thank You, Account has been Created please wait for verification code';
					}
					catch(Exception $e){
						if($channel!='app')
							send_sms(preg_replace('/\s/', '', $this->input->post('phone',TRUE)),$concat_message);
						$res['status']='success';
						$res['message']='Thank You, Account has been Created please wait for verification code';
					}
				}
				else{
					$res['status']='error';
					$res['message']='There was Error When Registering your Account';
				}
			}
		}else{
			$res['status']='error';
			$res['message']='Sorry ,all inputs must be filled';
		}
		exit(json_encode($res));
	}

	public function create_action_json()
	{
		$phoneCode = rand(1000, 9999);
		//Creating json response array of possible outcomes
		$res = array();

		//recieving post data and keeping them in array
		$data = array(
			'kyc' => '000000',
			'firstname' => $this->input->post('fname',TRUE),
			'middlename' => $this->input->post('mname',TRUE),
			'lastname' => $this->input->post('lname',TRUE),
			'gender' => $this->input->post('gender',TRUE),
			'dob' => date('Y-m-d',strtotime($this->input->post('dob',TRUE))),
			'email_address' => $this->input->post('email',TRUE),
			'phone_number' => preg_replace('/\s/', '', $this->input->post('phone',TRUE)),
			'auth_key' =>md5($this->input->post('password',TRUE)),
			'image_url' => uniqid('',true),
			'district_id'=>$this->input->post('district',TRUE),
		);
		//cchekin if account exist or not
		$check_phone_exisist = $this->Account_model->check_phone_exist($this->input->post('phone',TRUE));
		if($check_phone_exisist){
			$res['status']='warning';
			$res['message']='Sorry!, An account with this phone number already exist!';
		}else{
			$account=array(
				'account_number'=>preg_replace('/\s/', '', $this->input->post('phone',TRUE)),
				'pin'=>md5('000000')
			);
			$vericode=array(
				'account_number'=>preg_replace('/\s/', '', $this->input->post('phone',TRUE)),
				'code'=>$phoneCode
			);
			$concat_message='Your Kakupay Confirmation number  is:'.$phoneCode;

			//checkin if account is created or not

			// checking if Customer details are inserted or not

			if($data['firstname']!="" && $data['lastname'] !="" && $data['gender']!="" && $data['district_id']!="" && $data['phone_number']!="") {
				if (!preg_match('/^([a-zA-Z]+)$/', $data['firstname']) && !preg_match('/^([a-zA-Z]+)$/', $data['lastname'])) {
					$res['status'] = 'error';
					$res['message'] = 'Sorry ,name can only have characters';

				} else {
					$result = $this->Customer_model->insert($data, $account, $vericode);
					if ($result) {
						$logger = array(
							'user_type' => 'CUSTOMER',
							'user_id' => $data['phone_number'],
							'activity' => 'Registered account'
						);
						log_activity($logger);
						try {
							send_sms(preg_replace('/\s/', '', $this->input->post('phone', TRUE)), $concat_message);
//							sendMail($this->input->post('email'),'Registration Verification Code',$concat_message);
							$res['status'] = 'success';
							$res['message'] = 'Thank You, Account has been Created please wait for verification code';
						} catch (Exception $e) {
							send_sms(preg_replace('/\s/', '', $this->input->post('phone', TRUE)), $concat_message);
//                            sendMail($this->input->post('email'),'Registration Verification Code',$concat_message);
							$res['status'] = 'success';
							$res['message'] = 'Thank You, Account has been Created please wait for verification code';
						}


					} else {
						///$this->Account_model->delete_this_account($this->input->post('phone',TRUE));
						$res['status'] = 'error';
						$res['message'] = 'There was Error When Registering your Account';
					}
				}
			}

		}

		echo json_encode($res);

	}

	public function verify_customer(){
		$res = array();
		$data =array(
			'account_number'=>preg_replace('/\s/', '', $this->input->post('phone',TRUE)),
			'code'=>$this->input->post('verification_code')
		);
		$result1 = $this->Customer_verification_model->verify_customer($data);
		if($result1){
			$result=$this->Customer_model->get_by_account_number(preg_replace('/\s/', '', $this->input->post('phone',TRUE)));
			$rows=array(
				'firstname' => $result['firstname'],
				'middlename' => $result['middlename'],
				'lastname' => $result['lastname'],
				'gender' => $result['gender'],
				'dob' => $result['dob'],
				'email_address' => $result['email_address'],
				'phone_number' => $result['phone_number'],
				'stamp' => $result['stamp'],
				'image_url' => $result['image_url'],
			);
			$res['data']=$rows;
			$res['status']='success';
			$res['message']='Success,  the code you entered was verified ';
		}else{
			$res['status']='error';
			$res['message']='Sorry,  the code you entered is incorrect';
		}
		echo json_encode($res);
	}


	public function verify_customer_ussd(){
		$res = array();
		$data =array(
			'account_number'=>preg_replace('/\s/', '', $this->input->post('phone',TRUE)),
			'code'=>$this->input->post('verification_code')
		);
		$result1 = $this->Customer_verification_model->verify_customer($data);
		if($result1){
			$result=$this->Customer_model->get_by_account_number(preg_replace('/\s/', '', $this->input->post('phone',TRUE)));
			$rows=array(
				'firstname' => $result['firstname'],
				'middlename' => $result['middlename'],
				'lastname' => $result['lastname'],
				'gender' => $result['gender'],
				'dob' => $result['dob'],
				'email_address' => $result['email_address'],
				'phone_number' => $result['phone_number'],
				'stamp' => $result['stamp'],
				'image_url' => $result['image_url'],
			);

			$res['status']='success';
			$res['message']='Success,  the code you entered was verified ';
		}else{
			$res['status']='error';
			$res['message']='Sorry,  the code you entered is incorrect';
		}
		echo json_encode($res);
	}
	function verify_ussd(){
		$phone= $this->input->post('phone');
		$code= $this->input->post('code',TRUE);
		$result = $this->Customer_verification_model->verify($phone,$code);
		if($result){

			echo 'valid';
		}else{
			$phoneCode = rand(1000, 9999);
			$rr= array(
				'account_number'=>$phone,
				'code'=>$phoneCode,
			);
			$this->Customer_verification_model->readd($rr);
			$message= 'Dear Customer, your KakuPay Confirmation number  is :'.$phoneCode;
			send_sms($phone,$message);

			echo 'invalid';
		}
	}
	function account_activate(){

		$data =array(
			'account_number'=>$this->input->post('phone',TRUE),
			'code'=>$this->input->post('code'),
			'pin'=>md5($this->input->post('pin')),
		);
		$result= $this->Customer_verification_model->activation($data);
		if($result){
			echo 'success';
		}else{
			echo 'error';
		}

	}
	public function verify_merchant(){
		$res = array();
		$chec = check_exist_in_table('customer','phone_number',$this->input->post('sender'));
		$recepient = check_exist_in_table('merchant','merchant_id',$this->input->post('till_number'));
		$is_amount_negative = isNegative($this->input->post('amount'));
		if($is_amount_negative){
			$res['status'] ='error';
			$res['message'] = 'Sorry amount cannot be negative';
		}
		else{

			$data=array(
				'sender'=>$this->input->post('sender'),
				'recepient'=>$recepient->phone_number,
				'amount'=>$this->input->post('amount'),
				'description'=>$this->input->post('description'),
				'attendant'=>$this->input->post('attendant'),
				'pin'=>md5($this->input->post('pin')),
			);

			// check if account exist
			if($this->check_is_pin_default2($data['sender'])){
				$res['status']='warning';
				$res['command']='setpin';
				$res['message']='We have detected that this is your first time to make transaction, please  add a secure pin to be used for making transactions';
			}else{

				$is_number_exisit=	$this->Account_model->check_phone_exist($recepient->phone_number);
				if(!empty($recepient)){
					$is_account_active=$this->Account_model->is_account_active($data['recepient']);
					if($is_account_active){


						// check if sender has funds
						$is_balance_ok=$this->Account_model->check_balance_is_ok($data['sender'],$data['amount']);
						if($is_balance_ok){

							$money_out_limit_calculation = $this->Transactions_model->sum_moneyout($data['sender']);
//					get daily transaction limit
							$sender_moneyout_limit = $this->Account_model->transaction_limit($data['sender']);
							if($money_out_limit_calculation >= $sender_moneyout_limit->limit_amount || $money_out_limit_calculation+$data['amount']>$sender_moneyout_limit->limit_amount){
								$remainder = $sender_moneyout_limit->limit_amount-$money_out_limit_calculation;
								if($remainder > 0){
									$concat = ' or you may transfer funds  but not more than MK'. $remainder;
								}else{
									$concat ="";
								}
								$res['status']='error';
								$res['message']='Sorry, you have reached your daily debit transaction limit, please try again tomorrow '.$concat;
								send_sms($data['sender'],$res['message']);
							}else{
								$attendant = check_exist_in_table('merchant_account_users','phone_number',$data['attendant']);
								if(empty($attendant)){
									$res['status']='error';
									$res['message'] ='Sorry this attendant phone number does not exist';
								}else{
									$res['status']='success';
									$res['message']='Are you sure you want to make payment of MWK'.number_format($this->input->post('amount')).' to '.$recepient->company_name.'? ';

								}

							}






						}else{
							$res['status']='error';
							$res['message']='Sorry, You dont have enough balance to perform this transaction';
						}



					}else{
						$res['status']='error';
						$res['message']='Sorry, the account you are trying to send funds is not active ';
					}
					//check senders pin if is correct


				}else{
					$res['status']='error';
					$res['message']='Sorry, this Till number is not associated with any account';
				}

			}




		}


		echo json_encode($res);
	}
	public function pay_merchant(){
		$chec = check_exist_in_table('customer','phone_number',$this->input->post('sender'));
		$recepient = check_exist_in_table('merchant','merchant_id',$this->input->post('till_number'));
		$is_amount_negative = isNegative($this->input->post('amount'));
		if($is_amount_negative){
			$res['status'] ='error';
			$res['message'] = 'Sorry amount cannot be negative';
		}
		else{

			$res = array();
			$data=array(
				'sender'=>$this->input->post('sender'),
				'recepient'=>$recepient->phone_number,
				'amount'=>$this->input->post('amount'),
				'description'=>$this->input->post('description'),
				'attendant'=>$this->input->post('attendant'),
				'pin'=>md5($this->input->post('pin')),
			);

			// check if account exist
			if($this->check_is_pin_default2($data['sender'])){
				$res['status']='warning';
				$res['command']='setpin';
				$res['message']='We have detected that this is your first time to make transaction, please  add a secure pin to be used for making transactions';
			}else{

//				$is_number_exisit=	$this->Account_model->check_phone_exist($recepient->phone_number);
				if(!empty($recepient)){
					$is_account_active=$this->Account_model->is_account_active($data['recepient']);
					if($is_account_active){

						// check if sender has funds
						$is_balance_ok=$this->Account_model->check_balance_is_ok($data['sender'],$data['amount']);
						if($is_balance_ok){

							$money_out_limit_calculation = $this->Transactions_model->sum_moneyout($data['sender']);
//					get daily transaction limit
							$sender_moneyout_limit = $this->Account_model->transaction_limit($data['sender']);
							if($money_out_limit_calculation >= $sender_moneyout_limit->limit_amount || $money_out_limit_calculation+$data['amount']>$sender_moneyout_limit->limit_amount){
								$remainder = $sender_moneyout_limit->limit_amount-$money_out_limit_calculation;
								if($remainder > 0){
									$concat = ' or you may transfer funds  but not more than MK'. $remainder;
								}else{
									$concat ="";
								}
								$res['status']='error';
								$res['message']='Sorry, you have reached your daily transaction limit, please try again tomorrow '.$concat;
								send_sms($data['sender'],$res['message']);
							}else{
								$attendant = check_exist_in_table('merchant_account_users','phone_number',$data['attendant']);
								if(empty($attendant)){
									$res['status']='error';
									$res['message'] ='Sorry this attendant phone number does not exist';
								}else{
									$is_pin_correct= $this->Account_model->check_pin_correct($data['sender'],$data['pin']);
									if($is_pin_correct){

										//start transaction
										$transaction_success=$this->Account_model->send_money_transaction($data);
										if($transaction_success){
											$logger = array(
												'user_type'=>'CUSTOMER',
												'user_id'=>$data['sender'],
												'activity'=>'Sent money to'.$data['recepient'].'worth '.$data['amount']
											);
											log_activity($logger);
											$sender=$data['sender'];
											$reciever=$data['recepient'];
											$sms_attendant = $attendant->phone_number;
											$customer_sender= get_customer($sender);
											$customer_reciever= $attendant->phone_number;
											$sender_message='Trans ID:'.$transaction_success.', Dear Customer You have sent MWK'.number_format($data['amount'],2).' to  '.$recepient->company_name.'('.$recepient->merchant_id .')'.' Bal: MWK'.number_format($this->your_balance($sender),2);
											$reciever_message='Trans ID:'.$transaction_success.', Dear Customer You have received MWK'.number_format($data['amount'],2).' from '.$is_pin_correct['firstname'].' '.$is_pin_correct['lastname'].' ('.$sender.')';
											try{

												send_sms($sender,$sender_message);
												send_sms($reciever,$reciever_message);
												send_sms($sms_attendant,$reciever_message);
//														sendMail($customer_sender->email_address,'KakuPay Money Transfers',$sender_message);
//														sendMail($recepient->email_address,'KakuPay Money Transfers',$reciever_message);
												$res['status']='success';
												$res['message']='Transaction successful. Your new balance is MWK '.number_format($this->your_balance($sender),2);
												$logger_sms = array(
													'phone_number' => $sms_attendant,
													'mesage' => $reciever_message,
													'sender'=>$sender,
													'amount'=>$data['amount'],
													'merchant_number'=>$reciever
												);

												$this->Merchant_sms_logger_model->insert($logger_sms);

											}
											catch(Exception $e){

												send_sms($sender,$sender_message);
												send_sms($reciever,$reciever_message);
												send_sms($sms_attendant,$reciever_message);
//														sendMail($customer_sender->email_address,'KakuPay Money Transfers',$sender_message);
//														sendMail($recepient->email_address,'KakuPay Money Transfers',$reciever_message);
												$res['status']='success';
												$res['message']="Transaction Succeeded. Unfortunately we couldn't to send an SMS verification. Your new balance is MWK ".number_format($this->your_balance($sender),2);
												$res['status']='success';
												$res['message']='Transaction successful. Your new balance is MWK '.number_format($this->your_balance($sender),2);
												$logger_sms = array(
													'phone_number' => $sms_attendant,
													'mesage' => $reciever_message,
													'sender'=>$sender,
													'amount'=>$data['amount'],
													'merchant_number'=>$reciever
												);

												$this->Merchant_sms_logger_model->insert($logger_sms);


											}


										}else{
											$res['status']='error';
											$res['message']="Sorry! Something went wrong , we couldn't process your request. Please retry!";
										}


									}else{
										$res['status']='error';
										$res['message']='Denied! The pin you entered is incorrect.';
									}
								}

							}






						}else{
							$res['status']='error';
							$res['message']='Sorry, You dont have enough balance to perform this transaction';
						}



					}else{
						$res['status']='error';
						$res['message']='Sorry, the account you are trying to send funds is not active ';
					}
					//check senders pin if is correct


				}else{
					$res['status']='error';
					$res['message']='Sorry, this Till number is not associated with any account';
				}

			}




		}




		echo json_encode($res);
	}

//this function authenticates user
	public function login_user(){
		$res = array();
		$phone = preg_replace('/\s/', '', $this->input->post('phone',TRUE));
		$pass= md5($this->input->post('pass'));
		$result= $this->Customer_model->login_customer($phone,$pass);
		if($result==2){
			$this->session->set_userdata('user_id', $result['email_address']);
			$this->session->set_userdata('merchant_id', $result['merchant']);
			$this->session->set_userdata('fname', $result['first_name']);
			$this->session->set_userdata('lname', $result['last_name']);
			$this->session->set_userdata('role', $result['role']);
			$res['status']='error';
			$res['message']='Sorry, either phone number or password is incorrect';

		}else{
			if($this->Account_model->is_account_active($phone)){
				if($this->Check_pin_counter_model->is_counter_blocked($phone)){
					$logger = array(
						'user_type'=>'CUSTOMER',
						'user_id'=>$phone,
						'activity'=>'Account Blocked'
					);
					log_activity($logger);
					$res['status']='error';
					$res['data']='logout';
					$res['message']='Sorry, Your Account is blocked temporarily please try again after 24 hrs';
				}else {
					$tokenData['uniqueId'] = $result['phone_number'];
					$tokenData['role'] = 'Customer';
					$tokenData['timeStamp'] = Date('Y-m-d h:i:s');
					$jwtToken = $this->objOfJwt->GenerateToken($tokenData);
					$question=$this->get_user_security_questions($this->input->post('phone'));
					if(!empty($question)){
						$logger = array(
							'user_type'=>'CUSTOMER',
							'user_id'=>$result['phone_number'],
							'activity'=>'Logged into the system'
						);
						log_activity($logger);
						$rows = array(
							'firstname' => $result['firstname'],
							'middlename' => $result['middlename'],
							'lastname' => $result['lastname'],
							'gender' => $result['gender'],
							'dob' => $result['dob'],
							'email_address' => $result['email_address'],
							'phone_number' => $result['phone_number'],
							'stamp' => $result['stamp'],
							'image_url' => $result['image_url'],
							'token'=>$jwtToken,
						);
						$res['data'] = $rows;
						$res['status'] = 'success';
						$res['message'] = 'Welcome you have successfully  logged in';

					}else{
						$logger = array(
							'user_type'=>'CUSTOMER',
							'user_id'=>$result['phone_number'],
							'activity'=>'Logged into the system'
						);
						log_activity($logger);
						$rows = array(
							'firstname' => $result['firstname'],
							'middlename' => $result['middlename'],
							'lastname' => $result['lastname'],
							'gender' => $result['gender'],
							'dob' => $result['dob'],
							'email_address' => $result['email_address'],
							'phone_number' => $result['phone_number'],
							'stamp' => $result['stamp'],
							'image_url' => $result['image_url'],
							'token'=>$jwtToken,
						);
						$res['security_questions']=$this->get_all_questions();
						$res['data'] = $rows;
						$res['command'] = 'set_security_questions';
						$res['status'] = 'success';
						$res['message'] = 'Welcome you have successfully  logged in';
					}

				}
			}else{
				$res['status']='error';
				$res['command']='verify';
				$res['message']='Sorry, this account is not yet verified ';
			}

		}
		echo json_encode($res);

	}
//check if the number was registered via USSD or not
	function check_customer($phone){
		$res = array();
		$result= $this->Customer_model->get_by_account_number($phone);
		if(!empty($result)){
			if($result['channel']=='ussd'){
				$length = 5;

				$randomn= substr(str_shuffle('0123456789'),1,$length);
				$data = array(
					'auth_key'=>md5($randomn),
					'channel'=>'web'
				);
				$this->Customer_model->update($phone,$data);
				$message= 'Dear customer  your password is '.$randomn.' Please  make sure you change it once you login';
				send_sms($phone,$message);
				$res['status']='success';
				$res['notice']='password_sent';
				$res['message']='This number does not have  password. We have sent you one tome password';
			}else{
				$res['notice']='';
				$res['status']='success';
				$res['message']='Success the number is valid and has password';
			}

		}else{
			$res['status']='error';
			$res['message']='This number is not registered with KaKuPay';
		}
		echo json_encode($res);
	}


	function phindu_kakupay(){
		$res= array();
		$phone= $this->input->post('contact_number');
		$result= $this->Customer_model->get_by_account_number($phone);
		if(!empty($result)){
			$res['status']='success';

		}else{
			$res['status']='error';
		}
		echo json_encode($res);
	}
	function testb(){
		$sender = '+265884642594';
		$sender_message = 'Hi Misheck your OTP is 3649';
		send_sms($sender,$sender_message);
	}
//this function is used by mobile app to set up his app

	public function recover_user(){
		$res = array();
		$phone = preg_replace('/\s/', '', $this->input->post('phone',TRUE));
		$pass= md5($this->input->post('pass'));
		$result= $this->Customer_model->login_customer($phone,$pass);
		if($result==2){

			$res['status']='error';
			$res['message']='Sorry, either phone number or password is incorrect';

		}else{
			if($this->Account_model->is_account_active($phone)){
				if($this->Check_pin_counter_model->is_counter_blocked($phone)){
					$logger = array(
						'user_type'=>'CUSTOMER',
						'user_id'=>$phone,
						'activity'=>'Account blocked,more pin entry trial fails'
					);
					log_activity($logger);
					$res['status']='error';
					$res['data']='logout';
					$res['message']='Sorry, Your Account is blocked temporarily please try again after 24 hrs';
				}else {
					$question=$this->get_user_security_questions($this->input->post('phone'));
					if(!empty($question)){
						$rows = array(
							'firstname' => $result['firstname'],
							'middlename' => $result['middlename'],
							'lastname' => $result['lastname'],
							'gender' => $result['gender'],
							'dob' => $result['dob'],
							'email_address' => $result['email_address'],
							'phone_number' => $result['phone_number'],
							'stamp' => $result['stamp'],
							'image_url' => $result['image_url'],
						);
						$res['data'] = $rows;
						$res['status'] = 'success';
						$res['message'] = 'Your are verified please add v code sent to you';

					}else{
						$rows = array(
							'firstname' => $result['firstname'],
							'middlename' => $result['middlename'],
							'lastname' => $result['lastname'],
							'gender' => $result['gender'],
							'dob' => $result['dob'],
							'email_address' => $result['email_address'],
							'phone_number' => $result['phone_number'],
							'stamp' => $result['stamp'],
							'image_url' => $result['image_url'],
						);
						$res['security_questions']=$this->get_all_questions();
						$res['data'] = $rows;
						$res['command'] = 'set_security_questions';
						$res['status'] = 'success';
						$res['message'] = 'Your are verified please add v code sent to you';
					}

				}
			}else{
				$res['status']='error';
				$res['command']='verify';
				$res['message']='Sorry, this account is not yet verified ';
			}

		}
		echo json_encode($res);

	}
	public function validate_cashout_parameters(){
		$da = array(
			'phone_number'=>$this->input->post('phone')
		);

		$received_Token = $this->input->request_headers('Authorization');
		try {
			$jwtData = $this->objOfJwt->DecodeToken($received_Token['Token']);
			$d1 = $jwtData['timeStamp'];
			$uid = $jwtData['uniqueId'];
			$d2 = Date('Y-m-d h:i:s');
			$date1 = new DateTime($d1);
			$date2 = new DateTime($d2);

			$diff = $date2->diff($date1);

			$hours = $diff->h;
			$hours = $hours + ($diff->days * 24);

			if ($uid == $this->input->post('phone')) {
				if ($hours > 1) {
					$res['status'] = 'error';
					$res['message'] = "Sorry! your session has expired, please logout and login again";
				}
				else {

					$transaction_charge = get_transaction_charge($this->input->post('amount'), 14);
					$charge = $transaction_charge['charge'];
					$res = array();
					$is_amount_negative = isNegative($this->input->post('amount'));
					if ($is_amount_negative) {
						$res['status'] = 'error';
						$res['message'] = 'Sorry amount cannot be negative';
					} else {
						if ($this->check_is_pin_default2($this->input->post('phone'))) {
							$res['status'] = 'warning';
							$res['command'] = 'setpin';
							$res['message'] = 'We have detected that this is your first time to make transaction, please  add a secure pin to be used for making transactions';
						} else {
							if ($this->Agent->check_agent_code($this->input->post('agent_code'))) {
								if ($result = $this->Account_model->is_agent_account_active($this->input->post('agent_code'))) {
									if ($this->Account_model->check_balance_is_ok($this->input->post('phone'), $this->input->post('amount') + $charge)) {



										$money_out_limit_calculation = $this->Transactions_model->sum_moneyout($da['phone_number']);
//					get daily transaction limit
										$sender_moneyout_limit = $this->Account_model->transaction_limit($da['phone_number']);

										if ($money_out_limit_calculation >= $sender_moneyout_limit->limit_amount || $money_out_limit_calculation + $this->input->post('amount') > $sender_moneyout_limit->limit_amount) {
											$remainder = $sender_moneyout_limit->limit_amount - $money_out_limit_calculation;
											if ($remainder > 0) {
												$concat = ' or you may transfer funds  but not more than MK' . $remainder;
											} else {
												$concat = "";
											}
											$res['status'] = 'error';
											$res['message'] = 'Sorry, you have reached your daily transaction limit, please try again tomorrow ' . $concat;
											send_sms($da['phone_number'], $res['message']);
										} else {

											$res['status'] = 'success';
											$res['message'] = 'Are you sure you want to withdraw MWK' . number_format($this->input->post('amount')) . ' from ' . $result['firstname'] . ' ' . $result['lastname'] . '';

										}




									} else {
										$res['status'] = 'error';
										$res['message'] = 'Sorry , you dont have enough funds to perform this transaction';
									}
								} else {
									$res['status'] = 'error';
									$res['message'] = 'Sorry this agent code is deactivated';
								}
							} else {
								$res['status'] = 'error';
								$res['message'] = 'Sorry this agent code in not valid';
							}
						}
					}
				}
			}
			else{
				http_response_code('401');
				echo json_encode(array( "status" => 'error', "message" => 'Unauthorised access'));
				exit;
			}
		}catch (Exception $e)
		{
			http_response_code('401');
			echo json_encode(array( "status" => false, "message" => $e->getMessage()));
			exit;
		}

		echo  json_encode($res);


	}


	public  function finish_cashout_transaction(){
		$transaction_charge=get_transaction_charge($this->input->post('amount'),14);
		$beneficiearies = get_beneficiaries(14);
		$charge= $transaction_charge['charge'];

		$res=array();
		$is_amount_negative = isNegative($this->input->post('amount'));

		$received_Token = $this->input->request_headers('Authorization');
		try {
			$jwtData = $this->objOfJwt->DecodeToken($received_Token['Token']);
			$d1 = $jwtData['timeStamp'];
			$uid = $jwtData['uniqueId'];
			$d2 = Date('Y-m-d h:i:s');
			$date1 = new DateTime($d1);
			$date2 = new DateTime($d2);

			$diff = $date2->diff($date1);

			$hours = $diff->h;
			$hours = $hours + ($diff->days * 24);

			if ($uid == $this->input->post('phone')) {
				if ($hours > 1) {
					$res['status'] = 'error';
					$res['message'] = "Sorry! your session has expired, please logout and login again";
				} else {
					if ($is_amount_negative) {
						$res['status'] = 'error';
						$res['message'] = 'Sorry amount cannot be negative';
					} else {
						if ($this->Agent->check_agent_code($this->input->post('agent_code'))) {
							if ($result = $this->Account_model->is_agent_account_active($this->input->post('agent_code'))) {
								if ($this->Account_model->check_balance_is_ok($this->input->post('phone'), $this->input->post('amount') + $charge)) {



									$money_out_limit_calculation = $this->Transactions_model->sum_moneyout($this->input->post('phone'));
//					get daily transaction limit
									$sender_moneyout_limit = $this->Account_model->transaction_limit($this->input->post('phone'));

									if ($money_out_limit_calculation >= $sender_moneyout_limit->limit_amount || $money_out_limit_calculation + $this->input->post('amount') > $sender_moneyout_limit->limit_amount) {
										$remainder = $sender_moneyout_limit->limit_amount - $money_out_limit_calculation;
										if ($remainder > 0) {
											$concat = ' or you may transfer funds  but not more than MK' . $remainder;
										} else {
											$concat = "";
										}
										$res['status'] = 'error';
										$res['message'] = 'Sorry, you have reached your daily transaction limit, please try again tomorrow ' . $concat;
										send_sms($this->input->post('phone'), $res['message']);
									} else {

										if ($resultt = $this->Account_model->check_pin_correct($this->input->post('phone'), md5($this->input->post('pin')))) {
											$data = array(
												'sender' => $this->input->post('phone'),
												'recepient' => $result['phone_number'],
												'amount' => $this->input->post('amount'),
												'charge' => $charge,
												'agent_cashout'=> $transaction_charge['cashout'],
												'cashin'=> $transaction_charge['cashin'],
												'company_earning'=>$transaction_charge['company_commission'],
												'company_commission' => $beneficiearies
											);


											$result3 = $this->Account_model->cash_withdraw_test($data);
											if ($result3) {
												//$res['exec_time']=$this->output->enable_profiler(TRUE);
												$sender = $data['sender'];
												$reciever = $data['recepient'];
												$customer = get_customer($sender);
												$agent = get_agent($reciever);
												$sender_message = 'Trans ID:' . $result3 . ', Dear Customer, Cash out of MWK' . number_format($data['amount'], 2) . ' from ' . $this->input->post('agent_code') . ', ' . $result['firstname'] . ' ' . $result['lastname'] . ', your balance is MWK' . $this->your_balance($sender);
												$reciever_message = 'Trans ID:' . $result3 . ', Dear Customer  cash in of MWK' . number_format($data['amount'], 2) . ' from ' . $resultt['firstname'] . ' ' . $resultt['lastname'];
												try {
													$logger = array(
														'user_type' => 'CUSTOMER',
														'user_id' => $sender,
														'activity' => 'Made funds withdraw ' . number_format($data['amount'], 2) . ' From ' . $data['recepient']
													);
													log_activity($logger);
													send_sms($sender, $sender_message);
													send_sms($reciever, $reciever_message);
//                                                    sendMail($customer->email_address, 'Kakupay Cashout', $sender_message);
//                                                    sendMail($agent->email_address, 'Kakupay Cashin', $reciever_message);
													$res['status'] = 'success';
													$res['message'] = 'Dear customer, your transaction was successful. Your new balance is MWK' . number_format($this->your_balance($sender), 2);

												} catch (Exception $e) {
													$logger = array(
														'user_type' => 'CUSTOMER',
														'user_id' => $sender,
														'activity' => 'Made funds withdraw'
													);
													log_activity($logger);
													send_sms($sender, $sender_message);
													send_sms($reciever, $reciever_message);
													sendMail($customer->email_address, 'Kakupay Cashout', $sender_message);
													sendMail($agent->email_address, 'Kakupay Cashin', $reciever_message);
													$res['status'] = 'success';
													$res['message'] = 'Your transaction was successful. Your balance is MWK' . number_format($this->your_balance($sender), 2);
												}

											} else {
												$res['status'] = 'error';
												$res['message'] = 'Sorry! Your transaction was not successful please try again later';
											}

										} else {
											$res['status'] = 'error';
											$res['message'] = 'Sorry! Your pin is not correct';
										}

									}



								} else {
									$res['status'] = 'error';
									$res['message'] = 'Sorry , you dont have enough funds to perform this transaction';
								}
							} else {
								$res['status'] = 'error';
								$res['message'] = 'Sorry this agent code is deactivated';
							}
						} else {
							$res['status'] = 'error';
							$res['message'] = 'Sorry this agent code in not valid';
						}
					}
				}
			}else{
				http_response_code('401');
				echo json_encode(array( "status" => 'error', "message" => 'Unauthorised access'));
				exit;
			}
		}
		catch (Exception $e)
		{
			http_response_code('401');
			echo json_encode(array( "status" => false, "message" => $e->getMessage()));
			exit;
		}

		echo json_encode($res);
	}

	public function get_vericode(){
		$phone= $this->input->post('phone');
		$this->send_code($phone);

	}
	function test_mail(){
		$to = "kamulonim@gmail.com";
		$subject = "This is subject";

		$message = "<b>This is HTML message.</b>";
		$message .= "<h1>This is headline.</h1>";

		$header = "From:phindu2020@phindu.com \r\n";
		$header .= "Cc:ceo@infocustech-mw.com \r\n";
		$header .= "MIME-Version: 1.0\r\n";
		$header .= "Content-type: text/html\r\n";

		$retval = mail ($to,$subject,$message,$header);

		if( $retval == true ) {
			echo "Message sent successfully...";
		}else {
			echo "Message could not be sent...";
		}
	}
	public function send_code($phone){
		$result= $this->Customer_verification_model->get_vericode($phone);
		$customer=get_customer($phone);
		if(!empty($result)){
			$concat_message='Your Kakupay Confirmation number  is:'.$result['code'];
			send_sms($phone,$concat_message);

		}else{
			$phoneCode = rand(1000, 9999);
			$data =array(
				'account_number'=>$this->input->post('phone'),
				'code'=>$phoneCode
			);
			$this->Customer_verification_model->insert($data);
			$concat_message='Your Kakupay Confirmation number  is:'.$phoneCode;
			send_sms($result['account_number'],$concat_message);

		}
	}
	public function validate_send_money(){
		$res = array();
		$chec = check_exist_in_table('customer','phone_number',$this->input->post('sender'));
		$received_Token = $this->input->request_headers('Authorization');
		try {
			$jwtData = $this->objOfJwt->DecodeToken($received_Token['Token']);
			$d1 = $jwtData['timeStamp'];
			$uid = $jwtData['uniqueId'];
			$d2 = Date('Y-m-d h:i:s');
			$date1 = new DateTime($d1);
			$date2 = new DateTime($d2);

			$diff = $date2->diff($date1);

			$hours = $diff->h;
			$hours = $hours + ($diff->days * 24);

			if ($uid == $this->input->post('sender')) {
				if ($hours > 1) {
					$res['status'] = 'error';
					$res['message'] = "Sorry! your session has expired, please logout and login again";
				} else {


					$data = array(
						'sender' => $this->input->post('sender'),
						'recepient' => $this->input->post('recepient'),
						'amount' => $this->input->post('amount'),
						'description' => $this->input->post('description'),
						'pin' => md5($this->input->post('pin')),
					);
					//check if he sending to himself
					$is_amount_negative = isNegative($this->input->post('amount'));
					if ($is_amount_negative) {
						$res['status'] = 'error';
						$res['message'] = 'Sorry amount cannot be negative';
					} else {
						if ($data['sender'] == $data['recepient']) {
							$res['status'] = 'error';
							$res['message'] = 'Sorry, you cannot send funds to yourself';
						} else {
							// check if acoount exist
							if ($this->check_is_pin_default2($data['sender'])) {
								$res['status'] = 'warning';
								$res['command'] = 'setpin';
								$res['message'] = 'We have detected that this is your first time to make transaction, please  add a secure pin to be used for making transactions';
							} else {

								$is_number_exisit = $this->Account_model->check_phone_exist($data['recepient']);
								if ($is_number_exisit) {
									$is_account_active = $this->Account_model->is_account_active($data['recepient']);
									if ($is_account_active) {


										// check if sender has funds
										$is_balance_ok = $this->Account_model->check_balance_is_ok($data['sender'], $data['amount']);
										if ($is_balance_ok) {
											//start transaction
											//$transaction_success=$this->Account_model->send_money_transaction($data);

											$money_in_limit_calculation = $this->Transactions_model->sum_moneyin($data['recepient']);
//					get daily transaction limit
											$reciever_moneyin_limit = $this->Account_model->transaction_limit($data['recepient']);
											if ($money_in_limit_calculation >= $reciever_moneyin_limit->limit_amount || $money_in_limit_calculation + $data['amount'] > $reciever_moneyin_limit->limit_amount) {
												$remainder1 = $reciever_moneyin_limit->limit_amount - $money_in_limit_calculation;

												if ($remainder1 > 0) {
													$note = ' or you may transfer funds  but not more than MK' . $remainder1;
												} else {
													$note = "";
												}
												$res['status'] = 'error';
												$res['message'] = 'Sorry, the recipient has reached the daily transaction limit, try again tomorrow ' . $note;
												send_sms($data['sender'], $res['message']);
											} else {
												$money_out_limit_calculation = $this->Transactions_model->sum_moneyout($data['sender']);
//					get daily transaction limit
												$sender_moneyout_limit = $this->Account_model->transaction_limit($data['sender']);
												if ($money_out_limit_calculation >= $sender_moneyout_limit->limit_amount || $money_out_limit_calculation + $data['amount'] > $sender_moneyout_limit->limit_amount) {
													$remainder = $sender_moneyout_limit->limit_amount - $money_out_limit_calculation;
													if ($remainder > 0) {
														$concat = ' or you may transfer funds  but not more than MK' . $remainder;
													} else {
														$concat = "";
													}
													$res['status'] = 'error';
													$res['message'] = 'Sorry, you have reached your daily transaction limit, please try again tomorrow ' . $concat;
													send_sms($data['sender'], $res['message']);
												} else {
													$res['status'] = 'success';
													$res['message'] = 'Are you sure you want to send money MWK' . number_format($this->input->post('amount')) . ' to ' . $is_number_exisit['firstname'] . ' ' . $is_number_exisit['lastname'] . '';

												}

											}


										} else {
											$res['status'] = 'error';
											$res['message'] = 'Sorry, You dont have enough balance to perform this transaction';
										}


									} else {
										$res['status'] = 'error';
										$res['message'] = 'Sorry, the account you are trying to send funds is not active ';
									}
									//check senders pin if is correct


								} else {
									$res['status'] = 'error';
									$res['message'] = 'Sorry, this phone number is not associated with any account';
								}

							}


						}
					}

				}
			}else{
				http_response_code('401');
				echo json_encode(array( "status" => 'error', "message" => 'Unauthorised access'));
				exit;
			}
		}catch (Exception $e)
		{
			http_response_code('401');
			echo json_encode(array( "status" => false, "message" => $e->getMessage()));
			exit;
		}

		echo json_encode($res);
	}
	public function finish_send_money(){
		$res = array();
		$received_Token = $this->input->request_headers('Authorization');
		try {
			$jwtData = $this->objOfJwt->DecodeToken($received_Token['Token']);
			$d1 = $jwtData['timeStamp'];
			$uid = $jwtData['uniqueId'];
			$d2 = Date('Y-m-d h:i:s');
			$date1 = new DateTime($d1);
			$date2 = new DateTime($d2);

			$diff = $date2->diff($date1);

			$hours = $diff->h;
			$hours = $hours + ($diff->days * 24);

			if ($uid == $this->input->post('sender')) {
				if ($hours > 1) {
					$res['status'] = 'error';
					$res['message'] = "Sorry! your session has expired, please logout and login again";
				} else {
					$data = array(
						'sender' => $this->input->post('sender'),
						'recepient' => $this->input->post('recepient'),
						'amount' => $this->input->post('amount'),
						'description' => $this->input->post('description'),
						'pin' => md5($this->input->post('pin')),
					);
					//check if he sending to himself
					if ($data['sender'] == $data['recepient']) {
						$res['status'] = 'error';
						$res['message'] = 'Sorry, you cannot send funds to yourself';
					} else {
						// check if acoount exist
						$is_amount_negative = isNegative($this->input->post('amount'));
						if ($is_amount_negative) {
							$res['status'] = 'error';
							$res['message'] = 'Sorry amount cannot be negative';
						} else {
							$is_number_exisit = $this->Account_model->check_phone_exist($data['recepient']);
							if ($is_number_exisit) {
								$is_account_active = $this->Account_model->is_account_active($data['recepient']);
								if ($is_account_active) {

									$is_pin_correct = $this->Account_model->check_pin_correct($data['sender'], $data['pin']);
									if ($is_pin_correct) {
										// check if sender has funds
										$is_balance_ok = $this->Account_model->check_balance_is_ok($data['sender'], $data['amount']);
										if ($is_balance_ok) {
											//start transaction
											$transaction_success = $this->Account_model->send_money_transaction($data);
											if ($transaction_success) {
												$fees_not_paid = $this->Account_model->paid_reg_fees($data['recepient']);
												if ($fees_not_paid) {
													$this->pay_fees($transaction_success, $data['recepient']);
												}
												$logger = array(
													'user_type' => 'CUSTOMER',
													'user_id' => $data['sender'],
													'activity' => 'Sent money to' . $data['recepient'] . 'worth ' . $data['amount']
												);
												log_activity($logger);
												$sender = $data['sender'];
												$reciever = $data['recepient'];
												$customer_sender = get_customer($sender);
												$customer_reciever = get_customer($reciever);
												$sender_message = 'Trans ID:' . $transaction_success . ', Dear Customer You have sent MWK' . number_format($data['amount'], 2) . ' to ' . $is_number_exisit['firstname'] . ' ' . $is_number_exisit['lastname'] . '(' . $reciever . ')' . ' Bal: MWK' . number_format($this->your_balance($sender), 2);
												$reciever_message = 'Trans ID:' . $transaction_success . ', Dear Customer You have received MWK' . number_format($data['amount'], 2) . ' from ' . $is_pin_correct['firstname'] . ' ' . $is_pin_correct['lastname'] . ' (' . $sender . ')';
												try {
													if ($this->check_favourite_exist($data['sender'], $data['recepient'])) {
														send_sms($sender, $sender_message);
														send_sms($reciever, $reciever_message);
//							sendMail($customer_sender->email_address,'KakuPay Money Transfers',$sender_message);
//                                    sendMail($customer_reciever->email_address,'KakuPay Money Transfers',$reciever_message);
														$res['status'] = 'success';
														$res['message'] = 'Transaction successful. Your new balance is MWK ' . number_format($this->your_balance($sender), 2);

													} else {
														send_sms($sender, $sender_message);
														send_sms($reciever, $reciever_message);
//                                    sendMail($customer_sender->email_address,'KakuPay Money Transfers',$sender_message);
//                                    sendMail($customer_reciever->email_address,'KakuPay Money Transfers',$reciever_message);
														$res['status'] = 'success';
														$res['command'] = 'save_favourite';
														$res['fav_name'] = $is_number_exisit['firstname'] . ' ' . $is_number_exisit['lastname'];
														$res['fav_number'] = $reciever;
														$res['message'] = 'Transaction successful. Your new balance is MWK ' . number_format($this->your_balance($sender), 2);

													}
												} catch (Exception $e) {
													if ($this->check_favourite_exist($data['sender'], $data['recepient'])) {
														send_sms($sender, $sender_message);
														send_sms($reciever, $reciever_message);
														sendMail($customer_sender->email_address, 'KakuPay Money Transfers', $sender_message);
														sendMail($customer_reciever->email_address, 'KakuPay Money Transfers', $reciever_message);
														$res['status'] = 'success';
														$res['message'] = "Transaction Succeeded. Unfortunately we couldn't to send an SMS verification. Your new balance is MWK " . number_format($this->your_balance($sender), 2);


													} else {
														send_ms($sender, $sender_message);
														send_sms($reciever, $reciever_message);
														sendMail($customer_sender->email_address, 'KakuPay Money Transfers', $sender_message);
														sendMail($customer_reciever->email_address, 'KakuPay Money Transfers', $reciever_message);

														$res['command'] = 'save_favourite';
														$res['fav_name'] = $is_number_exisit['firstname'] . ' ' . $is_number_exisit['lastname'];
														$res['fav_number'] = $reciever;
														$res['status'] = 'success';
														$res['message'] = "Transaction Succeeded. Unfortunately we couldn't to send an SMS verification. Your new balance is MWK " . number_format($this->your_balance($sender), 2);

													}
												}


											} else {
												$res['status'] = 'error';
												$res['message'] = "Sorry! Something went wrong , we couldn't process your request. Please retry!";
											}
										} else {
											$res['status'] = 'error';
											$res['message'] = "Sorry! You have insufficient balance for this transaction.";
										}

									} else {
										$res['status'] = 'error';
										$res['message'] = 'Denied! The pin you entered is incorrect.';
									}

								} else {
									$res['status'] = 'error';
									$res['message'] = 'Sorry! The recipient account is not active!';
								}
								//check senders pin if is correct


							} else {
								$res['status'] = 'error';
								$res['message'] = 'Sorry! This phone number is not registered on our system';
							}
						}

					}
				}
			}else{
				http_response_code('401');
				echo json_encode(array( "status" => false, "message" => 'Bad request,Unauthorized access'));
				exit;
			}
		}catch (Exception $e)
		{
			http_response_code('401');
			echo json_encode(array( "status" => false, "message" => $e->getMessage()));
			exit;
		}
		echo json_encode($res);
	}
	public 	function pay_fees($transaction_ref,$sender){
		$beneficiearies = beneficiaries('21');
		$fees = check_fees('1');
		$get_kyc_id = check_exist_in_table('customer','phone_number',$sender);
		$facilitator = get_facilitator ($get_kyc_id->kyc);
		$data = array(

			'sender' =>$sender,
			'reference'=>$transaction_ref,
			'fees'=> $fees->amount,

		);
		if(!empty($facilitator)){
			$rt = $this->Account_model->transfer_fees($data,$beneficiearies,$facilitator->type,$facilitator->facilitator);
		}else{
			$rt = $this->Account_model->transfer_fees_no_kyc($data,$beneficiearies);
		}

		if($rt){
			$data2 = array(


				'paid_fees'=> 'Yes',

			);
			$this->Account_model->update_paid_fees($data['sender'], $data2);
			$res['message']=$transaction_ref.' Dear customer MK'.$data['fees'].' was deducted from your account, for account  Account Reg.';
			send_sms($sender,$res['message']);
		}

	}
	public function check_balance(){



		$res=array();


		$received_Token = $this->input->request_headers('Authorization');
		try
		{
			$jwtData = $this->objOfJwt->DecodeToken($received_Token['Token']);
			$d1=$jwtData['timeStamp'];
			$uid=$jwtData['uniqueId'];
			$d2 =   Date('Y-m-d h:i:s');
			$date1 = new DateTime($d1);
			$date2 = new DateTime($d2);

			$diff = $date2->diff($date1);

			$hours = $diff->h;
			$hours = $hours + ($diff->days*24);
			$result = $this->Account_model->check_balance($this->input->post('phone'));
			$result2 = $this->Transactions_model->count_transactions($this->input->post('phone'));
			if($uid==$this->input->post('phone')){
				if($hours > 1){
					$res['status']='error';
					$res['message']="Sorry! your session has expired, please logout and login again";
				}else{
					if($result){
						$res['status']='success';
						$res['data']=array('balance'=>$result['balance']);

						$res['message']='Success';
					}else{
						$res['status']='error';
						$res['message']="Sorry! We couldn't process your request";
					}
				}
			}else{
				http_response_code('401');
				echo json_encode(array( "status" => false, "message" => 'Bad request,invalid token'));
				exit;
			}



//		echo $hours;

		}
		catch (Exception $e)
		{
			http_response_code('401');
			echo json_encode(array( "status" => false, "message" => $e->getMessage()));
			exit;
		}




		echo json_encode($res);
//if(!empty($validate)){
//
//}else{
//	 header('HTTP/1.0 401 Unauthorized',TRUE,401);
//	echo json_encode(array('status'=>'Unauthorized'));
//	exit();
//}

	}
	public function your_balance($phone){
		$result = $this->Account_model->check_balance($phone);
		return $result['balance'];
	}


	public function update($id)
	{
		$row = $this->Customer_model->get_by_id($id);

		if ($row) {
			$data = array(
				'button' => 'Update',
				'action' => site_url('customer/update_action'),
				'kyc' => set_value('kyc', $row->kyc),
				'firstname' => set_value('firstname', $row->firstname),
				'middlename' => set_value('middlename', $row->middlename),
				'lastname' => set_value('lastname', $row->lastname),
				'gender' => set_value('gender', $row->gender),
				'dob' => set_value('dob', $row->dob),
				'email_address' => set_value('email_address', $row->email_address),
				'phone_number' => set_value('phone_number', $row->phone_number),
				'auth_key' => set_value('auth_key', $row->auth_key),
				'stamp' => set_value('stamp', $row->stamp),
				'image_url' => set_value('image_url', $row->image_url),
			);
			//$this->load->view('customer/customer_form', $data);
			$this->template->set('title', 'Customer');
			$this->template->load('new_template', 'contents' ,'customer/customer_form', $data);
		} else {
			$this->session->set_flashdata('message', 'Record Not Found');
			redirect(site_url('customer'));
		}
	}

	public function update_action()
	{
		$this->_rules();

		if ($this->form_validation->run() == FALSE) {
			$this->update($this->input->post('phone_number', TRUE));
		} else {
			$data = array(
				'kyc' => $this->input->post('kyc',TRUE),
				'firstname' => $this->input->post('firstname',TRUE),
				'middlename' => $this->input->post('middlename',TRUE),
				'lastname' => $this->input->post('lastname',TRUE),
				'gender' => $this->input->post('gender',TRUE),
				'dob' => $this->input->post('dob',TRUE),
				'email_address' => $this->input->post('email_address',TRUE),
				'auth_key' => $this->input->post('auth_key',TRUE),
				'stamp' => $this->input->post('stamp',TRUE),
				'image_url' => $this->input->post('image_url',TRUE),
			);

			$this->Customer_model->update($this->input->post('phone_number', TRUE), $data);
			$this->session->set_flashdata('message', 'Update Record Success');
			redirect(site_url('customer'));
		}
	}

	public function delete($id)
	{
		$row = $this->Customer_model->get_by_id($id);

		if ($row) {
			$this->Customer_model->delete($id);
			$this->session->set_flashdata('message', 'Delete Record Success');
			redirect(site_url('customer'));
		} else {
			$this->session->set_flashdata('message', 'Record Not Found');
			redirect(site_url('customer'));
		}
	}

	public function _rules()
	{

	}


	public function pay_your_bills(){
		$res = array();
		$chec = check_exist_in_table('customer','phone_number',$this->input->post('phone'));
		$recepient = check_exist_in_table('internal_accounts','account_code','105');
		$is_amount_negative = isNegative($this->input->post('amount'));


		if ($is_amount_negative) {
			$res['status'] = 'error';
			$res['message'] = 'Sorry amount cannot be negative';
		}
		else {


			$data = array(
				'sender' => $this->input->post('phone'),
				'recepient' => '105',
				'amount' => $this->input->post('amount'),
				'pin' => md5($this->input->post('pin')),
				'description' => $this->input->post('service_code'),
			);

			// check if account exist
			if ($this->check_is_pin_default2($data['sender'])) {
				$res['status'] = 'warning';
				$res['command'] = 'setpin';
				$res['message'] = 'We have detected that this is your first time to make transaction, please  add a secure pin to be used for making transactions';
			} else {





				// check if sender has funds
				$is_balance_ok = $this->Account_model->check_balance_is_ok($data['sender'], $data['amount']);
				if ($is_balance_ok) {

					$money_out_limit_calculation = $this->Transactions_model->sum_moneyout($data['sender']);
//					get daily transaction limit
					$sender_moneyout_limit = $this->Account_model->transaction_limit($data['sender']);
					if ($money_out_limit_calculation >= $sender_moneyout_limit->limit_amount || $money_out_limit_calculation + $data['amount'] > $sender_moneyout_limit->limit_amount) {
						$remainder = $sender_moneyout_limit->limit_amount - $money_out_limit_calculation;
						if ($remainder > 0) {
							$concat = ' or you may transfer funds  but not more than MK' . $remainder;
						} else {
							$concat = "";
						}
						$res['status'] = 'error';
						$res['message'] = 'Sorry, you have reached your daily transaction limit, please try again tomorrow ' . $concat;
						send_sms($data['sender'], $res['message']);
					} else {

						$is_pin_correct = $this->Account_model->check_pin_correct($data['sender'], $data['pin']);
						if ($is_pin_correct) {

							//start transaction
							$transaction_success = $this->Account_model->bill_transaction($data);
							if ($transaction_success) {
								$logger = array(
									'user_type' => 'CUSTOMER',
									'user_id' => $data['sender'],
									'activity' => 'Sent money to' . $data['recepient'] . 'worth ' . $data['amount'] . ' for Bill payment'.$this->input->post('service_code')
								);
								log_activity($logger);
								$dataair = array(
									'buyer' => $data['sender'],
									'recipient' => $this->input->post('receiver', TRUE),
									'amount' => $this->input->post('amount', TRUE),
									'transaction_reference' => $transaction_success
								);


								$request_it = array(
									"msisdn" => $data['sender'],
									"beneficiary_acc" => $this->input->post('beneficiary_acc', TRUE),
									"amount" => $this->input->post('amount', TRUE),
									"service_code" => $this->input->post('service_code', TRUE),
									"txn_reference" => $transaction_success,

								);



								$sender = $data['sender'];
								$reciever = $data['recepient'];



								$sender_message = 'Trans ID:' . $transaction_success . ', Dear Customer, you have paid MWK' . number_format($data['amount'], 2) . ' to '.$this->input->post('service_name').',  Bal: MWK' . number_format($this->your_balance($sender), 2);
								$reciever_message = 'Trans ID:' . $transaction_success . ', Dear Customer You have received MWK' . number_format($data['amount'], 2) . ' from ' . $is_pin_correct['firstname'] . ' ' . $is_pin_correct['lastname'] . ' (' . $sender . ')';
								try {

									send_sms($sender, $sender_message);

//														sendMail($customer_sender->email_address,'KakuPay Money Transfers',$sender_message);
//														sendMail($recepient->email_address,'KakuPay Money Transfers',$reciever_message);
									$res['status'] = 'success';
									$res['message'] = 'Transaction successful. Your new balance is MWK ' . number_format($this->your_balance($sender), 2);


								} catch (Exception $e) {

									send_sms($sender, $sender_message);
									send_sms($reciever, $reciever_message);

//														sendMail($customer_sender->email_address,'KakuPay Money Transfers',$sender_message);
//														sendMail($recepient->email_address,'KakuPay Money Transfers',$reciever_message);
									$res['status'] = 'success';
									$res['message'] = "Transaction Succeeded. Unfortunately we couldn't to send an SMS verification. Your new balance is MWK " . number_format($this->your_balance($sender), 2);


								}

								$this->pay_bills($request_it);
							} else {
								$res['status'] = 'error';
								$res['message'] = "Sorry! Something went wrong , we couldn't process your request. Please retry!";
							}


						} else {
							$res['status'] = 'error';
							$res['message'] = 'Denied! The pin you entered is incorrect.';
						}


					}


				} else {
					$res['status'] = 'error';
					$res['message'] = 'Sorry, You dont have enough balance to perform this transaction';
				}





			}


		}






		echo json_encode($res);
	}
	function pay_bills($data){
		bill_pay($data);
	}

	public function sendSms($sms_reciever,$message)
	{
		try{

			$this->load->library('twilio');
			//$sms_sender = ;

			$contact="\n(Sent from KakuPay Malawi)";

			$sms_message =$message.$contact;
			$from ='Kakupay'; //trial account twilio number
			$to = $sms_reciever; //sms recipient number
			$response = $this->twilio->sms($from, $to, $sms_message);

			if ($response->IsError) {

				return false;
			} else {

				return true;
			}
		}catch(Exception $e){
			return false;
		}
	}
	public function tiyese(){
		$this->sendSms('994099461','testing');
	}
	public function change_password(){
		$res=array();
		if($this->Customer_model->check_old_password($this->input->post('phone'),md5($this->input->post('old_password')))){

			$this->update_password($this->input->post('phone'),$this->input->post('new_password') );
			$res['status']='success';
			$res['message']='Success, your password changed';
			$logger = array(
				'user_type'=>'CUSTOMER',
				'user_id'=>$this->input->post('phone'),
				'activity'=>'changed password'
			);
			log_activity($logger);

		}else{

			$res['status']='error';
			$res['message']='Sorry, the old password is not valid';
		}
		echo json_encode($res);
	}
	public function	update_password($phone,$new_pass){
		$data=array(
			'auth_key'=>md5($new_pass)
		);
		$result=$this->Customer_model->update_password($this->input->post('phone'),$data);

		if($result){
			$logger = array(
				'user_type'=>'CUSTOMER',
				'user_id'=>$this->input->post('phone'),
				'activity'=>'changed password'
			);
			log_activity($logger);
			$res['status']='success';
			$res['message']='Success, your password was updated';
		}else{
			$res['status']='error';
			$res['message']='Sorry, the password was not updated';
		}
	}
	public function update_pass(){
		$res=array();
		$data=array(
			'auth_key'=>md5($this->input->post('new_password'))
		);
		$result=$this->Customer_model->update_password($this->input->post('phone'),$data);

		if($result){
			$logger = array(
				'user_type'=>'CUSTOMER',
				'user_id'=>$this->input->post('phone'),
				'activity'=>'changed password'
			);
			log_activity($logger);
			$res['status']='success';
			$res['message']='Success, your password was updated';
		}else{
			$res['status']='error';
			$res['message']='Something went wrong, your password was not updated';
		}
		echo json_encode($res);
	}
	public function update_pin(){
		$res=array();
		$data=array(
			'pin'=>md5($this->input->post('new_pin')),
		);
		$result=$this->Account_model->update_pin($this->input->post('phone'),$data);

		if($result){
			$logger = array(
				'user_type'=>'CUSTOMER',
				'user_id'=>$this->input->post('phone'),
				'activity'=>'changed pin'
			);
			log_activity($logger);
			$res['status']='success';
			$res['message']='Success, your pin was updated';
		}else{
			$res['status']='error';
			$res['message']='Sorry, the pin was not updated';
		}
		echo json_encode($res);
	}

	public function change_pin(){
		$res=array();
		$phone=$this->input->post('phone');
		$pass=md5($this->input->post('old_pin'));
		$checked=$this->Account_model->check_old_pin($phone,$pass);
		if($checked > 0){

			$data=array(
				'pin'=>md5($this->input->post('new_pin')),
			);
			$result=$this->Account_model->update_pin($this->input->post('phone'),$data);

			if($result){
				$logger = array(
					'user_type'=>'CUSTOMER',
					'user_id'=>$this->input->post('phone'),
					'activity'=>'changed pin'
				);
				log_activity($logger);
				$res['status']='success';
				$res['message']='Success, your pin was updated';
			}else{
				$res['status']='error';
				$res['message']='Sorry, the pin was not updated';
			}

		}else{
			$data1 = array(
				'account_number'=>$this->input->post('phone'),
				'action_type_id' =>1,
				'counter' =>1,
			);
			$limit=6;
			$this->Check_pin_counter_model->insert($data1,$limit);
			if($this->Check_pin_counter_model->is_counter_blocked($data1['account_number'])){
				$logger = array(
					'user_type'=>'CUSTOMER',
					'user_id'=>$data1['account_number'],
					'activity'=>'pin entry trials account blocked'
				);
				log_activity($logger);
				$res['status']='error';
				$res['command']='logout';
				$res['message']='Sorry, Your Account is blocked temporarily please try again after 24 hrs';
			}else{
				$res['status']='error';
				$res['message']='Sorry, the old pin is not valid';
			}

		}
		echo json_encode($res);
	}
	public function password_reset(){
		// $this->output->enable_profiler(TRUE);
		$res=array();
		$phone= $this->input->post('phone');
		$check_phone_exisist = $this->Account_model->check_phone_exist($phone);
		if($check_phone_exisist){
			if($this->Account_model->is_account_active($phone)){
				if($this->Check_pin_counter_model->is_counter_blocked($phone)){

					$res['status']='error';
					$res['command']='logout';
					$res['message']='Sorry, Your Account is blocked temporarily please try again after 24 hrs';
				}else{




					$phoneCode= $this->vericode_geenerator();
					$vericode=array(
						'account_number'=>$phone,
						'code'=>$phoneCode
					);


					$vcode=$this->Customer_verification_model->insert($vericode);

					$customer_sender=get_customer($phone);
					try{

						$concat_message='Dear Customer Your Kakupay recovery code is:'.$phoneCode;
						send_sms($phone,$concat_message);
						sendMail($customer_sender->email_address,'Account Recovery',$concat_message);
						$res['status']='success';

						$res['message']='Please insert number sent to your phone number';
					}catch(Exception $e){
						sendMail($customer_sender->email_address,'Account Recovery',$concat_message);
						$res['status']='error';
						$res['message']='Sorry we could not send sms but we have sent to your email';
					}









				}
			}else{
				$res['status']='error';
				$res['message']='Sorry, this account was deactivated';
			}
		}else{
			$res['status']='error';
			$res['message']='Sorry the account with this phone number does not exist in our system';
		}
		echo json_encode($res);
	}

	public function verify_passcode(){
		//$this->output->enable_profiler(TRUE);
		$res = array();
		$data =array(
			'account_number'=>$this->input->post('phone'),
			'code'=>$this->input->post('recovery_code'),

		);
		$result1=	$this->Customer_verification_model->verify_customer($data);
		if($result1){
			$question=$this->get_user_security_questions($this->input->post('phone'));
			if($question){
				$res['data']=$question;
				$res['status']='success';
				$res['message']='Success,  the code you entered was verified ';
			}else{
				$res['status']='error';
				$res['message']='Sorry, it seems you have not yet set sequrity questions yet';
			}

		}else{
			$res['status']='error';
			$res['message']='Sorry,  the code you entered is incorrect';
		}
		echo json_encode($res);
	}

	public function vericode_geenerator(){
		return $phoneCode = rand(1000, 9999);
	}
	public function get_user_security_questions($phone){
		$reult=$this->security_questions->get_by_id($phone);
		if($reult){
			$rows = array();
			foreach($reult as $result){
				$rows[]=$result;
			}
			return $rows;
		}else{
			return false;
		}
	}
	public function get_all_questions(){
		$reult=$this->Security_questions_model->get_all();
		if($reult){
			$rows = array();
			foreach($reult as $result){
				$rows[]=$result;
			}
			return $rows;
		}else{
			return false;
		}
	}
	public function request_questions(){
		$res=array();
		$result= $this->get_all_questions();
		if(!empty($result)){
			$res['data']=$result;
			$res['status']='success';
			$res['message']='Please set up security question first';
		}else{
			$res['status']='error';
			$res['message']='Sorry We could not process this';
		}

	}
	public function set_security_questions(){
		$res=array();
		$toinsert=array(
			$data1=array(
				'account_number' => $this->input->post('phone'),
				'question_id' => $this->input->post('q1'),
				'answer' => $this->input->post('a1')

			),
			$data2=array(
				'account_number' => $this->input->post('phone'),
				'question_id' => $this->input->post('q2'),
				'answer' => $this->input->post('a2')

			),
			$data3=array(
				'account_number' => $this->input->post('phone'),
				'question_id' => $this->input->post('q3'),
				'answer' => $this->input->post('a3')

			)
		);
		$result=$this->security_questions->insert_batch1($toinsert);
		if($result){
			$logger = array(
				'user_type'=>'CUSTOMER',
				'user_id'=>$data1['account_number'],
				'activity'=>'added security questions'
			);
			log_activity($logger);
			$res['status']='success';
			$res['message']='Success, your security questions were added successfully';
		}else{
			$res['status']='error';
			$res['command'] = 'set_security_questions';
			$res['message']='Sorry , Something went wrong';
		}
		echo json_encode($res);
	}
	public function get_customer_questions(){
		$res=array();
//	$key="1234567891234567";
		// echo cryptoJsAesDecrypt($key, $this->input->post('payload'));
		$question=$this->security_questions->get_user_questions_response($this->input->post('phone'));
		if(!empty($question)){

			$res['data']=$question;
			$res['status']='success';
			$res['message']='Success, these are your questions';

		}else{
			$res['status']='error';
			$res['command'] = 'set_security_questions';
			$res['message']='Sorry , Something went wrong';
		}
		echo json_encode($res);

	}
	public function receive_responses(){
		// $this->output->enable_profiler(TRUE);
		$res=array();

		$data1=array(
			'account_number' => $this->input->post('phone'),
			'question_id' => $this->input->post('q1'),
			'answer' => $this->input->post('a1')

		);
		$data2=array(
			'account_number' => $this->input->post('phone'),
			'question_id' => $this->input->post('q2'),
			'answer' => $this->input->post('a2')

		);
		$data3=array(
			'account_number' => $this->input->post('phone'),
			'question_id' => $this->input->post('q3'),
			'answer' => $this->input->post('a3')

		);

		$result=$this->security_questions->validate_responses($data1,$data2,$data3);
		if($result){
			$this->send_code($this->input->post('phone'));
			$res['status']='success';
			$res['message']='Success, your answers were correct';
		}else{
			$data4 = array(
				'account_number'=>$this->input->post('phone'),
				'action_type_id' =>5,
				'counter' =>1,
			);
			$limit=6;
			$this->Check_pin_counter_model->insert($data4,$limit);
			if($this->Check_pin_counter_model->is_counter_blocked($data4['account_number'])){

				$res['status']='error';
				$res['command']='logout';
				$res['message']='Sorry, Your Account is blocked temporarily please try again after 24 hrs';
			}else{
				$res['status']='error';
				$res['message']='Sorry, the answers are incorrect';
			}


		}
		echo json_encode($res);
	}
	public function hashe(){
		$res=array();
		//$id=$this->encryption->decrypt($this->input->post('payload'));
		$id=$this->input->post('payload');
		$res['data']=$id;

		echo $this->encryption->decrypt($this->input->post('payload'));

	}
	public function check_is_pin_default2($phone){
		$re= $this->Account_model->check_pin_is_default($phone);
		if($re==2){
			return true;
		}elseif($re==3){
			return false;
		}
	}

	public function check_favourite_exist($owner,$favourite){
		$result = $this->Favourites_model->check_favourite($owner,$favourite);
		if($result==2){
			return true;
		}elseif($result==3){
			return false;
		}
	}
	public function get_user($phone){
		$res = array();
		$row = $this->Customer_model->get_customer_kyc($phone);
		if(!empty($row)){
			$data = array(
				'kyc' => $row->kyc,
				'firstname' => $row->firstname,
				'middlename' => $row->middlename,
				'lastname' => $row->lastname,
				'gender' => $row->gender,
				'dob' => $row->dob,
				'email_address' => $row->email_address,
				'phone_number' => $row->phone_number,
				'date_created' => $row->stamp,

			);
			$res['status']= 'success';
			$res['data'] = $row;
			$res['message'] = 'User found in the system';
		}else{
			$res['status']= 'error';

			$res['message'] = 'User not  found in the system';
		}
		echo json_encode($res);
	}
//    public function request_airtime2($data){
//
//        try{
//            $ch = curl_init();
//            $endpoint = "https://airtime.kakupay.com";
//            //Set your auth headers
////        curl_setopt($ch, CURLOPT_HEADER, true);
//            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
//                "Content-Type: application/x-www-form-urlencoded",
//                "Content-Length: " . strlen(json_encode($data))
//            ));
//
//            curl_setopt($ch, CURLOPT_URL, $endpoint);
//            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
//            curl_setopt($ch,  CURLOPT_RETURNTRANSFER, true);
//            curl_setopt($ch, CURLOPT_POST, 1);
//            curl_setopt($ch, CURLOPT_POSTFIELDS,json_encode($data) );
////        echo json_encode($data);
//
//            $result = curl_exec ($ch);
////        $info = curl_getinfo($ch);
////        var_dump($result);
//
//            curl_close ($ch);
//
//            //'$final = json_decode($result,TRUE);
//
//            $final = json_decode($result);
////        $data2 = array(
////            'authorization_type' => '1',
////            'creator' => 'kamulonim@gmail.com',
////            'user_type' => '1',
////            'new_data' => $result,
////            'old_data' => $result,
////        );
////        add_request($data2);
//            $da = array(
//                'request_id'=>$final->requestId
//            );
//            $this->Airtime_purchase_logger_model->update2($data['trans_ref'],$da);
//
//
//        }catch(Exception $e){
//            return $e;
//        }
//
//
//    }
	public function request_sms(){
		$data = array(
			'msisdn'=>'+265994099461',
			'message'=>'Kakupay testing'
		);

		try{
			$ch = curl_init();
//            $endpoint = "https://537e97fa4f17.ngrok.io/sendsms";
			$endpoint = "http://162.241.158.123:3005/sendsms";
			//Set your auth headers
//        curl_setopt($ch, CURLOPT_HEADER, true);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array(
				"Content-Type: application/json",
//                "Content-Length: " . strlen(json_encode($data))
			));

			curl_setopt($ch, CURLOPT_URL, $endpoint);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
			curl_setopt($ch,  CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS,json_encode($data));
//        echo json_encode($data);

			$result = curl_exec ($ch);
//        $info = curl_getinfo($ch);
			var_dump($result);

			curl_close ($ch);

			//'$final = json_decode($result,TRUE);

			$final = json_decode($result);
//        $data2 = array(
//            'authorization_type' => '1',
//            'creator' => 'kamulonim@gmail.com',
//            'user_type' => '1',
//            'new_data' => $result,
//            'old_data' => $result,
//        );
//        add_request($data2);
//            $da = array(
//                'request_id'=>$final->requestId
//            );
//            $this->Airtime_purchase_logger_model->update2($data['trans_ref'],$da);
//

		}catch(Exception $e){
			return $e;
		}


	}

	public function request_airtime($request_it){


		$send_date =  array(
			"msisdn"=>$request_it['recipient'],
			"amount"=>$request_it['amount'],
			"sendSMS"=>false,
			"sourceAddress"=>"KakuPay",
			"content"=>"",
			"extRefId"=>$request_it['trans_ref'],
			"processingMode"=>"SYNC"
		);
		$result = send_air($send_date);


		$da = array(

			'request_id'=>$result->refId,
			'comment'=>$result->status,
			'status'=>$result->description
		);
		$this->Airtime_purchase_logger_model->update2($result->extRefId,$da);
		if($result->status=='FAILED'){
			$this->reversal($result->extRefId);
		}

	}

	public function reversal($id){

		$row = $this->Airtime_purchase_logger_model->get_by_reff($id);


		if(!empty($row)){
			$data= array(
				'sender'=>'+265998634378',
				'recepient'=>$row->buyer,
				'amount'=>$row->amount,
				'description'=>'Airtime purchase transaction reversal',
			);

			$is_number_exisit=	$this->Account_model->check_phone_exist($data['recepient']);
			if($is_number_exisit){
				$is_account_active=$this->Account_model->is_account_active($data['recepient']);
				if($is_account_active){
					// check if sender has funds
					$is_balance_ok=$this->Account_model->check_balance_is_ok($data['sender'],$data['amount']);
					if($is_balance_ok){


						//start transaction
						$transaction_success=$this->Account_model->send_money_transaction($data);
						if($transaction_success){
							$logger = array(
								'user_type'=>'MERCHANT',
								'user_id'=>$data['recepient'],
								'activity'=>'Reverse MWK'.number_format($data['amount'],2).' '.'from'.' '.$data['recepient']
							);
							log_activity($logger);
							$dataaa = array(
								'reversed' => 'YES',
							);

							$this->Airtime_purchase_logger_model->update3($id, $dataaa);

							$tr = array(

								'account_number' => $row->buyer,
								'ref' => $row->transaction_reference,
								'filed_by' => 'superadmin@kakupay.com',
								'status' => 'Reversed',

							);
							$this->Transaction_reversal_requests_model->insert($tr);
							$sender=$data['sender'];
							$reciever=$data['recepient'];

							$sender_message='Trans ID:'.$transaction_success.', Dear Customer MWK'.number_format($data['amount'],2).' has been refunded to '.$is_number_exisit['firstname'].' '.$is_number_exisit['lastname'].'('.$reciever.')'.' Bal: MWK'.number_format($this->your_balance($sender),2);
							$reciever_message='Trans ID:'.$transaction_success.', Dear Customer your  MWK'.number_format($data['amount'],2).' has been refunded from  failed airtime purchase';
							try{
								$dataz= array(
									'status'=>'Reversed'
								);
								$this->Transaction_reversal_requests_model->update($id, $dataz);
								send_sms($sender,$sender_message);
								send_sms($reciever,$reciever_message);
//									sendMail($customer_sender->email_address,'KakuPay Money Transfers',$sender_message);
//									sendMail($customer_reciever->email_address,'KakuPay Money Transfers',$reciever_message);
								$res['status']='success';
								$res['message']='Transaction successful. Your new balance is MWK '.number_format($this->your_balance($sender),2);

							}
							catch(Exception $e){
								$dataz= array(
									'status'=>'Reversed'
								);
								$this->Transaction_reversal_requests_model->update($id, $dataz);
								send_sms($sender,$sender_message);
								send_sms($reciever,$reciever_message);
//									sendMail($customer_sender->email_address,'KakuPay Money Transfers',$sender_message);
//									sendMail($customer_reciever->email_address,'KakuPay Money Transfers',$reciever_message);
								$res['status']='success';
								$res['message']='Transaction successful. Your new balance is MWK '.number_format($this->your_balance($sender),2);

							}


						}else{
							$res['status']='error';
							$res['message']="Sorry! Something went wrong , we couldn't process your request. Please retry!";
							$logger = array(
								'user_type'=>'MERCHANT',
								'user_id'=> $id,
								'activity'=>'Airtime Reversal failed  '
							);
							log_activity($logger);
						}
					}else{
						$res['status']='error';
						$res['message']="Sorry! You have insufficient balance for this transaction.";
						$logger = array(
							'user_type'=>'MERCHANT',
							'user_id'=> $id,
							'activity'=>'Airtime Reversal failed airtime account no enough funds '
						);
						log_activity($logger);

					}



				}else{
					$res['status']='error';
					$res['message']='Sorry! The recipient account is not active!';
					$logger = array(
						'user_type'=>'MERCHANT',
						'user_id'=> $id,
						'activity'=>'Airtime Reversal failed no  recipient not active '
					);
					log_activity($logger);

				}
				//check senders pin if is correct


			}else{
				$res['status']='error';
				$res['message']='Sorry! This phone number is not registered on our system';
				$logger = array(
					'user_type'=>'MERCHANT',
					'user_id'=> $id,
					'activity'=>'Airtime Reversal failed no phone number not associated with any account'
				);
				log_activity($logger);
			}
		}else {
			$logger = array(
				'user_type'=>'MERCHANT',
				'user_id'=> $id,
				'activity'=>'Airtime Reversal failed no id '
			);
			log_activity($logger);
		}



	}
	function airtime_reversal_auto($id,$status){


		if($status=='Success'){
			$dato = array(
				'status' => 'Success',
			);

			$this->Airtime_purchase_logger_model->update3($id, $dato);


		}
		elseif ($status=='FAILED'){

			$row = $this->Airtime_purchase_logger_model->get_by_ref($id);
			$check= $this->Transactions_model->get_t($row->transaction_reference,$row->buyer);
			if(!empty($row)){
				$data= array(
					'sender'=>'+265998634378',
					'recepient'=>$row->buyer,
					'amount'=>$row->amount,
					'description'=>'Airtime purchase transaction reversal',
				);

				$is_number_exisit=	$this->Account_model->check_phone_exist($data['recepient']);
				if($is_number_exisit){
					$is_account_active=$this->Account_model->is_account_active($data['recepient']);
					if($is_account_active){
						// check if sender has funds
						$is_balance_ok=$this->Account_model->check_balance_is_ok($data['sender'],$data['amount']);
						if($is_balance_ok){


							//start transaction
							$transaction_success=$this->Account_model->send_money_transaction($data);
							if($transaction_success){
								$logger = array(
									'user_type'=>'MERCHANT',
									'user_id'=>$data['recepient'],
									'activity'=>'Reverse MWK'.number_format($data['amount'],2).' '.'from'.' '.$data['recepient']
								);
								log_activity($logger);
								$dataaa = array(
									'reversed' => 'YES',
								);

								$this->Airtime_purchase_logger_model->update3($id, $dataaa);

								$tr = array(

									'account_number' => $row->buyer,
									'ref' => $row->transaction_reference,
									'filed_by' => 'superadmin@kakupay.com',
									'status' => 'Reversed',

								);
								$this->Transaction_reversal_requests_model->insert($tr);
								$sender=$data['sender'];
								$reciever=$data['recepient'];

								$sender_message='Trans ID:'.$transaction_success.', Dear Customer MWK'.number_format($data['amount'],2).' has been refunded to '.$is_number_exisit['firstname'].' '.$is_number_exisit['lastname'].'('.$reciever.')'.' Bal: MWK'.number_format($this->your_balance($sender),2);
								$reciever_message='Trans ID:'.$transaction_success.', Dear Customer your  MWK'.number_format($data['amount'],2).' has been refunded from  failed airtime purchase';
								try{
									$dataz= array(
										'status'=>'Reversed'
									);
									$this->Transaction_reversal_requests_model->update($id, $dataz);
									send_sms($sender,$sender_message);
									send_sms($reciever,$reciever_message);
//									sendMail($customer_sender->email_address,'KakuPay Money Transfers',$sender_message);
//									sendMail($customer_reciever->email_address,'KakuPay Money Transfers',$reciever_message);
									$res['status']='success';
									$res['message']='Transaction successful. Your new balance is MWK '.number_format($this->your_balance($sender),2);

								}
								catch(Exception $e){
									$dataz= array(
										'status'=>'Reversed'
									);
									$this->Transaction_reversal_requests_model->update($id, $dataz);
									send_sms($sender,$sender_message);
									send_sms($reciever,$reciever_message);
//									sendMail($customer_sender->email_address,'KakuPay Money Transfers',$sender_message);
//									sendMail($customer_reciever->email_address,'KakuPay Money Transfers',$reciever_message);
									$res['status']='success';
									$res['message']='Transaction successful. Your new balance is MWK '.number_format($this->your_balance($sender),2);

								}


							}else{
								$res['status']='error';
								$res['message']="Sorry! Something went wrong , we couldn't process your request. Please retry!";
								$logger = array(
									'user_type'=>'MERCHANT',
									'user_id'=> $id,
									'activity'=>'Airtime Reversal failed  '
								);
								log_activity($logger);
							}
						}else{
							$res['status']='error';
							$res['message']="Sorry! You have insufficient balance for this transaction.";
							$logger = array(
								'user_type'=>'MERCHANT',
								'user_id'=> $id,
								'activity'=>'Airtime Reversal failed airtime account no enough funds '
							);
							log_activity($logger);

						}



					}else{
						$res['status']='error';
						$res['message']='Sorry! The recipient account is not active!';
						$logger = array(
							'user_type'=>'MERCHANT',
							'user_id'=> $id,
							'activity'=>'Airtime Reversal failed no  recipient not active '
						);
						log_activity($logger);

					}
					//check senders pin if is correct


				}else{
					$res['status']='error';
					$res['message']='Sorry! This phone number is not registered on our system';
					$logger = array(
						'user_type'=>'MERCHANT',
						'user_id'=> $id,
						'activity'=>'Airtime Reversal failed no phone number not associated with any account'
					);
					log_activity($logger);
				}
			}else {
				$logger = array(
					'user_type'=>'MERCHANT',
					'user_id'=> $id,
					'activity'=>'Airtime Reversal failed no id '
				);
				log_activity($logger);
			}


		}





	}
	public function buy_airtime(){
		$res = array();
		$chec = check_exist_in_table('customer','phone_number',$this->input->post('sender'));
		$recepient = check_exist_in_table('merchant','phone_number','+265998634378');
		$is_amount_negative = isNegative($this->input->post('amount'));
		$system_down = '1';


		if ($is_amount_negative) {
			$res['status'] = 'error';
			$res['message'] = 'Sorry amount cannot be negative';
		}
		else {


			$data = array(
				'sender' => $this->input->post('sender'),
				'recepient' => '+265998634378',
				'amount' => $this->input->post('amount'),
				'pin' => md5($this->input->post('pin')),
				'description' => 'Airtime purchase'
			);

			// check if account exist
			if ($this->check_is_pin_default2($data['sender'])) {
				$res['status'] = 'warning';
				$res['command'] = 'setpin';
				$res['message'] = 'We have detected that this is your first time to make transaction, please  add a secure pin to be used for making transactions';
			} else {


				$is_account_active = $this->Account_model->is_account_active($data['recepient']);
				if ($is_account_active) {


					// check if sender has funds
					$is_balance_ok = $this->Account_model->check_balance_is_ok($data['sender'], $data['amount']);
					if ($is_balance_ok) {

						$money_out_limit_calculation = $this->Transactions_model->sum_moneyout($data['sender']);
//					get daily transaction limit
						$sender_moneyout_limit = $this->Account_model->transaction_limit($data['sender']);
						if ($money_out_limit_calculation >= $sender_moneyout_limit->limit_amount || $money_out_limit_calculation + $data['amount'] > $sender_moneyout_limit->limit_amount) {
							$remainder = $sender_moneyout_limit->limit_amount - $money_out_limit_calculation;
							if ($remainder > 0) {
								$concat = ' or you may transfer funds  but not more than MK' . $remainder;
							} else {
								$concat = "";
							}
							$res['status'] = 'error';
							$res['message'] = 'Sorry, you have reached your daily transaction limit, please try again tomorrow ' . $concat;
							send_sms($data['sender'], $res['message']);
						} else {

							$is_pin_correct = $this->Account_model->check_pin_correct($data['sender'], $data['pin']);
							if ($is_pin_correct) {

								//start transaction
								$transaction_success = $this->Account_model->send_money_transaction($data);
								if ($transaction_success) {
									$logger = array(
										'user_type' => 'CUSTOMER',
										'user_id' => $data['sender'],
										'activity' => 'Sent money to' . $data['recepient'] . 'worth ' . $data['amount'] . ' for airtime purchase'
									);
									log_activity($logger);
									$dataair = array(
										'buyer' => $data['sender'],
										'recipient' => $this->input->post('receiver', TRUE),
										'amount' => $this->input->post('amount', TRUE),
										'transaction_reference' => $transaction_success
									);


									$request_it = array(
										"creditor" => $data['sender'],
										"recipient" => $this->input->post('receiver', TRUE),
										"amount" => $this->input->post('amount', TRUE),
										"SECRET_BADGE" => "12345",
										"trans_ref" => $transaction_success,

									);

									$rid = $this->Airtime_purchase_logger_model->insert($dataair);

									$sender = $data['sender'];
									$reciever = $data['recepient'];

									$customer_sender = get_customer($sender);

									$sender_message = 'Trans ID:' . $transaction_success . ', Dear Customer, your account was deducted MWK' . number_format($data['amount'], 2) . ' towards airtime purchase,  Bal: MWK' . number_format($this->your_balance($sender), 2);
									$reciever_message = 'Trans ID:' . $transaction_success . ', Dear Customer You have received MWK' . number_format($data['amount'], 2) . ' from ' . $is_pin_correct['firstname'] . ' ' . $is_pin_correct['lastname'] . ' (' . $sender . ')';
									try {

										send_sms($sender, $sender_message);
										send_sms($reciever, $reciever_message);

//														sendMail($customer_sender->email_address,'KakuPay Money Transfers',$sender_message);
//														sendMail($recepient->email_address,'KakuPay Money Transfers',$reciever_message);
										$res['status'] = 'success';
										$res['message'] = 'Transaction successful. Your new balance is MWK ' . number_format($this->your_balance($sender), 2);


									} catch (Exception $e) {

										send_sms($sender, $sender_message);
										send_sms($reciever, $reciever_message);

//														sendMail($customer_sender->email_address,'KakuPay Money Transfers',$sender_message);
//														sendMail($recepient->email_address,'KakuPay Money Transfers',$reciever_message);
										$res['status'] = 'success';
										$res['message'] = "Transaction Succeeded. Unfortunately we couldn't to send an SMS verification. Your new balance is MWK " . number_format($this->your_balance($sender), 2);


									}

									$this->request_airtime($request_it);
								} else {
									$res['status'] = 'error';
									$res['message'] = "Sorry! Something went wrong , we couldn't process your request. Please retry!";
								}


							} else {
								$res['status'] = 'error';
								$res['message'] = 'Denied! The pin you entered is incorrect.';
							}


						}


					} else {
						$res['status'] = 'error';
						$res['message'] = 'Sorry, You dont have enough balance to perform this transaction';
					}


				} else {
					$res['status'] = 'error';
					$res['message'] = 'Sorry, the account you are trying to send funds is not active ';
				}
				//check senders pin if is correct


			}


		}




//        $res['status'] = 'error';
//        $res['message'] = 'Sorry we cant process your request at the moment we will come back to you later';


		echo json_encode($res);
	}
	public function buy_credit(){
		$res = array();
		$system_down = '1';
		if($system_down=='1'){
			$res['status']='error';
			$res['message'] = 'Sorry you cant purchase airtime now, try again later';
		}else{
			$received_Token = $this->input->request_headers('Authorization');
			try {
				$jwtData = $this->objOfJwt->DecodeToken($received_Token['Token']);
				$d1 = $jwtData['timeStamp'];
				$uid = $jwtData['uniqueId'];
				$d2 = Date('Y-m-d h:i:s');
				$date1 = new DateTime($d1);
				$date2 = new DateTime($d2);

				$diff = $date2->diff($date1);

				$hours = $diff->h;
				$hours = $hours + ($diff->days * 24);

				if ($uid == $this->input->post('sender')) {
					if ($hours > 1) {
						$res['status'] = 'error';
						$res['message'] = "Sorry! your session has expired, please logout and login again";
					} else {
						$data = array(
							'sender' => $this->input->post('sender'),
							'recepient' => '+265998634231',
							'amount' => $this->input->post('amount'),
							'description' => 'Airtime purchase',
							'pin' => md5($this->input->post('pin')),
						);
						//check if he sending to himself
						if ($data['sender'] == $data['recepient']) {
							$res['status'] = 'error';
							$res['message'] = 'Sorry, you cannot send funds to yourself';
						} else {
							// check if acoount exist
							$is_amount_negative = isNegative($this->input->post('amount'));
							if ($is_amount_negative) {
								$res['status'] = 'error';
								$res['message'] = 'Sorry amount cannot be negative';
							} else {
								$is_number_exisit = $this->Account_model->check_phone_exist($data['recepient']);
								if ($is_number_exisit) {
									$is_account_active = $this->Account_model->is_account_active($data['recepient']);
									if ($is_account_active) {

										$is_pin_correct = $this->Account_model->check_pin_correct($data['sender'], $data['pin']);
										if ($is_pin_correct) {
											// check if sender has funds
											$is_balance_ok = $this->Account_model->check_balance_is_ok($data['sender'], $data['amount']);
											if ($is_balance_ok) {
												//start transaction
												$transaction_success = $this->Account_model->send_money_transaction($data);
												if ($transaction_success) {
													$fees_not_paid = $this->Account_model->paid_reg_fees($data['recepient']);
													if ($fees_not_paid) {
														$this->pay_fees($transaction_success, $data['recepient']);
													}
													$logger = array(
														'user_type' => 'CUSTOMER',
														'user_id' => $data['sender'],
														'activity' => 'Sent money to' . $data['recepient'] . 'worth ' . $data['amount']
													);
													log_activity($logger);
													$sender = $data['sender'];
													$reciever = $data['recepient'];
													$customer_sender = get_customer($sender);
													$customer_reciever = get_customer($reciever);
													$sender_message = 'Trans ID:' . $transaction_success . ', Dear Customer You have sent MWK' . number_format($data['amount'], 2) . ' to ' . $is_number_exisit['firstname'] . ' ' . $is_number_exisit['lastname'] . '(' . $reciever . ')' . ' Bal: MWK' . number_format($this->your_balance($sender), 2);
													$reciever_message = 'Trans ID:' . $transaction_success . ', Dear Customer You have received MWK' . number_format($data['amount'], 2) . ' from ' . $is_pin_correct['firstname'] . ' ' . $is_pin_correct['lastname'] . ' (' . $sender . ')';
													try {
														if ($this->check_favourite_exist($data['sender'], $data['recepient'])) {
															send_sms($sender, $sender_message);
															send_sms($reciever, $reciever_message);
//							sendMail($customer_sender->email_address,'KakuPay Money Transfers',$sender_message);
//                                    sendMail($customer_reciever->email_address,'KakuPay Money Transfers',$reciever_message);
															$res['status'] = 'success';
															$res['message'] = 'Transaction successful. Your new balance is MWK ' . number_format($this->your_balance($sender), 2);

														} else {
															send_sms($sender, $sender_message);
															send_sms($reciever, $reciever_message);
//                                    sendMail($customer_sender->email_address,'KakuPay Money Transfers',$sender_message);
//                                    sendMail($customer_reciever->email_address,'KakuPay Money Transfers',$reciever_message);
															$res['status'] = 'success';
															$res['command'] = 'save_favourite';
															$res['fav_name'] = $is_number_exisit['firstname'] . ' ' . $is_number_exisit['lastname'];
															$res['fav_number'] = $reciever;
															$res['message'] = 'Transaction successful. Your new balance is MWK ' . number_format($this->your_balance($sender), 2);

														}
													} catch (Exception $e) {
														if ($this->check_favourite_exist($data['sender'], $data['recepient'])) {
															send_sms($sender, $sender_message);
															send_sms($reciever, $reciever_message);
															sendMail($customer_sender->email_address, 'KakuPay Money Transfers', $sender_message);
															sendMail($customer_reciever->email_address, 'KakuPay Money Transfers', $reciever_message);
															$res['status'] = 'success';
															$res['message'] = "Transaction Succeeded. Unfortunately we couldn't to send an SMS verification. Your new balance is MWK " . number_format($this->your_balance($sender), 2);


														} else {
															send_ms($sender, $sender_message);
															send_sms($reciever, $reciever_message);
															sendMail($customer_sender->email_address, 'KakuPay Money Transfers', $sender_message);
															sendMail($customer_reciever->email_address, 'KakuPay Money Transfers', $reciever_message);

															$res['command'] = 'save_favourite';
															$res['fav_name'] = $is_number_exisit['firstname'] . ' ' . $is_number_exisit['lastname'];
															$res['fav_number'] = $reciever;
															$res['status'] = 'success';
															$res['message'] = "Transaction Succeeded. Unfortunately we couldn't to send an SMS verification. Your new balance is MWK " . number_format($this->your_balance($sender), 2);

														}
													}


												} else {
													$res['status'] = 'error';
													$res['message'] = "Sorry! Something went wrong , we couldn't process your request. Please retry!";
												}
											} else {
												$res['status'] = 'error';
												$res['message'] = "Sorry! You have insufficient balance for this transaction.";
											}

										} else {
											$res['status'] = 'error';
											$res['message'] = 'Denied! The pin you entered is incorrect.';
										}

									} else {
										$res['status'] = 'error';
										$res['message'] = 'Sorry! The recipient account is not active!';
									}
									//check senders pin if is correct


								} else {
									$res['status'] = 'error';
									$res['message'] = 'Sorry! This phone number is not registered on our system';
								}
							}

						}
					}
				}else{
					http_response_code('401');
					echo json_encode(array( "status" => false, "message" => 'Bad request,Unauthorized access'));
					exit;
				}
			}catch (Exception $e)
			{
				http_response_code('401');
				echo json_encode(array( "status" => false, "message" => $e->getMessage()));
				exit;
			}
		}
		echo json_encode($res);
	}
	public function try_send(){
		send_sms('+265994099461','Hi testing');
	}
	public function bulk_actions(){
		$arr = array();
		for($i=0;$i<=200000; $i++){
			$arr[] = array("uuid"=>$i,'message'=>"this message is for item ".$i);
		}
//        echo "yes";
		$this->bulk_it($arr);
//        echo json_encode($arr);
	}
	public function bulk_it($data){


		try{
			$ch = curl_init();
//            $endpoint = "https://537e97fa4f17.ngrok.io/sendsms";
			$endpoint = "http://162.241.158.123:3006/sendsms";
			//Set your auth headers
//        curl_setopt($ch, CURLOPT_HEADER, true);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array(
				"Content-Type: application/json",
//                "Content-Length: " . strlen(json_encode($data))
			));

			curl_setopt($ch, CURLOPT_URL, $endpoint);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
			curl_setopt($ch,  CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS,json_encode($data));
//        echo json_encode($data);

			$result = curl_exec ($ch);
//        $info = curl_getinfo($ch);
			var_dump($result);

			curl_close ($ch);

			//'$final = json_decode($result,TRUE);

			$final = json_decode($result);


		}catch(Exception $e){
			return $e;
		}


	}
	public function insert_bulk(){
		$res = array();
		$data = array(
			'name'=>$this->input->post('uuid'),
			'password'=>$this->input->post('message'),

		);
		if($this->Customer_model->insert_bulk($data)) {
			$res['status'] = 'success';
			$res['message'] = 'OK';

		}else{
			$res['status'] = 'error';
			$res['message'] = 'Something went wrong';
		}
		echo json_encode($res);

	}
	public function get_transacted_export()
	{
		$from = $this->input->post('from');
		$to = $this->input->post('to');

		$this->load->helper('exportexcel');
		$namaFile = "customer_by_registered.xls";
		$judul = "customer_by_transaction_and_date_registered";
		$tablehead = 0;
		$tablebody = 1;
		$nourut = 1;
		//penulisan header
		header("Pragma: public");
		header("Expires: 0");
		header("Cache-Control: must-revalidate, post-check=0,pre-check=0");
		header("Content-Type: application/force-download");
		header("Content-Type: application/octet-stream");
		header("Content-Type: application/download");
		header("Content-Disposition: attachment;filename=" . $namaFile . "");
		header("Content-Transfer-Encoding: binary ");

		xlsBOF();

		$kolomhead = 0;
		xlsWriteLabel($tablehead, $kolomhead++, "No");
		xlsWriteLabel($tablehead, $kolomhead++, "Number of Transactions");
		xlsWriteLabel($tablehead, $kolomhead++, "Total credit");
		xlsWriteLabel($tablehead, $kolomhead++, "Total debit");
		xlsWriteLabel($tablehead, $kolomhead++, "Balance");
		xlsWriteLabel($tablehead, $kolomhead++, "firstname");
		xlsWriteLabel($tablehead, $kolomhead++, "Last name");
		xlsWriteLabel($tablehead, $kolomhead++, "Phone Number");
		xlsWriteLabel($tablehead, $kolomhead++, "Account Type");
		xlsWriteLabel($tablehead, $kolomhead++, "District");
		xlsWriteLabel($tablehead, $kolomhead++, "Date Opened");


		foreach ($this->Customer_model->get_transacted_report($from,$to) as $data) {
			$kolombody = 0;

			//ubah xlsWriteLabel menjadi xlsWriteNumber untuk kolom numeric
			xlsWriteNumber($tablebody, $kolombody++, $nourut);
			xlsWriteLabel($tablebody, $kolombody++, $data->numberoftratsaction);
			xlsWriteLabel($tablebody, $kolombody++, $data->total_credit);
			xlsWriteLabel($tablebody, $kolombody++, $data->total_debits);
			xlsWriteLabel($tablebody, $kolombody++, $data->balance);
			xlsWriteLabel($tablebody, $kolombody++, $data->firstname);
			xlsWriteLabel($tablebody, $kolombody++, $data->lastname);
			xlsWriteLabel($tablebody, $kolombody++, $data->phone_number);
			xlsWriteLabel($tablebody, $kolombody++, $data->account_type_name);
			xlsWriteLabel($tablebody, $kolombody++, $data->district_name);
			xlsWriteLabel($tablebody, $kolombody++, $data->date_opened);


			$tablebody++;
			$nourut++;
		}

		xlsEOF();
		exit();
	}
	public function check_number($number){
		$res = array();
		$attendant = check_exist_in_table('customer','phone_number',$number);
		if(!empty($attendant)){
			$res['status'] = 'success';
			$res['message'] ='Success!, account is valid';
		}else{
			$res['status'] = 'error';
			$res['message'] ='Sorry this account number is not valid customer account';
		}
		echo json_encode($res);
	}
}

