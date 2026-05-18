<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Group_assigned_amount extends CI_Controller
{
    function __construct()
    {
        parent::__construct();
        $this->load->model('Group_assigned_amount_model');
        $this->load->model('Groups_model');
        $this->load->library('form_validation');
    }

    public function index()
    {
        $q = urldecode($this->input->get('q', TRUE));
        $start = intval($this->input->get('start'));
        
        if ($q <> '') {
            $config['base_url'] = base_url() . 'group_assigned_amount/index.html?q=' . urlencode($q);
            $config['first_url'] = base_url() . 'group_assigned_amount/index.html?q=' . urlencode($q);
        } else {
            $config['base_url'] = base_url() . 'group_assigned_amount/index.html';
            $config['first_url'] = base_url() . 'group_assigned_amount/index.html';
        }

        $config['per_page'] = 10;
        $config['page_query_string'] = TRUE;
        $config['total_rows'] = $this->Group_assigned_amount_model->total_rows($q);
        $group_assigned_amount = $this->Group_assigned_amount_model->get_limit_data($config['per_page'], $start, $q);

        $this->load->library('pagination');
        $this->pagination->initialize($config);

        $data = array(
            'group_assigned_amount_data' => $this->Group_assigned_amount_model->get_all(),
            'q' => $q,
            'pagination' => $this->pagination->create_links(),
            'total_rows' => $config['total_rows'],
            'start' => $start,
        );
		$this->load->view('admin/header');
        $this->load->view('group_assigned_amount/group_assigned_amount_list', $data);
		$this->load->view('admin/footer');
    }
public function approve()
    {


        $data = array(
            'group_assigned_amount_data' => $this->Group_assigned_amount_model->get_all(),

        );
		$this->load->view('admin/header');
        $this->load->view('group_assigned_amount/group_assigned_amount_list_approve', $data);
		$this->load->view('admin/footer');
    }
	public function approval()
	{


		$comment = $this->input->post('comment',TRUE);
		$act = $this->input->post('action',TRUE);


		$data = array(

			'status' => $this->input->post('action',TRUE),
			'reject_comment' => $act ==="Rejected" ? $comment : 'Null',
			'approval_comment' => $act ==="Active" ? $comment : 'Null',

		);

		$this->Group_assigned_amount_model->update($this->input->post('gid', TRUE), $data);
		$this->toaster->success('Success, your action was successful');
		redirect(site_url('group_assigned_amount/approve'));

	}
    public function read($id) 
    {
        $row = $this->Group_assigned_amount_model->get_by_id($id);
        if ($row) {
            $data = array(
		'id' => $row->id,
		'group_id' => $row->group_id,
		'amount' => $row->amount,
		'status' => $row->status,
		'approval_comment' => $row->approval_comment,
		'reject_comment' => $row->reject_comment,
		'disbursed_by' => $row->disbursed_by,
		'date_disbursed' => $row->date_disbursed,
	    );
            $this->load->view('group_assigned_amount/group_assigned_amount_read', $data);
        } else {
            $this->session->set_flashdata('message', 'Record Not Found');
            redirect(site_url('group_assigned_amount'));
        }
    }

    public function create() 
    {
        $data = array(
            'button' => 'Create',
            'action' => site_url('group_assigned_amount/create_action'),
	    'id' => set_value('id'),
	    'group_id' => set_value('group_id'),
	    'amount' => set_value('amount'),
	    'status' => set_value('status'),
	    'approval_comment' => set_value('approval_comment'),
	    'reject_comment' => set_value('reject_comment'),
	    'disbursed_by' => set_value('disbursed_by'),
	    'date_disbursed' => set_value('date_disbursed'),
	    'groups' => $this->Groups_model->get_all_active(),
	);
		$this->load->view('admin/header');
        $this->load->view('group_assigned_amount/group_assigned_amount_form', $data);
		$this->load->view('admin/footer');
    }
    
    public function create_action() 
    {
        $this->_rules();

        if ($this->form_validation->run() == FALSE) {
            $this->create();
        } else {
            $data = array(
		'group_id' => $this->input->post('group_id',TRUE),
		'amount' => $this->input->post('amount',TRUE),

		'disbursed_by' => $this->session->userdata('user_id')

	    );

            $this->Group_assigned_amount_model->insert($data);
            $this->session->set_flashdata('message', 'Create Record Success');
            redirect(site_url('group_assigned_amount'));
        }
    }
    
    public function update($id) 
    {
        $row = $this->Group_assigned_amount_model->get_by_id($id);

        if ($row) {
            $data = array(
                'button' => 'Update',
                'action' => site_url('group_assigned_amount/update_action'),
		'id' => set_value('id', $row->id),
		'group_id' => set_value('group_id', $row->group_id),
		'amount' => set_value('amount', $row->amount),
		'status' => set_value('status', $row->status),
		'approval_comment' => set_value('approval_comment', $row->approval_comment),
		'reject_comment' => set_value('reject_comment', $row->reject_comment),
		'disbursed_by' => set_value('disbursed_by', $row->disbursed_by),
		'date_disbursed' => set_value('date_disbursed', $row->date_disbursed),
	    );
            $this->load->view('group_assigned_amount/group_assigned_amount_form', $data);
        } else {
            $this->session->set_flashdata('message', 'Record Not Found');
            redirect(site_url('group_assigned_amount'));
        }
    }
    
    public function update_action() 
    {
        $this->_rules();

        if ($this->form_validation->run() == FALSE) {
            $this->update($this->input->post('id', TRUE));
        } else {
            $data = array(
		'group_id' => $this->input->post('group_id',TRUE),
		'amount' => $this->input->post('amount',TRUE),
		'status' => $this->input->post('status',TRUE),
		'approval_comment' => $this->input->post('approval_comment',TRUE),
		'reject_comment' => $this->input->post('reject_comment',TRUE),
		'disbursed_by' => $this->input->post('disbursed_by',TRUE),
		'date_disbursed' => $this->input->post('date_disbursed',TRUE),
	    );

            $this->Group_assigned_amount_model->update($this->input->post('id', TRUE), $data);
            $this->session->set_flashdata('message', 'Update Record Success');
            redirect(site_url('group_assigned_amount'));
        }
    }
    
    public function delete($id) 
    {
        $row = $this->Group_assigned_amount_model->get_by_id($id);

        if ($row) {
            $this->Group_assigned_amount_model->delete($id);
            $this->session->set_flashdata('message', 'Delete Record Success');
            redirect(site_url('group_assigned_amount'));
        } else {
            $this->session->set_flashdata('message', 'Record Not Found');
            redirect(site_url('group_assigned_amount'));
        }
    }

    public function _rules() 
    {
	$this->form_validation->set_rules('group_id', 'group id', 'trim|required');
	$this->form_validation->set_rules('amount', 'amount', 'trim|required|numeric');


	$this->form_validation->set_rules('id', 'id', 'trim');
	$this->form_validation->set_error_delimiters('<span class="text-danger">', '</span>');
    }

}


