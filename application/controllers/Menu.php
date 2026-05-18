<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Menu extends CI_Controller
{
    function __construct()
    {
        parent::__construct();
        $this->load->model('Menu_model');
        $this->load->library('form_validation');
    }

    public function index()
    {
        $q = urldecode($this->input->get('q', TRUE));
        $start = intval($this->input->get('start'));
        
        if ($q <> '') {
            $config['base_url'] = base_url() . 'menu/index.html?q=' . urlencode($q);
            $config['first_url'] = base_url() . 'menu/index.html?q=' . urlencode($q);
        } else {
            $config['base_url'] = base_url() . 'menu/index.html';
            $config['first_url'] = base_url() . 'menu/index.html';
        }

        $config['per_page'] = 10;
        $config['page_query_string'] = TRUE;
        $config['total_rows'] = $this->Menu_model->total_rows($q);
        $menu = $this->Menu_model->get_limit_data($config['per_page'], $start, $q);

        $this->load->library('pagination');
        $this->pagination->initialize($config);

        $data = array(
            'menu_data' => $menu,
            'q' => $q,
            'pagination' => $this->pagination->create_links(),
            'total_rows' => $config['total_rows'],
            'start' => $start,
        );
        $this->load->view('menu/menu_list', $data);
    }

    public function read($id) 
    {
        $row = $this->Menu_model->get_by_id($id);
        if ($row) {
            $data = array(
		'id' => $row->id,
		'label' => $row->label,
		'type' => $row->type,
		'icon_color' => $row->icon_color,
		'link' => $row->link,
		'sort' => $row->sort,
		'parent' => $row->parent,
		'icon' => $row->icon,
		'menu_type_id' => $row->menu_type_id,
		'active' => $row->active,
		'roll' => $row->roll,
	    );
            $this->load->view('menu/menu_read', $data);
        } else {
            $this->session->set_flashdata('message', 'Record Not Found');
            redirect(site_url('menu'));
        }
    }

    public function create() 
    {
        $data = array(
            'button' => 'Create',
            'action' => site_url('menu/create_action'),
	    'id' => set_value('id'),
	    'label' => set_value('label'),
	    'type' => set_value('type'),
	    'icon_color' => set_value('icon_color'),
	    'link' => set_value('link'),
	    'sort' => set_value('sort'),
	    'parent' => set_value('parent'),
	    'icon' => set_value('icon'),
	    'menu_type_id' => set_value('menu_type_id'),
	    'active' => set_value('active'),
	    'roll' => set_value('roll'),
	);
        $this->load->view('menu/menu_form', $data);
    }
    
    public function create_action() 
    {
        $this->_rules();

        if ($this->form_validation->run() == FALSE) {
            $this->create();
        } else {
            $data = array(
		'label' => $this->input->post('label',TRUE),
		'type' => $this->input->post('type',TRUE),
		'icon_color' => $this->input->post('icon_color',TRUE),
		'link' => $this->input->post('link',TRUE),
		'sort' => $this->input->post('sort',TRUE),
		'parent' => $this->input->post('parent',TRUE),
		'icon' => $this->input->post('icon',TRUE),
		'menu_type_id' => $this->input->post('menu_type_id',TRUE),
		'active' => $this->input->post('active',TRUE),
		'roll' => $this->input->post('roll',TRUE),
	    );

            $this->Menu_model->insert($data);
            $this->session->set_flashdata('message', 'Create Record Success');
            redirect(site_url('menu'));
        }
    }
    
    public function update($id) 
    {
        $row = $this->Menu_model->get_by_id($id);

        if ($row) {
            $data = array(
                'button' => 'Update',
                'action' => site_url('menu/update_action'),
		'id' => set_value('id', $row->id),
		'label' => set_value('label', $row->label),
		'type' => set_value('type', $row->type),
		'icon_color' => set_value('icon_color', $row->icon_color),
		'link' => set_value('link', $row->link),
		'sort' => set_value('sort', $row->sort),
		'parent' => set_value('parent', $row->parent),
		'icon' => set_value('icon', $row->icon),
		'menu_type_id' => set_value('menu_type_id', $row->menu_type_id),
		'active' => set_value('active', $row->active),
		'roll' => set_value('roll', $row->roll),
	    );
            $this->load->view('menu/menu_form', $data);
        } else {
            $this->session->set_flashdata('message', 'Record Not Found');
            redirect(site_url('menu'));
        }
    }
    
    public function update_action() 
    {
        $this->_rules();

        if ($this->form_validation->run() == FALSE) {
            $this->update($this->input->post('id', TRUE));
        } else {
            $data = array(
		'label' => $this->input->post('label',TRUE),
		'type' => $this->input->post('type',TRUE),
		'icon_color' => $this->input->post('icon_color',TRUE),
		'link' => $this->input->post('link',TRUE),
		'sort' => $this->input->post('sort',TRUE),
		'parent' => $this->input->post('parent',TRUE),
		'icon' => $this->input->post('icon',TRUE),
		'menu_type_id' => $this->input->post('menu_type_id',TRUE),
		'active' => $this->input->post('active',TRUE),
		'roll' => $this->input->post('roll',TRUE),
	    );

            $this->Menu_model->update($this->input->post('id', TRUE), $data);
            $this->session->set_flashdata('message', 'Update Record Success');
            redirect(site_url('menu'));
        }
    }
    
    public function delete($id) 
    {
        $row = $this->Menu_model->get_by_id($id);

        if ($row) {
            $this->Menu_model->delete($id);
            $this->session->set_flashdata('message', 'Delete Record Success');
            redirect(site_url('menu'));
        } else {
            $this->session->set_flashdata('message', 'Record Not Found');
            redirect(site_url('menu'));
        }
    }

    public function _rules() 
    {
	$this->form_validation->set_rules('label', 'label', 'trim|required');
	$this->form_validation->set_rules('type', 'type', 'trim|required');
	$this->form_validation->set_rules('icon_color', 'icon color', 'trim|required');
	$this->form_validation->set_rules('link', 'link', 'trim|required');
	$this->form_validation->set_rules('sort', 'sort', 'trim|required');
	$this->form_validation->set_rules('parent', 'parent', 'trim|required');
	$this->form_validation->set_rules('icon', 'icon', 'trim|required');
	$this->form_validation->set_rules('menu_type_id', 'menu type id', 'trim|required');
	$this->form_validation->set_rules('active', 'active', 'trim|required');
	$this->form_validation->set_rules('roll', 'roll', 'trim|required');

	$this->form_validation->set_rules('id', 'id', 'trim');
	$this->form_validation->set_error_delimiters('<span class="text-danger">', '</span>');
    }

}

/* End of file Menu.php */
/* Location: ./application/controllers/Menu.php */
/* Please DO NOT modify this information : */
/* Generated by Harviacode Codeigniter CRUD Generator 2021-10-14 06:27:32 */
/* http://harviacode.com */