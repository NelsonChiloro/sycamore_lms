<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Sms_settings extends CI_Controller
{
    function __construct()
    {
        parent::__construct();
        $this->load->model('Sms_settings_model');
        $this->load->library('form_validation');
    }

    public function index()
    {
        $q = urldecode($this->input->get('q', TRUE));
        $start = intval($this->input->get('start'));
        
        if ($q <> '') {
            $config['base_url'] = base_url() . 'sms_settings/index.html?q=' . urlencode($q);
            $config['first_url'] = base_url() . 'sms_settings/index.html?q=' . urlencode($q);
        } else {
            $config['base_url'] = base_url() . 'sms_settings/index.html';
            $config['first_url'] = base_url() . 'sms_settings/index.html';
        }

        $config['per_page'] = 10;
        $config['page_query_string'] = TRUE;
        $config['total_rows'] = $this->Sms_settings_model->total_rows($q);
        $sms_settings = $this->Sms_settings_model->get_limit_data($config['per_page'], $start, $q);

        $this->load->library('pagination');
        $this->pagination->initialize($config);

        $data = array(
            'sms_settings_data' => $sms_settings,
            'q' => $q,
            'pagination' => $this->pagination->create_links(),
            'total_rows' => $config['total_rows'],
            'start' => $start,
        );
        $this->load->view('sms_settings/sms_settings_list', $data);
    }

    public function read($id) 
    {
        $row = $this->Sms_settings_model->get_by_id($id);
        if ($row) {
            $data = array(
		'id' => $row->id,
		'customer_approval' => $row->customer_approval,
		'group_approval' => $row->group_approval,
		'loan_disbursement' => $row->loan_disbursement,
		'before_notice' => $row->before_notice,
		'before_notice_period' => $row->before_notice_period,
		'arrears' => $row->arrears,
		'arrears_age' => $row->arrears_age,
		'customer_app_pass_recovery' => $row->customer_app_pass_recovery,
		'customer_birthday_notify' => $row->customer_birthday_notify,
		'loan_payment_notification' => $row->loan_payment_notification,
		'deposit_withdraw_notification' => $row->deposit_withdraw_notification,
	    );
            $this->load->view('sms_settings/sms_settings_read', $data);
        } else {
            $this->session->set_flashdata('message', 'Record Not Found');
            redirect(site_url('sms_settings'));
        }
    }

    public function create() 
    {
        $data = array(
            'button' => 'Create',
            'action' => site_url('sms_settings/create_action'),
	    'id' => set_value('id'),
	    'customer_approval' => set_value('customer_approval'),
	    'group_approval' => set_value('group_approval'),
	    'loan_disbursement' => set_value('loan_disbursement'),
	    'before_notice' => set_value('before_notice'),
	    'before_notice_period' => set_value('before_notice_period'),
	    'arrears' => set_value('arrears'),
	    'arrears_age' => set_value('arrears_age'),
	    'customer_app_pass_recovery' => set_value('customer_app_pass_recovery'),
	    'customer_birthday_notify' => set_value('customer_birthday_notify'),
	    'loan_payment_notification' => set_value('loan_payment_notification'),
	    'deposit_withdraw_notification' => set_value('deposit_withdraw_notification'),
	);
        $this->load->view('sms_settings/sms_settings_form', $data);
    }
    
    public function create_action() 
    {
        $this->_rules();

        if ($this->form_validation->run() == FALSE) {
            $this->create();
        } else {
            $data = array(
		'customer_approval' => $this->input->post('customer_approval',TRUE),
		'group_approval' => $this->input->post('group_approval',TRUE),
		'loan_disbursement' => $this->input->post('loan_disbursement',TRUE),
		'before_notice' => $this->input->post('before_notice',TRUE),
		'before_notice_period' => $this->input->post('before_notice_period',TRUE),
		'arrears' => $this->input->post('arrears',TRUE),
		'arrears_age' => $this->input->post('arrears_age',TRUE),
		'customer_app_pass_recovery' => $this->input->post('customer_app_pass_recovery',TRUE),
		'customer_birthday_notify' => $this->input->post('customer_birthday_notify',TRUE),
		'loan_payment_notification' => $this->input->post('loan_payment_notification',TRUE),
		'deposit_withdraw_notification' => $this->input->post('deposit_withdraw_notification',TRUE),
	    );

            $this->Sms_settings_model->insert($data);
            $this->session->set_flashdata('message', 'Create Record Success');
            redirect(site_url('sms_settings'));
        }
    }
    
    public function update($id) 
    {
        $row = $this->Sms_settings_model->get_by_id($id);

        if ($row) {
            $data = array(
                'button' => 'Update',
                'action' => site_url('sms_settings/update_action'),
		'id' => set_value('id', $row->id),
		'customer_approval' => set_value('customer_approval', $row->customer_approval),
		'group_approval' => set_value('group_approval', $row->group_approval),
		'loan_disbursement' => set_value('loan_disbursement', $row->loan_disbursement),
		'before_notice' => set_value('before_notice', $row->before_notice),
		'before_notice_period' => set_value('before_notice_period', $row->before_notice_period),
		'arrears' => set_value('arrears', $row->arrears),
		'arrears_age' => set_value('arrears_age', $row->arrears_age),
		'customer_app_pass_recovery' => set_value('customer_app_pass_recovery', $row->customer_app_pass_recovery),
		'customer_birthday_notify' => set_value('customer_birthday_notify', $row->customer_birthday_notify),
		'loan_payment_notification' => set_value('loan_payment_notification', $row->loan_payment_notification),
		'deposit_withdraw_notification' => set_value('deposit_withdraw_notification', $row->deposit_withdraw_notification),
	    );
            $this->load->view('admin/header');
            $this->load->view('sms_settings/sms_settings_form', $data);
            $this->load->view('admin/footer');
        } else {
            $this->session->set_flashdata('message', 'Record Not Found');
            redirect(site_url('sms_settings'));
        }
    }
    
    public function update_action() 
    {
        $this->_rules();

        if ($this->form_validation->run() == FALSE) {
            $this->update($this->input->post('id', TRUE));
        } else {
            $data = array(
		'customer_approval' => $this->input->post('customer_approval',TRUE),
		'group_approval' => $this->input->post('group_approval',TRUE),
		'loan_disbursement' => $this->input->post('loan_disbursement',TRUE),
		'before_notice' => $this->input->post('before_notice',TRUE),
		'before_notice_period' => $this->input->post('before_notice_period',TRUE),
		'arrears' => $this->input->post('arrears',TRUE),
		'arrears_age' => $this->input->post('arrears_age',TRUE),
		'customer_app_pass_recovery' => $this->input->post('customer_app_pass_recovery',TRUE),
		'customer_birthday_notify' => $this->input->post('customer_birthday_notify',TRUE),
		'loan_payment_notification' => $this->input->post('loan_payment_notification',TRUE),
		'deposit_withdraw_notification' => $this->input->post('deposit_withdraw_notification',TRUE),
	    );

            $this->Sms_settings_model->update($this->input->post('id', TRUE), $data);
            $this->session->set_flashdata('message', 'Update Record Success');
            redirect($_SERVER['HTTP_REFERER']);
        }
    }
    
    public function delete($id) 
    {
        $row = $this->Sms_settings_model->get_by_id($id);

        if ($row) {
            $this->Sms_settings_model->delete($id);
            $this->session->set_flashdata('message', 'Delete Record Success');
            redirect(site_url('sms_settings'));
        } else {
            $this->session->set_flashdata('message', 'Record Not Found');
            redirect(site_url('sms_settings'));
        }
    }

    public function _rules() 
    {
	$this->form_validation->set_rules('customer_approval', 'customer approval', 'trim|required');
	$this->form_validation->set_rules('group_approval', 'group approval', 'trim|required');
	$this->form_validation->set_rules('loan_disbursement', 'loan disbursement', 'trim|required');
	$this->form_validation->set_rules('before_notice', 'before notice', 'trim|required');
	$this->form_validation->set_rules('before_notice_period', 'before notice period', 'trim|required');
	$this->form_validation->set_rules('arrears', 'arrears', 'trim|required');
	$this->form_validation->set_rules('arrears_age', 'arrears age', 'trim|required');
	$this->form_validation->set_rules('customer_app_pass_recovery', 'customer app pass recovery', 'trim|required');
	$this->form_validation->set_rules('customer_birthday_notify', 'customer birthday notify', 'trim|required');
	$this->form_validation->set_rules('loan_payment_notification', 'loan payment notification', 'trim|required');
	$this->form_validation->set_rules('deposit_withdraw_notification', 'deposit withdraw notification', 'trim|required');

	$this->form_validation->set_rules('id', 'id', 'trim');
	$this->form_validation->set_error_delimiters('<span class="text-danger">', '</span>');
    }

}

/* End of file Sms_settings.php */
/* Location: ./application/controllers/Sms_settings.php */
/* Please DO NOT modify this information : */
/* Generated by Harviacode Codeigniter CRUD Generator 2022-05-29 03:47:48 */
/* http://harviacode.com */
