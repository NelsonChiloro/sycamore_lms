<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Authorized_signitories extends CI_Controller
{
    function __construct()
    {
        parent::__construct();
        $this->load->model('Authorized_signitories_model');
        $this->load->library('form_validation');
    }

    public function index()
    {
        $q = urldecode($this->input->get('q', TRUE));
        $start = intval($this->input->get('start'));
        
        if ($q <> '') {
            $config['base_url'] = base_url() . 'authorized_signitories/index.html?q=' . urlencode($q);
            $config['first_url'] = base_url() . 'authorized_signitories/index.html?q=' . urlencode($q);
        } else {
            $config['base_url'] = base_url() . 'authorized_signitories/index.html';
            $config['first_url'] = base_url() . 'authorized_signitories/index.html';
        }

        $config['per_page'] = 10;
        $config['page_query_string'] = TRUE;
        $config['total_rows'] = $this->Authorized_signitories_model->total_rows($q);
        $authorized_signitories = $this->Authorized_signitories_model->get_limit_data($config['per_page'], $start, $q);

        $this->load->library('pagination');
        $this->pagination->initialize($config);

        $data = array(
            'authorized_signitories_data' => $authorized_signitories,
            'q' => $q,
            'pagination' => $this->pagination->create_links(),
            'total_rows' => $config['total_rows'],
            'start' => $start,
        );
        $this->load->view('authorized_signitories/authorized_signitories_list', $data);
    }

    public function read($id) 
    {
        $row = $this->Authorized_signitories_model->get_by_id($id);
        if ($row) {
            $data = array(
		'id' => $row->id,
		'ClientId' => $row->ClientId,
		'FullLegalName' => $row->FullLegalName,
		'IDType' => $row->IDType,
		'IDNumber' => $row->IDNumber,
		'DocImageURL' => $row->DocImageURL,
		'SignatureImageURL' => $row->SignatureImageURL,
		'Stamp' => $row->Stamp,
	    );
            $this->load->view('authorized_signitories/authorized_signitories_read', $data);
        } else {
            $this->session->set_flashdata('message', 'Record Not Found');
            redirect(site_url('authorized_signitories'));
        }
    }

    public function create() 
    {
        $data = array(
            'button' => 'Create',
            'action' => site_url('authorized_signitories/create_action'),
	    'id' => set_value('id'),
	    'ClientId' => set_value('ClientId'),
	    'FullLegalName' => set_value('FullLegalName'),
	    'IDType' => set_value('IDType'),
	    'IDNumber' => set_value('IDNumber'),
	    'DocImageURL' => set_value('DocImageURL'),
	    'SignatureImageURL' => set_value('SignatureImageURL'),
	    'Stamp' => set_value('Stamp'),
	);
        $this->load->view('authorized_signitories/authorized_signitories_form', $data);
    }
    
    public function create_action() 
    {

            $data = array(
		'ClientId' => $this->input->post('ClientId',TRUE),
		'FullLegalName' => $this->input->post('FullLegalName',TRUE),
		'IDType' => $this->input->post('IDType',TRUE),
		'IDNumber' => $this->input->post('IDNumber',TRUE),
		'DocImageURL' => $this->input->post('DocImageURL',TRUE),
		'SignatureImageURL' => $this->input->post('SignatureImageURL',TRUE),

	    );

		$logger2 = array(
			'identity'=>$this->input->post('id', TRUE),
			'auth_type' => 'signatory_creation',
			'old_data' => json_encode($data),
			'new_data' => json_encode($data),
			'system_date' => $this->session->userdata('system_date'),
			'initiator' => $this->session->userdata('user_id')

		);
//            $this->Authorized_signitories_model->update($this->input->post('id', TRUE), $data);
		auth_logger($logger2);
		$this->toaster->success('Success, Corporate customer signatory was created  pending approval');
            redirect($_SERVER['HTTP_REFERER']);

    }
    
    public function update($id) 
    {
        $row = $this->Authorized_signitories_model->get_by_id($id);

        if ($row) {
            $data = array(
                'button' => 'Update',
                'action' => site_url('authorized_signitories/update_action'),
		'id' => set_value('id', $row->id),
		'ClientId' => set_value('ClientId', $row->ClientId),
		'FullLegalName' => set_value('FullLegalName', $row->FullLegalName),
		'IDType' => set_value('IDType', $row->IDType),
		'IDNumber' => set_value('IDNumber', $row->IDNumber),
		'DocImageURL' => set_value('DocImageURL', $row->DocImageURL),
		'SignatureImageURL' => set_value('SignatureImageURL', $row->SignatureImageURL),
		'Stamp' => set_value('Stamp', $row->Stamp),
	    );
			$this->template->set('title', 'Core Banking |Buy foreign currency');
			$this->template->load('template', 'contents' ,'authorized_signitories/authorized_signitories_form',$data);
        } else {
            $this->session->set_flashdata('message', 'Record Not Found');
            redirect(site_url('authorized_signitories'));
        }
    }
    
    public function update_action() 
    {

            $data = array(
		'ClientId' => $this->input->post('ClientId',TRUE),
		'FullLegalName' => $this->input->post('FullLegalName',TRUE),
		'IDType' => $this->input->post('IDType',TRUE),
		'IDNumber' => $this->input->post('IDNumber',TRUE),
		'DocImageURL' => $this->input->post('DocImageURL',TRUE),
		'SignatureImageURL' => $this->input->post('SignatureImageURL',TRUE),

	    );
			$row = $this->Authorized_signitories_model->get_by_id($this->input->post('id', TRUE));

			$data2 = array(

				'ClientId' => $row->ClientId,
				'FullLegalName' => $row->FullLegalName,
				'IDType' => $row->IDType,
				'IDNumber' => $row->IDNumber,
				'DocImageURL' => $row->DocImageURL,
				'SignatureImageURL' => $row->SignatureImageURL,

			);
			$logger2 = array(
				'identity'=>$this->input->post('id', TRUE),
				'auth_type' => 'signatory_update',
				'old_data' => json_encode($data2),
				'new_data' => json_encode($data),
				'system_date' => $this->session->userdata('system_date'),
				'initiator' => $this->session->userdata('user_id')

			);

			auth_logger($logger2);
			$this->toaster->success('Success, Corporate customer signatory was edited  pending approval');
            redirect($_SERVER['HTTP_REFERER']);

    }
    
    public function delete($id) 
    {
        $row = $this->Authorized_signitories_model->get_by_id($id);

        if ($row) {
            $this->Authorized_signitories_model->delete($id);
            $this->session->set_flashdata('message', 'Delete Record Success');
            redirect(site_url('authorized_signitories'));
        } else {
            $this->session->set_flashdata('message', 'Record Not Found');
            redirect(site_url('authorized_signitories'));
        }
    }

    public function _rules() 
    {


    }

}

