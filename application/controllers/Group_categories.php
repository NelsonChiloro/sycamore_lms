<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Group_categories extends CI_Controller
{
    function __construct()
    {
        parent::__construct();
        $this->load->model('Group_categories_model');
        $this->load->library('form_validation');
    }

    public function index()
    {
        $q = urldecode($this->input->get('q', TRUE));
        $start = intval($this->input->get('start'));
        
        if ($q <> '') {
            $config['base_url'] = base_url() . 'group_categories/index.html?q=' . urlencode($q);
            $config['first_url'] = base_url() . 'group_categories/index.html?q=' . urlencode($q);
        } else {
            $config['base_url'] = base_url() . 'group_categories/index.html';
            $config['first_url'] = base_url() . 'group_categories/index.html';
        }

        $config['per_page'] = 10;
        $config['page_query_string'] = TRUE;
        $config['total_rows'] = $this->Group_categories_model->total_rows($q);
        $group_categories = $this->Group_categories_model->get_limit_data($config['per_page'], $start, $q);

        $this->load->library('pagination');
        $this->pagination->initialize($config);

        $data = array(
            'group_categories_data' => $group_categories,
            'q' => $q,
            'pagination' => $this->pagination->create_links(),
            'total_rows' => $config['total_rows'],
            'start' => $start,
        );
        $this->load->view('group_categories/group_categories_list', $data);
    }

    public function read($id) 
    {
        $row = $this->Group_categories_model->get_by_id($id);
        if ($row) {
            $data = array(
		'group_category_id' => $row->group_category_id,
		'group_category_name' => $row->group_category_name,
	    );
            $this->load->view('group_categories/group_categories_read', $data);
        } else {
            $this->session->set_flashdata('message', 'Record Not Found');
            redirect(site_url('group_categories'));
        }
    }

    public function create() 
    {
        $data = array(
            'button' => 'Create',
            'action' => site_url('group_categories/create_action'),
	    'group_category_id' => set_value('group_category_id'),
	    'group_category_name' => set_value('group_category_name'),
	);
        $this->load->view('group_categories/group_categories_form', $data);
    }
    
    public function create_action() 
    {
        $this->_rules();

        if ($this->form_validation->run() == FALSE) {
            $this->create();
        } else {
            $data = array(
		'group_category_name' => $this->input->post('group_category_name',TRUE),
	    );

            $this->Group_categories_model->insert($data);
            $this->session->set_flashdata('message', 'Create Record Success');
            redirect(site_url('group_categories'));
        }
    }
    
    public function update($id) 
    {
        $row = $this->Group_categories_model->get_by_id($id);

        if ($row) {
            $data = array(
                'button' => 'Update',
                'action' => site_url('group_categories/update_action'),
		'group_category_id' => set_value('group_category_id', $row->group_category_id),
		'group_category_name' => set_value('group_category_name', $row->group_category_name),
	    );
            $this->load->view('group_categories/group_categories_form', $data);
        } else {
            $this->session->set_flashdata('message', 'Record Not Found');
            redirect(site_url('group_categories'));
        }
    }
    
    public function update_action() 
    {
        $this->_rules();

        if ($this->form_validation->run() == FALSE) {
            $this->update($this->input->post('group_category_id', TRUE));
        } else {
            $data = array(
		'group_category_name' => $this->input->post('group_category_name',TRUE),
	    );

            $this->Group_categories_model->update($this->input->post('group_category_id', TRUE), $data);
            $this->session->set_flashdata('message', 'Update Record Success');
            redirect(site_url('group_categories'));
        }
    }
    
    public function delete($id) 
    {
        $row = $this->Group_categories_model->get_by_id($id);

        if ($row) {
            $this->Group_categories_model->delete($id);
            $this->session->set_flashdata('message', 'Delete Record Success');
            redirect(site_url('group_categories'));
        } else {
            $this->session->set_flashdata('message', 'Record Not Found');
            redirect(site_url('group_categories'));
        }
    }

    public function _rules() 
    {
	$this->form_validation->set_rules('group_category_name', 'group category name', 'trim|required');

	$this->form_validation->set_rules('group_category_id', 'group_category_id', 'trim');
	$this->form_validation->set_error_delimiters('<span class="text-danger">', '</span>');
    }

}

/* End of file Group_categories.php */
/* Location: ./application/controllers/Group_categories.php */
/* Please DO NOT modify this information : */
/* Generated by Harviacode Codeigniter CRUD Generator 2021-10-18 06:00:10 */
/* http://harviacode.com */