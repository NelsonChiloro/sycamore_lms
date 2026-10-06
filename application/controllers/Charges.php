<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Charges extends CI_Controller
{
    function __construct()
    {
        parent::__construct();
        $this->load->model('Charges_model');
        $this->load->library('form_validation');
    }

    public function index()
    {

        $charges = $this->Charges_model->get_all();



        $data = array(
            'charges_data' => $charges,

        );
		$this->load->view('admin/header');
        $this->load->view('charges/charges_list', $data);
		$this->load->view('admin/footer');
    }

    public function read($id) 
    {
        $row = $this->Charges_model->get_by_id($id);
        if ($row) {
            $data = array(
		'charge_id' => $row->charge_id,
		'name' => $row->name,
		'charge_type' => $row->charge_type,
		'fixed_amount' => $row->fixed_amount,
		'variable_value' => $row->variable_value,
	    );
            $this->load->view('charges/charges_read', $data);
        } else {
            $this->session->set_flashdata('message', 'Record Not Found');
            redirect(site_url('charges'));
        }
    }

    public function create() 
    {
        $data = array(
            'button' => 'Create',
            'action' => site_url('charges/create_action'),
	    'charge_id' => set_value('charge_id'),
	    'name' => set_value('name'),
	    'charge_type' => set_value('charge_type'),
	    'fixed_amount' => set_value('fixed_amount'),
	    'variable_value' => set_value('variable_value'),
	);
        $this->load->view('charges/charges_form', $data);
    }
    
    public function create_action() 
    {
        $this->_rules();

        if ($this->form_validation->run() == FALSE) {
            $this->create();
        } else {
            $data = array(
		'name' => $this->input->post('name',TRUE),
		'charge_type' => $this->input->post('charge_type',TRUE),
		'fixed_amount' => $this->input->post('fixed_amount',TRUE),
		'variable_value' => $this->input->post('variable_value',TRUE),
	    );
			$logger = array(

				'user_id' => $this->session->userdata('user_id'),
				'activity' => 'Create System charges '

			);
			log_activity($logger);

            $this->Charges_model->insert($data);
            $this->session->set_flashdata('message', 'Create Record Success');
            redirect(site_url('charges'));
        }
    }
    
    public function update($id) 
    {
        $row = $this->Charges_model->get_by_id($id);

        if ($row) {
            $data = array(
                'button' => 'Update',
                'action' => site_url('charges/update_action'),
		'charge_id' => set_value('charge_id', $row->charge_id),
		'threshold' => set_value('threshold', $row->threshold),
		'charge_type' => set_value('charge_type', $row->charge_type),
		'fixed_amount' => set_value('fixed_amount', $row->fixed_amount),
		'variable_value' => set_value('variable_value', $row->variable_value),
                'above_charge_type' => set_value('above_charge_type', $row->above_charge_type),
		'above_fixed_amount' => set_value('above_fixed_amount', $row->above_fixed_amount),
		'above_variable_amount' => set_value('above_variable_amount', $row->above_variable_amount),
	    );
			$this->load->view('admin/header');
            $this->load->view('charges/charges_form', $data);
			$this->load->view('admin/footer');
        } else {
            $this->session->set_flashdata('message', 'Record Not Found');
          echo "Sorry something went wrong go back";
        }
    }
    
    public function update_action() 
    {
        $this->_rules();

        if ($this->form_validation->run() == FALSE) {
            $this->update($this->input->post('charge_id', TRUE));
        } else {
            $data = array(
		'threshold' => $this->input->post('threshold',TRUE),
		'charge_type' => $this->input->post('charge_type',TRUE),
		'fixed_amount' => $this->input->post('fixed_amount',TRUE),
		'variable_value' => $this->input->post('variable_value',TRUE),
                'above_charge_type' => $this->input->post('above_charge_type',TRUE),
		'above_fixed_amount' => $this->input->post('above_fixed_amount',TRUE),
		'above_variable_amount' => $this->input->post('above_variable_amount',TRUE),
	    );
			$logger = array(

				'user_id' => $this->session->userdata('user_id'),
				'activity' => 'Update system charges '

			);
			log_activity($logger);

			$this->Charges_model->update($this->input->post('charge_id', TRUE), $data);
            $this->session->set_flashdata('success', 'Update Records were Successful');
            redirect($_SERVER['HTTP_REFERER']);
        }
    }
    
    public function delete($id) 
    {
        $row = $this->Charges_model->get_by_id($id);

        if ($row) {
            $this->Charges_model->delete($id);
            $this->session->set_flashdata('message', 'Delete Record Success');
            redirect(site_url('charges'));
        } else {
            $this->session->set_flashdata('message', 'Record Not Found');
            redirect(site_url('charges'));
        }
    }

    public function _rules() 
    {
	
	$this->form_validation->set_rules('charge_type', 'charge type', 'trim|required');
	$this->form_validation->set_rules('fixed_amount', 'fixed amount', 'trim|required|numeric');
	$this->form_validation->set_rules('variable_value', 'variable value', 'trim|required');

	$this->form_validation->set_rules('charge_id', 'charge_id', 'trim');
	$this->form_validation->set_error_delimiters('<span class="text-danger">', '</span>');
    }

}


