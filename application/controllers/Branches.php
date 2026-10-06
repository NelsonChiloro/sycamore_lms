<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Branches extends CI_Controller
{
    function __construct()
    {
        parent::__construct();
        $this->load->model('Branches_model');
        $this->load->library('form_validation');
    }

    public function index()
    {
        $q = urldecode($this->input->get('q', TRUE));
        $start = intval($this->input->get('start'));
        
        if ($q <> '') {
            $config['base_url'] = base_url() . 'branches/index?q=' . urlencode($q);
            $config['first_url'] = base_url() . 'branches/index?q=' . urlencode($q);
        } else {
            $config['base_url'] = base_url() . 'branches/index';
            $config['first_url'] = base_url() . 'branches/index';
        }

        $config['per_page'] = 10;
        $config['page_query_string'] = TRUE;
        $config['total_rows'] = $this->Branches_model->total_rows($q);
        $branches = $this->Branches_model->get_limit_data($config['per_page'], $start, $q);

        $this->load->library('pagination');
        $this->pagination->initialize($config);

        $data = array(
            'branches_data' => $this->Branches_model->get_all(),
            'q' => $q,
            'pagination' => $this->pagination->create_links(),
            'total_rows' => $config['total_rows'],
            'start' => $start,
        );
		$this->load->view('admin/header');
		$this->load->view('branches/branches_list',$data);
		$this->load->view('admin/footer');

    }

    public function read($id) 
    {
        $row = $this->Branches_model->get_by_id($id);
        if ($row) {
            $data = array(
		'id' => $row->id,
		'BranchCode' => $row->BranchCode,
		'BranchName' => $row->BranchName,
		'AddressLine1' => $row->AddressLine1,
		'AddressLine2' => $row->AddressLine2,
		'City' => $row->City,
		'Stamp' => $row->Stamp,
	    );
            $this->load->view('branches/branches_read', $data);
        } else {
            $this->session->set_flashdata('message', 'Record Not Found');
            redirect(site_url('branches'));
        }
    }

    public function create() 
    {
        $data = array(
            'button' => 'Create',
            'action' => site_url('branches/create_action'),
	    'id' => set_value('id'),
	    'BranchCode' => set_value('BranchCode'),
	    'BranchName' => set_value('BranchName'),
	    'AddressLine1' => set_value('AddressLine1'),
	    'AddressLine2' => set_value('AddressLine2'),
	    'City' => set_value('City'),
	    'Stamp' => set_value('Stamp'),
	);
		$this->load->view('admin/header');
		$this->load->view('branches/branches_form',$data);
		$this->load->view('admin/footer');
    }
    
    public function create_action() 
    {
        $this->_rules();

        if ($this->form_validation->run() == FALSE) {
            $this->create();
        } else {
            $data = array(
		'BranchCode' => $this->input->post('BranchCode',TRUE),
		'Code' => rand(100,9999),
		'BranchName' => $this->input->post('BranchName',TRUE),
		'AddressLine1' => $this->input->post('AddressLine1',TRUE),
		'AddressLine2' => $this->input->post('AddressLine2',TRUE),
		'City' => $this->input->post('City',TRUE),

	    );

            $this->Branches_model->insert($data);
            $this->toaster->success('Success,branch was created');
            redirect(site_url('branches'));
        }
    }
    
    public function update($id) 
    {
        $row = $this->Branches_model->get_by_id($id);

        if ($row) {
            $data = array(
                'button' => 'Update',
                'action' => site_url('branches/update_action'),
		'id' => set_value('id', $row->id),
		'BranchCode' => set_value('BranchCode', $row->BranchCode),
		'BranchName' => set_value('BranchName', $row->BranchName),
		'AddressLine1' => set_value('AddressLine1', $row->AddressLine1),
		'AddressLine2' => set_value('AddressLine2', $row->AddressLine2),
		'City' => set_value('City', $row->City),
		'Stamp' => set_value('Stamp', $row->Stamp),
	    );
			$this->load->view('admin/header');
			$this->load->view('branches/branches_form',$data);
			$this->load->view('admin/footer');
        } else {
            $this->session->set_flashdata('message', 'Record Not Found');
            redirect(site_url('branches'));
        }
    }
    
    public function update_action() 
    {
        $this->_rules();

        if ($this->form_validation->run() == FALSE) {
            $this->update($this->input->post('id', TRUE));
        } else {
            $data = array(
		'BranchCode' => $this->input->post('BranchCode',TRUE),
		'BranchName' => $this->input->post('BranchName',TRUE),
		'AddressLine1' => $this->input->post('AddressLine1',TRUE),
		'AddressLine2' => $this->input->post('AddressLine2',TRUE),
		'City' => $this->input->post('City',TRUE),
		'Stamp' => $this->input->post('Stamp',TRUE),
	    );

            $this->Branches_model->update($this->input->post('id', TRUE), $data);
            $this->session->set_flashdata('message', 'Update Record Success');
            redirect(site_url('branches'));
        }
    }
    
    public function delete($id) 
    {
        $row = $this->Branches_model->get_by_id($id);

        if ($row) {
            $this->Branches_model->delete($id);
            $this->session->set_flashdata('message', 'Delete Record Success');
            redirect(site_url('branches'));
        } else {
            $this->session->set_flashdata('message', 'Record Not Found');
            redirect(site_url('branches'));
        }
    }

    public function _rules() 
    {
	$this->form_validation->set_rules('BranchCode', 'branchcode', 'trim|required');
	$this->form_validation->set_rules('BranchName', 'branchname', 'trim|required');

	$this->form_validation->set_rules('City', 'city', 'trim|required');


	$this->form_validation->set_rules('id', 'id', 'trim');
	$this->form_validation->set_error_delimiters('<span class="text-danger">', '</span>');
    }

}

