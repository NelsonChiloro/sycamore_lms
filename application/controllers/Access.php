<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Access extends CI_Controller
{
    function __construct()
    {
        parent::__construct();
        $this->load->model('Menu_model');
        $this->load->model('Menuitems_model');



        $this->load->model('Roles_model');
        $this->load->model('Access_model');
        $this->load->library('form_validation');
    }
    

    public function give_menu(){
        if($this->input->post('Submit')){
            $id=$this->input->post('id');
        }else{
        	$id=$this->session->userdata('roless');
		}
		$this->session->set_userdata('roless',$id);
		$idd=$this->session->userdata('roless');
        $data=array();
        $data['iddd']=$idd;


        $this->load->view('admin/header');
        $this->load->view('access/give_menu', $data);
        $this->load->view('admin/footer');

    }

    public function addmenu(){
        if($this->input->post('submit')) {
            $arr = $this->input->post('menu_id');
            $data = $this->input->post('id');
		$this->session->set_userdata('roless',$data);
            $this->Access_model->check_menu($arr, $data);
			$logger = array(

				'user_id' => $this->session->userdata('user_id'),
				'activity' => 'updated role access'

			);
			log_activity($logger);
			$this->toaster->success('Success, access grant was successful');
            redirect($_SERVER['HTTP_REFERER']);
        }else if($this->input->post('remove')){
            $arr = $this->input->post('menu_id');
            $data = $this->input->post('id');

            $this->Access_model->remove_menu($arr, $data);
			$logger = array(

				'user_id' => $this->session->userdata('user_id'),
				'activity' => 'updated role access'

			);
			log_activity($logger);
			$this->session->set_userdata('roless',$data);
            redirect($_SERVER['HTTP_REFERER']);
        }
    }

    public function index()
    {
        $q = urldecode($this->input->get('q', TRUE));
        $start = intval($this->input->get('start'));
        
        if ($q <> '') {
            $config['base_url'] = base_url() . 'access/index?q=' . urlencode($q);
            $config['first_url'] = base_url() . 'access/index?q=' . urlencode($q);
        } else {
            $config['base_url'] = base_url() . 'access/index';
            $config['first_url'] = base_url() . 'access/index';
        }

        $config['per_page'] = 10;
        $config['page_query_string'] = TRUE;
        $config['total_rows'] = $this->Access_model->total_rows($q);
        $access = $this->Access_model->get_limit_data($config['per_page'], $start, $q);

        $this->load->library('pagination');
        $this->pagination->initialize($config);

        $data = array(
            'access_data' => $access,
            'q' => $q,
            'pagination' => $this->pagination->create_links(),
            'total_rows' => $config['total_rows'],
            'start' => $start,
        );
        $this->load->view('access/access_list', $data);
    }

    public function read($id) 
    {
        $row = $this->Access_model->get_by_id($id);
        if ($row) {
            $data = array(
		'id' => $row->id,
		'roleid' => $row->roleid,
		'controllerid' => $row->controllerid,
	    );
            $this->load->view('access/access_read', $data);
        } else {
            $this->session->set_flashdata('message', 'Record Not Found');
            redirect(site_url('access'));
        }
    }

    public function create() 
    {
        $data = array(
            'button' => 'Create',
            'action' => site_url('access/create_action'),
	    'id' => set_value('id'),
	    'roleid' => set_value('roleid'),
	    'controllerid' => set_value('controllerid'),
	);
        $this->load->view('access/access_form', $data);
    }
    
    public function create_action() 
    {
        $this->_rules();

        if ($this->form_validation->run() == FALSE) {
            $this->create();
        } else {
            $data = array(
		'roleid' => $this->input->post('roleid',TRUE),
		'controllerid' => $this->input->post('controllerid',TRUE),
	    );

            $this->Access_model->insert($data);
            $this->session->set_flashdata('message', 'Create Record Success');
            redirect(site_url('access'));
        }
    }
    
    public function update($id) 
    {
        $row = $this->Access_model->get_by_id($id);

        if ($row) {
            $data = array(
                'button' => 'Update',
                'action' => site_url('access/update_action'),
		'id' => set_value('id', $row->id),
		'roleid' => set_value('roleid', $row->roleid),
		'controllerid' => set_value('controllerid', $row->controllerid),
	    );
            $this->load->view('access/access_form', $data);
        } else {
            $this->session->set_flashdata('message', 'Record Not Found');
            redirect(site_url('access'));
        }
    }
    
    public function update_action() 
    {
        $this->_rules();

        if ($this->form_validation->run() == FALSE) {
            $this->update($this->input->post('id', TRUE));
        } else {
            $data = array(
		'roleid' => $this->input->post('roleid',TRUE),
		'controllerid' => $this->input->post('controllerid',TRUE),
	    );

            $this->Access_model->update($this->input->post('id', TRUE), $data);
            $this->session->set_flashdata('message', 'Update Record Success');
            redirect(site_url('access'));
        }
    }
    
    public function delete($id) 
    {
        $row = $this->Access_model->get_by_id($id);

        if ($row) {
            $this->Access_model->delete($id);
            $this->session->set_flashdata('message', 'Delete Record Success');
            redirect(site_url('access'));
        } else {
            $this->session->set_flashdata('message', 'Record Not Found');
            redirect(site_url('access'));
        }
    }

    public function _rules() 
    {
	$this->form_validation->set_rules('roleid', 'roleid', 'trim|required');
	$this->form_validation->set_rules('controllerid', 'controllerid', 'trim|required');

	$this->form_validation->set_rules('id', 'id', 'trim');
	$this->form_validation->set_error_delimiters('<span class="text-danger">', '</span>');
    }

    public function excel()
    {
        $this->load->helper('exportexcel');
        $namaFile = "access.xls";
        $judul = "access";
        $tablehead = 0;
        $tablebody = 1;
        $nourut = 1;
        //penulisan header
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control: must-revalidate, post-check=0,pre-check=0");
        header("Content-Type: application/force-download");
        header("Content-Type: application/octet-stream");
        header("Content-Type: application/download");
        header("Content-Disposition: attachment;filename=" . $namaFile . "");
        header("Content-Transfer-Encoding: binary ");

        xlsBOF();

        $kolomhead = 0;
        xlsWriteLabel($tablehead, $kolomhead++, "No");
	xlsWriteLabel($tablehead, $kolomhead++, "Roleid");
	xlsWriteLabel($tablehead, $kolomhead++, "Controllerid");

	foreach ($this->Access_model->get_all() as $data) {
            $kolombody = 0;

            //ubah xlsWriteLabel menjadi xlsWriteNumber untuk kolom numeric
            xlsWriteNumber($tablebody, $kolombody++, $nourut);
	    xlsWriteNumber($tablebody, $kolombody++, $data->roleid);
	    xlsWriteNumber($tablebody, $kolombody++, $data->controllerid);

	    $tablebody++;
            $nourut++;
        }

        xlsEOF();
        exit();
    }

    public function word()
    {
        header("Content-type: application/vnd.ms-word");
        header("Content-Disposition: attachment;Filename=access.doc");

        $data = array(
            'access_data' => $this->Access_model->get_all(),
            'start' => 0
        );
        
        $this->load->view('access/access_doc',$data);
    }

}


