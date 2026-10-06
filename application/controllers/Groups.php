<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Groups extends CI_Controller
{
    function __construct()
    {
        parent::__construct();
        $this->load->model('Group_categories_model');
        $this->load->model('Groups_model');
        $this->load->model('Account_model');
        $this->load->model('Customer_groups_model');
        $this->load->model('Branches_model');
        $this->load->library('form_validation');
    }

    public function index()
    {


        $data = array(
            'groups_data' => $this->Groups_model->get_all(),
        );
		$menu_toggle['toggles'] = 49;
		$this->load->view('admin/header', $menu_toggle);
		$this->load->view('groups/groups_list',$data);
		$this->load->view('admin/footer');

}
 public function track()
    {
		$data = array(
			'groups_data' => $this->Groups_model->get_all(),
		);
		$user = $this->input->get('user');

		$status = $this->input->get('status');

		$from = $this->input->get('from');
		$to = $this->input->get('to');
		$search = $this->input->get('search');
		$menu_toggle['toggles'] = 49;
		if($search=="filter"){
			$data2['groups_data'] = $this->Groups_model->get_filter($status,$user, $from, $to);

			$this->load->view('admin/header', $menu_toggle);
			$this->load->view('groups/track',$data2);
			$this->load->view('admin/footer');
		}elseif($search=='export pdf'){
			$data2['groups_data'] = $this->Groups_model->get_filter($status,$user, $from, $to);

//			$this->pdf->createPDF($html, "customer report as on".date('Y-m-d'), true,'A4','landscape');
		} elseif($search=='export excel'){
			$groups_data = $this->Groups_model->get_filter($status,$user, $from, $to);

			$this->excel($groups_data);
		}else{
			$this->load->view('admin/header', $menu_toggle);
			$this->load->view('groups/track',$data);
			$this->load->view('admin/footer');
		}







}
    public function assign_members()
    {


        $data = array(
            'groups_data' => $this->Groups_model->get_all(),
        );
		$menu_toggle['toggles'] = 49;
        $this->load->view('admin/header', $menu_toggle);
        $this->load->view('groups/groups_list_assign',$data);
        $this->load->view('admin/footer');

    }
 public function approve()
    {


        $data = array(
            'groups_data' => $this->Groups_model->get_all(),
        );
		$menu_toggle['toggles'] = 49;
		$this->load->view('admin/header', $menu_toggle);
		$this->load->view('groups/groups_list_approve',$data);
		$this->load->view('admin/footer');

    }

    public function read($id) 
    {
        $row = $this->Groups_model->get_by_id($id);
        if ($row) {
            $data = array(
		'group_id' => $row->group_id,
		'group_code' => $row->group_code,
		'group_name' => $row->group_name,
		'group_category' => $row->group_category,
		'physical_address' => $row->physical_address,
		'contact_address' => $row->contact_address,
		'branch' => $row->BranchName,
		'group_description' => $row->group_description,
		'group_added_by' => $row->Firstname.' '.$row->Lastname,
		'group_registered_date' => $row->group_registered_date,
	    );
            $menu_toggle['toggles'] = 49;
            $this->load->view('admin/header', $menu_toggle);
            $this->load->view('groups/groups_read',$data);
            $this->load->view('admin/footer');

        } else {
            $this->session->set_flashdata('message', 'Record Not Found');
            redirect(site_url('groups'));
        }
    }

    public function create() 
    {
        $data = array(
            'button' => 'Create',
            'action' => site_url('groups/create_action'),
	    'group_id' => set_value('group_id'),
	    'group_code' => set_value('group_code'),
	    'group_name' => set_value('group_name'),
	    'group_category' => set_value('group_category'),
	    'market' => set_value('market'),
	    'physical_address' => set_value('physical_address'),
	    'contact_address' => set_value('contact_address'),
	    'file' => set_value('file'),
	    'branch' => set_value('branch'),
	    'group_description' => set_value('group_description'),
	    'group_added_by' => set_value('group_added_by'),
	    'group_registered_date' => set_value('group_registered_date'),
	);
		$menu_toggle['toggles'] = 49;
		$this->load->view('admin/header', $menu_toggle);
		$this->load->view('groups/groups_form',$data);
		$this->load->view('admin/footer');

	}
    
    public function create_action() 
    {
        $this->_rules();
        $clientid = rand(1001,99);
        if ($this->form_validation->run() == FALSE) {
            $this->create();
        } else {
            $data = array(
		'group_code' =>$clientid,
		'group_name' => $this->input->post('group_name',TRUE),
		'group_category' => $this->input->post('group_category',TRUE),

		'branch' => $this->input->post('branch',TRUE),
		'group_description' => $this->input->post('group_description',TRUE),
		'file' => $this->input->post('file',TRUE),
		'group_added_by' => $this->session->userdata('user_id')

	    );

           $id= $this->Groups_model->insert($data);
           $member_result = $this->Customer_groups_model->add_members($this->input->post('customer'),$id);

			// Check if there were any issues with member assignment
			if(is_array($member_result) && !$member_result['success']) {
				$error_message = 'Group creation failed. The following issues were found:<br><br>';
				
				if(isset($member_result['already_assigned'])) {
					$error_message .= '<strong>Members already in other groups:</strong><br>';
					foreach($member_result['already_assigned'] as $member) {
						$error_message .= '• ' . $member . '<br>';
					}
					$error_message .= '<br>';
				}
				
				if(isset($member_result['has_active_loans'])) {
					$error_message .= '<strong>Members with active individual loans:</strong><br>';
					foreach($member_result['has_active_loans'] as $member) {
						$error_message .= '• ' . $member . '<br>';
					}
					$error_message .= '<br>';
				}
				
				if(isset($member_result['error'])) {
					$error_message .= '<strong>Other errors:</strong><br>• ' . $member_result['error'] . '<br><br>';
				}
				
				$error_message .= 'Please resolve these issues and try again.';
				
				// Delete the created group since member assignment failed
				$this->Groups_model->delete($id);
				
				$this->toaster->error($error_message);
				redirect($_SERVER['HTTP_REFERER']);
				return;
			}

			$at = get_all_by_id('account','account_type','1');
			$ct = 1;
			foreach ($at as $cc){
				$ct ++;
			}
			$account = 500000+$ct;
			$account_data = array(
				'client_id' => $clientid,
				'account_number' => $account,
				'balance' => 0,
				'account_type' => 1,
				'account_type_product' => 2,

				'added_by' => $this->session->userdata('user_id'),

			);

			$this->Account_model->insert($account_data);

            $this->toaster->success('Success, Create of group was successful');
            redirect(site_url('groups'));
        }
    }
    
    public function update($id) 
    {
        $row = $this->Groups_model->get_by_id($id);

        if ($row) {
            $data = array(
                'button' => 'Update',
                'action' => site_url('groups/update_action'),
		'group_id' => set_value('group_id', $row->group_id),
		'group_code' => set_value('group_code', $row->group_code),
		'group_name' => set_value('group_name', $row->group_name),
		'group_category' => set_value('group_category', $row->group_category),
		'branch' => set_value('branch', $row->branch),
		'group_description' => set_value('group_description', $row->group_description),
		'group_added_by' => set_value('group_added_by', $row->group_added_by),
		'file' => set_value('file', $row->file),
		'group_registered_date' => set_value('group_registered_date', $row->group_registered_date),
	    );
			$menu_toggle['toggles'] = 49;
			$this->load->view('admin/header', $menu_toggle);
			$this->load->view('groups/groups_form',$data);
			$this->load->view('admin/footer');
        } else {
            $this->session->set_flashdata('message', 'Record Not Found');
            redirect(site_url('groups'));
        }
    }
    
    public function update_action() 
    {
        $this->_rules();

        if ($this->form_validation->run() == FALSE) {
            $this->update($this->input->post('group_id', TRUE));
        } else {
            $data = array(

		'group_name' => $this->input->post('group_name',TRUE),
		'file' => $this->input->post('file',TRUE),

		'branch' => $this->input->post('branch',TRUE),
		'group_description' => $this->input->post('group_description',TRUE),

	    );

            $this->Groups_model->update($this->input->post('group_id', TRUE), $data);
			$member_result = $this->Customer_groups_model->update_members($this->input->post('customer'),$this->input->post('group_id', TRUE));
			
			// Check if there were any issues with member assignment
			if(is_array($member_result) && !$member_result['success']) {
				$warning_message = 'Group update partially completed. The following issues were found:<br><br>';
				
				if(isset($member_result['already_assigned'])) {
					$warning_message .= '<strong>Members already in other groups:</strong><br>';
					foreach($member_result['already_assigned'] as $member) {
						$warning_message .= '• ' . $member . '<br>';
					}
					$warning_message .= '<br>';
				}
				
				if(isset($member_result['has_active_loans'])) {
					$warning_message .= '<strong>Members with active individual loans:</strong><br>';
					foreach($member_result['has_active_loans'] as $member) {
						$warning_message .= '• ' . $member . '<br>';
					}
					$warning_message .= '<br>';
				}
				
				$warning_message .= 'These members were not added to the group. Other valid members were updated successfully.';
				
				$this->toaster->warning($warning_message);
				redirect($_SERVER['HTTP_REFERER']);
				return;
			}
			
            $this->toaster->success('Group was updated successfully');
            redirect($_SERVER['HTTP_REFERER']);
        }
    }
    public function approval()
    {


    	$comment = $this->input->post('comment',TRUE);
    	$act = $this->input->post('action',TRUE);


            $data = array(

		'group_status' => $this->input->post('action',TRUE),
		'reject_comment' => $act ==="Rejected" ? $comment : 'Null',
		'approval_comment' => $act ==="Active" ? $comment : 'Null',

	    );

            $this->Groups_model->update($this->input->post('group_id', TRUE), $data);
			$this->toaster->success('Success, your action was successful');
            redirect(site_url('groups/approve'));

    }
    
    public function delete($id) 
    {
        $row = $this->Groups_model->get_by_id($id);

        if ($row) {
            $this->Groups_model->delete($id);
            $this->session->set_flashdata('message', 'Delete Record Success');
            redirect(site_url('groups'));
        } else {
            $this->session->set_flashdata('message', 'Record Not Found');
            redirect(site_url('groups'));
        }
    }
	public function excel($result)
	{
		$this->load->helper('exportexcel');
		$namaFile = "groups.xls";
		$judul = "groups";
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
		xlsWriteLabel($tablehead, $kolomhead++, "Group Code");
		xlsWriteLabel($tablehead, $kolomhead++, "Group Name");

		xlsWriteLabel($tablehead, $kolomhead++, "Branch");
		xlsWriteLabel($tablehead, $kolomhead++, "Group Description");
		xlsWriteLabel($tablehead, $kolomhead++, "Group File name");
		xlsWriteLabel($tablehead, $kolomhead++, "Approval Comment");
		xlsWriteLabel($tablehead, $kolomhead++, "Reject Comment");
		xlsWriteLabel($tablehead, $kolomhead++, "Group Added By");
		xlsWriteLabel($tablehead, $kolomhead++, "Group Status");
		xlsWriteLabel($tablehead, $kolomhead++, "Group Registered Date");

		foreach ($result as $data) {
			$kolombody = 0;

			//ubah xlsWriteLabel menjadi xlsWriteNumber untuk kolom numeric
			xlsWriteNumber($tablebody, $kolombody++, $nourut);
			xlsWriteLabel($tablebody, $kolombody++, $data->group_code);
			xlsWriteLabel($tablebody, $kolombody++, $data->group_name);
			xlsWriteLabel($tablebody, $kolombody++, $data->BranchName);
			xlsWriteLabel($tablebody, $kolombody++, $data->group_description);
			xlsWriteLabel($tablebody, $kolombody++, $data->file);
			xlsWriteLabel($tablebody, $kolombody++, $data->approval_comment);
			xlsWriteLabel($tablebody, $kolombody++, $data->reject_comment);
			xlsWriteLabel($tablebody, $kolombody++, $data->Firstname.' '.$data->Lastname);
			xlsWriteLabel($tablebody, $kolombody++, $data->group_status);
			xlsWriteLabel($tablebody, $kolombody++, $data->group_registered_date);

			$tablebody++;
			$nourut++;
		}

		xlsEOF();
		exit();
	}
    public function _rules() 
    {

	$this->form_validation->set_rules('group_name', 'group name', 'trim|required');

	$this->form_validation->set_rules('branch', 'branch', 'trim|required');
	$this->form_validation->set_rules('group_description', 'group description', 'trim|required');


	$this->form_validation->set_rules('group_id', 'group_id', 'trim');
	$this->form_validation->set_error_delimiters('<span class="text-danger">', '</span>');
    }

}

