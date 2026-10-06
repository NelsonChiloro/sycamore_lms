<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Borrowed extends CI_Controller
{
    function __construct()
    {
        parent::__construct();
        $this->load->model('Borrowed_model');
        $this->load->library('form_validation');
    }

    public function index()
    {


        $data = array(
            'borrowed_data' => $this->Borrowed_model->get_all(),

        );
		$menu_toggle['toggles'] = 45;
		$this->load->view('admin/header', $menu_toggle);
        $this->load->view('borrowed/borrowed_list', $data);
		$this->load->view('admin/footer');
    }

    public function read($id) 
    {
        $row = $this->Borrowed_model->get_by_id($id);
        if ($row) {
            $data = array(
		'borrowed_id' => $row->borrowed_id,
		'amount' => $row->amount,
		'total_interest' => $row->total_interest,
		'borrowed_from' => $row->borrowed_from,
		'date_borrowed' => $row->date_borrowed,
		'stamp' => $row->stamp,
	    );
            $this->load->view('borrowed/borrowed_read', $data);
        } else {
            $this->session->set_flashdata('message', 'Record Not Found');
            redirect(site_url('borrowed'));
        }
    }

    public function create() 
    {
        $data = array(
            'button' => 'Create',
            'action' => site_url('borrowed/create_action'),
	    'borrowed_id' => set_value('borrowed_id'),
	    'amount' => set_value('amount'),
	    'total_interest' => set_value('total_interest'),
	    'borrowed_from' => set_value('borrowed_from'),
	    'date_borrowed' => set_value('date_borrowed'),
	    'stamp' => set_value('stamp'),
	);
		$menu_toggle['toggles'] = 45;
		$this->load->view('admin/header', $menu_toggle);
		$this->load->view('borrowed/borrowed_form', $data);
		$this->load->view('admin/footer');
    }
    
    public function create_action() 
    {
        $this->_rules();

        if ($this->form_validation->run() == FALSE) {
            $this->create();
        } else {
            $data = array(
		'amount' => $this->input->post('amount',TRUE),
		'total_interest' => $this->input->post('total_interest',TRUE),
		'borrowed_from' => $this->input->post('borrowed_from',TRUE),
		'date_borrowed' => $this->input->post('date_borrowed',TRUE),

	    );

			$logger = array(
				'user_id' => $this->session->userdata('user_id'),
				'activity' => 'Added cost of Funding amount:'.' '.$this->input->post('amount').' '.
					'with Interest of:'.' '.$this->input->post('total_interest').' '.'Borrowed from:'.' '.
					$this->input->post('borrowed_from')

			);
			log_activity($logger);

            $this->Borrowed_model->insert($data);
            $this->session->set_flashdata('message', 'Create Record Success');
            redirect(site_url('borrowed'));
        }
    }
    
    public function update($id) 
    {
        $row = $this->Borrowed_model->get_by_id($id);

        if ($row) {
            $data = array(
                'button' => 'Update',
                'action' => site_url('borrowed/update_action'),
		'borrowed_id' => set_value('borrowed_id', $row->borrowed_id),
		'amount' => set_value('amount', $row->amount),
		'total_interest' => set_value('total_interest', $row->total_interest),
		'borrowed_from' => set_value('borrowed_from', $row->borrowed_from),
		'date_borrowed' => set_value('date_borrowed', $row->date_borrowed),

	    );
			$this->load->view('admin/header');
			$this->load->view('borrowed/borrowed_form', $data);
			$this->load->view('admin/footer');
        } else {
            $this->session->set_flashdata('message', 'Record Not Found');
            redirect(site_url('borrowed'));
        }
    }
    
    public function update_action() 
    {
        $this->_rules();

        if ($this->form_validation->run() == FALSE) {
            $this->update($this->input->post('borrowed_id', TRUE));
        } else {
            $data = array(
		'amount' => $this->input->post('amount',TRUE),
		'total_interest' => $this->input->post('total_interest',TRUE),
		'borrowed_from' => $this->input->post('borrowed_from',TRUE),
		'date_borrowed' => $this->input->post('date_borrowed',TRUE),
		'stamp' => $this->input->post('stamp',TRUE),
	    );

			$logger = array(
				'user_id' => $this->session->userdata('user_id'),
				'activity' => 'Update cost of Funding Amount:'.' '.$this->input->post('amount').
					'with Interest of:'.' '.$this->input->post('total_interest').' '.'Borrowed from:'.' '.
					$this->input->post('borrowed_from')

			);
			log_activity($logger);
            $this->Borrowed_model->update($this->input->post('borrowed_id', TRUE), $data);
            $this->session->set_flashdata('message', 'Update Record Success');
            redirect(site_url('borrowed'));
        }
    }
    
    public function delete($id) 
    {
        $row = $this->Borrowed_model->get_by_id($id);

        if ($row) {
			$logger = array(
				'user_id' => $this->session->userdata('user_id'),
				'activity' => 'Delete Cost of Funding'

			);
			log_activity($logger);
            $this->Borrowed_model->delete($id);
            $this->session->set_flashdata('message', 'Delete Record Success');
            redirect(site_url('borrowed'));
        } else {
            $this->session->set_flashdata('message', 'Record Not Found');
            redirect(site_url('borrowed'));
        }
    }

    public function _rules() 
    {
	$this->form_validation->set_rules('amount', 'amount', 'trim|required|numeric');
	$this->form_validation->set_rules('total_interest', 'total interest', 'trim|required|numeric');
	$this->form_validation->set_rules('borrowed_from', 'borrowed from', 'trim|required');
	$this->form_validation->set_rules('date_borrowed', 'date borrowed', 'trim|required');


	$this->form_validation->set_rules('borrowed_id', 'borrowed_id', 'trim');
	$this->form_validation->set_error_delimiters('<span class="text-danger">', '</span>');
    }

}


