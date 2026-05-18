<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Customer_access extends CI_Controller
{
    function __construct()
    {
        parent::__construct();
        $this->load->model('Customer_access_model');
        $this->load->library('form_validation');
    }

    public function index()
    {
        $data = array(
            'customer_access_data' => $this->Customer_access_model->get_all_by_status('Initiated'),
        );
        $this->load->view('admin/header');
        $this->load->view('customer_access/track', $data);
        $this->load->view('admin/footer');
    }
    public function track()
    {


        $data = array(
            'customer_access_data' => $this->Customer_access_model->get_all_by_status('Initiated'),
        );
        $this->load->view('admin/header');
        $this->load->view('customer_access/track', $data);
        $this->load->view('admin/footer');
    }
    public function approve()
    {


        $data = array(
            'customer_access_data' => $this->Customer_access_model->get_all_by_status('Initiated'),
        );
        $this->load->view('admin/header');
        $this->load->view('customer_access/approve', $data);
        $this->load->view('admin/footer');
    }
    public function approved()
    {
        $data = array(
            'customer_access_data' => $this->Customer_access_model->get_all_by_status('Active'),
        );
        $this->load->view('admin/header');
        $this->load->view('customer_access/approved', $data);
        $this->load->view('admin/footer');
    }
    public function rejected()
    {
        $data = array(
            'customer_access_data' => $this->Customer_access_model->get_all_by_status('Rejected'),
        );
        $this->load->view('admin/header');
        $this->load->view('customer_access/rejected', $data);
        $this->load->view('admin/footer');
    }

    public function reject($id)
    {


            $data = array(

                'denied_by' => $this->session->userdata('user_id'),
		'status' =>'Rejected',

	    );
      $this->Customer_access_model->update($id,$data);
        $this->toaster->success('Reject was done successfully');
        redirect($_SERVER['HTTP_REFERER']);
    }
    public function approve_action($id)
    {


            $data = array(
                'approved_by' =>$this->session->userdata('user_id'),
               
		'status' =>'Active',

	    );
      $this->Customer_access_model->update($id,$data);
            $this->toaster->success('Approval succeeded');
            redirect($_SERVER['HTTP_REFERER']);
    }

    public function create() 
    {
        $data = array(
            'button' => 'Create',
            'action' => site_url('customer_access/create_action'),
	    'id' => set_value('id'),
	    'customer_id' => set_value('customer_id'),
	    'phone_number' => set_value('phone_number'),
	    'created_by' => set_value('created_by'),
	    'approved_by' => set_value('approved_by'),
	    'denied_by' => set_value('denied_by'),
	    'status' => set_value('status'),
	    'stamp' => set_value('stamp'),
	);
        $this->load->view('admin/header');
        $this->load->view('customer_access/customer_access_form', $data);
        $this->load->view('admin/footer');

    }
    
    public function create_action() 
    {
       $get = get_by_id('individual_customers','id',$this->input->post('customer_id',TRUE));
            $data = array(
		'customer_id' => $this->input->post('customer_id',TRUE),
		'phone_number' => $get->PhoneNumber,
		'created_by' => $this->session->userdata('user_id'),
		'password' =>md5(rand(100,9999)),
	                    );

            $this->Customer_access_model->insert($data);
            $this->session->set_flashdata('message', 'Create Record Success');
            redirect(site_url('Customer_access/track'));

    }
    
    public function update($id) 
    {
        $row = $this->Customer_access_model->get_by_id($id);

        if ($row) {
            $data = array(
                'button' => 'Update',
                'action' => site_url('customer_access/update_action'),
		'id' => set_value('id', $row->id),
		'customer_id' => set_value('customer_id', $row->customer_id),
		'phone_number' => set_value('phone_number', $row->phone_number),
		'created_by' => set_value('created_by', $row->created_by),
		'approved_by' => set_value('approved_by', $row->approved_by),
		'denied_by' => set_value('denied_by', $row->denied_by),
		'status' => set_value('status', $row->status),
		'stamp' => set_value('stamp', $row->stamp),
	    );
            $this->load->view('customer_access/customer_access_form', $data);
        } else {
            $this->session->set_flashdata('message', 'Record Not Found');
            redirect(site_url('customer_access'));
        }
    }
    
    public function update_action() 
    {
        $this->_rules();

        if ($this->form_validation->run() == FALSE) {
            $this->update($this->input->post('id', TRUE));
        } else {
            $data = array(
		'customer_id' => $this->input->post('customer_id',TRUE),
		'phone_number' => $this->input->post('phone_number',TRUE),
		'created_by' => $this->input->post('created_by',TRUE),
		'approved_by' => $this->input->post('approved_by',TRUE),
		'denied_by' => $this->input->post('denied_by',TRUE),
		'status' => $this->input->post('status',TRUE),
		'stamp' => $this->input->post('stamp',TRUE),
	    );

            $this->Customer_access_model->update($this->input->post('id', TRUE), $data);
            $this->session->set_flashdata('message', 'Update Record Success');
            redirect(site_url('customer_access'));
        }
    }
    
    public function delete($id) 
    {
        $row = $this->Customer_access_model->get_by_id($id);

        if ($row) {
            $this->Customer_access_model->delete($id);
            $this->session->set_flashdata('message', 'Delete Record Success');
            redirect(site_url('customer_access'));
        } else {
            $this->session->set_flashdata('message', 'Record Not Found');
            redirect(site_url('customer_access'));
        }
    }

    public function _rules() 
    {
	$this->form_validation->set_rules('customer_id', 'customer id', 'trim|required');
	$this->form_validation->set_rules('phone_number', 'phone number', 'trim|required');
	$this->form_validation->set_rules('created_by', 'created by', 'trim|required');
	$this->form_validation->set_rules('approved_by', 'approved by', 'trim|required');
	$this->form_validation->set_rules('denied_by', 'denied by', 'trim|required');
	$this->form_validation->set_rules('status', 'status', 'trim|required');
	$this->form_validation->set_rules('stamp', 'stamp', 'trim|required');

	$this->form_validation->set_rules('id', 'id', 'trim');
	$this->form_validation->set_error_delimiters('<span class="text-danger">', '</span>');
    }

}

/* End of file Customer_access.php */
/* Location: ./application/controllers/Customer_access.php */
/* Please DO NOT modify this information : */
/* Generated by Harviacode Codeigniter CRUD Generator 2022-05-14 13:26:07 */
/* http://harviacode.com */
