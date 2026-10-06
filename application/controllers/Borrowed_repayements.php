<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Borrowed_repayements extends CI_Controller
{
    function __construct()
    {
        parent::__construct();
        $this->load->model('Borrowed_repayements_model');
        $this->load->library('form_validation');
    }

    public function index($id)
    {


        $data = array(
            'borrowed_repayements_data' => $this->Borrowed_repayements_model->get_related($id),

        );
		$menu_toggle['toggles'] = 45;
		$this->load->view('admin/header', $menu_toggle);

        $this->load->view('borrowed_repayements/borrowed_repayements_list', $data);

		$this->load->view('admin/footer');
    }

    public function read($id) 
    {
        $row = $this->Borrowed_repayements_model->get_by_id($id);
        if ($row) {
            $data = array(
		'b_id' => $row->b_id,
		'borrowed_id' => $row->borrowed_id,
		'interest_paid' => $row->interest_paid,
		'principal_paid' => $row->principal_paid,
		'paid_by' => $row->paid_by,
		'date_of repaymet' => $row->date_of_repaymet,
		'stamp' => $row->stamp,
	    );
            $this->load->view('borrowed_repayements/borrowed_repayements_read', $data);
        } else {
            $this->session->set_flashdata('message', 'Record Not Found');
            redirect(site_url('borrowed_repayements'));
        }
    }

    public function create() 
    {
        $data = array(
            'button' => 'Create',
            'action' => site_url('borrowed_repayements/create_action'),
	    'b_id' => set_value('b_id'),
	    'borrowed_id' => set_value('borrowed_id'),
	    'interest_paid' => set_value('interest_paid'),
	    'principal_paid' => set_value('principal_paid'),
	    'paid_by' => set_value('paid_by'),
	    'date_of repaymet' => set_value('date_of repaymet'),
	    'stamp' => set_value('stamp'),
	);
        $this->load->view('borrowed_repayements/borrowed_repayements_form', $data);
    }
    
    public function create_action() 
    {
        $this->_rules();

        if ($this->form_validation->run() == FALSE) {
            echo "there was an error go back and try again";
        } else {
            $data = array(
		'borrowed_id' => $this->input->post('borrowed_id',TRUE),
		'interest_paid' => $this->input->post('interest_paid',TRUE),
		'principal_paid' => $this->input->post('principal_paid',TRUE),
		'paid_by' => $this->session->userdata('user_id'),
		'date_of_repaymet' => $this->input->post('date_of_repaymet',TRUE),

	    );

			$logger = array(
				'user_id' => $this->session->userdata('user_id'),
				'activity' => 'Added cost of Funding Payment Plan Interest Paid:'.' '.$this->input->post('interest_paid').
					' '.'Principal Paid:'.' '.$this->input->post('principal_paid')

			);
			log_activity($logger);
            $this->Borrowed_repayements_model->insert($data);
            $this->toaster->success('Create Record Succeeded');
            redirect($_SERVER['HTTP_REFERER']);
        }
    }
    
    public function update($id) 
    {
        $row = $this->Borrowed_repayements_model->get_by_id($id);

        if ($row) {
            $data = array(
                'button' => 'Update',
                'action' => site_url('borrowed_repayements/update_action'),
		'b_id' => set_value('b_id', $row->b_id),
		'borrowed_id' => set_value('borrowed_id', $row->borrowed_id),
		'interest_paid' => set_value('interest_paid', $row->interest_paid),
		'principal_paid' => set_value('principal_paid', $row->principal_paid),
		'paid_by' => set_value('paid_by', $row->paid_by),
		'date_of repaymet' => set_value('date_of repaymet', $row->date_of_repaymet),
		'stamp' => set_value('stamp', $row->stamp),
	    );
            $this->load->view('borrowed_repayements/borrowed_repayements_form', $data);
        } else {
            $this->session->set_flashdata('message', 'Record Not Found');
            redirect(site_url('borrowed_repayements'));
        }
    }
    
    public function update_action() 
    {
        $this->_rules();

        if ($this->form_validation->run() == FALSE) {
            $this->update($this->input->post('b_id', TRUE));
        } else {
            $data = array(
		'borrowed_id' => $this->input->post('borrowed_id',TRUE),
		'interest_paid' => $this->input->post('interest_paid',TRUE),
		'principal_paid' => $this->input->post('principal_paid',TRUE),
		'paid_by' => $this->input->post('paid_by',TRUE),
		'date_of repaymet' => $this->input->post('date_of repaymet',TRUE),
		'stamp' => $this->input->post('stamp',TRUE),
	    );
			$logger = array(
				'user_id' => $this->session->userdata('user_id'),
				'activity' => 'Updated cost of Funding Payment Plan Interest Paid:'.' '.$this->input->post('interest_paid').
					' '.'Principal Paid:'.' '.$this->input->post('principal_paid')

			);
			log_activity($logger);

            $this->Borrowed_repayements_model->update($this->input->post('b_id', TRUE), $data);
            $this->session->set_flashdata('message', 'Update Record Success');
            redirect(site_url('borrowed_repayements'));
        }
    }
    
    public function delete($id) 
    {
        $row = $this->Borrowed_repayements_model->get_by_id($id);

        if ($row) {

			$logger = array(
				'user_id' => $this->session->userdata('user_id'),
				'activity' => 'Deleted Borrowed Payment Plan'

			);
			log_activity($logger);
            $this->Borrowed_repayements_model->delete($id);
            $this->session->set_flashdata('message', 'Delete Record Success');
            redirect(site_url('borrowed_repayements'));
        } else {
            $this->session->set_flashdata('message', 'Record Not Found');
            redirect(site_url('borrowed_repayements'));
        }
    }

    public function _rules() 
    {
	$this->form_validation->set_rules('borrowed_id', 'borrowed id', 'trim|required');
	$this->form_validation->set_rules('interest_paid', 'interest paid', 'trim|required|numeric');
	$this->form_validation->set_rules('principal_paid', 'principal paid', 'trim|required|numeric');

	$this->form_validation->set_rules('date_of_repaymet', 'date of repaymet', 'trim|required');


	$this->form_validation->set_rules('b_id', 'b_id', 'trim');
	$this->form_validation->set_error_delimiters('<span class="text-danger">', '</span>');
    }

}

