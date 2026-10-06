<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Fyer_holiday extends CI_Controller
{
    function __construct()
    {
        parent::__construct();
        $this->load->model('Fyer_holiday_model');
        $this->load->library('form_validation');
    }

    public function index()
    {
        $q = urldecode($this->input->get('q', TRUE));
        $start = intval($this->input->get('start'));
        
        if ($q <> '') {
            $config['base_url'] = base_url() . 'fyer_holiday/index.html?q=' . urlencode($q);
            $config['first_url'] = base_url() . 'fyer_holiday/index.html?q=' . urlencode($q);
        } else {
            $config['base_url'] = base_url() . 'fyer_holiday/index.html';
            $config['first_url'] = base_url() . 'fyer_holiday/index.html';
        }

        $config['per_page'] = 10;
        $config['page_query_string'] = TRUE;
        $config['total_rows'] = $this->Fyer_holiday_model->total_rows($q);
        $fyer_holiday = $this->Fyer_holiday_model->get_limit_data($config['per_page'], $start, $q);

        $this->load->library('pagination');
        $this->pagination->initialize($config);

        $data = array(
            'fyer_holiday_data' => $fyer_holiday,
            'q' => $q,
            'pagination' => $this->pagination->create_links(),
            'total_rows' => $config['total_rows'],
            'start' => $start,
        );
        $this->load->view('fyer_holiday/fyer_holiday_list', $data);
    }

    public function read($id) 
    {
        $row = $this->Fyer_holiday_model->get_by_id($id);
        if ($row) {
            $data = array(
		'fyer_id' => $row->fyer_id,
		'fyr_id' => $row->fyr_id,
		'date' => $row->date,
		'holiday_description' => $row->holiday_description,
		'date_added' => $row->date_added,
	    );
            $this->load->view('fyer_holiday/fyer_holiday_read', $data);
        } else {
            $this->session->set_flashdata('message', 'Record Not Found');
            redirect(site_url('fyer_holiday'));
        }
    }

    public function create() 
    {
        $data = array(
            'button' => 'Create',
            'action' => site_url('fyer_holiday/create_action'),
	    'fyer_id' => set_value('fyer_id'),
	    'fyr_id' => set_value('fyr_id'),
	    'date' => set_value('date'),
	    'holiday_description' => set_value('holiday_description'),
	    'date_added' => set_value('date_added'),
	);
        $this->load->view('fyer_holiday/fyer_holiday_form', $data);
    }
    
    public function create_action() 
    {
        $this->_rules();

        if ($this->form_validation->run() == FALSE) {
            $this->create();
        } else {
            $data = array(
		'fyr_id' => $this->input->post('fyr_id',TRUE),
		'date' => $this->input->post('date',TRUE),
		'holiday_description' => $this->input->post('holiday_description',TRUE),
		'date_added' => $this->input->post('date_added',TRUE),
	    );

            $this->Fyer_holiday_model->insert($data);
            $this->session->set_flashdata('message', 'Create Record Success');
            redirect(site_url('fyer_holiday'));
        }
    }
    
    public function update($id) 
    {
        $row = $this->Fyer_holiday_model->get_by_id($id);

        if ($row) {
            $data = array(
                'button' => 'Update',
                'action' => site_url('fyer_holiday/update_action'),
		'fyer_id' => set_value('fyer_id', $row->fyer_id),
		'fyr_id' => set_value('fyr_id', $row->fyr_id),
		'date' => set_value('date', $row->date),
		'holiday_description' => set_value('holiday_description', $row->holiday_description),
		'date_added' => set_value('date_added', $row->date_added),
	    );
            $this->load->view('fyer_holiday/fyer_holiday_form', $data);
        } else {
            $this->session->set_flashdata('message', 'Record Not Found');
            redirect(site_url('fyer_holiday'));
        }
    }
    
    public function update_action() 
    {
        $this->_rules();

        if ($this->form_validation->run() == FALSE) {
            $this->update($this->input->post('fyer_id', TRUE));
        } else {
            $data = array(
		'fyr_id' => $this->input->post('fyr_id',TRUE),
		'date' => $this->input->post('date',TRUE),
		'holiday_description' => $this->input->post('holiday_description',TRUE),
		'date_added' => $this->input->post('date_added',TRUE),
	    );

            $this->Fyer_holiday_model->update($this->input->post('fyer_id', TRUE), $data);
            $this->session->set_flashdata('message', 'Update Record Success');
            redirect(site_url('fyer_holiday'));
        }
    }
    
    public function delete($id) 
    {
        $row = $this->Fyer_holiday_model->get_by_id($id);

        if ($row) {
            $this->Fyer_holiday_model->delete($id);
            $this->session->set_flashdata('message', 'Delete Record Success');
            redirect(site_url('fyer_holiday'));
        } else {
            $this->session->set_flashdata('message', 'Record Not Found');
            redirect(site_url('fyer_holiday'));
        }
    }

    public function _rules() 
    {
	$this->form_validation->set_rules('fyr_id', 'fyr id', 'trim|required');
	$this->form_validation->set_rules('date', 'date', 'trim|required');
	$this->form_validation->set_rules('holiday_description', 'holiday description', 'trim|required');
	$this->form_validation->set_rules('date_added', 'date added', 'trim|required');

	$this->form_validation->set_rules('fyer_id', 'fyer_id', 'trim');
	$this->form_validation->set_error_delimiters('<span class="text-danger">', '</span>');
    }

}

/* End of file Fyer_holiday.php */
/* Location: ./application/controllers/Fyer_holiday.php */
/* Please DO NOT modify this information : */
/* Generated by Harviacode Codeigniter CRUD Generator 2021-10-14 06:27:32 */
/* http://harviacode.com */