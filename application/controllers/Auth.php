<?php


class Auth extends CI_Controller
{
public function __construct()
{
	parent::__construct();
	$this->load->library('form_validation');
	$this->load->model('User_access_model');
	$this->load->model('Sytem_date_model');
	$this->load->model('Access_model');
	$this->load->model('Loan_model');
}
//function load login page
public function index(){

	$this->load->view('login');
}
function update_state(){
	$this->Loan_model->update_defaulters();
}
	public function logout(){
		$this->User_access_model->update($this->session->userdata('user_id'),array('is_logged_in'=>'No'));
		$this->session->sess_destroy();
		$this->index();
	}
public function authenticate(){
	//validate user logins
	$this->load->library('form_validation');
	$this->form_validation->set_rules('username', 'Username', 'required');
	$this->form_validation->set_rules('password', 'Password', 'required');



	$this->form_validation->set_error_delimiters('<span class="text-danger">', '</span>');
	if($this->form_validation->run() == FALSE) {
		$this->index();

	}else{
		$result = $this->User_access_model->login_user($this->input->post('username'),sha1($this->input->post('password')));
		if(!empty($result)){
//			if($result['is_logged_in']=="Yes"){
//				$this->session->set_flashdata('active','Sorry another session with your credentials is on,Please log out or ask admin');
//
//				$this->toaster->error('error','Sorry another session with your credentials is on,Please log out or ask admin');
//				$this->index();
//			}else{
				$sdate =$this->Sytem_date_model->get_active();
				$rand_id = rand(100, 9999);
				$this->session->set_userdata('rand_id',$rand_id);
				$this->session->set_userdata('username',$result['AccessCode']);
				$this->session->set_userdata('user_id',$result['Employee']);
				$this->session->set_userdata('Firstname',$result['Firstname']);
				$this->session->set_userdata('Lastname',$result['Lastname']);
				$this->session->set_userdata('RoleName',$result['RoleName']);
				$this->session->set_userdata('role',$result['Role']);
				$this->session->set_userdata('profile_photo',$result['profile_photo']);
				$this->session->set_userdata('stamp',$result['server_date']);
				$this->session->set_userdata('system_date',$sdate->s_date);

				$data=$this->Access_model->get_all_acces($this->session->userdata('role'));
				$this->session->set_userdata('access',$data);
				$this->User_access_model->update($result['Employee'],array('is_logged_in'=>$rand_id));
				$logger = array(

					'user_id' => $this->session->userdata('user_id'),
					'activity' => 'logged in the system'

				);
				log_activity($logger);
				$this->toaster->success('Success you have logged in successfully');
				redirect('admin/index');
//			}

		}else{
			$this->toaster->error('error','Sorry username or password is not correct');
			$this->session->set_flashdata('error','Sorry username or password is not correct');
			$this->index();
		}
	}

}
}
