<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Revenue_report extends CI_Controller {

    public function __construct() {
        parent::__construct();
        // Load necessary models
        $this->load->model('Loan_model');
        $this->load->model('Employees_model');
        $this->load->model('Branches_model');
        $this->load->model('Loan_products_model');
        $this->load->helper('url');
        $this->load->helper('form');
        $this->load->library('session');
    }

    public function index() {
        // Get all loan officers for the filter dropdown
        $data['officers'] = $this->Employees_model->get_all();

        // Get all branches for the filter dropdown
        $data['branches'] = $this->Branches_model->get_all();

        // Set default date range (last 30 days)
        $data['start_date'] = date('Y-m-d', strtotime('-30 days'));
        $data['end_date'] = date('Y-m-d');

        // Load the view
        $menu_toggle['toggles'] = 40;
        // Load the report view
        $this->load->view('admin/header', $menu_toggle);
        $this->load->view('reports/revenue_report_filter', $data);
        $this->load->view('admin/footer');
    }

    public function generate() {
        // Get filter parameters
        $start_date = $this->input->post('start_date');
        $end_date = $this->input->post('end_date');
        $officer_id = $this->input->post('officer_id');
        $branch_id = $this->input->post('branch_id');

        // Prepare data for the view
        $data['start_date'] = $start_date;
        $data['end_date'] = $end_date;
        $data['officer_id'] = $officer_id;
        $data['branch_id'] = $branch_id;

        // Set default date display for empty dates
        $display_start_date = !empty($start_date) ? date('d M Y', strtotime($start_date)) : 'All time';
        $display_end_date = !empty($end_date) ? date('d M Y', strtotime($end_date)) : 'Present';
        $data['date_range_display'] = "$display_start_date to $display_end_date";

        // Get officer name if an officer is selected
        if (!empty($officer_id)) {
            $officer = $this->Employees_model->get_by_id($officer_id);
            $data['officer_name'] = $officer ? $officer->Firstname . ' ' . $officer->Lastname : 'Unknown';
        } else {
            $data['officer_name'] = 'All Officers';
        }

        // Get branch name if a branch is selected
        if (!empty($branch_id)) {
            $branch = $this->Branches_model->get_by_id($branch_id);
            $data['branch_name'] = $branch ? $branch->BranchName : 'Unknown';
        } else {
            $data['branch_name'] = 'All Branches';
        }

        // Get revenue data based on filters
        $data['revenue_data'] = $this->get_revenue_data($start_date, $end_date, $officer_id, $branch_id);

        // Calculate totals
        $totals = array(
            'interest' => 0,
            'admin_fees' => 0,
            'loan_cover' => 0,
            'processing_fees' => 0,
            'penalties' => 0,
            'write_off' => 0,
            'total' => 0
        );

        foreach ($data['revenue_data'] as $item) {
            $totals['interest'] += $item['interest'];
            $totals['admin_fees'] += $item['admin_fees'];
            $totals['loan_cover'] += $item['loan_cover'];
            $totals['processing_fees'] += $item['processing_fees'];
            $totals['penalties'] += $item['penalties'];
            $totals['write_off'] += $item['write_off'];
        }

        $totals['total'] = $totals['interest'] + $totals['admin_fees'] + $totals['loan_cover'] +
            $totals['processing_fees'] + $totals['penalties'] + $totals['write_off'];

        $data['totals'] = $totals;

        // Load the report view
        $menu_toggle['toggles'] = 40;
        // Load the report view
        $this->load->view('admin/header', $menu_toggle);
        $this->load->view('reports/revenue_report_view', $data);
        $this->load->view('admin/footer');
    }

    /**
     * Get revenue data based on filter criteria
     */
    /**
     * Get revenue data based on filter criteria
     */
    private function get_revenue_data($start_date = null, $end_date = null, $officer_id = null, $branch_id = null) {
        // First, get products for grouping


        $result = array();
        $branches = $this->Branches_model->get_all();

        if (!empty($branch_id)) {
            // If branch filter is applied, only get that branch
            $branches = array_filter($branches, function($branch) use ($branch_id) {
                return $branch->id == $branch_id;
            });
        }

        foreach ($branches as $branch) {
            $products = get_all_by_id('loan_products', 'branch', $branch->Code);
        foreach ($products as $product) {

            // For each product, get revenue by branch



                // Get loans for this product and branch using separate select statements for complex expressions
                $this->db->select('
                l.loan_id,
                l.loan_number,
                l.loan_customer,
                l.customer_type,
                l.loan_interest_amount as interest,
                l.admin_fees_amount as admin_fees,
                l.loan_cover_amount as loan_cover,
                0 as processing_fees
            ');

                // Add complex SQL expressions with FALSE parameter to prevent escaping
                $this->db->select('COALESCE((SELECT SUM(penalty_amount) FROM loan_penalties WHERE loan_id = l.loan_id), 0) as penalties', FALSE);
                $this->db->select('CASE WHEN l.loan_status = "WRITTEN_OFF" THEN l.loan_principal ELSE 0 END as write_off', FALSE);

                $this->db->from('loan l');
                $this->db->where('l.loan_product', $product->loan_product_id);
                $this->db->where('l.branch', $branch->id);

                // Apply date filters if provided
                if (!empty($start_date)) {
                    $this->db->where('l.loan_date >=', $start_date);
                }

                if (!empty($end_date)) {
                    $this->db->where('l.loan_date <=', $end_date);
                }

                // Apply officer filter if provided
                if (!empty($officer_id)) {
                    $this->db->where('l.loan_added_by', $officer_id);
                }

                // Only include disbursed loans
                $this->db->where('l.disbursed', 'Yes');

                $loans = $this->db->get()->result_array();

                // Calculate totals for this product and branch
                $interest = 0;
                $admin_fees = 0;
                $loan_cover = 0;
                $processing_fees = 0;
                $penalties = 0;
                $write_off = 0;

                foreach ($loans as $loan) {
                    $interest += $loan['interest'];
                    $admin_fees += $loan['admin_fees'];
                    $loan_cover += $loan['loan_cover'];
                    $processing_fees += $loan['processing_fees'];
                    $penalties += $loan['penalties'];
                    $write_off += $loan['write_off'];
                }

                // Only add to result if there are loans for this product and branch
                if (count($loans) > 0) {
                    $result[] = array(
                        'product_id' => $product->loan_product_id,
                        'product_name' => $product->product_name,
                        'branch_id' => $branch->id,
                        'branch_name' => $branch->BranchName,
                        'interest' => $interest,
                        'admin_fees' => $admin_fees,
                        'loan_cover' => $loan_cover,
                        'processing_fees' => $processing_fees,
                        'penalties' => $penalties,
                        'write_off' => $write_off,
                        'total' => $interest + $admin_fees + $loan_cover + $processing_fees + $penalties + $write_off,
                        'loans_count' => count($loans)
                    );
                }
            }

                                     }

        return $result;
    }
    /**
     * Export the report to Excel
     */
    public function export_excel() {
        // Get filter parameters from the query string
        $start_date = $this->input->get('start_date');
        $end_date = $this->input->get('end_date');
        $officer_id = $this->input->get('officer_id');
        $branch_id = $this->input->get('branch_id');

        // Get revenue data based on filters
        $revenue_data = $this->get_revenue_data($start_date, $end_date, $officer_id, $branch_id);

        // Calculate totals
        $totals = array(
            'interest' => 0,
            'admin_fees' => 0,
            'loan_cover' => 0,
            'processing_fees' => 0,
            'penalties' => 0,
            'write_off' => 0,
            'total' => 0
        );

        foreach ($revenue_data as $item) {
            $totals['interest'] += $item['interest'];
            $totals['admin_fees'] += $item['admin_fees'];
            $totals['loan_cover'] += $item['loan_cover'];
            $totals['processing_fees'] += $item['processing_fees'];
            $totals['penalties'] += $item['penalties'];
            $totals['write_off'] += $item['write_off'];
        }

        $totals['total'] = $totals['interest'] + $totals['admin_fees'] + $totals['loan_cover'] +
            $totals['processing_fees'] + $totals['penalties'] + $totals['write_off'];

        // Load PHPExcel library
        $this->load->library('Excel');

        // Create new PHPExcel object
        $objPHPExcel = new PHPExcel();

        // Set document properties
        $objPHPExcel->getProperties()->setCreator("Finance System")
            ->setLastModifiedBy("Finance System")
            ->setTitle("Revenue Analysis Report")
            ->setSubject("Revenue Analysis Report")
            ->setDescription("Revenue Analysis Report generated on " . date('Y-m-d H:i:s'));

        // Add header row
        $objPHPExcel->setActiveSheetIndex(0)
            ->setCellValue('A1', 'Branch')
            ->setCellValue('B1', 'Product')
            ->setCellValue('C1', 'Interest')
            ->setCellValue('D1', 'Admin Fees')
            ->setCellValue('E1', 'Loan Cover')
            ->setCellValue('F1', 'Processing Fees')
            ->setCellValue('G1', 'Penalties')
            ->setCellValue('H1', 'Write Off Income')
            ->setCellValue('I1', 'Total')
            ->setCellValue('J1', 'Loans Count');

        // Add data rows
        $row = 2;
        foreach ($revenue_data as $item) {
            $objPHPExcel->setActiveSheetIndex(0)
                ->setCellValue('A' . $row, $item['branch_name'])
                ->setCellValue('B' . $row, $item['product_name'])
                ->setCellValue('C' . $row, number_format($item['interest'], 2))
                ->setCellValue('D' . $row, number_format($item['admin_fees'], 2))
                ->setCellValue('E' . $row, number_format($item['loan_cover'], 2))
                ->setCellValue('F' . $row, number_format($item['processing_fees'], 2))
                ->setCellValue('G' . $row, number_format($item['penalties'], 2))
                ->setCellValue('H' . $row, number_format($item['write_off'], 2))
                ->setCellValue('I' . $row, number_format($item['total'], 2))
                ->setCellValue('J' . $row, $item['loans_count']);
            $row++;
        }

        // Add totals row
        $objPHPExcel->setActiveSheetIndex(0)
            ->setCellValue('A' . $row, 'TOTALS')
            ->setCellValue('B' . $row, '')
            ->setCellValue('C' . $row, number_format($totals['interest'], 2))
            ->setCellValue('D' . $row, number_format($totals['admin_fees'], 2))
            ->setCellValue('E' . $row, number_format($totals['loan_cover'], 2))
            ->setCellValue('F' . $row, number_format($totals['processing_fees'], 2))
            ->setCellValue('G' . $row, number_format($totals['penalties'], 2))
            ->setCellValue('H' . $row, number_format($totals['write_off'], 2))
            ->setCellValue('I' . $row, number_format($totals['total'], 2));

        // Style the totals row
        $objPHPExcel->getActiveSheet()->getStyle('A'.$row.':I'.$row)->getFont()->setBold(true);

        // Auto size columns
        foreach(range('A','J') as $column) {
            $objPHPExcel->getActiveSheet()->getColumnDimension($column)->setAutoSize(true);
        }

        // Set active sheet index to the first sheet
        $objPHPExcel->setActiveSheetIndex(0);

        // Rename sheet
        $objPHPExcel->getActiveSheet()->setTitle('Revenue Analysis');

        // Set header and footer. When displaying online:
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="Revenue_Analysis_Report_' . date('Y-m-d') . '.xlsx"');
        header('Cache-Control: max-age=0');

        // Save Excel 2007 file
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save('php://output');
        exit;
    }
}