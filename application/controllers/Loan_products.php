<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Loan_products extends CI_Controller
{
    function __construct()
    {
        parent::__construct();
        $this->load->model('Loan_products_model');
        $this->load->library('form_validation');
    }

    public function index()
    {
        $q = urldecode((string) $this->input->get('q', TRUE));
        $start = intval($this->input->get('start'));
        
        if ($q <> '') {
            $config['base_url'] = base_url() . 'loan_products/index?q=' . urlencode($q);
            $config['first_url'] = base_url() . 'loan_products/index?q=' . urlencode($q);
        } else {
            $config['base_url'] = base_url() . 'loan_products/index';
            $config['first_url'] = base_url() . 'loan_products/index';
        }

        $config['per_page'] = 10;
        $config['page_query_string'] = TRUE;
        $config['total_rows'] = $this->Loan_products_model->total_rows($q);
        $loan_products = $this->Loan_products_model->get_limit_data($config['per_page'], $start, $q);

        $this->load->library('pagination');
        $this->pagination->initialize($config);

        $data = array(
            'loan_products_data' => $this->Loan_products_model->get_all(),
            'q' => $q,
            'pagination' => $this->pagination->create_links(),
            'total_rows' => $config['total_rows'],
            'start' => $start,
        );
		$menu_toggle['toggles'] = 51;
        $this->load->view('admin/header',$menu_toggle);
        $this->load->view('loan_products/loan_products_list', $data);
		$this->load->view('admin/footer');
    }
    public function to_delete()
    {
        $q = urldecode((string) $this->input->get('q', TRUE));
        $start = intval($this->input->get('start'));

        if ($q <> '') {
            $config['base_url'] = base_url() . 'loan_products/to_delete?q=' . urlencode($q);
            $config['first_url'] = base_url() . 'loan_products/to_delete?q=' . urlencode($q);
        } else {
            $config['base_url'] = base_url() . 'loan_products/to_delete';
            $config['first_url'] = base_url() . 'loan_products/to_delete';
        }

        $config['per_page'] = 10;
        $config['page_query_string'] = TRUE;
        $config['total_rows'] = $this->Loan_products_model->total_rows($q);
        $loan_products = $this->Loan_products_model->get_limit_data($config['per_page'], $start, $q);

        $this->load->library('pagination');
        $this->pagination->initialize($config);

        $data = array(
            'loan_products_data' => $this->Loan_products_model->get_all(),
            'q' => $q,
            'pagination' => $this->pagination->create_links(),
            'total_rows' => $config['total_rows'],
            'start' => $start,
        );
		$menu_toggle['toggles'] = 51;
        $this->load->view('admin/header',$menu_toggle);
        $this->load->view('loan_products/loan_products_delete', $data);
		$this->load->view('admin/footer');
    }
public function to_update()
    {
        $q = urldecode((string) $this->input->get('q', TRUE));
        $start = intval($this->input->get('start'));

        if ($q <> '') {
            $config['base_url'] = base_url() . 'loan_products/edit?q=' . urlencode($q);
            $config['first_url'] = base_url() . 'loan_products/edit?q=' . urlencode($q);
        } else {
            $config['base_url'] = base_url() . 'loan_products/edit';
            $config['first_url'] = base_url() . 'loan_products/edit';
        }

        $config['per_page'] = 10;
        $config['page_query_string'] = TRUE;
        $config['total_rows'] = $this->Loan_products_model->total_rows($q);
        $loan_products = $this->Loan_products_model->get_limit_data($config['per_page'], $start, $q);

        $this->load->library('pagination');
        $this->pagination->initialize($config);

        $data = array(
            'loan_products_data' => $this->Loan_products_model->get_all(),
            'q' => $q,
            'pagination' => $this->pagination->create_links(),
            'total_rows' => $config['total_rows'],
            'start' => $start,
        );
		$menu_toggle['toggles'] = 51;
        $this->load->view('admin/header',$menu_toggle);
        $this->load->view('loan_products/loan_products_edit', $data);
		$this->load->view('admin/footer');
    }

    public function read($id) 
    {
        $row = $this->Loan_products_model->get_by_id($id);
        if ($row) {
            $data = array(
		'loan_product_id' => $row->loan_product_id,
		'product_name' => $row->product_name,
		'interest' => $row->interest,
		'added_by' => $row->added_by,
		'date_created' => $row->date_created,
	    );
            $this->load->view('loan_products/loan_products_read', $data);
        } else {
            $this->session->set_flashdata('message', 'Record Not Found');
            redirect(site_url('loan_products'));
        }
    }

    public function create() 
    {
        $data = array(
            'button' => 'Create',
            'action' => site_url('loan_products/create_action'),
	    'loan_product_id' => set_value('loan_product_id'),
	    'product_name' => set_value('product_name'),
	    'interest' => set_value('interest'),
	    'admin_fees' => set_value('admin_fees'),
	    'loan_cover' => set_value('loan_cover'),
	    	'penalty' => set_value('penalty'),
            'branch' => set_value('branch'),
            'product_code' => set_value('product_code'),
	    'added_by' => set_value('added_by'),
	    'date_created' => set_value('date_created'),
	);
		$menu_toggle['toggles'] = 51;
		$this->load->view('admin/header', $menu_toggle);
		$this->load->view('loan_products/loan_products_form', $data);
		$this->load->view('admin/footer');
    }
    
    public function create_action() 
    {
        $this->_rules();

        if ($this->form_validation->run() == FALSE) {
            $this->create();
        } else {
            $data = array(
		'product_name' => $this->input->post('product_name',TRUE),
		'interest' => $this->input->post('interest',TRUE),
		'admin_fees' => $this->input->post('admin_fees',TRUE),
		'loan_cover' => $this->input->post('loan_cover',TRUE),
			'penalty' => $this->input->post('penalty',TRUE),
		'added_by' => $this->session->userdata('user_id'),
		'frequency' => $this->input->post('frequency',TRUE),
                'branch' => $this->input->post('branch',TRUE),
                'product_code' => $this->input->post('product_code',TRUE),
	    );
			$logger = array(

				'user_id' => $this->session->userdata('user_id'),
				'activity' => 'Create loan Product, name:'.' '.$data['product_name'].' '.' interest='.$data['interest'].' '.' frequency='.$data['frequency']

			);
			log_activity($logger);


			$this->Loan_products_model->insert($data);
            $this->toaster->success('Success,Create Record Success');
            redirect(site_url('loan_products'));
        }
    }
    
    public function update($id) 
    {
        $row = $this->Loan_products_model->get_by_id($id);

        if ($row) {
            $data = array(
                'button' => 'Update',
                'action' => site_url('loan_products/update_action'),
		'loan_product_id' => set_value('loan_product_id', $row->loan_product_id),
		'product_name' => set_value('product_name', $row->product_name),
		'interest' => set_value('interest', $row->interest),
		'admin_fees' => set_value('admin_fees', $row->admin_fees ),
		'loan_cover' => set_value('loan_cover', $row->loan_cover),

                'penalty' => set_value('penalty', $row->penalty),
                'branch' => set_value('branch',$row->branch),
                'product_code' => set_value('product_code',$row->product_code),
		'added_by' => set_value('added_by', $row->added_by),
		'date_created' => set_value('date_created', $row->date_created),
	    );
			$menu_toggle['toggles'] = 51;
			$this->load->view('admin/header', $menu_toggle);
			$this->load->view('loan_products/loan_products_form', $data);
			$this->load->view('admin/footer');
        } else {
            $this->session->set_flashdata('message', 'Record Not Found');
            redirect(site_url('loan_products'));
        }
    }
    
    public function update_action() 
    {
        $this->_rules();

        if ($this->form_validation->run() == FALSE) {
            $this->update($this->input->post('loan_product_id', TRUE));
        } else {
            $data = array(
		'product_name' => $this->input->post('product_name',TRUE),
		'interest' => $this->input->post('interest',TRUE),
		'admin_fees' => $this->input->post('admin_fees',TRUE),
		'loan_cover' => $this->input->post('loan_cover',TRUE),
                'branch' => $this->input->post('branch',TRUE),
                'product_code' => $this->input->post('product_code',TRUE),

	    );
			$logger = array(

				'user_id' => $this->session->userdata('user_id'),
				'activity' => 'Update loan product name to:'.' '.$data['product_name'].' '.'interest='.' '.$data['interest']

			);
			log_activity($logger);

            $this->Loan_products_model->update($this->input->post('loan_product_id', TRUE), $data);
            $this->session->set_flashdata('message', 'Update Record Success');
            redirect(site_url('loan_products'));
        }
    }
    
    public function delete($id) 
    {
        $row = $this->Loan_products_model->get_by_id($id);

        if ($row) {
            $this->Loan_products_model->delete($id);
            $this->session->set_flashdata('message', 'Delete Record Success');
            redirect(site_url('loan_products'));
        } else {
            $this->session->set_flashdata('message', 'Record Not Found');
            redirect(site_url('loan_products'));
        }
    }

    public function _rules() 
    {
	$this->form_validation->set_rules('product_name', 'product name', 'trim|required');
	$this->form_validation->set_rules('interest', 'interest', 'trim|required|numeric');



	$this->form_validation->set_rules('loan_product_id', 'loan_product_id', 'trim');
	$this->form_validation->set_error_delimiters('<span class="text-danger">', '</span>');
    }

}

