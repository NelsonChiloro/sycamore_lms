<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Settings extends CI_Controller
{
    function __construct()
    {
        parent::__construct();
        $this->load->model('Settings_model');
        $this->load->library('form_validation');
    }


    
    public function update($id) 
    {
        $row = $this->Settings_model->get_by_id($id);

        if ($row) {
            $data = array(
                'button' => 'Update',
                'action' => site_url('settings/update_action'),
		'settings_id' => set_value('settings_id', $row->settings_id),
		'logo' => set_value('logo', $row->logo),
		'address' => set_value('address', $row->address),
		'phone_number' => set_value('phone_number', $row->phone_number),
		'company_name' => set_value('company_name', $row->company_name),
		'company_email' => set_value('company_email', $row->company_email),
		'currency' => set_value('currency', $row->currency),
		'time_zone' => set_value('time_zone', $row->time_zone),
		'tax' => set_value('tax', $row->tax),
		'defaulter_durations' => set_value('defaulter_durations', $row->defaulter_durations),
	    );
			$this->load->view('admin/header');
            $this->load->view('settings/settings_form', $data);
			$this->load->view('admin/footer');
        } else {
			$this->toaster->error('Opps, settings were not retrieved updated');
            redirect($_SERVER['HTTP_REFERER']);
        }
    }
    
    public function update_action() 
    {
        $this->_rules();

        if ($this->form_validation->run() == FALSE) {
            $this->update($this->input->post('settings_id', TRUE));
        } else {
            $data = array(
		'logo' => $this->input->post('logo',TRUE),
		'address' => $this->input->post('address',TRUE),
		'phone_number' => $this->input->post('phone_number',TRUE),
		'company_name' => $this->input->post('company_name',TRUE),
		'company_email' => $this->input->post('company_email',TRUE),
		'currency' => $this->input->post('currency',TRUE),
		'time_zone' => $this->input->post('time_zone',TRUE),
		'tax' => $this->input->post('tax',TRUE),
		'defaulter_durations' => $this->input->post('defaulter_durations',TRUE),
	    );
			$logger = array(

				'user_id' => $this->session->userdata('user_id'),
				'activity' => 'Update system settings eg company logo, address, phone number, email etc'

			);
			log_activity($logger);

            $this->Settings_model->update($this->input->post('settings_id', TRUE), $data);
			$this->toaster->success('Success, settings were updated');
			redirect($_SERVER['HTTP_REFERER']);
        }
    }
    


    public function _rules() 
    {
	$this->form_validation->set_rules('logo', 'logo', 'trim|required');
	$this->form_validation->set_rules('address', 'address', 'trim|required');
	$this->form_validation->set_rules('phone_number', 'phone number', 'trim|required');
	$this->form_validation->set_rules('company_name', 'company name', 'trim|required');
	$this->form_validation->set_rules('company_email', 'company email', 'trim|required');
	$this->form_validation->set_rules('currency', 'currency', 'trim|required');
	$this->form_validation->set_rules('time_zone', 'time zone', 'trim|required');

	$this->form_validation->set_rules('settings_id', 'settings_id', 'trim');
	$this->form_validation->set_error_delimiters('<span class="text-danger">', '</span>');
    }

}

