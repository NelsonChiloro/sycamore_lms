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
                'SupervisorName' => $this->format_supervisor_name(isset($row->Supervisor) ? $row->Supervisor : null),
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

    /**
     * Map existing relationship/risk officers to their supervisors.
     */
    public function map_supervisors()
    {
        $data = array(
            'officers' => $this->Employees_model->get_relationship_officers(true),
            'supervisors' => $this->Employees_model->get_relationship_supervisors(),
            'branch_managers' => $this->Employees_model->get_branch_managers(),
            'risk_officers' => $this->Employees_model->get_risk_officers(true),
            'risk_supervisors' => $this->Employees_model->get_risk_rehab_supervisors(),
        );
        $this->load->view('admin/header');
        $this->load->view('employees/map_supervisors', $data);
        $this->load->view('admin/footer');
    }

    public function map_supervisors_action()
    {
        $ro_assignments = $this->input->post('supervisor');
        $risk_assignments = $this->input->post('risk_supervisor');
        if (!is_array($ro_assignments) && !is_array($risk_assignments)) {
            $this->toaster->error('No mapping data received.');
            redirect(site_url('employees/map_supervisors'));
            return;
        }

        $updated = 0;
        if (is_array($ro_assignments)) {
            $updated += $this->apply_supervisor_assignments(
                $ro_assignments,
                array('Employees_model', 'role_is_relationship_officer'),
                array('Employees_model', 'role_can_supervise_relationship_officer')
            );
        }
        if (is_array($risk_assignments)) {
            $updated += $this->apply_supervisor_assignments(
                $risk_assignments,
                array('Employees_model', 'role_is_risk_officer'),
                array('Employees_model', 'role_is_risk_rehab_supervisor')
            );
        }

        log_activity(array(
            'user_id' => $this->session->userdata('user_id'),
            'activity' => 'Updated officer supervisor mappings (' . $updated . ' officers)',
        ));
        $this->toaster->success('Supervisor mappings saved successfully.');
        redirect(site_url('employees/map_supervisors'));
    }

    /**
     * Apply officer => supervisor assignments where both roles satisfy the given checks.
     * Returns the number of officers updated.
     */
    private function apply_supervisor_assignments($assignments, $officer_role_check, $supervisor_role_check)
    {
        $updated = 0;
        foreach ($assignments as $officer_id => $supervisor_id) {
            $officer_id = (int) $officer_id;
            if ($officer_id <= 0) {
                continue;
            }
            $officer = $this->Employees_model->get_by_id($officer_id);
            if (!$officer || !call_user_func($officer_role_check, $officer->RoleName)) {
                continue;
            }
            $supervisor_id = (int) $supervisor_id;
            if ($supervisor_id <= 0) {
                $this->Employees_model->update_supervisor($officer_id, 0);
                $updated++;
                continue;
            }
            $supervisor = $this->Employees_model->get_by_id($supervisor_id);
            if (!$supervisor || !call_user_func($supervisor_role_check, $supervisor->RoleName)) {
                continue;
            }
            $this->Employees_model->update_supervisor($officer_id, $supervisor_id);
            $updated++;
        }
        return $updated;
    }

    public function create() 
    {
        $roles = $this->Roles_model->get_all();
        $data = array(
            'button' => 'Create',
            'action' => site_url('employees/create_action'),
            'roles_json' => json_encode($this->build_role_type_map($roles)),
            'relationship_supervisors' => $this->Employees_model->get_relationship_supervisors(),
            'branch_managers' => $this->Employees_model->get_branch_managers(),
            'risk_rehab_supervisors' => $this->Employees_model->get_risk_rehab_supervisors(),
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
            'Supervisor' => set_value('Supervisor'),
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
            $role_row = $this->Roles_model->get_by_id($this->input->post('Role', TRUE));
            $needs_supervisor = $role_row && (
                $this->Employees_model->role_is_relationship_officer($role_row->RoleName)
                || $this->Employees_model->role_is_risk_officer($role_row->RoleName)
            );
            $supervisor_val = null;
            if ($needs_supervisor) {
                $supervisor_val = (int) $this->input->post('Supervisor', TRUE);
            }
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
                'Supervisor' => $supervisor_val > 0 ? $supervisor_val : null,
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
            $roles = $this->Roles_model->get_all();
            $data = array(
                'button' => 'Update',
                'action' => site_url('employees/update_action'),
                'roles_json' => json_encode($this->build_role_type_map($roles)),
                'relationship_supervisors' => $this->Employees_model->get_relationship_supervisors(),
                'branch_managers' => $this->Employees_model->get_branch_managers(),
                'risk_rehab_supervisors' => $this->Employees_model->get_risk_rehab_supervisors(),
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
                'Supervisor' => set_value('Supervisor', $row->Supervisor),

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
            $role_row = $this->Roles_model->get_by_id($this->input->post('Role', TRUE));
            $needs_supervisor = $role_row && (
                $this->Employees_model->role_is_relationship_officer($role_row->RoleName)
                || $this->Employees_model->role_is_risk_officer($role_row->RoleName)
            );
            $supervisor_val = null;
            if ($needs_supervisor) {
                $supervisor_val = (int) $this->input->post('Supervisor', TRUE);
            }
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
                'Supervisor' => $supervisor_val > 0 ? $supervisor_val : null,

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
        $this->form_validation->set_rules('Supervisor', 'supervisor', 'trim|callback_validate_supervisor_for_role');



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
        $this->form_validation->set_rules('Supervisor', 'supervisor', 'trim|callback_validate_supervisor_for_role');



	$this->form_validation->set_rules('id', 'id', 'trim');
	$this->form_validation->set_error_delimiters('<span class="text-danger">', '</span>');
    }

    public function validate_supervisor_for_role($supervisor_id)
    {
        $role_id = $this->input->post('Role');
        $role = $this->Roles_model->get_by_id($role_id);
        if (!$role) {
            return true;
        }
        $is_ro = $this->Employees_model->role_is_relationship_officer($role->RoleName);
        $is_risk = $this->Employees_model->role_is_risk_officer($role->RoleName);
        if (!$is_ro && !$is_risk) {
            return true;
        }
        if ($supervisor_id === '' || $supervisor_id === null || (int) $supervisor_id <= 0) {
            $msg = $is_ro
                ? 'A Relationship supervisor or Branch manager is required for Relationship officers.'
                : 'A Risk and Rehabilitation supervisor is required for Risk officers.';
            $this->form_validation->set_message('validate_supervisor_for_role', $msg);
            return false;
        }
        $supervisor = $this->Employees_model->get_by_id((int) $supervisor_id);
        $valid = false;
        if ($supervisor) {
            $valid = $is_ro
                ? $this->Employees_model->role_can_supervise_relationship_officer($supervisor->RoleName)
                : $this->Employees_model->role_is_risk_rehab_supervisor($supervisor->RoleName);
        }
        if (!$valid) {
            $msg = $is_ro
                ? 'Please select a valid Relationship supervisor or Branch manager.'
                : 'Please select a valid Risk and Rehabilitation supervisor.';
            $this->form_validation->set_message('validate_supervisor_for_role', $msg);
            return false;
        }
        return true;
    }

    private function format_supervisor_name($supervisor_id)
    {
        if (empty($supervisor_id)) {
            return 'N/A';
        }
        $sup = $this->Employees_model->get_by_id((int) $supervisor_id);
        if (!$sup) {
            return 'N/A';
        }
        return trim($sup->Firstname . ' ' . $sup->Lastname);
    }

    private function build_role_type_map($roles)
    {
        $map = array('officer' => array(), 'supervisor' => array(), 'risk_officer' => array());
        foreach ($roles as $role) {
            if ($this->Employees_model->role_is_relationship_officer($role->RoleName)) {
                $map['officer'][] = (int) $role->id;
            }
            if ($this->Employees_model->role_is_relationship_supervisor($role->RoleName)) {
                $map['supervisor'][] = (int) $role->id;
            }
            if ($this->Employees_model->role_is_risk_officer($role->RoleName)) {
                $map['risk_officer'][] = (int) $role->id;
            }
        }
        return $map;
    }

}


