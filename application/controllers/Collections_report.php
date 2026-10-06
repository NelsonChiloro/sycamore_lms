<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Loan Collections Report Controller
 */
class Collections_report extends CI_Controller {

    public function __construct() {
        parent::__construct();
        // Load necessary models
        $this->load->model('Loan_model');
        $this->load->model('Groups_model');
        $this->load->model('Individual_customers_model');
        $this->load->model('Payement_schedules_model');
        // Load custom helpers
    }

    /**
     * Default method - displays the collections report filter form
     */
    public function index() {
        $data = array();

        // Get filter parameters
        $branch = $this->input->get('branch') ? $this->input->get('branch') : 'All';
        $loan_officer = $this->input->get('user') ? $this->input->get('user') : 'All';
        $period = $this->input->get('period') ? $this->input->get('period') : null;
        $from_date = $this->input->get('from') ;
        $to_date = $this->input->get('to') ;

        // Load data for filtering options
        $data['users'] = get_all('employees');
        $data['branches'] = get_all('branches');

        // Set filter values for view
        $data['selected_branch'] = $branch;
        $data['selected_officer'] = $loan_officer;
        $data['selected_period'] = $period;
        $data['from_date'] = $from_date;
        $data['to_date'] = $to_date;

        // Load view
        $this->load->view('admin/header');
        $this->load->view('loan/collections_report', $data);
        $this->load->view('admin/footer');
    }

    /**
     * Process collections report via Node.js backend
     */
    public function collections_filter()
    {
        $branch = $this->input->post('branch');
        $officer = $this->input->post('officer');
        $period = $this->input->post('period');
        $date_from = $this->input->post('date_from');
        $date_to = $this->input->post('date_to');
        if($branch=='All'){
            $branch_name = "All Branches";
        }else{
            $branch_name = get_by_id('branches','id',$branch)->BranchName;
        }
      if($officer=='All'){
            $officer_name = "All Officers";
        }else{
            $officer_details = get_by_id('employees','id',$officer);
            $officer_name = $officer_details->Firstname." ".$officer_details->Lastname;

        }
        // Initialize cURL session
        $ch = curl_init();

        // Set the URL of the endpoint through the shared helper
        $url = report_service_url('generate-report-collections');

        // Prepare the data to be sent
        $data = array_merge([
            "report_type" => "LOAN_COLLECTIONS",
            "user" => $this->session->userdata('Firstname')." ".$this->session->userdata('Lastname'),
            "user_id" => $this->session->userdata('user_id'),
            "branch" => $branch,
            "branch_name" => $branch_name,
            "officer" => $officer,
            "officer_name" => $officer_name,
            "period" => $period,
            "date_from" => $date_from,
            "date_to" => $date_to
        ], report_supervisor_curl_payload());

        // Convert the data array to JSON
        $jsonData = json_encode($data);

        // Set cURL options
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($jsonData)
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);

        // Execute the cURL request
        $response = curl_exec($ch);

        // Check for errors
        if (curl_errno($ch)) {
            $this->toaster->error('Error: ' . curl_error($ch));
        } else {
            // Success message
            $this->toaster->success('Success! Loan Collections Report is being processed. You may continue with other tasks and check back later for the report.');
        }

        // Close the cURL session
        curl_close($ch);

        // Redirect to main Report controller
        redirect(site_url('report'));
    }
}