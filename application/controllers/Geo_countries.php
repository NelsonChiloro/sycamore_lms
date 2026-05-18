<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Geo_countries extends CI_Controller
{
    function __construct()
    {
        parent::__construct();
        $this->load->model('Geo_countries_model');
        $this->load->library('form_validation');
    }

    public function index()
    {
        $q = urldecode($this->input->get('q', TRUE));
        $start = intval($this->input->get('start'));
        
        if ($q <> '') {
            $config['base_url'] = base_url() . 'geo_countries/index.html?q=' . urlencode($q);
            $config['first_url'] = base_url() . 'geo_countries/index.html?q=' . urlencode($q);
        } else {
            $config['base_url'] = base_url() . 'geo_countries/index.html';
            $config['first_url'] = base_url() . 'geo_countries/index.html';
        }

        $config['per_page'] = 10;
        $config['page_query_string'] = TRUE;
        $config['total_rows'] = $this->Geo_countries_model->total_rows($q);
        $geo_countries = $this->Geo_countries_model->get_limit_data($config['per_page'], $start, $q);

        $this->load->library('pagination');
        $this->pagination->initialize($config);

        $data = array(
            'geo_countries_data' => $geo_countries,
            'q' => $q,
            'pagination' => $this->pagination->create_links(),
            'total_rows' => $config['total_rows'],
            'start' => $start,
        );
        $this->load->view('geo_countries/geo_countries_list', $data);
    }

    public function read($id) 
    {
        $row = $this->Geo_countries_model->get_by_id($id);
        if ($row) {
            $data = array(
		'name' => $row->name,
		'id' => $row->id,
		'abv3' => $row->abv3,
		'abv3_alt' => $row->abv3_alt,
		'code' => $row->code,
		'slug' => $row->slug,
	    );
            $this->load->view('geo_countries/geo_countries_read', $data);
        } else {
            $this->session->set_flashdata('message', 'Record Not Found');
            redirect(site_url('geo_countries'));
        }
    }

    public function create() 
    {
        $data = array(
            'button' => 'Create',
            'action' => site_url('geo_countries/create_action'),
	    'name' => set_value('name'),
	    'id' => set_value('id'),
	    'abv3' => set_value('abv3'),
	    'abv3_alt' => set_value('abv3_alt'),
	    'code' => set_value('code'),
	    'slug' => set_value('slug'),
	);
        $this->load->view('geo_countries/geo_countries_form', $data);
    }
    
    public function create_action() 
    {
        $this->_rules();

        if ($this->form_validation->run() == FALSE) {
            $this->create();
        } else {
            $data = array(
		'name' => $this->input->post('name',TRUE),
		'abv3' => $this->input->post('abv3',TRUE),
		'abv3_alt' => $this->input->post('abv3_alt',TRUE),
		'code' => $this->input->post('code',TRUE),
		'slug' => $this->input->post('slug',TRUE),
	    );

            $this->Geo_countries_model->insert($data);
            $this->session->set_flashdata('message', 'Create Record Success');
            redirect(site_url('geo_countries'));
        }
    }
    
    public function update($id) 
    {
        $row = $this->Geo_countries_model->get_by_id($id);

        if ($row) {
            $data = array(
                'button' => 'Update',
                'action' => site_url('geo_countries/update_action'),
		'name' => set_value('name', $row->name),
		'id' => set_value('id', $row->id),
		'abv3' => set_value('abv3', $row->abv3),
		'abv3_alt' => set_value('abv3_alt', $row->abv3_alt),
		'code' => set_value('code', $row->code),
		'slug' => set_value('slug', $row->slug),
	    );
            $this->load->view('geo_countries/geo_countries_form', $data);
        } else {
            $this->session->set_flashdata('message', 'Record Not Found');
            redirect(site_url('geo_countries'));
        }
    }
    
    public function update_action() 
    {
        $this->_rules();

        if ($this->form_validation->run() == FALSE) {
            $this->update($this->input->post('id', TRUE));
        } else {
            $data = array(
		'name' => $this->input->post('name',TRUE),
		'abv3' => $this->input->post('abv3',TRUE),
		'abv3_alt' => $this->input->post('abv3_alt',TRUE),
		'code' => $this->input->post('code',TRUE),
		'slug' => $this->input->post('slug',TRUE),
	    );

            $this->Geo_countries_model->update($this->input->post('id', TRUE), $data);
            $this->session->set_flashdata('message', 'Update Record Success');
            redirect(site_url('geo_countries'));
        }
    }
    
    public function delete($id) 
    {
        $row = $this->Geo_countries_model->get_by_id($id);

        if ($row) {
            $this->Geo_countries_model->delete($id);
            $this->session->set_flashdata('message', 'Delete Record Success');
            redirect(site_url('geo_countries'));
        } else {
            $this->session->set_flashdata('message', 'Record Not Found');
            redirect(site_url('geo_countries'));
        }
    }

    public function _rules() 
    {
	$this->form_validation->set_rules('name', 'name', 'trim|required');
	$this->form_validation->set_rules('abv3', 'abv3', 'trim|required');
	$this->form_validation->set_rules('abv3_alt', 'abv3 alt', 'trim|required');
	$this->form_validation->set_rules('code', 'code', 'trim|required');
	$this->form_validation->set_rules('slug', 'slug', 'trim|required');

	$this->form_validation->set_rules('id', 'id', 'trim');
	$this->form_validation->set_error_delimiters('<span class="text-danger">', '</span>');
    }

}

/* End of file Geo_countries.php */
/* Location: ./application/controllers/Geo_countries.php */
/* Please DO NOT modify this information : */
/* Generated by Harviacode Codeigniter CRUD Generator 2021-10-14 06:27:32 */
/* http://harviacode.com */