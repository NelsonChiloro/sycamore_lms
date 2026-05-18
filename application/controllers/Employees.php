<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');
require APPPATH . '/libraries/FPDF.php';
class Employees extends CI_Controller
{
    function __construct()
    {
        parent::__construct();
		$this->load->model('Geo_countries_model');
        $this->load->model('Employees_model');
		$this->load->model('Branches_model');
		$this->load->model('Roles_model');
        $this->load->library('form_validation');
    }
	function git()
	{
		$this->load->library('Pdf');
		$html = $this->load->view('testv', array(), true);
		$this->pdf->createPDF($html, 'mypdf', true);
	}
    public function index()
    {


        $data = array(
            'employees_data' => $this->Employees_model->get_all(),

        );
		$this->load->view('admin/header');
		$this->load->view('employees/employees_list', $data);
		$this->load->view('admin/footer');


    }

    public function read($id) 
    {
        $row = $this->Employees_model->get_by_id($id);
        if ($row) {
            $data = array(
		'id' => $row->id,
		'Firstname' => $row->Firstname,
		'Middlename' => $row->Middlename,
		'Lastname' => $row->Lastname,
		'Gender' => $row->Gender,
		'DateOfBirth' => $row->DateOfBirth,
		'EmailAddress' => $row->EmailAddress,
		'PhoneNumber' => $row->PhoneNumber,
		'AddressLine1' => $row->AddressLine1,
		'AddressLine2' => $row->AddressLine2,
		'Province' => $row->Province,
		'City' => $row->City,
		'Country' => $row->Country,
		'Role' => $row->RoleName,
		'Branch' => $row->Branch,
		'EmploymentStatus' => $row->EmploymentStatus,
		'LastUpdatedOn' => $row->LastUpdatedOn,
		'CreatedOn' => $row->CreatedOn,
	    );
			$this->load->view('admin/header');
			$this->load->view('employees/employees_read', $data);
			$this->load->view('admin/footer');
        } else {
            $this->session->set_flashdata('message', 'Record Not Found');
            redirect(site_url('employees'));
        }
    }
    public function profile(){
		$this->load->view('admin/header');
		$this->load->view('user_access/profile');
		$this->load->view('admin/footer');
	}
    public function get_employee($id)
    {
		$res = array();
        $row = $this->Employees_model->get_by_id($id);
        if ($row) {

            $data = array(
		'id' => $row->id,
		'Firstname' => $row->Firstname,
		'Middlename' => $row->Middlename,
		'Lastname' => $row->Lastname,
		'Gender' => $row->Gender,
		'DateOfBirth' => $row->DateOfBirth,
		'EmailAddress' => $row->EmailAddress,
		'PhoneNumber' => $row->PhoneNumber,
		'AddressLine1' => $row->AddressLine1,
		'AddressLine2' => $row->AddressLine2,
		'Province' => $row->Province,
		'City' => $row->City,
		'Country' => $row->Country,
		'Role' => $row->RoleName,
		'Branch' => $row->Branch,
		'EmploymentStatus' => $row->EmploymentStatus,
		'LastUpdatedOn' => $row->LastUpdatedOn,
		'CreatedOn' => $row->CreatedOn,
	    );
			$res['status'] = 'success';
			$res['data'] = $data;
        } else {
			$res['status'] = 'error';

        }
        echo json_encode($res);
    }

    public function create() 
    {
        $data = array(
            'button' => 'Create',
            'action' => site_url('employees/create_action'),
	    'id' => set_value('id'),
	    'Firstname' => set_value('Firstname'),
	    'Middlename' => set_value('Middlename'),
	    'Lastname' => set_value('Lastname'),
	    'Gender' => set_value('Gender'),
	    'DateOfBirth' => set_value('DateOfBirth'),
	    'EmailAddress' => set_value('EmailAddress'),
	    'PhoneNumber' => set_value('PhoneNumber'),
	    'AddressLine1' => set_value('AddressLine1'),
	    'AddressLine2' => set_value('AddressLine2'),
	    'Province' => set_value('Province'),
	    'City' => set_value('City'),
	    'Country' => set_value('Country'),
	    'Role' => set_value('Role'),
	    'Branch' => set_value('Branch'),
	    'EmploymentStatus' => set_value('EmploymentStatus'),
	    'LastUpdatedOn' => set_value('LastUpdatedOn'),
	    'CreatedOn' => set_value('CreatedOn'),
	);
		$this->load->view('admin/header');
		$this->load->view('employees/employees_form', $data);
		$this->load->view('admin/footer');
    }
    
    public function create_action() 
    {
        $this->_rules();

        if ($this->form_validation->run() == FALSE) {
            $this->create();
        } else {
            $data = array(
		'Firstname' => $this->input->post('Firstname',TRUE),
		'Middlename' => $this->input->post('Middlename',TRUE),
		'Lastname' => $this->input->post('Lastname',TRUE),
		'Gender' => $this->input->post('Gender',TRUE),
		'DateOfBirth' => $this->input->post('DateOfBirth',TRUE),
		'EmailAddress' => $this->input->post('EmailAddress',TRUE),
		'PhoneNumber' => $this->input->post('PhoneNumber',TRUE),
		'AddressLine1' => $this->input->post('AddressLine1',TRUE),
		'AddressLine2' => $this->input->post('AddressLine2',TRUE),
		'Province' => $this->input->post('Province',TRUE),
		'City' => $this->input->post('City',TRUE),
		'Country' => $this->input->post('Country',TRUE),
		'Role' => $this->input->post('Role',TRUE),
		'BranchCode' => $this->input->post('Branch',TRUE),
			);

//			$logger = array(
//				'auth_type' => 'employee_creation',
//				'old_data' => json_encode($data),
//				'new_data' => json_encode($data),
//
//				'system_date' => $this->session->userdata('system_date'),
//
//				'initiator' => $this->session->userdata('user_id')
//
//			);

			$logger = array(

				'user_id' => $this->session->userdata('user_id'),
				'activity' => 'Create employee'.$data['Firstname'].' '.$data['Lastname']

			);
			log_activity($logger);
            $this->Employees_model->insert($data);
//			auth_logger($logger);
			$this->toaster->success('Success, employee was created please pending authorisation');
            redirect(site_url('employees'));
        }
    }
    
    public function update($id) 
    {
        $row = $this->Employees_model->get_by_id($id);

        if ($row) {
            $data = array(
                'button' => 'Update',
                'action' => site_url('employees/update_action'),
		'id' => set_value('id', $row->empid),
		'Firstname' => set_value('Firstname', $row->Firstname),
		'Middlename' => set_value('Middlename', $row->Middlename),
		'Lastname' => set_value('Lastname', $row->Lastname),
		'Gender' => set_value('Gender', $row->Gender),
		'DateOfBirth' => set_value('DateOfBirth', $row->DateOfBirth),
		'EmailAddress' => set_value('EmailAddress', $row->EmailAddress),
		'PhoneNumber' => set_value('PhoneNumber', $row->PhoneNumber),
		'AddressLine1' => set_value('AddressLine1', $row->AddressLine1),
		'AddressLine2' => set_value('AddressLine2', $row->AddressLine2),
		'Province' => set_value('Province', $row->Province),
		'City' => set_value('City', $row->City),
		'Country' => set_value('Country', $row->Country),
		'Role' => set_value('Role', $row->Role),
		'Branch' => set_value('Branch', $row->BranchCode),

	    );
			$this->load->view('admin/header');
			$this->load->view('employees/employees_form', $data);
			$this->load->view('admin/footer');
        } else {
            $this->session->set_flashdata('message', 'Record Not Found');
            redirect(site_url('employees'));
        }
    }
    
    public function update_action() 
    {
        $this->_rules2();

        if ($this->form_validation->run() == FALSE) {
            $this->update($this->input->post('id', TRUE));
        } else {
            $data = array(
		'Firstname' => $this->input->post('Firstname',TRUE),
		'Middlename' => $this->input->post('Middlename',TRUE),
		'Lastname' => $this->input->post('Lastname',TRUE),
		'Gender' => $this->input->post('Gender',TRUE),
		'DateOfBirth' => $this->input->post('DateOfBirth',TRUE),
		'EmailAddress' => $this->input->post('EmailAddress',TRUE),
		'PhoneNumber' => $this->input->post('PhoneNumber',TRUE),
		'AddressLine1' => $this->input->post('AddressLine1',TRUE),
		'AddressLine2' => $this->input->post('AddressLine2',TRUE),
		'Province' => $this->input->post('Province',TRUE),
		'City' => $this->input->post('City',TRUE),
		'Country' => $this->input->post('Country',TRUE),
		'Role' => $this->input->post('Role',TRUE),
		'BranchCode' => $this->input->post('Branch',TRUE),

	    );


			$logger = array(

				'user_id' => $this->session->userdata('user_id'),
				'activity' => 'Update Employee info of'.' '.$data['Firstname'].' '.$data['Lastname']

			);
			log_activity($logger);
		 $this->Employees_model->update($this->input->post('id'),$data);

			$this->toaster->success('Success, employee update request was added');

            redirect(site_url('employees'));
        }
    }
    
    public function delete($id) 
    {
        $row = $this->Employees_model->get_by_id($id);

        if ($row) {

			$logger = array(

				'user_id' => $this->session->userdata('user_id'),
				'activity' => 'Delete user'

			);
			log_activity($logger);
            $this->Employees_model->delete($id);
            $this->session->set_flashdata('message', 'Delete Record Success');
            redirect(site_url('employees'));
        } else {
            $this->session->set_flashdata('message', 'Record Not Found');
            redirect(site_url('employees'));
        }
    }

    public function _rules() 
    {
	$this->form_validation->set_rules('Firstname', 'firstname', 'trim|required');
	$this->form_validation->set_rules('Lastname', 'lastname', 'trim|required');
	$this->form_validation->set_rules('Gender', 'gender', 'trim|required');
	$this->form_validation->set_rules('DateOfBirth', 'dateofbirth', 'trim|required');
	$this->form_validation->set_rules('EmailAddress', 'emailaddress', 'trim|required|is_unique[employees.EmailAddress]');
	$this->form_validation->set_rules('PhoneNumber', 'phonenumber', 'trim|required|is_unique[employees.phonenumber]');
	$this->form_validation->set_rules('AddressLine1', 'addressline1', 'trim|required');
	$this->form_validation->set_rules('Province', 'province', 'trim|required');
	$this->form_validation->set_rules('City', 'city', 'trim|required');
	$this->form_validation->set_rules('Country', 'country', 'trim|required');
	$this->form_validation->set_rules('Role', 'role', 'trim|required');



	$this->form_validation->set_rules('id', 'id', 'trim');
	$this->form_validation->set_error_delimiters('<span class="text-danger">', '</span>');
    }
    public function _rules2()
    {
	$this->form_validation->set_rules('Firstname', 'firstname', 'trim|required');
	$this->form_validation->set_rules('Lastname', 'lastname', 'trim|required');
	$this->form_validation->set_rules('Gender', 'gender', 'trim|required');
	$this->form_validation->set_rules('DateOfBirth', 'dateofbirth', 'trim|required');
	$this->form_validation->set_rules('EmailAddress', 'emailaddress', 'trim|required');
	$this->form_validation->set_rules('PhoneNumber', 'phonenumber', 'trim|required');
	$this->form_validation->set_rules('AddressLine1', 'addressline1', 'trim|required');
	$this->form_validation->set_rules('Province', 'province', 'trim|required');
	$this->form_validation->set_rules('City', 'city', 'trim|required');
	$this->form_validation->set_rules('Country', 'country', 'trim|required');
	$this->form_validation->set_rules('Role', 'role', 'trim|required');



	$this->form_validation->set_rules('id', 'id', 'trim');
	$this->form_validation->set_error_delimiters('<span class="text-danger">', '</span>');
    }

}


