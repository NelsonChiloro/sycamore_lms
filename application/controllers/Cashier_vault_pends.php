<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Cashier_vault_pends extends CI_Controller
{
    function __construct()
    {
        parent::__construct();
        $this->load->model('Cashier_vault_pends_model');
        $this->load->library('form_validation');
    }

    public function index()
    {
        $q = urldecode($this->input->get('q', TRUE));
        $start = intval($this->input->get('start'));
        
        if ($q <> '') {
            $config['base_url'] = base_url() . 'cashier_vault_pends/index.html?q=' . urlencode($q);
            $config['first_url'] = base_url() . 'cashier_vault_pends/index.html?q=' . urlencode($q);
        } else {
            $config['base_url'] = base_url() . 'cashier_vault_pends/index.html';
            $config['first_url'] = base_url() . 'cashier_vault_pends/index.html';
        }

        $config['per_page'] = 10;
        $config['page_query_string'] = TRUE;
        $config['total_rows'] = $this->Cashier_vault_pends_model->total_rows($q);
        $cashier_vault_pends = $this->Cashier_vault_pends_model->get_limit_data($config['per_page'], $start, $q);

        $this->load->library('pagination');
        $this->pagination->initialize($config);

        $data = array(
            'cashier_vault_pends_data' => $cashier_vault_pends,
            'q' => $q,
            'pagination' => $this->pagination->create_links(),
            'total_rows' => $config['total_rows'],
            'start' => $start,
        );
        $this->load->view('cashier_vault_pends/cashier_vault_pends_list', $data);
    }

    public function read($id) 
    {
        $row = $this->Cashier_vault_pends_model->get_by_id($id);
        if ($row) {
            $data = array(
		'cvpid' => $row->cvpid,
		'vault_account' => $row->vault_account,
		'teller_account' => $row->teller_account,
		'amount' => $row->amount,
		'creator' => $row->creator,
		'system_date' => $row->system_date,
		'stamp' => $row->stamp,
	    );
            $this->load->view('cashier_vault_pends/cashier_vault_pends_read', $data);
        } else {
            $this->session->set_flashdata('message', 'Record Not Found');
            redirect(site_url('cashier_vault_pends'));
        }
    }

    public function create() 
    {
        $data = array(
            'button' => 'Create',
            'action' => site_url('cashier_vault_pends/create_action'),
	    'cvpid' => set_value('cvpid'),
	    'vault_account' => set_value('vault_account'),
	    'teller_account' => set_value('teller_account'),
	    'amount' => set_value('amount'),
	    'creator' => set_value('creator'),
	    'system_date' => set_value('system_date'),
	    'stamp' => set_value('stamp'),
	);
        $this->load->view('cashier_vault_pends/cashier_vault_pends_form', $data);
    }
    
    public function create_action() 
    {
        $this->_rules();

        if ($this->form_validation->run() == FALSE) {
            $this->create();
        } else {
            $data = array(
		'vault_account' => $this->input->post('vault_account',TRUE),
		'teller_account' => $this->input->post('teller_account',TRUE),
		'amount' => $this->input->post('amount',TRUE),
		'creator' => $this->input->post('creator',TRUE),
		'system_date' => $this->input->post('system_date',TRUE),
		'stamp' => $this->input->post('stamp',TRUE),
	    );

            $this->Cashier_vault_pends_model->insert($data);
            $this->session->set_flashdata('message', 'Create Record Success');
            redirect(site_url('cashier_vault_pends'));
        }
    }
    
    public function update($id) 
    {
        $row = $this->Cashier_vault_pends_model->get_by_id($id);

        if ($row) {
            $data = array(
                'button' => 'Update',
                'action' => site_url('cashier_vault_pends/update_action'),
		'cvpid' => set_value('cvpid', $row->cvpid),
		'vault_account' => set_value('vault_account', $row->vault_account),
		'teller_account' => set_value('teller_account', $row->teller_account),
		'amount' => set_value('amount', $row->amount),
		'creator' => set_value('creator', $row->creator),
		'system_date' => set_value('system_date', $row->system_date),
		'stamp' => set_value('stamp', $row->stamp),
	    );
            $this->load->view('cashier_vault_pends/cashier_vault_pends_form', $data);
        } else {
            $this->session->set_flashdata('message', 'Record Not Found');
            redirect(site_url('cashier_vault_pends'));
        }
    }
    
    public function update_action() 
    {
        $this->_rules();

        if ($this->form_validation->run() == FALSE) {
            $this->update($this->input->post('cvpid', TRUE));
        } else {
            $data = array(
		'vault_account' => $this->input->post('vault_account',TRUE),
		'teller_account' => $this->input->post('teller_account',TRUE),
		'amount' => $this->input->post('amount',TRUE),
		'creator' => $this->input->post('creator',TRUE),
		'system_date' => $this->input->post('system_date',TRUE),
		'stamp' => $this->input->post('stamp',TRUE),
	    );

            $this->Cashier_vault_pends_model->update($this->input->post('cvpid', TRUE), $data);
            $this->session->set_flashdata('message', 'Update Record Success');
            redirect(site_url('cashier_vault_pends'));
        }
    }
    public function acceptance(){
    	$result['data']= $this->Cashier_vault_pends_model->get_all();
        $this->load->view('admin/header');
        $this->load->view('cashier_vault_pends/acceptance',$result);
        $this->load->view('admin/footer');

	}
    public function delete($id) 
    {
        $row = $this->Cashier_vault_pends_model->get_by_id($id);

        if ($row) {
            $this->Cashier_vault_pends_model->delete($id);
			$this->toaster->success('Success, delete was successful');
            redirect($_SERVER['HTTP_REFERER']);
        } else {
            $this->toaster->error('Success, delete was not successful');
			redirect($_SERVER['HTTP_REFERER']);
        }
    }

    public function _rules() 
    {
	$this->form_validation->set_rules('vault_account', 'vault account', 'trim|required');
	$this->form_validation->set_rules('teller_account', 'teller account', 'trim|required');
	$this->form_validation->set_rules('amount', 'amount', 'trim|required|numeric');
	$this->form_validation->set_rules('creator', 'creator', 'trim|required');
	$this->form_validation->set_rules('system_date', 'system date', 'trim|required');
	$this->form_validation->set_rules('stamp', 'stamp', 'trim|required');

	$this->form_validation->set_rules('cvpid', 'cvpid', 'trim');
	$this->form_validation->set_error_delimiters('<span class="text-danger">', '</span>');
    }

}
