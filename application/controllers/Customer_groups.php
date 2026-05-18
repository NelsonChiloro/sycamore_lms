<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Customer_groups extends CI_Controller
{
    function __construct()
    {
        parent::__construct();
        $this->load->model('Customer_groups_model');
        $this->load->model('Groups_model');
        $this->load->model('Loan_model');
        $this->load->library('form_validation');
    }

    public function members($id)
    {

        $customer_groups = $this->Customer_groups_model->get_members($id);
        $groups = $this->Groups_model->get_by_id($id);



        $data = array(
            'customer_groups_data' => $customer_groups,
			'group_id'=>$id,
			'group'=>$groups,


        );
		$this->load->view('admin/header');
        $this->load->view('customer_groups/customer_groups_list', $data);
		$this->load->view('admin/footer');
    }
    public function get_members($id)
    {
$res = array();
        $customer_groups = $this->Customer_groups_model->get_members($id);
        $loans = $this->Loan_model->get_group_loan($id);
        $group = $this->Groups_model->get_by_id($id);
        $res['data'] = $customer_groups;
        $res['loan'] = $loans;
        $res['group'] = $group;
	echo json_encode($res);
    }
    public function manage($id)
    {

        $customer_groups = $this->Customer_groups_model->get_members($id);
        $groups = $this->Groups_model->get_by_id($id);



        $data = array(
            'customer_groups_data' => $customer_groups,
			'group_id'=>$id,
			'group'=>$groups,


        );
		$this->load->view('admin/header');
        $this->load->view('customer_groups/customer_groups_list_manage', $data);
		$this->load->view('admin/footer');
    }
    public function print_group($id)
    {

        $customer_groups = $this->Customer_groups_model->get_members($id);
        $groups = $this->Groups_model->get_by_id($id);



        $data = array(
            'customer_groups_data' => $customer_groups,
			'group_id'=>$id,
			'group'=>$groups,

        );
		$this->load->library('Pdf');
		$html = $this->load->view('customer_groups/report', $data,true);
		$this->pdf->createPDF($html, "Group report as on".date('Y-m-d'), true,'A4','portrait');


    }

    public function read($id) 
    {
        $row = $this->Customer_groups_model->get_by_id($id);
        if ($row) {
            $data = array(
		'customer_group_id' => $row->customer_group_id,
		'customer' => $row->customer,
		'group_id' => $row->group_id,
		'date_joined' => $row->date_joined,
	    );
            $this->load->view('customer_groups/customer_groups_read', $data);
        } else {
            $this->session->set_flashdata('message', 'Record Not Found');
            redirect(site_url('customer_groups'));
        }
    }

    public function create() 
    {
        $data = array(
            'button' => 'Create',
            'action' => site_url('customer_groups/create_action'),
	    'customer_group_id' => set_value('customer_group_id'),
	    'customer' => set_value('customer'),
	    'group_id' => set_value('group_id'),
	    'date_joined' => set_value('date_joined'),
	);
        $this->load->view('customer_groups/customer_groups_form', $data);
    }
    
    public function create_action() 
    {

            $data = array(
		'customer' => $this->input->post('customer',TRUE),
		'group_id' => $this->input->post('group_id',TRUE),

	    );
$check =   $this->Customer_groups_model->check($data['group_id'],$data['customer']);
if(!empty($check)){
	$this->toaster->error('Member already added to the group');
	redirect($_SERVER['HTTP_REFERER']);
}else{
	$this->Customer_groups_model->insert($data);
	$this->toaster->success('Member added to group');
	redirect($_SERVER['HTTP_REFERER']);
	}



    }
    
    public function update($id) 
    {
        $row = $this->Customer_groups_model->get_by_id($id);

        if ($row) {
            $data = array(
                'button' => 'Update',
                'action' => site_url('customer_groups/update_action'),
		'customer_group_id' => set_value('customer_group_id', $row->customer_group_id),
		'customer' => set_value('customer', $row->customer),
		'group_id' => set_value('group_id', $row->group_id),
		'date_joined' => set_value('date_joined', $row->date_joined),
	    );
            $this->load->view('customer_groups/customer_groups_form', $data);
        } else {
            $this->session->set_flashdata('message', 'Record Not Found');
            redirect(site_url('customer_groups'));
        }
    }
    
    public function update_action() 
    {
        $this->_rules();

        if ($this->form_validation->run() == FALSE) {
            $this->update($this->input->post('customer_group_id', TRUE));
        } else {
            $data = array(
		'customer' => $this->input->post('customer',TRUE),
		'group_id' => $this->input->post('group_id',TRUE),
		'date_joined' => $this->input->post('date_joined',TRUE),
	    );

            $this->Customer_groups_model->update($this->input->post('customer_group_id', TRUE), $data);
            $this->session->set_flashdata('message', 'Update Record Success');
            redirect(site_url('customer_groups'));
        }
    }
    
    public function delete($id) 
    {
        $row = $this->Customer_groups_model->get_by_id($id);

        if ($row) {
            $this->Customer_groups_model->delete($id);
			$this->toaster->success('Group Member deleted successfully ');
			redirect($_SERVER['HTTP_REFERER']);
        } else {
            $this->toaster->error('Record Not Found');
			redirect($_SERVER['HTTP_REFERER']);
        }
    }

    public function _rules() 
    {
	$this->form_validation->set_rules('customer', 'customer', 'trim|required');
	$this->form_validation->set_rules('group_id', 'group id', 'trim|required');


	$this->form_validation->set_rules('customer_group_id', 'customer_group_id', 'trim');
	$this->form_validation->set_error_delimiters('<span class="text-danger">', '</span>');
    }

}


