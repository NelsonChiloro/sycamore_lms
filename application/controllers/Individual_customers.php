<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Individual_customers extends CI_Controller
{
    function __construct()
    {
        parent::__construct();
        $this->load->model('Individual_customers_model');
        $this->load->model('Corporate_customers_model');
		$this->load->model('Branches_model');
		$this->load->model('Customer_groups_model');
		$this->load->model('Loan_model');
		$this->load->model('Account_model');
		$this->load->model('Geo_countries_model');
		$this->load->model('Proofofidentity_model');
//		$this->load->model('Chart_of_accounts_model');
        $this->load->library('form_validation');
    }

	private function is_duplicate_entry_error($db_error)
	{
		return is_array($db_error)
			&& isset($db_error['code'])
			&& (int)$db_error['code'] === 1062;
	}

	private function generate_unique_client_id($max_attempts = 10)
	{
		for ($i = 0; $i < $max_attempts; $i++) {
			$candidate = (string) mt_rand(10000000, 99999999);

			$exists_query = $this->db
				->select('id')
				->from('individual_customers')
				->where('ClientId', $candidate)
				->limit(1)
				->get();

			if ($exists_query === false) {
				continue;
			}

			$exists = $exists_query->row();

			if (empty($exists)) {
				return $candidate;
			}
		}

		return null;
	}

	private function get_next_customer_account_number($offset = 0)
	{
		$query = $this->db->query(
			"SELECT MAX(CAST(account_number AS UNSIGNED)) AS max_acc
			 FROM account
			 WHERE account_type = 1
			 AND account_number REGEXP '^[0-9]+$'"
		);

		if ($query === false) {
			return 500001 + (int)$offset;
		}

		$row = $query->row();
		$max_acc = isset($row->max_acc) ? (int)$row->max_acc : 500000;

		return max(500001, $max_acc + 1 + (int)$offset);
	}

	public function products($id){
    	$data = array(
    		'id'=>$id
		);
		$this->template->set('title', 'Core Banking | Customer products');
		$this->template->load('template', 'contents' ,'individual_customers/product',$data);
	}
function send_s(){
//    $s =   send_sms('+265992046150','Hello Testing agritrust');
    $s =   get_balance();
    var_dump($s);
}

	public function index()
    {
		$data['individual_customers_data'] = $this->Individual_customers_model->get_all2();
		$user = $this->input->get('user');
		$country = $this->input->get('country');
       
        $branch = $this->input->get('branch');
		$status = $this->input->get('status');
		$gender = $this->input->get('gender');
		$from = $this->input->get('from');
		$to = $this->input->get('to');
		$search = $this->input->get('search');
		$menu_toggle['toggles'] = 43;
		if($search=="filter"){
			$data2['individual_customers_data'] = $this->Individual_customers_model->get_filter($status,$user,$country,$branch,$gender, $from, $to);

			$this->load->view('admin/header',$menu_toggle);
			$this->load->view('individual_customers/individual_customers_list',$data2);
			$this->load->view('admin/footer');
		}elseif($search=='export pdf'){
			$data['individual_customers_data'] = $this->Individual_customers_model->get_filter($status,$user,$country,$branch,$gender, $from, $to);
			$data['officer'] = ($user=="All") ? "All Officers" : get_by_id('employees','id',$user)->Firstname;
//			$data['product'] =($product=="All") ? "All Products" : get_by_id('loan_products','loan_product_id',$product)->product_name;
			$data['from'] = $from;
			$data['to'] = $to;
			$this->load->library('Pdf');
			$html = $this->load->view('individual_customers/individual_customers_list', $data,true);
			$this->pdf->createPDF($html, "customer report as on".date('Y-m-d'), true,'A4','landscape');
		} elseif($search=='export excel'){
			$data_excel = $this->Individual_customers_model->get_filter($status,$user,$country,$branch,$gender, $from, $to);
//		print_r($data_excel);
//		exit();
			$this->excel($data_excel);
		}else{
			$this->load->view('admin/header',$menu_toggle);
			$this->load->view('individual_customers/individual_customers_list',$data);
			$this->load->view('admin/footer');
		}


    }
    public function index1()
    {
        $q = urldecode($this->input->get('q', TRUE));
        $start = intval($this->input->get('start'));

        if ($q <> '') {
            $config['base_url'] = base_url() . 'individual_customers/index?q=' . urlencode($q);
            $config['first_url'] = base_url() . 'individual_customers/index?q=' . urlencode($q);
        } else {
            $config['base_url'] = base_url() . 'individual_customers/index';
            $config['first_url'] = base_url() . 'individual_customers/index';
        }

        $config['per_page'] = 10;
        $config['page_query_string'] = TRUE;
        $config['total_rows'] = $this->Individual_customers_model->total_rows($q);
        $individual_customers = $this->Individual_customers_model->get_limit_data($config['per_page'], $start, $q);

        $this->load->library('pagination');
        $this->pagination->initialize($config);

        $data = array(
            'individual_customers_data' => $this->Individual_customers_model->get_all(),
            'q' => $q,
            'pagination' => $this->pagination->create_links(),
            'total_rows' => $config['total_rows'],
            'start' => $start,
        );
		$menu_toggle['toggles'] = 43;
		$this->load->view('admin/header',$menu_toggle);
		$this->load->view('individual_customers/individual_customers_list',$data);
		$this->load->view('admin/footer');
    }
	public function edit()
    {
        $q = urldecode($this->input->get('q', TRUE));
        $start = intval($this->input->get('start'));

        if ($q <> '') {
            $config['base_url'] = base_url() . 'individual_customers/edit?q=' . urlencode($q);
            $config['first_url'] = base_url() . 'individual_customers/edit?q=' . urlencode($q);
        } else {
            $config['base_url'] = base_url() . 'individual_customers/edit';
            $config['first_url'] = base_url() . 'individual_customers/edit';
        }

        $config['per_page'] = 10;
        $config['page_query_string'] = TRUE;
        $config['total_rows'] = $this->Individual_customers_model->total_rows($q);
        $individual_customers = $this->Individual_customers_model->get_limit_data($config['per_page'], $start, $q);

        $this->load->library('pagination');
        $this->pagination->initialize($config);

        $data = array(
            'individual_customers_data' => $this->Individual_customers_model->get_all(),
            'q' => $q,
            'pagination' => $this->pagination->create_links(),
            'total_rows' => $config['total_rows'],
            'start' => $start,
        );
		$menu_toggle['toggles'] = 43;
		$this->load->view('admin/header',$menu_toggle);
		$this->load->view('individual_customers/individual_customers_toedit',$data);
		$this->load->view('admin/footer');
    }
    public function to_delete()
    {
        $q = urldecode($this->input->get('q', TRUE));
        $start = intval($this->input->get('start'));

        if ($q <> '') {
            $config['base_url'] = base_url() . 'individual_customers/edit?q=' . urlencode($q);
            $config['first_url'] = base_url() . 'individual_customers/edit?q=' . urlencode($q);
        } else {
            $config['base_url'] = base_url() . 'individual_customers/edit';
            $config['first_url'] = base_url() . 'individual_customers/to_delete';
        }

        $config['per_page'] = 10;
        $config['page_query_string'] = TRUE;
        $config['total_rows'] = $this->Individual_customers_model->total_rows($q);
        $individual_customers = $this->Individual_customers_model->get_limit_data($config['per_page'], $start, $q);

        $this->load->library('pagination');
        $this->pagination->initialize($config);

        $data = array(
            'individual_customers_data' => $this->Individual_customers_model->get_all(),
            'q' => $q,
            'pagination' => $this->pagination->create_links(),
            'total_rows' => $config['total_rows'],
            'start' => $start,
        );
		$menu_toggle['toggles'] = 43;
		$this->load->view('admin/header',$menu_toggle);
		$this->load->view('individual_customers/individual_customers_delete',$data);
		$this->load->view('admin/footer');
    }
    public function kyc_edit()
    {
        $q = urldecode($this->input->get('q', TRUE));
        $start = intval($this->input->get('start'));

        if ($q <> '') {
            $config['base_url'] = base_url() . 'individual_customers/kyc_edit?q=' . urlencode($q);
            $config['first_url'] = base_url() . 'individual_customers/kyc_edit?q=' . urlencode($q);
        } else {
            $config['base_url'] = base_url() . 'individual_customers/kyc_edit';
            $config['first_url'] = base_url() . 'individual_customers/kyc_edit';
        }

        $config['per_page'] = 10;
        $config['page_query_string'] = TRUE;
        $config['total_rows'] = $this->Individual_customers_model->total_rows($q);
        $individual_customers = $this->Individual_customers_model->get_limit_data($config['per_page'], $start, $q);

        $this->load->library('pagination');
        $this->pagination->initialize($config);

        $data = array(
            'individual_customers_data' => $this->Individual_customers_model->get_all(),
            'q' => $q,
            'pagination' => $this->pagination->create_links(),
            'total_rows' => $config['total_rows'],
            'start' => $start,
        );
		$menu_toggle['toggles'] = 43;
		$this->load->view('admin/header',$menu_toggle);
		$this->load->view('individual_customers/individual_customers_kyc_edit',$data);
		$this->load->view('admin/footer');
    }
    public function my_customers()
    {


        $data = array(
            'individual_customers_data' => $this->Individual_customers_model->get_selective($this->session->userdata('user_id')),

        );
		$menu_toggle['toggles'] = 43;
		$this->load->view('admin/header', $menu_toggle);
		$this->load->view('individual_customers/my_customers',$data);
		$this->load->view('admin/footer');
    }
    public function approve()
    {


        $data = array(
            'individual_customers_data' => $this->Individual_customers_model->get_status('Recommended')

        );
		$menu_toggle['toggles'] = 43;
		$this->load->view('admin/header',$menu_toggle);
		$this->load->view('individual_customers/approve',$data);
		$this->load->view('admin/footer');
    }
    public function recommend()
    {


        $data = array(
            'individual_customers_data' => $this->Individual_customers_model->get_status('Not Approved')

        );
		$menu_toggle['toggles'] = 43;
		$this->load->view('admin/header',$menu_toggle);
		$this->load->view('individual_customers/recommend',$data);
		$this->load->view('admin/footer');
    }
    public function approved()
    {


        $data = array(
            'individual_customers_data' =>  $this->Individual_customers_model->get_status('Approved')

        );
		$menu_toggle['toggles'] = 43;
		$this->load->view('admin/header', $menu_toggle);
		$this->load->view('individual_customers/track',$data);
		$this->load->view('admin/footer');
    }
    public function rejected()
    {


        $data = array(
            'individual_customers_data' =>  $this->Individual_customers_model->get_status('Rejected')

        );
		$menu_toggle['toggles'] = 43;
		$this->load->view('admin/header', $menu_toggle);
		$this->load->view('individual_customers/rejected',$data);
		$this->load->view('admin/footer');
    }

    public function read($id) 
    {
        $row = $this->Individual_customers_model->get_by_id($id);
        if ($row) {
            $data = array(
		'id' => $row->id,
		'ClientId' => $row->ClientId,
		'Title' => $row->Title,
		'Firstname' => $row->Firstname,
		'Middlename' => $row->Middlename,
		'Lastname' => $row->Lastname,
		'Gender' => $row->Gender,
                'Marital_status' => $row->Marital_status,
		'DateOfBirth' => $row->DateOfBirth,
		'EmailAddress' => $row->EmailAddress,
		'PhoneNumber' => $row->PhoneNumber,
		'AddressLine1' => $row->AddressLine1,
		'AddressLine2' => $row->AddressLine2,
		'AddressLine3' => $row->AddressLine3,
		'Province' => $row->Province,
		'City' => $row->City,
		'Country' => $row->Country,
		'ResidentialStatus' => $row->ResidentialStatus,
		'Profession' => $row->Profession,
		'SourceOfIncome' => $row->SourceOfIncome,
		'GrossMonthlyIncome' => $row->GrossMonthlyIncome,
		'Branch' => $row->Branch,
		'LastUpdatedOn' => $row->LastUpdatedOn,
		'CreatedOn' => $row->CreatedOn,
		'customer_groups' => $this->Customer_groups_model->get_groups_by_customer($id),
	    );
			$menu_toggle['toggles'] = 43;
			$this->load->view('admin/header',$menu_toggle);
			$this->load->view('individual_customers/individual_customers_read',$data);
			$this->load->view('admin/footer');
        } else {
            $this->session->set_flashdata('message', 'Record Not Found');
            redirect(site_url('individual_customers'));
        }
    }
    public function view($id)
    {
        $row = $this->Individual_customers_model->get_by_id($id);
        if ($row) {
            $data = array(
		'id' => $row->id,
		'ClientId' => $row->ClientId,
		'Title' => $row->Title,
		'Firstname' => $row->Firstname,
		'Middlename' => $row->Middlename,
		'Lastname' => $row->Lastname,
		'Gender' => $row->Gender,
                'Marital_status' => $row->Marital_status,
		'DateOfBirth' => $row->DateOfBirth,
		'EmailAddress' => $row->EmailAddress,
		'PhoneNumber' => $row->PhoneNumber,
		'AddressLine1' => $row->AddressLine1,
		'AddressLine2' => $row->AddressLine2,
		'AddressLine3' => $row->AddressLine3,
		'Province' => $row->Province,
		'City' => $row->City,
		'Country' => $row->Country,
		'ResidentialStatus' => $row->ResidentialStatus,
		'Profession' => $row->Profession,
		'SourceOfIncome' => $row->SourceOfIncome,
		'GrossMonthlyIncome' => $row->GrossMonthlyIncome,
		'Branch' => $row->Branch,
		'LastUpdatedOn' => $row->LastUpdatedOn,
		'CreatedOn' => $row->CreatedOn,
		'customer_groups' => $this->Customer_groups_model->get_groups_by_customer($id),
	    );
			$menu_toggle['toggles'] = 43;
			$this->load->view('admin/header', $menu_toggle);
			$this->load->view('individual_customers/view',$data);
			$this->load->view('admin/footer');
        } else {
            $this->session->set_flashdata('message', 'Record Not Found');
            redirect(site_url('individual_customers'));
        }
    } public function view_kyc($id)
    {
        $row = $this->Individual_customers_model->get_by_id($id);
        if ($row) {
            $data = array(
		'id' => $row->id,
		'ClientId' => $row->ClientId,
		'Title' => $row->Title,
		'Firstname' => $row->Firstname,
		'Middlename' => $row->Middlename,
		'Lastname' => $row->Lastname,
		'Gender' => $row->Gender,
		'DateOfBirth' => $row->DateOfBirth,
		'EmailAddress' => $row->EmailAddress,
		'PhoneNumber' => $row->PhoneNumber,
		'AddressLine1' => $row->AddressLine1,
		'AddressLine2' => $row->AddressLine2,
		'AddressLine3' => $row->AddressLine3,
		'Province' => $row->Province,
		'City' => $row->City,
		'Country' => $row->Country,
		'ResidentialStatus' => $row->ResidentialStatus,
		'Profession' => $row->Profession,
		'SourceOfIncome' => $row->SourceOfIncome,
		'GrossMonthlyIncome' => $row->GrossMonthlyIncome,
		'Branch' => $row->Branch,
		'LastUpdatedOn' => $row->LastUpdatedOn,
		'CreatedOn' => $row->CreatedOn,
	    );
			$menu_toggle['toggles'] = 43;
			$this->load->view('admin/header', $menu_toggle);
			$this->load->view('individual_customers/view_kyc',$data);
			$this->load->view('admin/footer');
        } else {
            $this->session->set_flashdata('message', 'Record Not Found');
            redirect(site_url('individual_customers'));
        }
    }
    public function report($id)
    {
        $row = $this->Individual_customers_model->get_by_id($id);
        if ($row) {
            $data = array(
		'id' => $row->id,
		'ClientId' => $row->ClientId,
		'Title' => $row->Title,
		'Firstname' => $row->Firstname,
		'Middlename' => $row->Middlename,
		'Lastname' => $row->Lastname,
		'Gender' => $row->Gender,
		'DateOfBirth' => $row->DateOfBirth,
		'EmailAddress' => $row->EmailAddress,
		'PhoneNumber' => $row->PhoneNumber,
		'AddressLine1' => $row->AddressLine1,
		'AddressLine2' => $row->AddressLine2,
		'AddressLine3' => $row->AddressLine3,
		'Province' => $row->Province,
		'City' => $row->City,
		'Country' => $row->Country,
		'ResidentialStatus' => $row->ResidentialStatus,
		'Profession' => $row->Profession,
		'SourceOfIncome' => $row->SourceOfIncome,
		'GrossMonthlyIncome' => $row->GrossMonthlyIncome,
		'Branch' => $row->Branch,
		'LastUpdatedOn' => $row->LastUpdatedOn,
		'CreatedOn' => $row->CreatedOn,
	    );
			$this->load->library('Pdf');
			$html = $this->load->view('individual_customers/report', $data,true);
			$this->pdf->createPDF($html, "Customer report as on".date('Y-m-d'), true,'A4','landscape');


        } else {
            $this->session->set_flashdata('message', 'Record Not Found');
            redirect(site_url('individual_customers'));
        }
    }
    public function view_customer($id)
    {
    	$res = array();
        $row = $this->Individual_customers_model->get_by_id($id);
        $loans = $this->Loan_model->get_user_loan($id);

        if ($row) {
			$kyc = $this->Proofofidentity_model->check($row->ClientId);
            $data = array(
            	'loan'=>$loans,
            	'kyc'=>$kyc,
		'id' => $row->id,
		'ClientId' => $row->ClientId,
		'Title' => $row->Title,
		'Firstname' => $row->Firstname,
		'Middlename' => $row->Middlename,
		'Lastname' => $row->Lastname,
		'Gender' => $row->Gender,
		'DateOfBirth' => $row->DateOfBirth,
		'EmailAddress' => $row->EmailAddress,
		'PhoneNumber' => $row->PhoneNumber,
		'AddressLine1' => $row->AddressLine1,
		'AddressLine2' => $row->AddressLine2,
		'AddressLine3' => $row->AddressLine3,
		'Province' => $row->Province,
		'City' => $row->City,
		'Country' => $row->Country,
		'ResidentialStatus' => $row->ResidentialStatus,
		'Profession' => $row->Profession,
		'SourceOfIncome' => $row->SourceOfIncome,
		'GrossMonthlyIncome' => $row->GrossMonthlyIncome,
		'Branch' => $row->Branch,
		'LastUpdatedOn' => $row->LastUpdatedOn,
		'CreatedOn' => $row->CreatedOn,
	    );
			$res['status'] = 'success';
			$res['data'] = $data;
		} else {
			$res['status'] = 'error';

		}
		echo json_encode($res);
    }
    public function get_details($id)
    {
        $row = $this->Individual_customers_model->get_by_id($id);
        if ($row) {
            $data = array(
		'id' => $row->id,
		'ClientId' => $row->ClientId,
		'Title' => $row->Title,
		'Firstname' => $row->Firstname,
		'Middlename' => $row->Middlename,
		'Lastname' => $row->Lastname,
		'Gender' => $row->Gender,
		'DateOfBirth' => $row->DateOfBirth,
		'EmailAddress' => $row->EmailAddress,
		'PhoneNumber' => $row->PhoneNumber,
		'AddressLine1' => $row->AddressLine1,
		'AddressLine2' => $row->AddressLine2,
		'AddressLine3' => $row->AddressLine3,
		'Province' => $row->Province,
		'City' => $row->City,
		'Country' => $row->Country,
		'ResidentialStatus' => $row->ResidentialStatus,
		'Profession' => $row->Profession,
		'SourceOfIncome' => $row->SourceOfIncome,
		'GrossMonthlyIncome' => $row->GrossMonthlyIncome,
		'Branch' => $row->Branch,
		'LastUpdatedOn' => $row->LastUpdatedOn,
		'CreatedOn' => $row->CreatedOn,
	    );
			$this->load->view('admin/header');
			$this->load->view('individual_customers/individual_customers_read',$data);
			$this->load->view('admin/footer');
        } else {
            $this->session->set_flashdata('message', 'Record Not Found');
            redirect(site_url('individual_customers'));
        }
    }

    public function create() 
    {
        $data = array(
            'button' => 'Create',
            'action' => site_url('individual_customers/create_action'),
	    'id' => set_value('id'),
	    'ClientId' => set_value('ClientId'),
	    'Title' => set_value('Title'),
	    'Firstname' => set_value('Firstname'),
	    'Middlename' => set_value('Middlename'),
	    'Lastname' => set_value('Lastname'),
	    'Gender' => set_value('Gender'),
            'Marital_status' => set_value('Marital_status'),

	    'DateOfBirth' => set_value('DateOfBirth'),
	    'EmailAddress' => set_value('EmailAddress'),
	    'PhoneNumber' => set_value('PhoneNumber'),
	    'AddressLine1' => set_value('AddressLine1'),
	    'AddressLine2' => set_value('AddressLine2'),
	    'AddressLine3' => set_value('AddressLine3'),
	    'Province' => set_value('Province'),
	    'City' => set_value('City'),
	    'Country' => set_value('Country'),
	    'ResidentialStatus' => set_value('ResidentialStatus'),
	    'Profession' => set_value('Profession'),
	    'SourceOfIncome' => set_value('SourceOfIncome'),
	    'GrossMonthlyIncome' => set_value('GrossMonthlyIncome'),
	    'Branch' => set_value('Branch'),
	    'LastUpdatedOn' => set_value('LastUpdatedOn'),
	    'CreatedOn' => set_value('CreatedOn'),
	    'narration' => set_value('narration'),
	);
		$menu_toggle['toggles'] = 43;
		$this->load->view('admin/header', $menu_toggle);
		$this->load->view('individual_customers/individual_customers_form',$data);
		$this->load->view('admin/footer');
    }
    
    public function create_action() 
    {
    	$dd = $this->Individual_customers_model->count_it();
    	$d1 = $this->Corporate_customers_model->count_it();
        $this->_rules();

        if ($this->form_validation->run() == FALSE) {
            $this->create();
        } else {
			$db_debug = $this->db->db_debug;
			$this->db->db_debug = false;

			$created = false;
			$last_error = array('code' => 0, 'message' => '');
			$max_attempts = 3;

			for ($attempt = 1; $attempt <= $max_attempts; $attempt++) {
				$clientid = $this->generate_unique_client_id();
				if (empty($clientid)) {
					$last_error = array('code' => 1, 'message' => 'Failed to generate unique customer ID');
					break;
				}

            $data = array(
		'ClientId' => $clientid,
		'Title' => $this->input->post('Title',TRUE),
		'Firstname' => $this->input->post('Firstname',TRUE),
		'Middlename' => $this->input->post('Middlename',TRUE),
		'Lastname' => $this->input->post('Lastname',TRUE),
		'Gender' => $this->input->post('Gender',TRUE),
                'Marital_status' => $this->input->post('Marital_status',TRUE),
		'DateOfBirth' => $this->input->post('DateOfBirth',TRUE),
		'EmailAddress' => $this->input->post('EmailAddress',TRUE),
		'PhoneNumber' => $this->input->post('PhoneNumber',TRUE),
		'AddressLine1' => $this->input->post('AddressLine1',TRUE),
		'AddressLine2' => $this->input->post('AddressLine2',TRUE),
		'AddressLine3' => $this->input->post('AddressLine3',TRUE),
		'Province' => $this->input->post('Province',TRUE),
		'City' => $this->input->post('City',TRUE),
		'Country' => $this->input->post('Country',TRUE),
		'ResidentialStatus' => $this->input->post('ResidentialStatus',TRUE),
		'Profession' => $this->input->post('Profession',TRUE),
		'SourceOfIncome' => $this->input->post('SourceOfIncome',TRUE),
		'GrossMonthlyIncome' => $this->input->post('GrossMonthlyIncome',TRUE),
		'Branch' => $this->input->post('Branch',TRUE),
		'narration' => $this->input->post('narration',TRUE),
		'added_by' => $this->session->userdata('user_id')

	    );

            $this->db->trans_begin();

            $insert_id = $this->Individual_customers_model->insert($data);

			$last_error = $this->db->error();
			if (empty($insert_id) || (!empty($last_error['code']) && (int)$last_error['code'] !== 0)) {
				$this->db->trans_rollback();
				if ($this->is_duplicate_entry_error($last_error) && $attempt < $max_attempts) {
					continue;
				}
				break;
			}

			$account_inserted = false;
			$base_account = $this->get_next_customer_account_number(0);
			for ($account_attempt = 0; $account_attempt < 5; $account_attempt++) {
				$account = (int)$base_account + $account_attempt;

				// Avoid duplicate insert errors inside an active transaction.
				// A single failed query marks CI transaction status as FALSE permanently.
				$account_exists_query = $this->db
					->select('account_number')
					->from('account')
					->where('account_number', $account)
					->limit(1)
					->get();

				if ($account_exists_query === false) {
					$last_error = $this->db->error();
					break;
				}

				$account_exists = $account_exists_query->row();

				if (!empty($account_exists)) {
					continue;
				}

				$account_data = array(
					'client_id' => $insert_id,
					'account_number' => $account,
					'balance' => 0,
					'account_type' => 1,
					'account_type_product' => 2,
					'added_by' => $this->session->userdata('user_id'),
				);

				$this->db->insert('account', $account_data);
				$last_error = $this->db->error();

				if (empty($last_error['code']) || (int)$last_error['code'] === 0) {
					$account_inserted = true;
					break;
				}

				if ($this->is_duplicate_entry_error($last_error)) {
					continue;
				}

				break;
			}

			if (!$account_inserted) {
				$this->db->trans_rollback();
				if ($this->is_duplicate_entry_error($last_error) && $attempt < $max_attempts) {
					continue;
				}
				break;
			}

            $kyc_data = array(
                'IDType' => $this->input->post('IDType', TRUE),
                'IDNumber' => $this->input->post('IDNumber', TRUE),
                'IssueDate' => $this->input->post('IssueDate', TRUE),
                'ExpiryDate' => $this->input->post('ExpiryDate', TRUE),
                'ClientId' => $clientid,
                'photograph' => $this->input->post('photograph', TRUE),
                'signature' => $this->input->post('signature', TRUE),
                'Id_back' => $this->input->post('Id_back', TRUE),
                'id_front' => $this->input->post('id_front', TRUE),
            );
            $this->Proofofidentity_model->insert($kyc_data);

			$last_error = $this->db->error();
			if ($this->db->trans_status() === FALSE || (!empty($last_error['code']) && (int)$last_error['code'] !== 0)) {
				$this->db->trans_rollback();
				if ($this->is_duplicate_entry_error($last_error) && $attempt < $max_attempts) {
					continue;
				}
				break;
			}

			$this->db->trans_commit();

			$logger = array(
				'user_id' => $this->session->userdata('user_id'),
				'activity' => 'Register a customer'.$data['Firstname'].' '.$data['Lastname']
			);
			log_activity($logger);

			$created = true;
			break;
			}

			$this->db->db_debug = $db_debug;

			if ($created) {
                $this->toaster->success('Success, customer was created pending Approval');
                redirect(site_url('individual_customers/my_customers'));
			} else {
				if ($this->is_duplicate_entry_error($last_error)) {
					$this->toaster->error('Error, duplicate reference detected while creating customer. Please retry.');
				} else {
					$this->toaster->error('Error, failed to create customer. Please try again.');
				}
				redirect(site_url('individual_customers/create'));
            }
        }
    }
    
    public function update($id) 
    {
        $row = $this->Individual_customers_model->get_by_id($id);

        if ($row) {
            $data = array(
                'button' => 'Update',
                'action' => site_url('individual_customers/update_action'),
		'id' => set_value('id', $row->id),
		'ClientId' => set_value('ClientId', $row->ClientId),
		'Title' => set_value('Title', $row->Title),
		'Firstname' => set_value('Firstname', $row->Firstname),
		'Middlename' => set_value('Middlename', $row->Middlename),
		'Lastname' => set_value('Lastname', $row->Lastname),
		'Gender' => set_value('Gender', $row->Gender),
		'DateOfBirth' => set_value('DateOfBirth', $row->DateOfBirth),
		'EmailAddress' => set_value('EmailAddress', $row->EmailAddress),
		'PhoneNumber' => set_value('PhoneNumber', $row->PhoneNumber),
		'AddressLine1' => set_value('AddressLine1', $row->AddressLine1),
		'AddressLine2' => set_value('AddressLine2', $row->AddressLine2),
		'AddressLine3' => set_value('AddressLine3', $row->AddressLine3),
		'Province' => set_value('Province', $row->Province),
		'City' => set_value('City', $row->City),
		'Country' => set_value('Country', $row->Country),
		'ResidentialStatus' => set_value('ResidentialStatus', $row->ResidentialStatus),
		'Profession' => set_value('Profession', $row->Profession),
		'SourceOfIncome' => set_value('SourceOfIncome', $row->SourceOfIncome),
		'GrossMonthlyIncome' => set_value('GrossMonthlyIncome', $row->GrossMonthlyIncome),
		'Branch' => set_value('Branch', $row->Branch),
		'LastUpdatedOn' => set_value('LastUpdatedOn', $row->LastUpdatedOn),
		'CreatedOn' => set_value('CreatedOn', $row->CreatedOn),
		'added_by' => set_value('added_by', $row->added_by),
		'narration' => set_value('narration', $row->narration),
	    );
			$menu_toggle['toggles'] = 43;
			$this->load->view('admin/header', $menu_toggle);
			$this->load->view('individual_customers/individual_customers_edit',$data);
			$this->load->view('admin/footer');

			    } else {
            $this->session->set_flashdata('message', 'Record Not Found');
            redirect(site_url('individual_customers'));
        }
    }
    
    public function update_action() 
    {
        $this->_rules();

        if ($this->form_validation->run() == FALSE) {
            $this->update($this->input->post('id', TRUE));
        } else {
            $data = array(

		'Title' => $this->input->post('Title',TRUE),
		'Firstname' => $this->input->post('Firstname',TRUE),
		'Middlename' => $this->input->post('Middlename',TRUE),
		'Lastname' => $this->input->post('Lastname',TRUE),
		'Gender' => $this->input->post('Gender',TRUE),
		'DateOfBirth' => $this->input->post('DateOfBirth',TRUE),
		'EmailAddress' => $this->input->post('EmailAddress',TRUE),
		'PhoneNumber' => $this->input->post('PhoneNumber',TRUE),
		'AddressLine1' => $this->input->post('AddressLine1',TRUE),
		'AddressLine2' => $this->input->post('AddressLine2',TRUE),
		'AddressLine3' => $this->input->post('AddressLine3',TRUE),
		'Province' => $this->input->post('Province',TRUE),
		'City' => $this->input->post('City',TRUE),
		'Country' => $this->input->post('Country',TRUE),
		'ResidentialStatus' => $this->input->post('ResidentialStatus',TRUE),
		'Profession' => $this->input->post('Profession',TRUE),
		'SourceOfIncome' => $this->input->post('SourceOfIncome',TRUE),
		'GrossMonthlyIncome' => $this->input->post('GrossMonthlyIncome',TRUE),
		'Branch' => $this->input->post('Branch',TRUE),
		'LastUpdatedOn' => $this->input->post('LastUpdatedOn',TRUE),
		'CreatedOn' => $this->input->post('CreatedOn',TRUE),
		'added_by' => $this->input->post('added_by',TRUE),
		'narration' => $this->input->post('narration',TRUE),
	    );
			$logger = array(

				'user_id' => $this->session->userdata('user_id'),
				'activity' => 'Update customer info of'.' '.$data['Firstname'].' '.$data['Lastname']

			);
			log_activity($logger);

			 $this->Individual_customers_model->update($this->input->post('id', TRUE),$data);

			$this->toaster->success('Success, employee was created please pending Approval');


            redirect($_SERVER['HTTP_REFERER']);
        }
    }
    
    public function delete($id) 
    {
        $row = $this->Individual_customers_model->get_by_id($id);

        if ($row) {

			$logger = array(

				'user_id' => $this->session->userdata('user_id'),
				'activity' => 'Delete a customer'

			);
			log_activity($logger);
            $data = array(
                'approval_status'=>'Archived'
            );
            $this->Individual_customers_model->update($id, $data);
            $this->Account_model->update_approval($id,array('account_status'=>'Inactive'));
            $this->toaster->success('Delete Record Success');
            redirect($_SERVER["HTTP_REFERER"]);
        } else {
			$this->toaster->error('Delete Record Failed');
			redirect($_SERVER["HTTP_REFERER"]);
        }
    }
    public function blacklist($id)
    {
        $row = $this->Individual_customers_model->get_by_id($id);

        if ($row) {

			$logger = array(

				'user_id' => $this->session->userdata('user_id'),
				'activity' => 'Blacklisted a customer'

			);
			log_activity($logger);
			$data = array(
			    'approval_status'=>'Blacklisted'
            );
            $this->Individual_customers_model->update($id,$data);
            $this->Account_model->update_approval($id,array('account_status'=>'Inactive'));
            $this->toaster->success('Blacklist of customer Succeeded');
            redirect($_SERVER["HTTP_REFERER"]);
        } else {
			$this->toaster->error('Delete Record Failed');
			redirect($_SERVER["HTTP_REFERER"]);
        }
    }
    public function approval_action($id)
    {
        $row = $this->Individual_customers_model->get_by_id($id);
    $notify = get_by_id('sms_settings','id','1');
        if ($row) {
            $this->Individual_customers_model->update($id,array('approval_status'=>'Approved'));
            $this->Account_model->update_approval($id,array('account_status'=>'Active'));
            if($notify->customer_approval=='Yes'){
                send_sms($row->PhoneNumber,'Dear customer, registration has been approved, you can call numbers below for more');
            }
            $this->toaster->success('Customer was approved successfully');
            redirect(site_url('individual_customers/approve'));
        } else {

        }

    }
    public function recommend_action($id)
    {
        $row = $this->Individual_customers_model->get_by_id($id);

        if ($row) {
            $this->Individual_customers_model->update($id,array('approval_status'=>'Recommended'));


            $this->toaster->success('Customer was recommended successfully');
            redirect($_SERVER['HTTP_REFERER']);
        } else {

        }
    }public function activate($id)
    {
        $row = $this->Individual_customers_model->get_by_id($id);

        if ($row) {
            $this->Individual_customers_model->update($id,array('approval_status'=>'Approved'));
            $this->Account_model->update_approval($id,array('account_status'=>'Active'));

            $this->toaster->success('Customer was restored successfully');
            redirect($_SERVER["HTTP_REFERER"]);
        } else {

        }
    }
 public function reject_action($id)
    {
        $row = $this->Individual_customers_model->get_by_id($id);

        if ($row) {
            $this->Individual_customers_model->update($id,array('approval_status'=>'Rejected'));
            $this->toaster->success('Customer was rejected successfully');
            redirect(site_url('individual_customers/approve'));
        } else {

        }
    }

    public function _rules() 
    {

	$this->form_validation->set_rules('Title', 'title', 'trim|required');
	$this->form_validation->set_rules('Firstname', 'firstname', 'trim|required');

	$this->form_validation->set_rules('Lastname', 'lastname', 'trim|required');
	$this->form_validation->set_rules('Gender', 'gender', 'trim|required');
	$this->form_validation->set_rules('DateOfBirth', 'dateofbirth', 'trim|required');

	$this->form_validation->set_rules('PhoneNumber', 'phonenumber', 'trim|required');
	$this->form_validation->set_rules('AddressLine1', 'addressline1', 'trim|required');

	$this->form_validation->set_rules('Province', 'province', 'trim|required');
	$this->form_validation->set_rules('City', 'city', 'trim|required');
	$this->form_validation->set_rules('Country', 'country', 'trim|required');

	$this->form_validation->set_rules('Profession', 'profession', 'trim|required');
	$this->form_validation->set_rules('SourceOfIncome', 'sourceofincome', 'trim|required');
	$this->form_validation->set_rules('GrossMonthlyIncome', 'grossmonthlyincome', 'trim|required|numeric');
	$this->form_validation->set_rules('Branch', 'branch', 'trim|required');


	$this->form_validation->set_rules('id', 'id', 'trim');
	$this->form_validation->set_error_delimiters('<span class="text-danger">', '</span>');
    }
	public function excel($result )
	{

		$namaFile = "customers-dump.xls";
		$judul = "individual_customers";
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
		xlsWriteLabel($tablehead, $kolomhead++, "ClientId");
		xlsWriteLabel($tablehead, $kolomhead++, "Title");
		xlsWriteLabel($tablehead, $kolomhead++, "First name");
		xlsWriteLabel($tablehead, $kolomhead++, "Middle name");
		xlsWriteLabel($tablehead, $kolomhead++, "Last name");
		xlsWriteLabel($tablehead, $kolomhead++, "Gender");
		xlsWriteLabel($tablehead, $kolomhead++, "Date Of Birth");
		xlsWriteLabel($tablehead, $kolomhead++, "Email Address");
		xlsWriteLabel($tablehead, $kolomhead++, "Phone Number");
		xlsWriteLabel($tablehead, $kolomhead++, "Address Line1");
		xlsWriteLabel($tablehead, $kolomhead++, "Address Line2");
		xlsWriteLabel($tablehead, $kolomhead++, "Address Line3");
		xlsWriteLabel($tablehead, $kolomhead++, "Province");
		xlsWriteLabel($tablehead, $kolomhead++, "Village");
		xlsWriteLabel($tablehead, $kolomhead++, "TA");
		xlsWriteLabel($tablehead, $kolomhead++, "City");
		xlsWriteLabel($tablehead, $kolomhead++, "Country");
		xlsWriteLabel($tablehead, $kolomhead++, "Marital Status");
		xlsWriteLabel($tablehead, $kolomhead++, "Residential Status");
		xlsWriteLabel($tablehead, $kolomhead++, "Profession");
		xlsWriteLabel($tablehead, $kolomhead++, "Source Of Income");
		xlsWriteLabel($tablehead, $kolomhead++, "Gross Monthly Income");
		xlsWriteLabel($tablehead, $kolomhead++, "Bank name");
		xlsWriteLabel($tablehead, $kolomhead++, "Bank Branch");
		xlsWriteLabel($tablehead, $kolomhead++, "Bank Account name");
		xlsWriteLabel($tablehead, $kolomhead++, "Account Number");
		xlsWriteLabel($tablehead, $kolomhead++, "Branch");
		xlsWriteLabel($tablehead, $kolomhead++, "Last Updated On");
		xlsWriteLabel($tablehead, $kolomhead++, "Created On");
		xlsWriteLabel($tablehead, $kolomhead++, "System Date");
		xlsWriteLabel($tablehead, $kolomhead++, "Approval Status");
		xlsWriteLabel($tablehead, $kolomhead++, "Added By");

		foreach ($result as $data) {
			$kolombody = 0;

			//ubah xlsWriteLabel menjadi xlsWriteNumber untuk kolom numeric
			xlsWriteNumber($tablebody, $kolombody++, $nourut);
			xlsWriteLabel($tablebody, $kolombody++, $data->ClientId);
			xlsWriteLabel($tablebody, $kolombody++, $data->Title);
			xlsWriteLabel($tablebody, $kolombody++, $data->cfname);
			xlsWriteLabel($tablebody, $kolombody++, $data->cmname);
			xlsWriteLabel($tablebody, $kolombody++, $data->clname);
			xlsWriteLabel($tablebody, $kolombody++, $data->cgender);
			xlsWriteLabel($tablebody, $kolombody++, $data->cdob);
			xlsWriteLabel($tablebody, $kolombody++, $data->cemail);
			xlsWriteLabel($tablebody, $kolombody++, $data->PhoneNumber);
			xlsWriteLabel($tablebody, $kolombody++, $data->AddressLine1);
			xlsWriteLabel($tablebody, $kolombody++, $data->AddressLine2);
			xlsWriteLabel($tablebody, $kolombody++, $data->AddressLine3);
			xlsWriteLabel($tablebody, $kolombody++, $data->Province);
			xlsWriteLabel($tablebody, $kolombody++, $data->Village);
			xlsWriteLabel($tablebody, $kolombody++, $data->TA);
			xlsWriteLabel($tablebody, $kolombody++, $data->City);
			xlsWriteLabel($tablebody, $kolombody++, $data->geoname);
			xlsWriteLabel($tablebody, $kolombody++, $data->Marital_status);
			xlsWriteLabel($tablebody, $kolombody++, $data->ResidentialStatus);
			xlsWriteLabel($tablebody, $kolombody++, $data->Profession);
			xlsWriteLabel($tablebody, $kolombody++, $data->SourceOfIncome);
			xlsWriteNumber($tablebody, $kolombody++, $data->GrossMonthlyIncome);
			xlsWriteLabel($tablebody, $kolombody++, $data->bankname);
			xlsWriteLabel($tablebody, $kolombody++, $data->bank_branch);
			xlsWriteLabel($tablebody, $kolombody++, $data->banckAccountname);
			xlsWriteLabel($tablebody, $kolombody++, $data->accountNumber);
			xlsWriteLabel($tablebody, $kolombody++, $data->Branch);
			xlsWriteLabel($tablebody, $kolombody++, $data->LastUpdatedOn);
			xlsWriteLabel($tablebody, $kolombody++, $data->CreatedOn);
			xlsWriteLabel($tablebody, $kolombody++, $data->system_date);
			xlsWriteLabel($tablebody, $kolombody++, $data->approval_status);
			xlsWriteLabel($tablebody, $kolombody++, $data->efname." ".$data->elname);

			$tablebody++;
			$nourut++;
		}

		xlsEOF();
		exit();
	}

}

