<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Arrears_report extends CI_Controller {

    public function __construct() {
        parent::__construct();
        // Load necessary models
        $this->load->model('Loan_model');
        $this->load->model('Employees_model');
        $this->load->model('Individual_customers_model');
        $this->load->model('Branches_model');
        $this->load->helper('form');

    }

    public function index() {
        // Get all loan officers for the filter dropdown
        $data['officers'] = $this->Employees_model->get_all();

        // Set default date range (last 30 days)
        $data['start_date'] = date('Y-m-d', strtotime('-30 days'));
        $data['end_date'] = date('Y-m-d');

        // Load the view
        $menu_toggle['toggles'] = 40;

        $this->load->view('admin/header', $menu_toggle);
        $this->load->view('reports/arrears_filter', $data);
        $this->load->view('admin/footer');
    }

    public function generate() {
        // Get filter parameters
        $start_date = $this->input->post('start_date');
        $end_date = $this->input->post('end_date');
        $officer_id = $this->input->post('officer_id');
        $branch_id = $this->input->post('branch_id');

        // Get officer name if an officer is selected
        $officer_name = 'All Officers';
        if (!empty($officer_id) && $officer_id !== 'All') {
            $officer = $this->Employees_model->get_by_id($officer_id);
            $officer_name = $officer ? $officer->Firstname . ' ' . $officer->Lastname : 'Unknown';
        }

        // Get branch name if a branch is selected
        $branch_name = 'All Branches';
        if (!empty($branch_id) && $branch_id !== 'All') {
            $branch = $this->Branches_model->get_by_id($branch_id);
            $branch_name = $branch ? $branch->BranchName : 'Unknown';
        }

        // Initialize cURL session
        $ch = curl_init();

        // Set the URL of the Node.js endpoint through the shared helper
        $url = report_service_url('generate-report-arrears');

        // Prepare the data to be sent
        $data = [
            "report_type" => "Arrears Report",
            "user" => $this->session->userdata('Firstname')." ".$this->session->userdata('Lastname'),
            "user_id" => $this->session->userdata('user_id'),
            "start_date" => $start_date,
            "end_date" => $end_date,
            "officer_id" => $officer_id,
            "officer_name" => $officer_name,
            "branch_id" => $branch_id,
            "branch_name" => $branch_name
        ];

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
            echo 'Error:' . curl_error($ch);
        } else {
            // Show success message and redirect
            $this->toaster->success('Success! Arrears Report is being processed. You may do other things and come back to check progress.');
            redirect(site_url('report'));
        }

        // Close the cURL session
        curl_close($ch);
    }


    /**
     * Get loans in arrears based on filter criteria
     */
// Fix for the SQL syntax error in the get_loans_in_arrears function

    private function get_loans_in_arrears($start_date = null, $end_date = null, $officer_id = null, $branch = null) {
        // Query to get loans with missed payments
        $this->db->select('
        l.loan_id, 
        l.loan_number, 
        l.loan_customer, 
        l.customer_type, 
        lp.product_name, 
        l.loan_date, 
        l.loan_principal as amount_disbursed, 
        (ps.interest + ps.padmin_fee + ps.ploan_cover) as loan_charges,
        l.loan_period as term,
        l.period_type as repayment_frequency,
        l.loan_amount_term  as repayment_amount,
        l.loan_added_by as officer_id,
        branches.BranchName,
        ps.paid_amount as loan_paid_amount,
        MAX(ps.payment_schedule) as due_date,
        SUM(CASE WHEN ps.status IN ("NOT PAID", "PARTIAL PAID") AND ps.payment_schedule < CURDATE() THEN 1 ELSE 0 END) as num_missed_payments,
        SUM(CASE WHEN ps.status IN ("NOT PAID", "PARTIAL PAID") AND ps.payment_schedule < CURDATE() THEN (ps.amount - ps.paid_amount) ELSE 0 END) as total_arrears,
        MAX(ps.paid_date) as last_transaction_date,
        DATEDIFF(CURDATE(), MIN(CASE WHEN ps.status IN ("NOT PAID", "PARTIAL PAID") AND ps.payment_schedule < CURDATE() THEN ps.payment_schedule ELSE NULL END)) as arrear_days
    ');

        $this->db->from('loan l');
        $this->db->join('payement_schedules ps', 'ps.loan_id = l.loan_id', 'left');
        $this->db->join('branches', 'branches.id = l.branch');
        $this->db->join('loan_products lp', 'lp.loan_product_id = l.loan_product');
        $this->db->join('individual_customers ic', 'l.loan_customer = ic.id AND l.customer_type = "individual"', 'left');
        $this->db->join('groups g', 'l.loan_customer = g.group_id AND l.customer_type = "group"', 'left');

        // Add date filters only if both start and end dates are provided
        if (!empty($start_date) && !empty($end_date)) {
            $this->db->where('l.loan_date >=', $start_date);
            $this->db->where('l.loan_date <=', $end_date);
        }

        // Add officer filter if specified
        if (!empty($officer_id)) {
            $this->db->where('l.loan_added_by', $officer_id);
        }
        // Filter by branch if specified
        if (!empty($branch)) {
            $this->db->where('l.branch', $branch);
        }


        // Only active and disbursed loans
        $this->db->where('l.loan_status IN ("APPROVED", "ACTIVE")');
        $this->db->where('l.disbursed', 'Yes');

        // Having clause to filter loans actually in arrears
        $this->db->having('num_missed_payments >', 0);

        // Group by loan
        $this->db->group_by('l.loan_id');

        // Order by arrear days (highest first)
        $this->db->order_by('arrear_days', 'DESC');

        $query = $this->db->get();
        $loans = $query->result_array();

        // Get additional customer information
        foreach ($loans as &$loan) {
            if ($loan['customer_type'] == 'individual') {
                $customer = $this->db->get_where('individual_customers', ['id' => $loan['loan_customer']])->row_array();
                $loan['client_name'] = $customer ? $customer['Firstname'] . ' ' . $customer['Lastname'] : 'Unknown';
                $loan['group_name'] = 'N/A';
                // Get branch for individual
                $branch_id = $customer ? $customer['Branch'] : null;
            } else {
                $group = $this->db->get_where('groups', ['group_id' => $loan['loan_customer']])->row_array();
                $loan['client_name'] = 'N/A';
                $loan['group_name'] = $group ? $group['group_name'] . ' (' . $group['group_code'] . ')' : 'Unknown';
                // Get branch for group
                $branch_id = $group ? $group['branch'] : null;
            }

            // Get branch name


            // Get loan officer name
            $officer = $this->db->get_where('employees', ['id' => $loan['officer_id']])->row_array();
            $loan['officer_name'] = $officer ? $officer['Firstname'] . ' ' . $officer['Lastname'] : 'Unknown';
        }

        return $loans;
    }

    /**
     * Export the report to Excel
     */
    public function export_excel() {
        // Get filter parameters from the session or query parameters
        $start_date = $this->input->get('start_date') ? $this->input->get('start_date') : date('Y-m-d', strtotime('-30 days'));
        $end_date = $this->input->get('end_date') ? $this->input->get('end_date') : date('Y-m-d');
        $officer_id = $this->input->get('officer_id');

        // Get the loans in arrears based on filters
        $loans_in_arrears = $this->get_loans_in_arrears($start_date, $end_date, $officer_id);

        // Load PHPExcel library
        $this->load->library('Excel');

        // Create new PHPExcel object
        $objPHPExcel = new PHPExcel();

        // Set document properties
        $objPHPExcel->getProperties()->setCreator("Finance System")
            ->setLastModifiedBy("Finance System")
            ->setTitle("Arrears Report")
            ->setSubject("Arrears Report")
            ->setDescription("Arrears Report generated on " . date('Y-m-d H:i:s'));

        // Add header row
        $objPHPExcel->setActiveSheetIndex(0)
            ->setCellValue('A1', 'Loan No.')
            ->setCellValue('B1', 'Client Name')
            ->setCellValue('C1', 'Group Name')
            ->setCellValue('D1', 'Amount Disbursed')
            ->setCellValue('E1', 'Loan Charges')
            ->setCellValue('F1', 'Term')
            ->setCellValue('G1', 'Repayment Frequency')
            ->setCellValue('H1', 'Repayment Amount')
            ->setCellValue('I1', 'Due Date')
            ->setCellValue('J1', 'No. of Missed Payments')
            ->setCellValue('K1', 'Total Arrears')
            ->setCellValue('L1', 'Last Transaction Date')
            ->setCellValue('M1', 'Arrear Days')
            ->setCellValue('N1', 'Loan Officer');

        // Add data rows
        $row = 2;
        foreach ($loans_in_arrears as $loan) {
            $objPHPExcel->setActiveSheetIndex(0)
                ->setCellValue('A' . $row, $loan['loan_number'])
                ->setCellValue('B' . $row, $loan['client_name'])
                ->setCellValue('C' . $row, $loan['group_name'])
                ->setCellValue('D' . $row, number_format($loan['amount_disbursed'], 2))
                ->setCellValue('E' . $row, number_format($loan['loan_charges'], 2))
                ->setCellValue('F' . $row, $loan['term'])
                ->setCellValue('G' . $row, $loan['repayment_frequency'])
                ->setCellValue('H' . $row, number_format($loan['repayment_amount'], 2))
                ->setCellValue('I' . $row, $loan['due_date'])
                ->setCellValue('J' . $row, $loan['num_missed_payments'])
                ->setCellValue('K' . $row, number_format($loan['total_arrears'], 2))
                ->setCellValue('L' . $row, $loan['last_transaction_date'])
                ->setCellValue('M' . $row, $loan['arrear_days'])
                ->setCellValue('N' . $row, $loan['officer_name']);
            $row++;
        }

        // Auto size columns
        foreach(range('A','N') as $column) {
            $objPHPExcel->getActiveSheet()->getColumnDimension($column)->setAutoSize(true);
        }

        // Set active sheet index to the first sheet
        $objPHPExcel->setActiveSheetIndex(0);

        // Rename sheet
        $objPHPExcel->getActiveSheet()->setTitle('Arrears Report');

        // Set header and footer. When displaying online:
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="Arrears_Report_' . date('Y-m-d') . '.xlsx"');
        header('Cache-Control: max-age=0');

        // Save Excel 2007 file
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save('php://output');
        exit;
    }
}