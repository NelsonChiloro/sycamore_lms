<?php


class Login extends CI_Controller
{
	public function  __construct()
	{
		parent::__construct();
	}

	public function index(){
		redirect('auth/index');
	}

	public function auth(){
		redirect('Auth/authenticate');
	}

	public function logout(){
		redirect('auth/logout');
	}

}
