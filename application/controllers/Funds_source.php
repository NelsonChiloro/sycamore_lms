<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Funds_source extends CI_Controller
{
    function __construct()
    {
        parent::__construct();
        $this->load->model('Funds_source_model');
        $this->load->library('form_validation');
    }

    public function index()
    {
        $q = urldecode($this->input->get('q', TRUE));
        $start = intval($this->input->get('start'));
        
        if ($q <> '') {
            $config['base_url'] = base_url() . 'funds_source/index?q=' . urlencode($q);
            $config['first_url'] = base_url() . 'funds_source/index?q=' . urlencode($q);
        } else {
            $config['base_url'] = base_url() . 'funds_source/index';
            $config['first_url'] = base_url() . 'funds_source/index';
        }

        $config['per_page'] = 10;
        $config['page_query_string'] = TRUE;
        $config['total_rows'] = $this->Funds_source_model->total_rows($q);
        $funds_sources = $this->Funds_source_model->get_limit_data($config['per_page'], $start, $q);

        $this->load->library('pagination');
        $this->pagination->initialize($config);

        $data = array(
            'funds_source_data' => $funds_sources,
            'q' => $q,
            'pagination' => $this->pagination->create_links(),
            'total_rows' => $config['total_rows'],
            'start' => $start,
        );
        $this->load->view('admin/header');
        $this->load->view('funds_source/funds_source_list', $data);
        $this->load->view('admin/footer');
    }

    public function read($id) 
    {
        $row = $this->Funds_source_model->get_by_id($id);
        if ($row) {
            $data = array(
                'funds_source' => $row->funds_source,
                'source_name' => $row->source_name,
                'description' => $row->description,
                'date_added' => $row->date_added,
                'added_by' => $row->added_by,
            );
            $this->load->view('admin/header');
            $this->load->view('funds_source/funds_source_read', $data);
            $this->load->view('admin/footer');
        } else {
            $this->session->set_flashdata('message', 'Record Not Found');
            redirect(site_url('funds_source'));
        }
    }

    public function create() 
    {
        $data = array(
            'button' => 'Create',
            'action' => site_url('funds_source/create_action'),
            'funds_source' => set_value('funds_source'),
            'source_name' => set_value('source_name'),
            'description' => set_value('description'),
        );
        $this->load->view('admin/header');
        $this->load->view('funds_source/funds_source_form', $data);
        $this->load->view('admin/footer');
    }
    
    public function create_action() 
    {
        $this->_rules();

        if ($this->form_validation->run() == FALSE) {
            $this->create();
        } else {
            // Check if source name already exists
            if ($this->Funds_source_model->source_name_exists($this->input->post('source_name', TRUE))) {
                $this->session->set_flashdata('message', 'Error: Source name already exists');
                $this->create();
                return;
            }

            $data = array(
                'source_name' => $this->input->post('source_name', TRUE),
                'description' => $this->input->post('description', TRUE),
                'added_by' => $this->session->userdata('user_id'), // Assuming user_id is stored in session
            );

            $this->Funds_source_model->insert($data);
            $this->session->set_flashdata('message', 'Success: Funds source was created');
            redirect(site_url('funds_source'));
        }
    }
    
    public function update($id) 
    {
        $row = $this->Funds_source_model->get_by_id($id);

        if ($row) {
            $data = array(
                'button' => 'Update',
                'action' => site_url('funds_source/update_action'),
                'funds_source' => set_value('funds_source', $row->funds_source),
                'source_name' => set_value('source_name', $row->source_name),
                'description' => set_value('description', $row->description),
            );
            $this->load->view('admin/header');
            $this->load->view('funds_source/funds_source_form', $data);
            $this->load->view('admin/footer');
        } else {
            $this->session->set_flashdata('message', 'Record Not Found');
            redirect(site_url('funds_source'));
        }
    }
    
    public function update_action() 
    {
        $this->_rules();

        if ($this->form_validation->run() == FALSE) {
            $this->update($this->input->post('funds_source', TRUE));
        } else {
            // Check if source name already exists (excluding current record)
            if ($this->Funds_source_model->source_name_exists(
                $this->input->post('source_name', TRUE), 
                $this->input->post('funds_source', TRUE)
            )) {
                $this->session->set_flashdata('message', 'Error: Source name already exists');
                $this->update($this->input->post('funds_source', TRUE));
                return;
            }

            $data = array(
                'source_name' => $this->input->post('source_name', TRUE),
                'description' => $this->input->post('description', TRUE),
            );

            $this->Funds_source_model->update($this->input->post('funds_source', TRUE), $data);
            $this->session->set_flashdata('message', 'Update Record Success');
            redirect(site_url('funds_source'));
        }
    }
    
    public function delete($id) 
    {
        $row = $this->Funds_source_model->get_by_id($id);

        if ($row) {
            $this->Funds_source_model->delete($id);
            $this->session->set_flashdata('message', 'Delete Record Success');
            redirect(site_url('funds_source'));
        } else {
            $this->session->set_flashdata('message', 'Record Not Found');
            redirect(site_url('funds_source'));
        }
    }

    public function _rules() 
    {
        $this->form_validation->set_rules('source_name', 'Source Name', 'trim|required|min_length[3]|max_length[250]');
        $this->form_validation->set_rules('description', 'Description', 'trim');
        $this->form_validation->set_rules('funds_source', 'ID', 'trim');
        $this->form_validation->set_error_delimiters('<span class="text-danger">', '</span>');
    }
}