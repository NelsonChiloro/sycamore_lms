<?php


class Login extends CI_Controller
{
	public function  __construct()
	{
		parent:: __construct();
//		$this->load->model('Users_model');
	}
	public function index(){
		$this->load->view('login');
	}
	public function auth(){
		$email = $this->input->post('email');
		$pass = md5($this->input->post('password'));
		$result = $this->Users_model->auth($email,$pass);
		if(!empty($result)){

			$this->session->set_userdata('user_id',$result->id);
			$this->session->set_userdata('fullname',$result->fullname);
			redirect('Admin');
		}else{
			$this->session->set_flashdata('error','Email or password is not valid');
			redirect('Login');
		}
	}
	public function logout(){
		$this->session->sess_destroy();
		redirect(base_url().'login/index');
	}

}
