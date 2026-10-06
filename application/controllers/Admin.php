<?php

class Admin extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
        $this->load->model('Loan_model');
	}
	public function index(){
		$this->load->view('admin/header');
		$this->load->view('admin/index');
		$this->load->view('admin/footer');
	}
}
