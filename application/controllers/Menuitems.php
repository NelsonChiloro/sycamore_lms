<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Menuitems extends CI_Controller
{
    function __construct()
    {
        parent::__construct();
        $this->load->model('Menuitems_model');
        $this->load->library('form_validation');
    }

    public function index()
    {
        $q = urldecode($this->input->get('q', TRUE));
        $start = intval($this->input->get('start'));
        
        if ($q <> '') {
            $config['base_url'] = base_url() . 'menuitems/index.html?q=' . urlencode($q);
            $config['first_url'] = base_url() . 'menuitems/index.html?q=' . urlencode($q);
        } else {
            $config['base_url'] = base_url() . 'menuitems/index.html';
            $config['first_url'] = base_url() . 'menuitems/index.html';
        }

        $config['per_page'] = 10;
        $config['page_query_string'] = TRUE;
        $config['total_rows'] = $this->Menuitems_model->total_rows($q);
        $menuitems = $this->Menuitems_model->get_limit_data($config['per_page'], $start, $q);

        $this->load->library('pagination');
        $this->pagination->initialize($config);

        $data = array(
            'menuitems_data' => $menuitems,
            'q' => $q,
            'pagination' => $this->pagination->create_links(),
            'total_rows' => $config['total_rows'],
            'start' => $start,
        );
        $this->load->view('menuitems/menuitems_list', $data);
    }

    public function read($id) 
    {
        $row = $this->Menuitems_model->get_by_id($id);
        if ($row) {
            $data = array(
		'id' => $row->id,
		'mid' => $row->mid,
		'label' => $row->label,
		'method' => $row->method,
		'fa_icon' => $row->fa_icon,
		'sortt' => $row->sortt,
	    );
            $this->load->view('menuitems/menuitems_read', $data);
        } else {
            $this->session->set_flashdata('message', 'Record Not Found');
            redirect(site_url('menuitems'));
        }
    }

    public function create() 
    {
        $data = array(
            'button' => 'Create',
            'action' => site_url('menuitems/create_action'),
	    'id' => set_value('id'),
	    'mid' => set_value('mid'),
	    'label' => set_value('label'),
	    'method' => set_value('method'),
	    'fa_icon' => set_value('fa_icon'),
	    'sortt' => set_value('sortt'),
	);
        $this->load->view('menuitems/menuitems_form', $data);
    }
    
    public function create_action() 
    {
        $this->_rules();

        if ($this->form_validation->run() == FALSE) {
            $this->create();
        } else {
            $data = array(
		'mid' => $this->input->post('mid',TRUE),
		'label' => $this->input->post('label',TRUE),
		'method' => $this->input->post('method',TRUE),
		'fa_icon' => $this->input->post('fa_icon',TRUE),
		'sortt' => $this->input->post('sortt',TRUE),
	    );

            $this->Menuitems_model->insert($data);
            $this->session->set_flashdata('message', 'Create Record Success');
            redirect(site_url('menuitems'));
        }
    }
    
    public function update($id) 
    {
        $row = $this->Menuitems_model->get_by_id($id);

        if ($row) {
            $data = array(
                'button' => 'Update',
                'action' => site_url('menuitems/update_action'),
		'id' => set_value('id', $row->id),
		'mid' => set_value('mid', $row->mid),
		'label' => set_value('label', $row->label),
		'method' => set_value('method', $row->method),
		'fa_icon' => set_value('fa_icon', $row->fa_icon),
		'sortt' => set_value('sortt', $row->sortt),
	    );
            $this->load->view('menuitems/menuitems_form', $data);
        } else {
            $this->session->set_flashdata('message', 'Record Not Found');
            redirect(site_url('menuitems'));
        }
    }
    
    public function update_action() 
    {
        $this->_rules();

        if ($this->form_validation->run() == FALSE) {
            $this->update($this->input->post('id', TRUE));
        } else {
            $data = array(
		'mid' => $this->input->post('mid',TRUE),
		'label' => $this->input->post('label',TRUE),
		'method' => $this->input->post('method',TRUE),
		'fa_icon' => $this->input->post('fa_icon',TRUE),
		'sortt' => $this->input->post('sortt',TRUE),
	    );

            $this->Menuitems_model->update($this->input->post('id', TRUE), $data);
            $this->session->set_flashdata('message', 'Update Record Success');
            redirect(site_url('menuitems'));
        }
    }
    
    public function delete($id) 
    {
        $row = $this->Menuitems_model->get_by_id($id);

        if ($row) {
            $this->Menuitems_model->delete($id);
            $this->session->set_flashdata('message', 'Delete Record Success');
            redirect(site_url('menuitems'));
        } else {
            $this->session->set_flashdata('message', 'Record Not Found');
            redirect(site_url('menuitems'));
        }
    }

    public function _rules() 
    {
	$this->form_validation->set_rules('mid', 'mid', 'trim|required');
	$this->form_validation->set_rules('label', 'label', 'trim|required');
	$this->form_validation->set_rules('method', 'method', 'trim|required');
	$this->form_validation->set_rules('fa_icon', 'fa icon', 'trim|required');
	$this->form_validation->set_rules('sortt', 'sortt', 'trim|required');

	$this->form_validation->set_rules('id', 'id', 'trim');
	$this->form_validation->set_error_delimiters('<span class="text-danger">', '</span>');
    }

}

/* End of file Menuitems.php */
/* Location: ./application/controllers/Menuitems.php */
/* Please DO NOT modify this information : */
/* Generated by Harviacode Codeigniter CRUD Generator 2021-10-14 06:27:32 */
/* http://harviacode.com */