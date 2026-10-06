<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class User_group extends CI_Controller
{
    function __construct()
    {
        parent::__construct();
        $this->load->model('User_group_model');
        $this->load->library('form_validation');
    }

    public function index()
    {
        $q = urldecode($this->input->get('q', TRUE));
        $start = intval($this->input->get('start'));
        
        if ($q <> '') {
            $config['base_url'] = base_url() . 'user_group/index.html?q=' . urlencode($q);
            $config['first_url'] = base_url() . 'user_group/index.html?q=' . urlencode($q);
        } else {
            $config['base_url'] = base_url() . 'user_group/index.html';
            $config['first_url'] = base_url() . 'user_group/index.html';
        }

        $config['per_page'] = 10;
        $config['page_query_string'] = TRUE;
        $config['total_rows'] = $this->User_group_model->total_rows($q);
        $user_group = $this->User_group_model->get_limit_data($config['per_page'], $start, $q);

        $this->load->library('pagination');
        $this->pagination->initialize($config);

        $data = array(
            'user_group_data' => $user_group,
            'q' => $q,
            'pagination' => $this->pagination->create_links(),
            'total_rows' => $config['total_rows'],
            'start' => $start,
        );
        $this->load->view('user_group/user_group_list', $data);
    }

    public function read($id) 
    {
        $row = $this->User_group_model->get_by_id($id);
        if ($row) {
            $data = array(
		'user_group_id' => $row->user_group_id,
		'user_id' => $row->user_id,
		'group_id' => $row->group_id,
		'added_by' => $row->added_by,
		'date_created' => $row->date_created,
	    );
            $this->load->view('user_group/user_group_read', $data);
        } else {
            $this->session->set_flashdata('message', 'Record Not Found');
            redirect(site_url('user_group'));
        }
    }

    public function create() 
    {
        $data = array(
            'button' => 'Create',
            'action' => site_url('user_group/create_action'),
	    'user_group_id' => set_value('user_group_id'),
	    'user_id' => set_value('user_id'),
	    'group_id' => set_value('group_id'),
	    'added_by' => set_value('added_by'),
	    'date_created' => set_value('date_created'),
	);
        $this->load->view('user_group/user_group_form', $data);
    }
    
    public function create_action() 
    {
        $this->_rules();

        if ($this->form_validation->run() == FALSE) {
            $this->create();
        } else {
            $data = array(
		'user_id' => $this->input->post('user_id',TRUE),
		'group_id' => $this->input->post('group_id',TRUE),
		'added_by' => $this->input->post('added_by',TRUE),
		'date_created' => $this->input->post('date_created',TRUE),
	    );

            $this->User_group_model->insert($data);
            $this->session->set_flashdata('message', 'Create Record Success');
            redirect(site_url('user_group'));
        }
    }
    
    public function update($id) 
    {
        $row = $this->User_group_model->get_by_id($id);

        if ($row) {
            $data = array(
                'button' => 'Update',
                'action' => site_url('user_group/update_action'),
		'user_group_id' => set_value('user_group_id', $row->user_group_id),
		'user_id' => set_value('user_id', $row->user_id),
		'group_id' => set_value('group_id', $row->group_id),
		'added_by' => set_value('added_by', $row->added_by),
		'date_created' => set_value('date_created', $row->date_created),
	    );
            $this->load->view('user_group/user_group_form', $data);
        } else {
            $this->session->set_flashdata('message', 'Record Not Found');
            redirect(site_url('user_group'));
        }
    }
    
    public function update_action() 
    {
        $this->_rules();

        if ($this->form_validation->run() == FALSE) {
            $this->update($this->input->post('user_group_id', TRUE));
        } else {
            $data = array(
		'user_id' => $this->input->post('user_id',TRUE),
		'group_id' => $this->input->post('group_id',TRUE),
		'added_by' => $this->input->post('added_by',TRUE),
		'date_created' => $this->input->post('date_created',TRUE),
	    );

            $this->User_group_model->update($this->input->post('user_group_id', TRUE), $data);
            $this->session->set_flashdata('message', 'Update Record Success');
            redirect(site_url('user_group'));
        }
    }
    
    public function delete($id) 
    {
        $row = $this->User_group_model->get_by_id($id);

        if ($row) {
            $this->User_group_model->delete($id);
            $this->session->set_flashdata('message', 'Delete Record Success');
            redirect(site_url('user_group'));
        } else {
            $this->session->set_flashdata('message', 'Record Not Found');
            redirect(site_url('user_group'));
        }
    }

    public function _rules() 
    {
	$this->form_validation->set_rules('user_id', 'user id', 'trim|required');
	$this->form_validation->set_rules('group_id', 'group id', 'trim|required');
	$this->form_validation->set_rules('added_by', 'added by', 'trim|required');
	$this->form_validation->set_rules('date_created', 'date created', 'trim|required');

	$this->form_validation->set_rules('user_group_id', 'user_group_id', 'trim');
	$this->form_validation->set_error_delimiters('<span class="text-danger">', '</span>');
    }

}

/* End of file User_group.php */
/* Location: ./application/controllers/User_group.php */
/* Please DO NOT modify this information : */
/* Generated by Harviacode Codeigniter CRUD Generator 2021-10-18 06:00:26 */
/* http://harviacode.com */