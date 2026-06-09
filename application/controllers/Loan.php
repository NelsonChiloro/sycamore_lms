<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');
require APPPATH . '/libraries/FPDF.php';

class Loan extends CI_Controller
{
    function __construct()
    {
        parent::__construct();
        $this->load->model('Loan_model');
        $this->load->model('Groups_model');
        $this->load->model('Customer_groups_model');
        $this->load->model('charges_model');
        $this->load->model('Account_model');
        $this->load->model('Individual_customers_model');
        $this->load->model('Loan_products_model');
        $this->load->model('Transactions_model');
        $this->load->model('Group_loan_tracker_model');
        $this->load->model('Tellering_model');
        $this->load->model('Collateral_model');
        $this->load->model('Funds_source_model');

        $this->load->model('Loan_products_model');
        $this->load->model('Payement_schedules_model');
        $this->load->model('Loan_customer_first_drafr_model');
        $this->load->model('Group_batch_model');
        $this->load->model('Employees_model');
        $this->load->library('form_validation');

    }
    public function file_add(){
        $this->load->view('import');
    }

    /** Minimum rows per page for loan list screens. */
    private function loan_list_per_page()
    {
        $per_page = (int) $this->input->get('per_page');
        return $per_page >= 10 ? $per_page : 10;
    }

    private function loan_list_page_number()
    {
        return max(1, (int) $this->input->get('page'));
    }

    private function loan_list_has_filter_params()
    {
        $keys = array('branch', 'product', 'status', 'user', 'supervisor', 'from', 'to', 'loan_number', 'customer_name', 'batch');
        foreach ($keys as $key) {
            $val = $this->input->get($key);
            if ($val !== null && $val !== '' && $val !== 'All') {
                return true;
            }
        }
        return $this->input->get('search') === 'filter';
    }

    /**
     * Standard options for paginated loan list pages (filters + pagination).
     */
    private function loan_list_page_options($method, array $overrides = array())
    {
        $base_path = 'loan/' . $method;
        return array_merge(array(
            'exclude_deleted' => true,
            'use_track_filters' => true,
            'show_loan_filters' => true,
            'base_path' => $base_path,
            'filter_form_action' => base_url($base_path),
        ), $overrides);
    }

    /**
     * Build filter array for paginated loan lists (GET params + page options).
     */
    private function build_loan_list_filters(array $options = array())
    {
        $filters = array(
            'exclude_deleted' => !empty($options['exclude_deleted']),
        );

        $has_user_filters = $this->loan_list_has_filter_params();

        if (!empty($options['use_track_filters'])) {
            $supervisor = $this->input->get('supervisor');
            if ($supervisor !== null && $supervisor !== '' && $supervisor !== 'All') {
                $filters['supervisor'] = (int) $supervisor;
                $filters['supervisor_officer_ids'] = $this->Employees_model->get_officer_ids_under_supervisor($filters['supervisor']);
            } elseif (empty($options['officer_id'])) {
                $filters['user'] = $this->input->get('user') ?: 'All';
            }
            $filters['branch'] = $this->input->get('branch') ?: 'All';
            $filters['product'] = $this->input->get('product') ?: 'All';

            $status = $this->input->get('status');
            if (!empty($options['status_in']) && is_array($options['status_in'])) {
                if ($status !== null && $status !== '' && $status !== 'All') {
                    $filters['status'] = $status;
                } else {
                    $filters['status_in'] = $options['status_in'];
                }
            } elseif ($status !== null && $status !== '' && $status !== 'All') {
                $filters['status'] = $status;
            } elseif (!$has_user_filters && !empty($options['default_status']) && $options['default_status'] !== 'All') {
                $filters['status'] = $options['default_status'];
            }

            $from = trim((string) $this->input->get('from'));
            $to = trim((string) $this->input->get('to'));
            if ($from !== '') {
                $filters['from'] = $from;
            }
            if ($to !== '') {
                $filters['to'] = $to;
            }

            $batch = trim((string) $this->input->get('batch'));
            if ($batch !== '') {
                $filters['batch'] = $batch;
            }
        } elseif (array_key_exists('status', $options) && $options['status'] !== null && $options['status'] !== '') {
            $filters['status'] = $options['status'];
        }

        if (empty($filters['supervisor']) && !empty($options['officer_id'])) {
            $filters['user'] = $options['officer_id'];
        }

        if (!empty($options['batch']) && empty($filters['batch'])) {
            $filters['batch'] = $options['batch'];
        }

        if (!empty($options['disbursed'])) {
            $filters['disbursed'] = $options['disbursed'];
        }

        if (!empty($options['written_off_pending'])) {
            $filters['written_off_pending'] = true;
        }

        $loan_number = trim((string) $this->input->get('loan_number'));
        if ($loan_number !== '') {
            $filters['loan_number'] = $loan_number;
        }

        $customer_name = trim((string) $this->input->get('customer_name'));
        if ($customer_name !== '') {
            $filters['customer_name'] = $customer_name;
        }

        return $filters;
    }

    private function build_loan_pagination_html($base_path, $current_page, $per_page, $total_rows)
    {
        if ($total_rows <= $per_page) {
            return '';
        }

        $total_pages = (int) ceil($total_rows / $per_page);
        $current_page = max(1, min($current_page, $total_pages));
        $base_url = base_url($base_path);

        $params = $_GET;
        unset($params['page']);
        $query = http_build_query($params);
        $joiner = ($query !== '') ? '&' : '';

        $from_row = (($current_page - 1) * $per_page) + 1;
        $to_row = min($current_page * $per_page, $total_rows);

        $html = '<div class="loan-list-pagination d-flex flex-wrap justify-content-between align-items-center mt-3 mb-2">';
        $html .= '<div class="text-muted small">Showing ' . $from_row . '–' . $to_row . ' of ' . number_format($total_rows) . ' loans</div>';
        $html .= '<ul class="pagination pagination-sm mb-0">';

        if ($current_page > 1) {
            $html .= '<li class="page-item"><a class="page-link" href="' . htmlspecialchars($base_url . '?' . $query . $joiner . 'page=' . ($current_page - 1)) . '">Previous</a></li>';
        }

        for ($p = 1; $p <= $total_pages; $p++) {
            if ($total_pages > 9 && $p > 2 && $p < $total_pages - 1 && abs($p - $current_page) > 1) {
                if ($p === 3 || $p === $total_pages - 2) {
                    $html .= '<li class="page-item disabled"><span class="page-link">…</span></li>';
                }
                continue;
            }
            $active = ($p === $current_page) ? ' active' : '';
            $html .= '<li class="page-item' . $active . '"><a class="page-link" href="' . htmlspecialchars($base_url . '?' . $query . $joiner . 'page=' . $p) . '">' . $p . '</a></li>';
        }

        if ($current_page < $total_pages) {
            $html .= '<li class="page-item"><a class="page-link" href="' . htmlspecialchars($base_url . '?' . $query . $joiner . 'page=' . ($current_page + 1)) . '">Next</a></li>';
        }

        $html .= '</ul></div>';
        return $html;
    }

    /**
     * Export format from GET/POST (excel|pdf) or legacy track ?search=export excel.
     */
    private function loan_list_export_format_from_request()
    {
        $export = $this->input->get('export');
        if ($export !== 'excel' && $export !== 'pdf') {
            $export = $this->input->post('export');
        }
        if ($export === 'excel' || $export === 'pdf') {
            return $export;
        }
        $search = $this->input->get('search');
        if ($search === 'export excel') {
            return 'excel';
        }
        if ($search === 'export pdf') {
            return 'pdf';
        }
        return null;
    }

    /**
     * All loans matching list filters (ignores pagination).
     */
    private function fetch_loan_list_export_data(array $filters, array $options = array())
    {
        if (!empty($options['repayment_list'])) {
            return $this->Loan_model->get_loan_repayment_list($filters);
        }
        return $this->Loan_model->get_loan_list_for_export($filters);
    }

    private function export_loan_list_excel(array $filters, array $options = array())
    {
        $loan_data = $this->fetch_loan_list_export_data($filters, $options);
        $this->loan_list_excel_export($loan_data);
    }

    private function export_loan_list_pdf(array $filters, array $options = array())
    {
        $data['loan_data'] = $this->fetch_loan_list_export_data($filters, $options);
        $this->load->library('Pdf');
        $html = $this->load->view('loan/loan_report_pdf', $data, true);
        $this->pdf->createPDF($html, 'loan report as on ' . date('Y-m-d'), true, 'A4', 'landscape');
    }

    /**
     * Load a loan list view (paginated by default; full list for repayment page or customer name filter).
     */
    private function load_paginated_loan_list($view, array $options = array())
    {
        $filters = $this->build_loan_list_filters($options);
        $export_format = $this->loan_list_export_format_from_request();
        if ($export_format === 'excel') {
            $this->export_loan_list_excel($filters, $options);
            return;
        }
        if ($export_format === 'pdf') {
            $this->export_loan_list_pdf($filters, $options);
            return;
        }

        $use_full_list = !empty($options['repayment_list']) || !empty($filters['customer_name']);

        $base_path = !empty($options['base_path']) ? $options['base_path'] : 'loan/' . $this->router->method;

        $data = array(
            'loan_list_full' => $use_full_list,
        );

        if (!empty($options['repayment_list'])) {
            $data['loan_data'] = $this->Loan_model->get_loan_repayment_list($filters);
        } elseif ($use_full_list) {
            $data['loan_data'] = $this->Loan_model->get_loan_list_all($filters, true);
        } else {
            $per_page = $this->loan_list_per_page();
            $page = $this->loan_list_page_number();
            $offset = ($page - 1) * $per_page;
            $total_rows = $this->Loan_model->count_loan_list($filters);
            $data['loan_data'] = $this->Loan_model->get_loan_list_paginated($filters, $per_page, $offset);
            $data['pagination_html'] = $this->build_loan_pagination_html($base_path, $page, $per_page, $total_rows);
            $data['total_rows'] = $total_rows;
            $data['list_offset'] = $offset;
            $data['per_page'] = $per_page;
            $data['current_page'] = $page;
        }

        if (isset($options['batch_filter'])) {
            $data['batch_filter'] = $options['batch_filter'];
        }

        $data['show_loan_filters'] = !isset($options['show_loan_filters']) || $options['show_loan_filters'];
        $data['filter_form_action'] = !empty($options['filter_form_action'])
            ? $options['filter_form_action']
            : base_url($base_path);
        $data['filter_default_status'] = isset($options['default_status']) ? $options['default_status'] : 'All';
        $data['filter_lock_officer'] = !empty($options['officer_id']);
        $data['filter_officer_id'] = !empty($options['officer_id']) ? $options['officer_id'] : null;
        $data['relationship_supervisors'] = $this->Employees_model->get_relationship_supervisors();
        $data['relationship_officers'] = $this->Employees_model->get_relationship_officers(false);

        if (!empty($options['plain_header'])) {
            $this->load->view('admin/header');
        } else {
            $menu_toggle = isset($options['menu_toggle']) ? $options['menu_toggle'] : array('toggles' => 23);
            $this->load->view('admin/header', $menu_toggle);
        }
        $this->load->view($view, $data);
        $this->load->view('admin/footer');
    }

    // Helper function to get customer name for activity logging
    private function get_customer_name_for_loan($loan_id) {
        $loan_details = get_by_id('loan', 'loan_id', $loan_id);
        $customer_name = '';

        if (!empty($loan_details)) {
            $customer_name = $this->resolve_loan_customer_name($loan_details->loan_customer, $loan_details->customer_type);
        }

        return array(
            'customer_name' => $customer_name,
            'loan_details' => $loan_details
        );
    }

    /**
     * Resolve customer display name across id/code mismatches in production data.
     */
    private function resolve_loan_customer_name($loan_customer, $customer_type = null)
    {
        $customer_ref = trim((string)$loan_customer);
        $normalized_type = strtolower(trim((string)$customer_type));

        if ($customer_ref === '') {
            return 'Unknown';
        }

        $try_individual = function () use ($customer_ref) {
            return $this->db->select('Firstname, Lastname, ClientId')
                ->from('individual_customers')
                ->group_start()
                ->where('id', $customer_ref)
                ->or_where('ClientId', $customer_ref)
                ->group_end()
                ->limit(1)
                ->get()
                ->row();
        };

        $try_group = function () use ($customer_ref) {
            return $this->db->select('group_name, group_code')
                ->from('groups')
                ->group_start()
                ->where('group_id', $customer_ref)
                ->or_where('group_code', $customer_ref)
                ->group_end()
                ->limit(1)
                ->get()
                ->row();
        };

        if (in_array($normalized_type, array('individual', 'member'), true)) {
            $individual = $try_individual();
            if (!empty($individual)) {
                return trim((string)$individual->Firstname . ' ' . (string)$individual->Lastname);
            }
            $group = $try_group();
            if (!empty($group)) {
                return trim((string)$group->group_name . ' (' . (string)$group->group_code . ')');
            }
        } elseif (in_array($normalized_type, array('group', 'groups'), true)) {
            $group = $try_group();
            if (!empty($group)) {
                return trim((string)$group->group_name . ' (' . (string)$group->group_code . ')');
            }
            $individual = $try_individual();
            if (!empty($individual)) {
                return trim((string)$individual->Firstname . ' ' . (string)$individual->Lastname);
            }
        } else {
            $individual = $try_individual();
            if (!empty($individual)) {
                return trim((string)$individual->Firstname . ' ' . (string)$individual->Lastname);
            }
            $group = $try_group();
            if (!empty($group)) {
                return trim((string)$group->group_name . ' (' . (string)$group->group_code . ')');
            }
        }

        return 'Unknown';
    }

    private function is_duplicate_entry_error($db_error)
    {
        return is_array($db_error)
            && isset($db_error['code'])
            && (int)$db_error['code'] === 1062;
    }

    private function is_group_zitsamba_product($loan_product)
    {
        if (!$loan_product) {
            return false;
        }

        $product_name = strtoupper(trim((string)($loan_product->product_name ?? '')));
        $product_code = strtoupper(trim((string)($loan_product->product_code ?? '')));

        $normalized_name = preg_replace('/[^A-Z0-9]/', '', $product_name);
        $name_matches = in_array($normalized_name, array(
            'GROUPLOANPRODUCTZITSAMBALL',
            'GROUPLOANPRODUCTZITSAMBABT',
        ), true);

        $code_matches = in_array($product_code, array(
            'GROUPZITSAMBA',
            'GROUPZITSAMBABT',
            'GZITSAMBA',
            'ZTGLBT',
            'ZTGLLL',
        ), true);

        return $name_matches || $code_matches;
    }

    private function get_group_zitsamba_term_validation_error($loan_product_id, $months)
    {
        $loan_product = $this->Loan_products_model->get_by_id($loan_product_id);
        if (!$this->is_group_zitsamba_product($loan_product)) {
            return null;
        }

        if ((int)$months > 4) {
            return 'Maximum term for Group Loan Product - Zitsamba is 4 months.';
        }

        return null;
    }

    private function is_masamba_promotion_lingwe_blantyre($loan_row, $product_row = null)
    {
        if (!$loan_row) {
            return false;
        }

        if (!$product_row && !empty($loan_row->loan_product)) {
            $product_row = $this->Loan_products_model->get_by_id($loan_row->loan_product);
        }
        if (!$product_row) {
            return false;
        }

        $product_code = strtoupper(trim((string)($product_row->product_code ?? '')));
        $product_name = strtoupper(trim((string)($product_row->product_name ?? '')));
        $normalized_name = preg_replace('/[^A-Z0-9]/', '', $product_name);
        $is_masamba = in_array($product_code, array('MASPROLL', 'MASPROBT', 'MASLL', 'MASBT', 'MAIICLL', 'MAIICBT'), true)
            || $normalized_name === 'MASAMBAPROMOTION'
            || $normalized_name === 'MASAMBAMAIIC';

        // Handle legacy Masamba variants like "Masamba (MasLL)" and "Masamba (MasBT)".
        $is_masamba = $is_masamba
            || in_array($normalized_name, array('MASAMBAMASLL', 'MASAMBAMASBT'), true);
        if (!$is_masamba) {
            return false;
        }

        if (in_array($product_code, array('MASPROLL', 'MASPROBT', 'MASLL', 'MASBT', 'MAIICLL', 'MAIICBT'), true)) {
            return true;
        }

        $branch_name = '';
        $branch_code = '';
        if (!empty($loan_row->branch)) {
            $branch = get_branch_for_loan_value($loan_row->branch);
            if ($branch) {
                $branch_name = strtoupper(trim((string)($branch->BranchName ?? '')));
                $branch_code = strtoupper(trim((string)($branch->BranchCode ?? '')));
            }
        }

        return strpos($branch_name, 'LINGWE') !== false
            || strpos($branch_name, 'BLANTYRE') !== false
            || $branch_code === 'LL'
            || $branch_code === 'BT';
    }

    /** Products shown on add-loan / group / calculator forms (all products; no branch filter). */
    private function loan_products_for_current_user()
    {
        return $this->Loan_products_model->get_all();
    }

    /** Edit forms: full product list (same as create; no branch filter). */
    private function loan_products_for_edit()
    {
        return $this->Loan_products_model->get_all();
    }

    /**
     * Branch-based product restriction is disabled; only invalid product id is rejected.
     *
     * @param object|null $product loan_products row
     * @return string|null error message, or null if allowed
     */
    private function validate_loan_product_access_for_user($product)
    {
        if (!$product) {
            return 'Invalid loan product.';
        }
        return null;
    }

    /**
     * Attach late-charge totals used by loan balance summaries and schedule views.
     */
    private function enrich_loan_payments_for_display($payments)
    {
        $this->load->model('Late_charges_model');

        foreach ($payments as &$payment) {
            $payment->total_late_charge = isset($payment->total_late_charge) ? $payment->total_late_charge : 0;
            $payment->total_pay_amount = (float) $payment->amount + (float) $payment->total_late_charge;
        }
        unset($payment);

        return $payments;
    }

    /**
     * @param array $payments
     * @param object $loan_row Loan_model row with loan_amount_total
     * @return object
     */
    private function build_loan_payment_balance_summary($payments, $loan_row)
    {
        return $this->Payement_schedules_model->summarize_loan_balances(
            $payments,
            isset($loan_row->loan_amount_total) ? $loan_row->loan_amount_total : null
        );
    }

    private function branches_for_loan_forms()
    {
        $this->db->order_by('BranchName', 'ASC');
        return $this->db->get('branches')->result();
    }

    /**
     * @return int|null branches.id from POST, or null if missing/invalid
     */
    private function resolve_loan_branch_id_from_post()
    {
        $branch_id = (int) $this->input->post('branch_id');
        if ($branch_id <= 0) {
            return null;
        }

        $branch = get_by_id('branches', 'id', $branch_id);
        return $branch ? (int) $branch->id : null;
    }

    /**
     * Resolve disbursement datetime from user input; defaults to now when empty/invalid.
     *
     * @param string|null $disbursement_date Y-m-d or datetime string
     * @return string Y-m-d H:i:s
     */
    /**
     * Redirect back to the loan workflow list that matches the approval action.
     */
    private function redirect_after_approval_action($action)
    {
        $action = strtoupper(trim((string) $action));
        $return_to = trim((string) $this->input->post('return_to'));
        if ($return_to === '') {
            $return_to = trim((string) $this->input->get('return_to'));
        }
        if ($return_to !== '' && strpos($return_to, 'loan/') === 0) {
            redirect($return_to);
            return;
        }

        switch ($action) {
            case 'APPROVED':
            case 'REJECT':
            case 'REJECTED':
                redirect('loan/initiated');
                return;
            case 'RECOMMENDED':
                redirect('loan/recommend');
                return;
            case 'ACTIVE':
                redirect('loan/approved');
                return;
            default:
                $referer = isset($_SERVER['HTTP_REFERER']) ? (string) $_SERVER['HTTP_REFERER'] : '';
                if (strpos($referer, 'loan/initiated') !== false) {
                    redirect('loan/initiated');
                    return;
                }
                if (strpos($referer, 'loan/recommend') !== false) {
                    redirect('loan/recommend');
                    return;
                }
                if (strpos($referer, 'loan/approved') !== false) {
                    redirect('loan/approved');
                    return;
                }
                if (strpos($referer, 'loan/edit_approve') !== false) {
                    redirect('loan/edit_approve');
                    return;
                }
                redirect('loan/initiated');
        }
    }

    private function resolve_disbursement_datetime($disbursement_date = null)
    {
        if ($disbursement_date === null) {
            $disbursement_date = $this->input->post('disbursement_date');
        }
        if ($disbursement_date === null || $disbursement_date === '') {
            $disbursement_date = $this->input->get('disbursement_date');
        }

        $disbursement_date = trim((string) $disbursement_date);
        if ($disbursement_date === '') {
            return date('Y-m-d H:i:s');
        }

        $timestamp = strtotime($disbursement_date);
        if ($timestamp === false) {
            return date('Y-m-d H:i:s');
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $disbursement_date)) {
            return date('Y-m-d 00:00:00', $timestamp);
        }

        return date('Y-m-d H:i:s', $timestamp);
    }

    public function correct_loan(){
        $this->Loan_model->delete_replace_loans();
    }
    function update_figures(){
        $l = $this->Loan_model->get_all_by_product();
        $count = 0;
        foreach ($l as $item){

            echo $item->loan_principal;
            $count ++;
        }
        echo "total count".$count;
    }
    function test_hhtp(){


        $a = false;
        if ($a){
            http_response_code(200);
        }else{
            http_response_code(401);

        }
        echo json_encode(array('message'=>"zathela","data"=>array("name"=>"misheck")));
    }
// 	function import_preview()
// 	{
// 		if(isset($_FILES["file"]["name"])) {
// 			$path = $_FILES["file"]["tmp_name"];
// 			$object = PHPExcel_IOFactory::load($path);
// 			foreach ($object->getWorksheetIterator() as $worksheet) {
// 				$highestRow = $worksheet->getHighestRow();
// 				$highestColumn = $worksheet->getHighestColumn();
// 				for ($row = 2; $row <= $highestRow; $row++) {


// 					$title = $worksheet->getCellByColumnAndRow(0, $row)->getValue();
// 					$fname = $worksheet->getCellByColumnAndRow(1, $row)->getValue();
// 					$mdame = $worksheet->getCellByColumnAndRow(2, $row)->getValue();
// 					$lastname = $worksheet->getCellByColumnAndRow(3, $row)->getValue();
// 					$gender = $worksheet->getCellByColumnAndRow(4, $row)->getValue();
// 					$dob = $worksheet->getCellByColumnAndRow(5, $row)->getValue();
// 					$phone = $worksheet->getCellByColumnAndRow(6, $row)->getValue();
// 					$village = $worksheet->getCellByColumnAndRow(7, $row)->getValue();
// 					$ta = $worksheet->getCellByColumnAndRow(8, $row)->getValue();
// 					$group_name = $worksheet->getCellByColumnAndRow(9, $row)->getValue();
// 					$city = $worksheet->getCellByColumnAndRow(10, $row)->getValue();
// 					$marital = $worksheet->getCellByColumnAndRow(11, $row)->getValue();
// 					$country = $worksheet->getCellByColumnAndRow(12, $row)->getValue();
// 					$mresidential_status = $worksheet->getCellByColumnAndRow(13, $row)->getValue();
// 					$profession = $worksheet->getCellByColumnAndRow(14, $row)->getValue();
// 					$source_of_income = $worksheet->getCellByColumnAndRow(15, $row)->getValue();
// 					$gross = $worksheet->getCellByColumnAndRow(16, $row)->getValue();
// 					$customer_created_on = $worksheet->getCellByColumnAndRow(17, $row)->getValue();
// 					$loan_number = $worksheet->getCellByColumnAndRow(18, $row)->getValue();
// 					$loan_product = $worksheet->getCellByColumnAndRow(19, $row)->getValue();
// 					$loan_effective_date = $worksheet->getCellByColumnAndRow(20, $row)->getValue();
// 					$pricipal = $worksheet->getCellByColumnAndRow(21, $row)->getValue();
// 					$loan_period = $worksheet->getCellByColumnAndRow(22, $row)->getValue();
// 					$period_type = $worksheet->getCellByColumnAndRow(23, $row)->getValue();
// 					$interest = $worksheet->getCellByColumnAndRow(24, $row)->getValue();
// 					$next_payment_number = $worksheet->getCellByColumnAndRow(25, $row)->getValue();
// 					$loan_added_by = $worksheet->getCellByColumnAndRow(26, $row)->getValue();
// 					$loan_status = $worksheet->getCellByColumnAndRow(27, $row)->getValue();
// 					$loan_added_date = $worksheet->getCellByColumnAndRow(28, $row)->getValue();
// 					$total_repaid = $worksheet->getCellByColumnAndRow(29, $row)->getValue();
// 					$pricipal_paid = $worksheet->getCellByColumnAndRow(30, $row)->getValue();
// 					$interest_paid = $worksheet->getCellByColumnAndRow(31, $row)->getValue();


// 					//$added_by = $this->session->userdata('istitution_code');
// 					$data = array(
// 						'Title' => $title,
// 						'Firstname' => $fname,
// 						'Middlename' => $mdame,
// 						'Lastname' => $lastname,
// 						'Gender' => $gender,
// 						'DateOfBirth' => $dob,
// 						'PhoneNumber' => $phone,
// 						'Village' => $village,
// 						'TA' => $ta,
// 						'ClubName' => $group_name,
// 						'City' => $city,
// 						'MarritalStatus' => $marital,
// 						'Country' => $country,
// 						'ResidentialStatus' => $mresidential_status,
// 						'Profession' => $profession,
// 						'SourceOfIncome' => $source_of_income,
// 						'GrossMonthlyIncome' => $gross,
// 						'CreatedOnCustomer' => $customer_created_on,
// 						'loan_number' => $loan_number,
// 						'loan_product' => $loan_product,
// 						'loan_effectve_date' => $loan_effective_date,
// 						'loan_principal' => $pricipal,
// 						'loan_period' => $loan_period,
// 						'period_type' => $period_type,
// 						'loan_interest' => $interest,
// 						'next_payment_number' => $next_payment_number,
// 						'loan_added_by' => $loan_added_by,
// 						'loan_status' => $loan_status,
// 						'loan_added_date' => $loan_added_date,
// 						'Totalrepaid' => $total_repaid,
// 						'PrincipalPaid' => $pricipal_paid,
// 						'InteresrPaid' => $interest_paid,
// 					);

// 					$this->Loan_customer_first_drafr_model->insert($data);
// 				}



// 			}
// 		}
// 	}
    function add_loan_products(){
        $this->Loan_customer_first_drafr_model->add_loan_products();
    }	function migrate_customer(){
    $this->Loan_customer_first_drafr_model->insert_c();
}
    function convert_date(){
        $r =	$this->Loan_customer_first_drafr_model->get_all();

        foreach ($r as $l){
            $my_date = date('Y-m-d', strtotime($l->CreatedOnCustomer));

            $this->Loan_customer_first_drafr_model->update($l->id,array('CreatedOnCustomer'=>$my_date));
        }


    }
    function convert_date1(){
        $r =	$this->Loan_customer_first_drafr_model->get_all_active();
        $c = 0;
        foreach ($r as $l){
            $this->Loan_model->update1($l->customer_id, array('loan_status'=>'Active'));
        }
        echo $c;

    }
    function update2(){
        $r =	$this->Loan_model->get_all2();
        $c = 0;
        foreach ($r as $l){
            $this->Payement_schedules_model->update1($l->loan_id, array('status'=>'PAID'));
        }
        echo $c;

    }
    function update_loan_payment(){
        $r =	$this->Loan_customer_first_drafr_model->get_all_active();
        $c = 0;
        foreach ($r as $l){
            $this->Loan_model->update1($l->customer_id, array('loan_status'=>'Active'));
        }
        echo $c;

    }
    function add_groups(){
        $this->Loan_customer_first_drafr_model->add_groups();
    }
    function add_customer_to_group(){
        $this->Loan_customer_first_drafr_model->add_customer_to_group();
    }
    function csv_loan_create(){
        $r =	$this->Loan_model->get_all();

        foreach ($r as $l){
            $this->Loan_model->add_loan($l->loan_principal, $l->loan_period, $l->loan_product_id, $l->loan_effectve_date,$l->customer_id,'N/A','N/A',7);

        }


    }
    function reprocess_loan(){
        $r =	$this->Loan_model->get_all_list();

        foreach ($r as $l){
            $this->Loan_model->add_loan_rerun($l->loan_principal, $l->loan_period, $l->loan_product, $l->loan_date,$l->loan_customer,$l->customer_type,$l->loan_number , $l->loan_id);

        }
//print_r($r);
    }
    function get_by_customer($id){
        $res = '<option>-select loan number-</option>';
        $data = $this->Loan_model->get_user_loan($id);

        foreach ($data as $dd){
            $res .='<option value="'.$dd->loan_id.'">'.$dd->loan_number.'</option>';
        }
        echo  $res;

    }

    // Controller function to search for customers
    public function search_customer($customerType) {
        $searchTerm = $this->input->get('search');

        // Adjust the query based on customer type (individual/group)
        if ($customerType == 'individual') {
            $result = get_all_customer('individual_customers', $searchTerm);
        } else {
            $result = get_all_customer('groups', $searchTerm);
        }

        // Prepare the response HTML
        $res = '<option value="">--select customer--</option>';
        foreach ($result as $customer) {
            if ($customerType == 'individual') {
                $res .= '<option value="'. $customer->id.'">'. $customer->Firstname . " " . $customer->Lastname.'</option>';
            } else {
                $res .= '<option value="'. $customer->group_id.'">'. $customer->group_name . " (" . $customer->group_code.')</option>';
            }
        }

        // Log or output the response for debugging
        echo $res;
        // die();  // Add die to stop further execution and check output in the browser
    }


// Controller function to get loans by customer ID
    public function get_by_loan_transact($id) {
        // Fetch the loan data by customer ID
        $data = $this->Loan_model->get_user_loan($id);

        // Prepare the response HTML for the loan dropdown
        $res = '<option value="">--select loan--</option>';
        foreach ($data as $loan) {
            $res .= '<option value="'. $loan->loan_id .'">'. $loan->loan_number . '</option>';
        }
        echo $res;
    }


    function get_by_customer_type($id){


        $res = '<option>-select customer-</option>';
        if($id=='individual'){
            $customer = get_all('individual_customers');

            foreach ($customer as $c){
                $res .='<option value="'. $c->id.'">'.$c->Firstname. " ".$c->Lastname.'</option>';
            }
            echo  $res;
        }
        else{
            $customer = get_all('groups');

            foreach ($customer as $c){
                $res .='<option value="'. $c->group_id.'">'.$c->group_name. " ".$c->group_code.'</option>';
            }
            echo  $res;

        }
        $data = $this->Loan_model->get_user_loan($id);

        foreach ($data as $dd){
            $res .='<option value="'.$dd->loan_id.'">'.$dd->loan_number.'</option>';
        }
        echo  $res;

    }
    function get_charges($id){
        $re = array();
        $charge_value = 0;
        $loan =	$this->Loan_model->get_by_id($id);
        $charge = get_by_id('charges','charge_id','1');
        if($charge->charge_type=="Fixed"){
            $charge_value = $charge->fixed_amount;
        }elseif($charge->charge_type=="Variable"){
            $charge_value =  ($charge->variable_value/100) *  ($loan->loan_principal);

        }
        echo $charge_value;


    }
    function get_late_charg($id){
        $re = array();
        $charge_value = 0;

        $loan =	$this->Loan_model->get_by_id($id);
        $loanproduct =	$this->Loan_products_model->get_by_id($loan->loan_product);

        $charge_value =  ($loanproduct->penalty/100) *  ($loan->loan_amount_term);


        echo $charge_value;


    }

    public function add(){
        $data['customers'] =$this->Individual_customers_model->get_all_active();
        $data['funds_sources'] = $this->Funds_source_model->get_all_funds_sources();
        $data['loan_types'] = $this->loan_products_for_current_user();
        $data['branches'] = $this->branches_for_loan_forms();
        $menu_toggle['toggles'] = 23;
        $this->load->view('admin/header', $menu_toggle);
        $this->load->view('loan/add_loan',$data);
        $this->load->view('admin/footer');
    }
    public function add_group(){
        $data['customers'] =$this->Groups_model->get_all_active();
        $data['funds_sources'] = $this->Funds_source_model->get_all_funds_sources();
        $data['loan_types'] = $this->loan_products_for_current_user();
        $data['branches'] = $this->branches_for_loan_forms();
        $menu_toggle['toggles'] = 23;
        $this->load->view('admin/header', $menu_toggle);
        $this->load->view('loan/add_loan_group',$data);
        $this->load->view('admin/footer');
    }
    
    public function add_group_members(){
        $data['customers'] = $this->Groups_model->get_all_active();
        $data['funds_sources'] = $this->Funds_source_model->get_all_funds_sources();
        $data['loan_types'] = $this->loan_products_for_current_user();
        $data['branches'] = $this->branches_for_loan_forms();
        
        // Generate batch number: BATCH + current date + random number
        $batch_number = 'BATCH' . date('Ymd') . rand(1000, 9999);
        $data['batch_number'] = $batch_number;
        
        $menu_toggle['toggles'] = 23;
        $this->load->view('admin/header', $menu_toggle);
        $this->load->view('loan/add_group_member_loans',$data);
        $this->load->view('admin/footer');
    }
    
    public function group_file(){
        $data['groups'] = $this->Groups_model->get_all_active();
        $menu_toggle['toggles'] = 23;
        $this->load->view('admin/header', $menu_toggle);
        $this->load->view('loan/group_file',$data);
        $this->load->view('admin/footer');
    }
    
    public function get_group_batches(){
        // Set content type to JSON
        header('Content-Type: application/json');
        
        try {
            $group_id = $this->input->post('group_id');
            
            if (!$group_id) {
                echo json_encode(['success' => false, 'message' => 'Group ID is required']);
                return;
            }
            
            // Check if database connection exists
            if (!$this->db) {
                echo json_encode(['success' => false, 'message' => 'Database connection not available']);
                return;
            }
            
            // Get distinct batches for the group
            $this->db->distinct()
                     ->select('batch')
                     ->from('loan')
                     ->where('group_id', $group_id)
                     ->where('batch IS NOT NULL')
                     ->where('batch !=', '')
                     ->order_by('batch', 'DESC');
            
            $batches = $this->db->get()->result();
            
            // Check for database errors
            if ($this->db->error()['code'] != 0) {
                echo json_encode(['success' => false, 'message' => 'Database error: ' . $this->db->error()['message']]);
                return;
            }
            
            if ($batches && count($batches) > 0) {
                echo json_encode(['success' => true, 'batches' => $batches]);
            } else {
                echo json_encode(['success' => false, 'message' => 'No batches found for this group']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Exception: ' . $e->getMessage()]);
        }
    }
    
    public function group_batch_loans($batch = null){
        if (!$batch) {
            redirect('loan/group_file');
            return;
        }
        
        // Get all loans for the selected batch
        $this->db->select('loan.*, 
                  individual_customers.Firstname, individual_customers.Lastname,
                  customer_groups.group_name as customer_group_name,
                  individual_customers.ClientId, loan_products.product_name,
                  groups.group_name, groups.group_code')
                 ->from('loan')
             ->join('individual_customers', 'individual_customers.id = loan.loan_customer', 'left')
             ->join('groups as customer_groups', 'customer_groups.group_id = loan.loan_customer', 'left')
                 ->join('loan_products', 'loan_products.loan_product_id = loan.loan_product', 'left')
                 ->join('groups', 'groups.group_id = loan.group_id', 'left')
                 ->where('loan.batch', $batch)
                 ->order_by('loan.loan_id', 'ASC');
        
        $data['loans'] = $this->db->get()->result();

        // Build a reliable display name for each row, handling legacy mappings.
        foreach ($data['loans'] as $loan_row) {
            $member_name = trim((string)$loan_row->Firstname . ' ' . (string)$loan_row->Lastname);

            if ($member_name === '' && !empty($loan_row->customer_group_name)) {
                $member_name = (string)$loan_row->customer_group_name;
            }

            if ($member_name === '' && !empty($loan_row->loan_customer)) {
                $fallback_individual = $this->db->select('Firstname, Lastname')
                    ->from('individual_customers')
                    ->group_start()
                    ->where('id', $loan_row->loan_customer)
                    ->or_where('ClientId', $loan_row->loan_customer)
                    ->group_end()
                    ->limit(1)
                    ->get()
                    ->row();

                if (!empty($fallback_individual)) {
                    $member_name = trim((string)$fallback_individual->Firstname . ' ' . (string)$fallback_individual->Lastname);
                }
            }

            if ($member_name === '' && !empty($loan_row->loan_customer)) {
                $fallback_group = $this->db->select('group_name')
                    ->from('groups')
                    ->group_start()
                    ->where('group_id', $loan_row->loan_customer)
                    ->or_where('group_code', $loan_row->loan_customer)
                    ->group_end()
                    ->limit(1)
                    ->get()
                    ->row();

                if (!empty($fallback_group)) {
                    $member_name = (string)$fallback_group->group_name;
                }
            }

            if ($member_name === '') {
                $member_name = 'Member #' . $loan_row->loan_customer;
            }

            $loan_row->member_name = $member_name;
        }
        $data['batch'] = $batch;
        $data['batch_summary'] = $this->Group_batch_model->build_financial_summary($data['loans']);
        $data['repayment_members'] = $this->Group_batch_model->build_repayment_member_rows($data['loans']);
        $data['pending_batch_edit'] = $this->Group_batch_model->get_pending_batch_edit($batch);
        $data['edit_context'] = empty($data['pending_batch_edit'])
            ? $this->Group_batch_model->build_edit_context($data['loans'], $batch)
            : null;

        foreach ($data['loans'] as $loan_row) {
            $payments = $this->Payement_schedules_model->get_all_by_id($loan_row->loan_id);
            $loan_row->schedule_count = count($payments);
            $loan_row->payment_balance = $this->Payement_schedules_model->summarize_loan_balances(
                $payments,
                isset($loan_row->loan_amount_total) ? $loan_row->loan_amount_total : null
            );
        }
        $data['loan_types'] = $this->loan_products_for_current_user();
        if (!is_array($data['loan_types'])) {
            $data['loan_types'] = array();
        }
        $data['officers'] = $this->db->order_by('Firstname', 'ASC')->get('employees')->result();
        if (!is_array($data['officers'])) {
            $data['officers'] = array();
        }

        // Check user permissions for batch actions
        $data['permissions'] = $this->get_user_batch_permissions();
        
        if (empty($data['loans'])) {
            $this->session->set_flashdata('error', 'No loans found for this batch');
            redirect('loan/group_file');
            return;
        }
        
        $menu_toggle['toggles'] = 23;
        $this->load->view('admin/header', $menu_toggle);
        $this->load->view('loan/group_batch_loans',$data);
        $this->load->view('admin/footer');
    }
    
    private function get_user_batch_permissions() {
        // Get user's accessible menu items from session
        $user_access = $this->session->userdata('access');
        $role_name = strtoupper(trim((string)$this->session->userdata('RoleName')));
        $is_cashier_role = (strpos($role_name, 'CASHIER') !== false);
        
        if(empty($user_access)) {
            return array(
                'can_pay_off' => false,
                'can_pay' => $is_cashier_role,
                'can_disburse' => false,
                'can_recommend' => false,
                'can_approve' => false,
                'can_edit' => false,
            );
        }
        
        // Get the menu item IDs that user has access to
        $accessible_ids = array();
        foreach($user_access as $access) {
            $accessible_ids[] = $access->controllerid;
        }
        
        if(empty($accessible_ids)) {
            return array(
                'can_pay_off' => false,
                'can_pay' => $is_cashier_role,
                'can_disburse' => false,
                'can_recommend' => false,
                'can_approve' => false,
                'can_edit' => false,
            );
        }
        
        // Check for specific group loan permissions in menuitems table
        $this->db->select('method');
        $this->db->from('menuitems');
        $this->db->where_in('id', $accessible_ids);
        $this->db->where_in('method', array(
            'group_loan.pay_off',
            'group_loan.pay', 
            'group_loan.disburse',
            'group_loan.recommend',
            'group_loan.approve'
        ));
        $user_methods = $this->db->get()->result();
        
        // Convert to simple array of methods
        $methods = array();
        foreach($user_methods as $method) {
            $methods[] = $method->method;
        }
        
        // Return permission flags
        $can_edit = in_array('group_loan.recommend', $methods)
            || in_array('group_loan.approve', $methods)
            || $role_name === 'SUPER ADMIN';

        return array(
            'can_pay_off' => in_array('group_loan.pay_off', $methods),
            'can_pay' => in_array('group_loan.pay', $methods) || $is_cashier_role,
            'can_disburse' => in_array('group_loan.disburse', $methods),
            'can_recommend' => in_array('group_loan.recommend', $methods),
            'can_approve' => in_array('group_loan.approve', $methods),
            'can_edit' => $can_edit,
        );
    }

    /**
     * Initiate a group-wide loan edit (approval workflow, same rules as individual edit).
     */
    public function batch_edit_action()
    {
        $batch = trim($this->input->post('batch', TRUE));
        if ($batch === '') {
            $this->toaster->error('Batch number is required.');
            redirect($_SERVER['HTTP_REFERER']);
            return;
        }

        $this->db->where('batch', $batch);
        $this->db->where('loan_status !=', 'DELETED');
        $loans = $this->db->get('loan')->result();

        if (empty($loans)) {
            $this->toaster->error('No loans found for this batch.');
            redirect('loan/group_file');
            return;
        }

        $loan_type = (int) $this->input->post('loan_type');
        $months = (int) $this->input->post('months');
        $loan_date = $this->input->post('loan_date');
        $user = (int) $this->input->post('user');
        $narration = $this->input->post('narration');
        $period_type = trim((string) $this->input->post('period_type'));
        $member_amounts = $this->input->post('member_amounts');

        if ($loan_type <= 0 || $months <= 0 || empty($loan_date) || $user <= 0) {
            $this->toaster->error('Please complete all required group loan edit fields.');
            redirect('loan/group_batch_loans/' . rawurlencode($batch));
            return;
        }

        $product_row = $this->Loan_products_model->get_by_id($loan_type);
        $productAccessErr = $this->validate_loan_product_access_for_user($product_row);
        if ($productAccessErr !== null) {
            $this->toaster->error($productAccessErr);
            redirect('loan/group_batch_loans/' . rawurlencode($batch));
            return;
        }

        if ($product_row && !empty($product_row->frequency)) {
            $period_type = trim((string) $product_row->frequency);
        }

        $members_old = array();
        $members_new = array();

        foreach ($loans as $loan_stub) {
            $loan_row = $this->Loan_model->get_by_id($loan_stub->loan_id);
            if (!$loan_row) {
                continue;
            }

            $loan_id = (int) $loan_row->loan_id;
            $principal_raw = is_array($member_amounts) && isset($member_amounts[$loan_id])
                ? $member_amounts[$loan_id]
                : $loan_row->loan_principal;
            $principal = str_replace(array(',', ' '), '', (string) $principal_raw);
            if (!is_numeric($principal) || (float) $principal <= 0) {
                $this->toaster->error('Invalid principal for loan ' . $loan_row->loan_number);
                redirect('loan/group_batch_loans/' . rawurlencode($batch));
                return;
            }

            $posted = array(
                'loan_number' => $loan_row->loan_number,
                'loan_type' => $loan_type,
                'months' => $months,
                'loan_date' => $loan_date,
                'user' => $user,
                'narration' => $narration,
                'period_type' => $period_type,
                'worthness_file' => $loan_row->worthness_file,
                'principal' => $principal,
            );

            $snapshots = $this->Group_batch_model->build_member_edit_snapshots($loan_row, $product_row, $posted);
            $members_old[] = $snapshots['old'];
            $members_new[] = $snapshots['new'];
        }

        $payload_old = array('batch' => $batch, 'members' => $members_old);
        $payload_new = array(
            'batch' => $batch,
            'shared' => array(
                'sy_loan_product' => $loan_type,
                'loan_period' => $months,
                'loan_date' => $loan_date,
                'sy_added_by' => $user,
                'narration' => $narration,
                'period_type' => $period_type,
            ),
            'members' => $members_new,
        );

        $initiated_by = current_session_user_id();
        if ($initiated_by <= 0) {
            $this->toaster->error('Your session has expired or is invalid. Please log in again.');
            redirect('auth/logout');
            return;
        }

        if (!auth_logger(array(
            'type' => 'Loan edit',
            'state' => 'Initiated',
            'old_info' => json_encode($payload_old),
            'new_info' => json_encode($payload_new),
            'id' => (int) $loans[0]->loan_id,
            'summary' => 'Batch ' . $batch . ' (' . count($members_new) . ' loans)',
            'Initiated_by' => $initiated_by,
        ))) {
            $this->toaster->error('Could not save the approval record. Please log in again.');
            redirect('auth/logout');
            return;
        }

        log_activity(array(
            'user_id' => $initiated_by,
            'activity' => 'Initiated group batch edit for ' . $batch . ' (' . count($members_new) . ' loans)',
        ));

        $this->toaster->success('Group loan edit submitted. Next: Recommend loan edit, then Approve loan edit.');
        redirect('loan/group_batch_loans/' . rawurlencode($batch));
    }

    /**
     * Apply an approved group batch edit to all member loans.
     */
    public function create_act_batch_edit()
    {
        $approval_id = $this->session->userdata('group_batch_edit');
        if (empty($approval_id)) {
            $this->toaster->error('No pending group batch edit found.');
            redirect('loan/edit_approve');
            return;
        }

        $row = get_by_id('approval_edits', 'approval_edits_id', $approval_id);
        if (!$row || !$this->Group_batch_model->is_group_batch_approval($row) || $row->state !== 'Approved') {
            $this->toaster->error('Group batch edit approval record is invalid or not approved.');
            redirect('loan/edit_approve');
            return;
        }

        $result = $this->Group_batch_model->apply_approved_batch_edit($row);
        $this->session->unset_userdata('group_batch_edit');

        if (!$result['success']) {
            $this->toaster->error($result['message']);
            redirect('loan/edit_approve');
            return;
        }

        log_activity(array(
            'user_id' => $this->session->userdata('user_id'),
            'activity' => 'Applied approved group batch edit: ' . ($result['batch'] ?: 'batch'),
        ));

        $this->toaster->success($result['message']);
        if (!empty($result['batch'])) {
            redirect('loan/group_batch_loans/' . rawurlencode($result['batch']));
        } else {
            redirect('loan/edit_approve');
        }
    }
    
    public function batch_recommend(){
        header('Content-Type: application/json');
        
        $batch = $this->input->post('batch');
        
        if(!$batch) {
            echo json_encode(['success' => false, 'message' => 'Batch number is required']);
            return;
        }
        
        try {
            // Get all loans in this batch
            $this->db->where('batch', $batch);
            $this->db->where('loan_status !=', 'DELETED');
            $loans = $this->db->get('loan')->result();
            
            if(empty($loans)) {
                echo json_encode(['success' => false, 'message' => 'No loans found in this batch']);
                return;
            }
            
            $recommended_count = 0;
            $skipped_count = 0;
            $errors = [];
            
            foreach($loans as $loan) {
                // Skip already recommended loans
                if($loan->loan_status == 'RECOMMENDED') {
                    $skipped_count++;
                    continue;
                }
                
                // Update loan status to RECOMMENDED
                $update_data = array(
                    'loan_status' => 'RECOMMENDED',
                    'loan_approved_by' => $this->session->userdata('user_id'),
                    'approved_date' => date('Y-m-d H:i:s')
                );
                
                $this->Loan_model->update($loan->loan_id, $update_data);
                
                // Check if the update was successful by verifying the database
                $this->db->where('loan_id', $loan->loan_id);
                $this->db->where('loan_status', 'RECOMMENDED');
                $updated_loan = $this->db->get('loan')->row();
                
                if($updated_loan) {
                    $recommended_count++;
                    
                    // Log activity
                    $logger = array(
                        'user_id' => $this->session->userdata('user_id'),
                        'activity' => 'RECOMMENDED a loan (Batch: '.$batch.')'
                    );
                    log_activity($logger);
                } else {
                    $errors[] = 'Failed to recommend loan: ' . $loan->loan_number;
                }
            }
            
            $message = "Batch recommendation completed. ";
            $message .= "Recommended: {$recommended_count} loans. ";
            if($skipped_count > 0) {
                $message .= "Skipped: {$skipped_count} (already recommended). ";
            }
            if(!empty($errors)) {
                $message .= "Errors: " . implode(', ', $errors);
            }
            
            echo json_encode([
                'success' => true,
                'message' => $message,
                'recommended_count' => $recommended_count,
                'skipped_count' => $skipped_count,
                'error_count' => count($errors)
            ]);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }
    
    public function batch_approve(){
        header('Content-Type: application/json');
        
        $batch = $this->input->post('batch');
        
        if(!$batch) {
            echo json_encode(['success' => false, 'message' => 'Batch number is required']);
            return;
        }
        
        try {
            // Get all RECOMMENDED loans in this batch
            $this->db->where('batch', $batch);
            $this->db->where('loan_status', 'RECOMMENDED');
            $loans = $this->db->get('loan')->result();
            
            if(empty($loans)) {
                echo json_encode(['success' => false, 'message' => 'No RECOMMENDED loans found in this batch']);
                return;
            }
            
            $approved_count = 0;
            $errors = [];
            
            foreach($loans as $loan) {
                // Update loan status to APPROVED
                $update_data = array(
                    'loan_status' => 'APPROVED',
                    'loan_approved_by' => $this->session->userdata('user_id'),
                    'approved_date' => date('Y-m-d H:i:s')
                );
                
                $this->Loan_model->update($loan->loan_id, $update_data);
                
                // Check if the update was successful by verifying the database
                $this->db->where('loan_id', $loan->loan_id);
                $this->db->where('loan_status', 'APPROVED');
                $updated_loan = $this->db->get('loan')->row();
                
                if($updated_loan) {
                    $approved_count++;
                    
                    // Log activity
                    $logger = array(
                        'user_id' => $this->session->userdata('user_id'),
                        'activity' => 'APPROVED a loan (Batch: '.$batch.')'
                    );
                    log_activity($logger);
                } else {
                    $errors[] = 'Failed to approve loan: ' . $loan->loan_number;
                }
            }
            
            $message = "Batch approval completed. ";
            $message .= "Approved: {$approved_count} loans. ";
            if(!empty($errors)) {
                $message .= "Errors: " . implode(', ', $errors);
            }
            
            echo json_encode([
                'success' => true,
                'message' => $message,
                'approved_count' => $approved_count,
                'error_count' => count($errors)
            ]);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }
    
    public function batch_disburse(){
        header('Content-Type: application/json');
        
        $batch = $this->input->post('batch');
        
        if(!$batch) {
            echo json_encode(['success' => false, 'message' => 'Batch number is required']);
            return;
        }
        
        try {
            // Get all APPROVED loans in this batch
            $this->db->where('batch', $batch);
            $this->db->where('loan_status', 'APPROVED');
            $loans = $this->db->get('loan')->result();
            
            if(empty($loans)) {
                echo json_encode(['success' => false, 'message' => 'No APPROVED loans found in this batch']);
                return;
            }
            
            $disbursed_count = 0;
            $errors = [];
            
            $disbursement_datetime = $this->resolve_disbursement_datetime();
            $transaction_date = date('Y-m-d', strtotime($disbursement_datetime));

            foreach($loans as $loan) {
                try {
                    // Process cash transaction (same as individual disbursement)
                    $this->cash_transaction($loan->loan_id, $transaction_date);

                    // Process pay off loan (activates the loan and creates payment schedule)
                    $this->pay_off_loan($loan->loan_id, $disbursement_datetime);
                    $this->Payement_schedules_model->correct_premature_loan_closure($loan->loan_id);

                    // Check if the update was successful by verifying the database
                    $this->db->where('loan_id', $loan->loan_id);
                    $this->db->where('loan_status', 'ACTIVE');
                    $updated_loan = $this->db->get('loan')->row();
                    
                    if($updated_loan) {
                        $disbursed_count++;
                        
                        // Log activity
                        $logger = array(
                            'user_id' => $this->session->userdata('user_id'),
                            'activity' => 'DISBURSED a loan (Batch: '.$batch.')'
                        );
                        log_activity($logger);
                        
                        // Send SMS notification if enabled
                        $notify = get_by_id('sms_settings','id','1');
                        if($notify->loan_disbursement=='Yes') {
                            $customer = $this->Loan_model->loan_user($loan->loan_id);
                            send_sms($customer->PhoneNumber,'Dear customer, loan has been approved, you can call numbers below for more');
                        }
                    } else {
                        $errors[] = 'Failed to disburse loan: ' . $loan->loan_number;
                    }
                    
                } catch (Exception $e) {
                    $errors[] = 'Error disbursing loan ' . $loan->loan_number . ': ' . $e->getMessage();
                }
            }
            
            $message = "Batch disbursement completed. ";
            $message .= "Disbursed: {$disbursed_count} loans. ";
            if(!empty($errors)) {
                $message .= "Errors: " . implode(', ', $errors);
            }
            
            echo json_encode([
                'success' => true,
                'message' => $message,
                'disbursed_count' => $disbursed_count,
                'error_count' => count($errors)
            ]);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }

    public function batch_pay(){
        header('Content-Type: application/json');

        try {
            $batch = trim($this->input->post('batch', TRUE));
            $raw_total_amount = $this->input->post('total_amount', TRUE);
            $payment_reference = trim($this->input->post('payment_reference', TRUE));
            $payment_type = trim($this->input->post('payment_type', TRUE));
            $input_datetime = trim($this->input->post('pdate', TRUE));
            $allocations = $this->input->post('allocations');

            if (empty($batch)) {
                echo json_encode(['success' => false, 'message' => 'Batch number is required.']);
                return;
            }

            $normalized_total = str_replace([',', ' '], '', (string)$raw_total_amount);
            if (!is_numeric($normalized_total) || (float)$normalized_total <= 0) {
                echo json_encode(['success' => false, 'message' => 'Please enter a valid total deposited amount.']);
                return;
            }
            $total_amount = round((float)$normalized_total, 2);

            if (empty($payment_reference)) {
                echo json_encode(['success' => false, 'message' => 'Transaction ID / Receipt Number is required.']);
                return;
            }

            if (!in_array($payment_type, ['bank', 'cash'])) {
                echo json_encode(['success' => false, 'message' => 'Please select a valid payment type (Bank or Cash).']);
                return;
            }

            if (!is_array($allocations) || empty($allocations)) {
                echo json_encode(['success' => false, 'message' => 'Please provide member allocations for this batch payment.']);
                return;
            }

            $existing_reference = $this->Transactions_model->check_duplicate_reference($payment_reference);
            if (!empty($existing_reference)) {
                echo json_encode(['success' => false, 'message' => 'Duplicate payment reference detected. Please verify and use a new reference.']);
                return;
            }

            $proof = date('Y-m-d H:i:s');
            if (!empty($input_datetime)) {
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $input_datetime)) {
                    $proof = $input_datetime . ' 00:00:00';
                } else {
                    $datetime = new DateTime($input_datetime);
                    $proof = $datetime->format('Y-m-d H:i:s');
                }
            }

            $clean_allocations = [];
            $allocation_sum = 0.00;
            foreach ($allocations as $loan_id => $amount_value) {
                $loan_id = (int)$loan_id;
                if ($loan_id <= 0) {
                    continue;
                }

                $normalized_amount = str_replace([',', ' '], '', (string)$amount_value);
                if ($normalized_amount === '') {
                    continue;
                }

                if (!is_numeric($normalized_amount)) {
                    echo json_encode(['success' => false, 'message' => 'Invalid amount detected in one or more member allocations.']);
                    return;
                }

                $amount = round((float)$normalized_amount, 2);
                if ($amount < 0) {
                    echo json_encode(['success' => false, 'message' => 'Allocation amounts cannot be negative.']);
                    return;
                }

                if ($amount > 0) {
                    $clean_allocations[$loan_id] = $amount;
                    $allocation_sum += $amount;
                }
            }

            $allocation_sum = round($allocation_sum, 2);
            if (empty($clean_allocations)) {
                echo json_encode(['success' => false, 'message' => 'Please enter at least one allocation amount greater than zero.']);
                return;
            }

            if (abs($allocation_sum - $total_amount) > 0.01) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Allocated repayment amount must equal total deposited amount.',
                    'expected_total' => $total_amount,
                    'allocation_total' => $allocation_sum
                ]);
                return;
            }

            $loan_ids = array_keys($clean_allocations);
            $this->db->select('loan_id, loan_number, loan_status, batch');
            $this->db->from('loan');
            $this->db->where('batch', $batch);
            $this->db->where_in('loan_id', $loan_ids);
            $batch_loans = $this->db->get()->result();

            if (count($batch_loans) !== count($loan_ids)) {
                echo json_encode(['success' => false, 'message' => 'Some selected members are not part of the chosen batch.']);
                return;
            }

            $validated_loans = [];
            foreach ($batch_loans as $loan) {
                if (strtoupper(trim($loan->loan_status)) !== 'ACTIVE') {
                    echo json_encode(['success' => false, 'message' => 'Only ACTIVE loans can be paid in batch mode.']);
                    return;
                }

                $this->db->select('payment_number, total_late_charge');
                $this->db->from('payement_schedules');
                $this->db->where('loan_id', $loan->loan_id);
                $this->db->where_in('status', array('NOT PAID', 'PARTIAL PAID'));
                $this->db->order_by('payment_number', 'ASC');
                $this->db->limit(1);
                $next_payment = $this->db->get()->row();

                if (!$next_payment) {
                    echo json_encode(['success' => false, 'message' => 'A selected member has no pending schedule to pay.']);
                    return;
                }

                $this->db->select('SUM(amount - paid_amount) as outstanding');
                $this->db->from('payement_schedules');
                $this->db->where('loan_id', $loan->loan_id);
                $this->db->where_in('status', array('NOT PAID', 'PARTIAL PAID'));
                $outstanding_row = $this->db->get()->row();

                $outstanding = round((float)($outstanding_row ? $outstanding_row->outstanding : 0), 2);
                $loan_amount = $clean_allocations[(int)$loan->loan_id];

                if ($loan_amount > $outstanding + 0.01) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Allocated amount for loan ' . $loan->loan_number . ' exceeds its outstanding balance.'
                    ]);
                    return;
                }

                $validated_loans[] = [
                    'loan_id' => (int)$loan->loan_id,
                    'loan_number' => $loan->loan_number,
                    'amount' => $loan_amount,
                    'payment_number' => (int)$next_payment->payment_number,
                    'has_late_charges' => ((float)$next_payment->total_late_charge > 0)
                ];
            }

            $collection_account = get_by_id('account', 'collection_account', 'Yes');

            $get_account = $this->Tellering_model->get_teller_account($this->session->userdata('user_id'));
            if (empty($get_account) && $this->session->userdata('RoleName') === 'SUPER ADMIN') {
                $get_account = $this->Tellering_model->get_teller_account1();
            }

            // Allow repayment processing even when teller/account mapping is missing.
            $can_move_funds = false;
            $funding_account = null;
            if (!empty($collection_account)) {
                $funding_account = !empty($get_account) ? $get_account->account : $collection_account->account_number;
                $funding_account_row = !empty($funding_account) ? $this->Account_model->get_account($funding_account) : null;
                $collection_account_row = $this->Account_model->get_account($collection_account->account_number);
                $can_move_funds = (!empty($funding_account_row) && !empty($collection_account_row));
            }

            $mode = 'deposit';
            $group_tid = 'TR-B' . rand(100, 9999) . date('YmdHis');
            $log_parts = [];
            $warnings = [];

            $this->db->trans_begin();

            foreach ($validated_loans as $index => $loan_data) {
                $loan_tid = $group_tid . '-' . ($index + 1);

                if ($can_move_funds) {
                    $loan_account_row = $this->Account_model->get_account($loan_data['loan_number']);
                    if (!empty($loan_account_row)) {
                        $deposit_status = $this->Account_model->cash_transaction_modified(
                            $funding_account,
                            $loan_data['loan_number'],
                            $loan_data['amount'],
                            $mode,
                            $loan_tid,
                            $proof,
                            'deposit'
                        );

                        if ($deposit_status === 'success') {
                            $this->Account_model->transfer_funds1(
                                $loan_data['loan_number'],
                                $collection_account->account_number,
                                $loan_data['amount'],
                                $loan_tid,
                                $proof
                            );
                        } else {
                            $warnings[] = 'Fund movement skipped for loan ' . $loan_data['loan_number'] . ' (deposit step failed).';
                        }
                    } else {
                        $warnings[] = 'Fund movement skipped for loan ' . $loan_data['loan_number'] . ' (loan account missing).';
                    }
                } else {
                    $warnings[] = 'Fund movement skipped (teller/collection account mapping unavailable).';
                }

                if ($loan_data['has_late_charges']) {
                    $pay_result = $this->Payement_schedules_model->pay_loan_with_late_charges(
                        $loan_data['loan_id'],
                        $loan_data['payment_number'],
                        $loan_data['amount'],
                        $proof,
                        $loan_tid
                    );

                    if (!(is_array($pay_result) && isset($pay_result['success']) && $pay_result['success'])) {
                        $this->db->trans_rollback();
                        $db_error = $this->db->error();
                        $error_details = (!empty($db_error['message']) ? ' DB: ' . $db_error['message'] : '');
                        echo json_encode(['success' => false, 'message' => 'Failed to allocate late charges for loan ' . $loan_data['loan_number'] . '.' . $error_details]);
                        return;
                    }
                } else {
                    $pay_result = $this->Payement_schedules_model->new_pay_new(
                        $loan_data['loan_id'],
                        $loan_data['payment_number'],
                        $loan_data['amount'],
                        $proof,
                        $loan_tid
                    );

                    if (!$pay_result) {
                        $this->db->trans_rollback();
                        $db_error = $this->db->error();
                        $error_details = (!empty($db_error['message']) ? ' DB: ' . $db_error['message'] : '');
                        echo json_encode(['success' => false, 'message' => 'Failed to apply payment for loan ' . $loan_data['loan_number'] . '.' . $error_details]);
                        return;
                    }
                }

                $this->Transactions_model->insert([
                    'ref' => $loan_tid,
                    'loan_id' => $loan_data['loan_id'],
                    'amount' => $loan_data['amount'],
                    'transaction_type' => 1,
                    'payment_number' => $loan_data['payment_number'],
                    'payment_reference' => $payment_reference,
                    'payment_type' => $payment_type,
                    'date_stamp' => $proof,
                    'added_by' => $this->session->userdata('user_id')
                ]);

                $log_parts[] = $loan_data['loan_number'] . ': MWK ' . number_format($loan_data['amount'], 2);
            }

            if ($this->db->trans_status() === FALSE) {
                $this->db->trans_rollback();
                $db_error = $this->db->error();
                $error_details = (!empty($db_error['message']) ? ' DB: ' . $db_error['message'] : '');
                echo json_encode(['success' => false, 'message' => 'Batch payment failed. All changes were rolled back.' . $error_details]);
                return;
            }

            $this->db->trans_commit();

            $logger = array(
                'user_id' => $this->session->userdata('user_id'),
                'activity' => 'Group Batch Payment: Batch ' . $batch .
                              ' | Pay Ref: ' . $payment_reference .
                              ' | Type: ' . strtoupper($payment_type) .
                              ' | Total: MWK ' . number_format($total_amount, 2) .
                              ' | Loans: ' . implode('; ', $log_parts)
            );
            log_activity($logger);

            echo json_encode([
                'success' => true,
                'message' => 'Batch payment posted successfully.' . (!empty($warnings) ? ' Warnings: ' . implode(' ', array_values(array_unique($warnings))) : ''),
                'batch' => $batch,
                'total_amount' => $total_amount,
                'receipt_url' => base_url('loan/print_batch_payment_receipt/') . rawurlencode($payment_reference),
                'redirect_url' => base_url('loan/group_batch_loans/') . rawurlencode($batch)
            ]);
        } catch (Exception $e) {
            if ($this->db->trans_status() !== FALSE) {
                $this->db->trans_rollback();
            }
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }

    public function print_batch_payment_receipt($payment_reference = null) {
        if (empty($payment_reference)) {
            show_404();
            return;
        }

        $payment_reference = urldecode($payment_reference);

        $transaction_fields = $this->db->list_fields('transactions');
        $reference_column = in_array('payment_reference', $transaction_fields) ? 'payment_reference' : (in_array('reference', $transaction_fields) ? 'reference' : '');
        $payment_type_column = in_array('payment_type', $transaction_fields) ? 'payment_type' : (in_array('method', $transaction_fields) ? 'method' : '');

        if ($reference_column === '') {
            show_404();
            return;
        }

        $payment_type_select = $payment_type_column !== '' ? 'transactions.' . $payment_type_column . ' as payment_type_value' : '"" as payment_type_value';
        $this->db->select('transactions.transaction_id, transactions.ref, transactions.amount, transactions.payment_number, transactions.' . $reference_column . ' as payment_reference_value, ' . $payment_type_select . ', transactions.date_stamp, loan.loan_id, loan.loan_number, loan.batch, groups.group_name, groups.group_code, individual_customers.Firstname, individual_customers.Lastname, employees.Firstname as staff_firstname, employees.Lastname as staff_lastname');
        $this->db->from('transactions');
        $this->db->join('loan', 'loan.loan_id = transactions.loan_id', 'left');
        $this->db->join('groups', 'groups.group_id = loan.group_id', 'left');
        $this->db->join('individual_customers', 'individual_customers.id = loan.loan_customer', 'left');
        $this->db->join('employees', 'employees.id = transactions.added_by', 'left');
        $this->db->where('transactions.' . $reference_column, $payment_reference);
        $this->db->where('transactions.transaction_type', 1);
        $this->db->order_by('transactions.transaction_id', 'ASC');
        $rows = $this->db->get()->result();

        if (empty($rows)) {
            show_404();
            return;
        }

        $total_allocated = 0;
        $internal_refs = [];
        foreach ($rows as $row) {
            $total_allocated += (float)$row->amount;
            if (!empty($row->ref)) {
                $internal_refs[] = $row->ref;
            }
        }

        $internal_refs = array_values(array_unique($internal_refs));
        $first_row = $rows[0];
        $printed_on = date('Y-m-d H:i:s');
        $posted_on = !empty($first_row->date_stamp) ? $first_row->date_stamp : $printed_on;
        $officer = trim(($first_row->staff_firstname ?? '') . ' ' . ($first_row->staff_lastname ?? ''));
        $raw_payment_type = (string)($first_row->payment_type_value ?? '');
        if ($payment_type_column === 'method') {
            $payment_type = ((string)$raw_payment_type === '1') ? 'BANK' : (((string)$raw_payment_type === '0') ? 'CASH' : strtoupper($raw_payment_type));
        } else {
            $payment_type = strtoupper($raw_payment_type);
        }

        $data = [
            'receipt_reference' => $payment_reference,
            'batch' => $first_row->batch,
            'group_name' => $first_row->group_name,
            'group_code' => $first_row->group_code,
            'payment_type' => $payment_type,
            'posted_on' => $posted_on,
            'printed_on' => $printed_on,
            'officer' => $officer,
            'total_allocated' => $total_allocated,
            'allocations' => $rows,
            'internal_refs' => $internal_refs
        ];

        $this->load->view('loan/print_batch_payment_receipt', $data);
    }
    
    public function get_next_payment_info(){
        header('Content-Type: application/json');
        
        $loan_id = $this->input->post('loan_id');
        
        if(!$loan_id) {
            echo json_encode(['success' => false, 'message' => 'Loan ID is required']);
            return;
        }
        
        try {
            // Get loan details
            $loan = $this->Loan_model->get_by_id($loan_id);
            if(!$loan) {
                echo json_encode(['success' => false, 'message' => 'Loan not found']);
                return;
            }
            
            $customer_name = $this->resolve_loan_customer_name($loan->loan_customer, $loan->customer_type);
            
            // Get next payment details - find the earliest incomplete schedule
            $this->db->where('loan_id', $loan_id);
            $this->db->where_in('status', array('NOT PAID', 'PARTIAL PAID'));
            $this->db->order_by('payment_number', 'ASC');
            $this->db->limit(1);
            $next_payment = $this->db->get('payement_schedules')->row();
            if(!$next_payment) {
                echo json_encode(['success' => false, 'message' => 'No pending payments found']);
                return;
            }

            $remaining_due = (float)$next_payment->amount - (float)$next_payment->paid_amount;

            echo json_encode([
                'success' => true,
                'customer_name' => $customer_name,
                'payment_number' => $next_payment->payment_number,
                'amount' => $remaining_due,
                'amount_formatted' => number_format($remaining_due, 2),
                'due_date' => $next_payment->payment_schedule
            ]);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }
    
    public function get_payoff_info(){
        header('Content-Type: application/json');
        
        $loan_id = $this->input->post('loan_id');
        
        if(!$loan_id) {
            echo json_encode(['success' => false, 'message' => 'Loan ID is required']);
            return;
        }
        
        try {
            // Get loan details
            $loan = $this->Loan_model->get_by_id($loan_id);
            if(!$loan) {
                echo json_encode(['success' => false, 'message' => 'Loan not found']);
                return;
            }
            
            $customer_name = $this->resolve_loan_customer_name($loan->loan_customer, $loan->customer_type);
            
            // Get next payment details - find the earliest incomplete schedule
            $this->db->where('loan_id', $loan_id);
            $this->db->where_in('status', array('NOT PAID', 'PARTIAL PAID'));
            $this->db->order_by('payment_number', 'ASC');
            $this->db->limit(1);
            $next_payment = $this->db->get('payement_schedules')->row();
            
            if(!$next_payment) {
                echo json_encode(['success' => false, 'message' => 'No pending payments found']);
                return;
            }
            
            // Get all payments for this loan for payoff calculation
            $this->db->where('loan_id', $loan_id);
            $this->db->order_by('payment_number', 'ASC');
            $payments = $this->db->get('payement_schedules')->result();
            
            // Calculate pay off amount (same logic as original pay off page)
            $loan_period = $loan->loan_period;
            $halfSchedules = $loan_period / 2;
            $total_payoff = 0;
            $v = $this->getMedianSchedule($loan_period);
            
            // Get middle schedule
            $this->db->where('payment_number', $v);
            $this->db->where('loan_id', $loan_id);
            $get_middle_schedule = $this->db->get('payement_schedules')->row();
            
            if(!$get_middle_schedule) {
                echo json_encode(['success' => false, 'message' => 'Could not find middle payment schedule']);
                return;
            }
            
            // Check if the current schedule is less than 50% of the total schedules
            if ($next_payment->payment_number <= $v) {
                // Loop through only $v schedules
                for ($i = 0; $i < $v; $i++) {
                    if(isset($payments[$i])) {
                        $total_payoff += $payments[$i]->amount;
                    }
                }
            } else {
                $total_payoff = $get_middle_schedule->amount;
            }
            
            $payoff_amount = $total_payoff + $get_middle_schedule->loan_balance;
            $total_balance = $get_middle_schedule->loan_balance + $get_middle_schedule->amount;
            
            echo json_encode([
                'success' => true,
                'customer_name' => $customer_name,
                'payment_number' => $next_payment->payment_number,
                'payoff_amount' => $payoff_amount,
                'payoff_amount_formatted' => number_format($payoff_amount, 2),
                'repay_amounts' => $total_payoff,
                'total_balance' => $total_balance,
                'middle_payment' => $v
            ]);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }
    
    public function get_group_members(){
        // Set content type to JSON
        header('Content-Type: application/json');
        
        $group_id = $this->input->post('group_id');
        
        // Debug: Log the received group_id
        log_message('debug', 'Group ID received: ' . $group_id);
        
        if($group_id){
            try {
                // First check if group exists
                $group = $this->Groups_model->get_by_id($group_id);
                if(!$group) {
                    echo json_encode(['error' => 'Group not found with ID: ' . $group_id]);
                    return;
                }
                
                // Get members
                $members = $this->Customer_groups_model->get_members($group_id);
                
                // Debug: Log member count
                log_message('debug', 'Members found: ' . count($members));
                
                if(empty($members)) {
                    echo json_encode(['error' => 'No members found for group: ' . $group->group_name]);
                    return;
                }
                
                echo json_encode($members);
            } catch (Exception $e) {
                log_message('error', 'Error in get_group_members: ' . $e->getMessage());
                echo json_encode(['error' => $e->getMessage()]);
            }
        } else {
            echo json_encode(['error' => 'No group ID provided']);
        }
    }
    public function calculator(){
        $data['result'] = '';
        $data['loan_types'] = $this->loan_products_for_current_user();
        $menu_toggle['toggles'] = 41;
        $this->load->view('admin/header', $menu_toggle);
        $this->load->view('loan/calculator',$data);
        $this->load->view('admin/footer');
    }
    function calculate(){
        $data['loan_types'] = $this->loan_products_for_current_user();
        $id = $this->input->get('loan_type');
        $exist = null;
        if ($id !== null && $id !== '') {
            $exist = $this->Loan_products_model->get_by_id($id);
            $accessErr = $this->validate_loan_product_access_for_user($exist);
            if ($accessErr !== null) {
                $data['result'] = '<div class="alert alert-danger">' . html_escape($accessErr) . '</div>';
                $menu_toggle['toggles'] = 41;
                $this->load->view('admin/header', $menu_toggle);
                $this->load->view('loan/calculator', $data);
                $this->load->view('admin/footer');
                return;
            }
        }

        $term_validation_error = $this->get_group_zitsamba_term_validation_error($id, $this->input->get('months'));
        if ($term_validation_error !== null) {
            $data['result'] = '<div class="alert alert-danger">' . $term_validation_error . '</div>';
            $menu_toggle['toggles'] = 41;
            $this->load->view('admin/header', $menu_toggle);
            $this->load->view('loan/calculator', $data);
            $this->load->view('admin/footer');
            return;
        }

        if ($exist) {
            if($exist->method=="Straight line"){
                $principal = (($exist->processing_fees/100)*$this->input->get('amount'))+$this->input->get('amount');
                $result = $this->calculate_amortization_weekly($principal,$this->input->get('loan_type'),$this->input->get('months'),$this->input->get('loan_date'));
                $data['result'] = $result;
                $menu_toggle['toggles'] = 41;
                $this->load->view('admin/header', $menu_toggle);
                $this->load->view('loan/calculator',$data);
                $this->load->view('admin/footer');
            }else{
                $result = $this->Loan_model->calculate($this->input->get('amount'), $this->input->get('months'), $this->input->get('loan_type'), $this->input->get('loan_date'));
                $data['result'] = $result;
                $menu_toggle['toggles'] = 41;
                $this->load->view('admin/header', $menu_toggle);
                $this->load->view('loan/calculator',$data);
                $this->load->view('admin/footer');
            }

        } else {

        }
    }

    public function calculate_amortization_weekly($loan_amount, $product_id, $loan_term, $start_date) {
        // Calculate the total number of payments
        $loan = check_exist_in_table('loan_products','loan_product_id',$product_id);
        $num_payments = $loan_term;
        $interest_rate =  $loan->interest ;
        // Calculate the weekly interest rate
        $weekly_interest_rate = ($interest_rate / 100) / 52;

        // Calculate the payment amount
        $payment_amount = $loan_amount / $num_payments;

        // Calculate the interest amount for each payment
        $interest_amount = ($loan_amount * $weekly_interest_rate);
        $total_interest_amount = ($loan_amount * $weekly_interest_rate)*$loan_term;

        // Calculate the principal amount for each payment
        $principal_amount = $payment_amount - $interest_amount;

        // Initialize the amortization schedule array
        $amortization_schedule = array();

        // Initialize the payment date to the given start date
        $payment_date = new DateTime($start_date);

        // Loop through each payment period and calculate the payment details


        $table = '<div id="calculator"><h3>Loan Info</h3>';
        $table = $table . '<table border="1" class="table">';
        $table = $table . '<tr><td>Loan Name:</td><td>' . $loan->product_name . '</td></tr>';
        $table = $table . '<tr><td>Interest:</td><td>' . $loan->interest . '%</td></tr>';
        $table = $table . '<tr><td>Admin Fee %:</td><td>' . $loan->admin_fees . '%</td></tr>';
        $table = $table . '<tr><td>Loan cover %:</td><td>' . $loan->loan_cover . '%</td></tr>';
        $table = $table . '<tr><td>Terms:</td><td>' . $loan_term . '/'. $loan->frequency . '</td></tr>';
        $table = $table . '<tr><td>Loan start date:</td><td>' . $start_date . '</td></tr>';
        $table = $table . '<tr><td>Loan effective date:</td><td>' . $start_date . '</td></tr>';

        $table = $table . '<tr><td>Frequency:</td><td> ' . $loan->frequency . ' </td></tr>';
        $table = $table . '</table>';
        $table = $table . '<h3>Computation</h3>';
        $table = $table . '<table>';
        $table = $table . '<tr><td>Loan Amount:</td><td> ' . $this->config->item('currency_symbol') . number_format($loan_amount, 2, '.', ',') . '</td></tr>';
        $table = $table . '<tr><td>Total interest:</td><td> ' . $this->config->item('currency_symbol') . number_format(($total_interest_amount), 2) . '</td></tr>';
        $table = $table . '<tr><td>Total Admin fee:</td><td> ' . $this->config->item('currency_symbol') . number_format((0), 2) . '</td></tr>';
        $table = $table . '<tr><td>Total Loan cover:</td><td> ' . $this->config->item('currency_symbol') . number_format((0), 2) . '</td></tr>';
        $table = $table . '<tr><td>Amount Per Term:</td><td> ' . $this->config->item('currency_symbol') . number_format($payment_amount, 2) . '</td></tr>';

        $table = $table . '<tr><td>Total Payment:</td><td> ' . $this->config->item('currency_symbol') . number_format($loan_amount+$total_interest_amount, 2, '.', ',') . '</td></tr>';
        $table = $table . '</table>';

        //$monthly_payment = $amount*($i/12)*pow((1+$i/12),$months)/(pow((1+$i/12),$months)-1);


        $table = $table . '<table class="table" >

				<tr>
					<th width="30" align="center"><b>Pmt</b></th>
					<th width="60" align="center"><b>Payment</b></th>
					<th width="60" align="center"><b>Principal</b></th>
					<th width="60" align="center"><b>Interest</b></th>
					
					<th width="60" align="center"><b>Admin Fee</b></th>
				
					<th width="60" align="center"><b>Loan cover</b></th>
				
					<th width="70" align="center"><b>Balance</b></th>
				</tr>	
			';


        $table = $table . "<tr>";
        $table = $table . "<td width='30'>0</td>";
        $table = $table . "<td width='60'>&nbsp;</td>";
        $table = $table . "<td width='60'>&nbsp;</td>";

        $table = $table . "<td width='85'>&nbsp;</td>";
        $table = $table . "<td width='85'>&nbsp;</td>";
        $table = $table . "<td width='85'>&nbsp;</td>";


        $table = $table . "<td width='70'>" . round($loan_amount, 2) . "</td>";
        $table = $table . "</tr>";



        for ($i = 1; $i <= $num_payments; $i++) {
            // Check if the payment date falls on a weekend (Saturday or Sunday)
            if ($payment_date->format('N') >= 6) {
                // If so, adjust the payment date to the next available weekday (Monday)
                $payment_date->modify('next monday');
            }

            // Calculate the remaining loan balance
            $loan_balance = $loan_amount - ($i * $payment_amount);

            // Calculate the interest and principal amounts for this payment
            $interest_payment = ($i == 1) ? $interest_amount : $interest_amount + ($loan_balance * $weekly_interest_rate);
            $principal_payment = $payment_amount - $interest_payment;

            // Add the payment details to the amortization schedule array
            $amortization_schedule[] = array(
                'payment_number' => $i,
                'payment_date' => $payment_date->format('Y-m-d'),
                'payment_amount' => $payment_amount,
                'interest_amount' => $interest_payment,
                'principal_amount' => $principal_payment,
                'loan_balance' => $loan_balance,
            );

            $table = $table . "<tr class='table_info'>";
            $table = $table . "<td>" . $i . "</td>";
            $table = $table . "<td>" . number_format(($payment_amount), 2) . "</td>";
            $table = $table . "<td>" . number_format($principal_payment, 2) . "</td>";
            $table = $table . "<td>" . number_format($interest_payment, 2) . "</td>";

            $table = $table . "<td>" . number_format(0, 2) . "</td>";

            $table = $table . "<td>" . number_format(0, 2) . "</td>";
            ;
            $table = $table . "<td>" . number_format($loan_balance, 2) . "</td>";
            $table = $table . "</tr>";




            // Move the payment date to the next week
            $payment_date->modify('+1 week');
        }

        // Return the amortization schedule
        $table = $table . "<tr style='color: white; background-color: #0e9970'>";
        $table = $table . "<td width='30'>-</td>";
        $table = $table . "<td width='30'>-</td>";

        $table = $table . "<td width='70'>-</td>";
        $table = $table . "<td width='70'>-</td>";
        $table = $table . "<td width='70'>-</td>";
        $table = $table . "<td width='70'>-</td>";
        $table = $table . "<td width='70'>-</td>";
        $table = $table . "</tr>";
        $table = $table . '</table></div>';

        return $table;
    }

    function create_act()
    {
        $product_id = (int)$this->input->post('loan_type');
        $has_loan = $this->Loan_model->get_customer_open_loan_for_product(
            $this->input->post('customer'),
            $this->input->post('customer_type'),
            $product_id
        );

        if (!empty($has_loan)) {
            if (strtoupper(trim((string)$has_loan->loan_status)) !== 'ACTIVE') {
                $this->toaster->error('Error, customer already has an open loan for this loan product.');
                redirect($_SERVER['HTTP_REFERER']);
                return;
            }

            $this->db->select_sum('paid_amount');
            $this->db->where('loan_id', $has_loan->loan_id)->where('status', 'NOT PAID');
            $query = $this->db->get('payement_schedules');

            if ($this->input->post('amount') < $query->paid_amount) {
                $this->toaster->error('Error, Sorry principal should be greater than curren ACTIVE loan balance for top up to pass through');
                redirect($_SERVER['HTTP_REFERER']);
                return;
            }
        }
        $branch_id_value = $this->resolve_loan_branch_id_from_post();
        if (!$branch_id_value) {
            $this->toaster->error('Error, please select a branch for this loan.');
            redirect($_SERVER['HTTP_REFERER']);
            return;
        }
        $id = $this->input->post('loan_type');
        $exist = $this->Loan_products_model->get_by_id($id);
        $productAccessErr = $this->validate_loan_product_access_for_user($exist);
        if ($productAccessErr !== null) {
            $this->toaster->error($productAccessErr);
            redirect($_SERVER['HTTP_REFERER']);
            return;
        }

        $term_validation_error = $this->get_group_zitsamba_term_validation_error($id, $this->input->post('months'));
        if ($term_validation_error !== null) {
            $this->toaster->error($term_validation_error);
            redirect($_SERVER['HTTP_REFERER']);
            return;
        }

        $this->load->library('upload');//loading the library
        $imagePath = realpath(APPPATH . '../uploads/');//this is your real path APPPATH means you are at the application folder
        $number_of_files_uploaded = count($_FILES['files']['name']);
        $name = $this->input->post('name');
        $ctype = $this->input->post('type');
        $serial = $this->input->post('serial');
        $value = $this->input->post('value');
        $description = $this->input->post('desc');

        $db_debug = $this->db->db_debug;
        $this->db->db_debug = false;

        $result = null;
        $loan_created = false;
        $last_error = array('code' => 0, 'message' => '');
        $max_attempts = 3;

        for ($attempt = 1; $attempt <= $max_attempts; $attempt++) {
            $this->db->trans_begin();

            $result = $this->Loan_model->add_loan(
                $this->input->post('amount'),
                $this->input->post('months'),
                $this->input->post('loan_type'),
                $this->input->post('loan_date'),
                $this->input->post('customer'),
                $this->input->post('customer_type'),
                $this->input->post('worthness_file'),
                $this->input->post('narration'),
                $this->input->post('user'),
                $branch_id_value,
                $this->input->post('funds_source')
            );

            $last_error = $this->db->error();
            $has_error = ($this->db->trans_status() === false)
                || (!empty($last_error['code']) && (int)$last_error['code'] !== 0)
                || empty($result);

            if ($has_error) {
                $this->db->trans_rollback();

                if ($this->is_duplicate_entry_error($last_error) && $attempt < $max_attempts) {
                    continue;
                }

                break;
            }

            $this->db->trans_commit();
            $loan_created = true;
            break;
        }

        if (!$loan_created) {
            $this->db->db_debug = $db_debug;
            if ($this->is_duplicate_entry_error($last_error)) {
                $this->toaster->error('Error, duplicate loan reference detected. Please retry.');
            } else {
                $this->toaster->error('Error, failed to create loan. Please try again.');
            }
            redirect($_SERVER['HTTP_REFERER']);
            return;
        }

        $collateral_failures = 0;


        for ($i = 0; $i < $number_of_files_uploaded; $i++) {
            $_FILES['userfile']['name'] = $_FILES['files']['name'][$i];
            $_FILES['userfile']['type'] = $_FILES['files']['type'][$i];
            $_FILES['userfile']['tmp_name'] = $_FILES['files']['tmp_name'][$i];
            $_FILES['userfile']['error'] = $_FILES['files']['error'][$i];
            $_FILES['userfile']['size'] = $_FILES['files']['size'][$i];
            //configuration for upload your images
            $config = array(
                'file_name' => rand(100, 1000) . $_FILES['userfile']['name'],
                'allowed_types' => '*',
                'max_size' => 20000,
                'overwrite' => FALSE,
                'upload_path'
                => $imagePath
            );
            $this->upload->initialize($config);
            $errCount = 0;//counting errrs
            if (!$this->upload->do_upload()) {
                $error = array('error' => $this->upload->display_errors());
                $carImages[] = array(
                    'errors' => $error
                );//saving arrors in the array
            } else {

                $filename = $this->upload->data();


                $data = array(
                    'loan_id' => $result,
                    'collateral_name' => $name[$i],
                    'collateral_type' => $ctype[$i],
                    'serial' => $serial[$i],
                    'estimated_price' => $value[$i],
                    'attachement' => $config['file_name'],
                    'description' => $description[$i],
                    'added_by' => $this->session->userdata('user_id'),
                );

                $this->Collateral_model->insert($data);

                $collateral_error = $this->db->error();
                if (!empty($collateral_error['code']) && (int)$collateral_error['code'] !== 0) {
                    $collateral_failures++;
                }

            }//if file uploaded

        }//for loop ends here

        $this->db->db_debug = $db_debug;


        if ($collateral_failures > 0) {
            $this->toaster->success('Success, loan was created pending authorisation. Some collateral files were not saved.');
        } else {
            $this->toaster->success('Success, loan was created  pending authorisation');
        }

        redirect('loan/track');



    }

    function create_group_member_loans_act()
    {
        // Prevent any output that might cause header issues
        ob_start();
        
        try {
            // Get general loan parameters (same for all members)
            $group_id = $this->input->post('group_id');
            $loan_type = $this->input->post('loan_type');
            $loan_date = $this->input->post('loan_date');
            $loan_period = $this->input->post('loan_period'); // Same term for all members
            $batch_number = $this->input->post('batch_number'); // Batch number for all members
            $funds_source = $this->input->post('funds_source');
            $worthness_file = $this->input->post('worthness_file');
            $narration = $this->input->post('narration');
            $user = $this->input->post('user');

            $term_validation_error = $this->get_group_zitsamba_term_validation_error($loan_type, $loan_period);
            if ($term_validation_error !== null) {
                ob_end_clean();
                $this->toaster->error($term_validation_error);
                redirect($_SERVER['HTTP_REFERER']);
                return;
            }

            $loanProductRow = $this->Loan_products_model->get_by_id($loan_type);
            $productAccessErr = $this->validate_loan_product_access_for_user($loanProductRow);
            if ($productAccessErr !== null) {
                ob_end_clean();
                $this->toaster->error($productAccessErr);
                redirect($_SERVER['HTTP_REFERER']);
                return;
            }
            
            // Get member-specific data
            $member_ids = $this->input->post('member_ids');
            $member_amounts = $this->input->post('member_amounts');
            
            $branch_id_value = $this->resolve_loan_branch_id_from_post();
            if (!$branch_id_value) {
                ob_end_clean();
                $this->toaster->error('Error: Please select a branch for these loans.');
                redirect($_SERVER['HTTP_REFERER']);
                return;
            }

            // Validate inputs
            if (empty($member_ids) || empty($member_amounts)) {
                ob_end_clean();
                $this->toaster->error('Error: No members selected or amounts specified');
                redirect($_SERVER['HTTP_REFERER']);
                return;
            }

            // Protect against duplicate member rows from UI payloads.
            $normalized_member_ids = array();
            $normalized_member_amounts = array();
            $seen_member_ids = array();
            for ($i = 0; $i < count($member_ids); $i++) {
                $member_id = (int)$member_ids[$i];
                if ($member_id <= 0 || isset($seen_member_ids[$member_id])) {
                    continue;
                }

                $seen_member_ids[$member_id] = true;
                $normalized_member_ids[] = $member_id;
                $normalized_member_amounts[] = isset($member_amounts[$i]) ? $member_amounts[$i] : 0;
            }

            $member_ids = $normalized_member_ids;
            $member_amounts = $normalized_member_amounts;
        
            $created_loans = 0;
            $failed_loans = 0;
            $duplicate_loans = 0;
            $active_loan_skips = 0;
            $empty_amount_skips = 0;
            $insert_failures = 0;
            $failure_details = array();
            
            // Loop through each member and create individual loans
            for ($i = 0; $i < count($member_ids); $i++) {
                $member_id = $member_ids[$i];
                $amount = isset($member_amounts[$i]) ? preg_replace('/[^0-9.\-]/', '', (string)$member_amounts[$i]) : 0;
                
                // Skip if amount is empty or zero
                if (empty($amount) || $amount <= 0) {
                    $empty_amount_skips++;
                    continue;
                }
                
                try {
                    // One open loan per member per loan product (other products are allowed).
                    $has_loan = $this->Loan_model->get_customer_open_loan_for_product(
                        $member_id,
                        'individual',
                        $loan_type
                    );

                    if (!empty($has_loan)) {
                        $failed_loans++;
                        $active_loan_skips++;
                        $failure_details[] = 'Member ' . $member_id . ' already has open loan ' . $has_loan->loan_number . ' for this product';
                        continue;
                    }

                    // Prevent duplicate: skip if loan already exists for this member+group+batch
                    $dup = $this->db->select("*")->from('loan')
                        ->where('loan_customer', $member_id)
                        ->where('group_id', $group_id)
                        ->where('batch', $batch_number)
                        ->where('loan_status !=', 'DELETED')
                        ->get()->row();
                    if (!empty($dup)) {
                        $failed_loans++;
                        $duplicate_loans++;
                        $failure_details[] = 'Member ' . $member_id . ' already exists in batch ' . $batch_number;
                        continue;
                    }

                    // Create individual loan for this member
                    $result = $this->Loan_model->add_loan(
                        $amount,                    // loan amount (individual per member)
                        $loan_period,              // loan term (same for all)
                        $loan_type,                // loan product (same for all)
                        $loan_date,                // loan date (same for all)
                        $member_id,                // customer (individual member)
                        'individual',              // customer type (individual)
                        $worthness_file,           // worthiness file (same for all)
                        $narration,                // narration (same for all)
                        $user,                     // added by (same for all)
                        $branch_id_value,            // branch (same for all)
                        $funds_source,             // funds source (same for all)
                        $batch_number,             // batch number (same for all)
                        'Yes',                     // from_group = Yes for group member loans
                        $group_id                  // group_id = the selected group
                    );
                    
                    if ($result) {
                        $created_loans++;
                    } else {
                        $failed_loans++;
                        $insert_failures++;
                        $failure_details[] = 'Member ' . $member_id . ' insert returned no loan ID';
                    }
                    
                } catch (Exception $e) {
                    $failed_loans++;
                    $insert_failures++;
                    $failure_details[] = 'Member ' . $member_id . ': ' . $e->getMessage();
                    // Log error or continue with next member
                    log_message('error', 'Failed to create loan for member ' . $member_id . ': ' . $e->getMessage());
                }
            }
            
            // Clear output buffer
            ob_end_clean();
            
            // Show success/error messages
            if ($created_loans > 0) {
                $this->toaster->success("Success: Created {$created_loans} individual loans for group members");
            }
            
            if ($failed_loans > 0) {
                $warning_parts = array();
                if ($duplicate_loans > 0) {
                    $warning_parts[] = "{$duplicate_loans} already exist in this batch";
                }
                if ($active_loan_skips > 0) {
                    $warning_parts[] = "{$active_loan_skips} already have an open loan for this product";
                }
                if ($empty_amount_skips > 0) {
                    $warning_parts[] = "{$empty_amount_skips} have no valid amount";
                }
                if ($insert_failures > 0) {
                    $warning_parts[] = "{$insert_failures} failed during insert";
                }
                if (empty($warning_parts)) {
                    $warning_parts[] = "{$failed_loans} could not be created";
                }
                $this->toaster->warning('Warning: ' . implode(', ', $warning_parts) . '.');
            }
            
            if ($created_loans == 0) {
                if ($duplicate_loans > 0 && $failed_loans == $duplicate_loans) {
                    $this->toaster->success('Loans for this group and batch were already created.');
                    redirect('loan/group_batch_loans/' . urlencode($batch_number));
                    return;
                }

                if ($empty_amount_skips > 0 && $failed_loans == 0) {
                    $this->toaster->error('Error: No loans were created because no member had a valid amount.');
                } elseif ($active_loan_skips > 0) {
                    $this->toaster->error('Error: No loans were created because the selected members already have an open loan for this loan product.');
                } elseif ($insert_failures > 0) {
                    $message = 'Error: No loans were created because insert failed.';
                    if (!empty($failure_details)) {
                        $message .= ' ' . implode('; ', array_slice($failure_details, 0, 3));
                    }
                    $this->toaster->error($message);
                } else {
                    $message = 'Error: No loans were created. Please check member selection and amounts.';
                    if (!empty($failure_details)) {
                        $message .= ' ' . implode('; ', array_slice($failure_details, 0, 3));
                    }
                    $this->toaster->error($message);
                }
                redirect($_SERVER['HTTP_REFERER']);
            } else {
                redirect('loan/track');
            }
            
        } catch (Exception $e) {
            // Clear output buffer in case of error
            ob_end_clean();
            
            // Log the error
            log_message('error', 'Error in create_group_member_loans_act: ' . $e->getMessage());
            
            // Show error message and redirect
            $this->toaster->error('Error: ' . $e->getMessage());
            redirect($_SERVER['HTTP_REFERER']);
        }
    }


    function create_act_edit(){
        $row = get_by_id('approval_edits','approval_edits_id',$this->session->userdata('loan_data'));
        $data_new = json_decode($row->new_info);

        $selected_period_type = isset($data_new->period_type) ? $data_new->period_type : null;
        $this->Loan_model->add_loan_edit($row->id,$data_new->loan_principal, $data_new->loan_period, $data_new->sy_loan_product, $data_new->loan_date,$data_new->sy_loan_customer,$data_new->customer_type,$data_new->loan_worthness_file,$data_new->narration,$data_new->sy_added_by, $selected_period_type);
        $this->toaster->success('Success, loan edit was authorised  pending authorisation');
        redirect('loan/edit_approve');


    }
    public  function create_act_delete()
    {
        $row = get_by_id('approval_edits','approval_edits_id',$this->session->userdata('loan_delete'));

        $this->Loan_model->update($row->id,array('loan_status'=>'DELETED'));
        $this->toaster->success('Success, loan delete was authorised and archived');
        redirect('loan/delete_approve');
    }
    function create_acti(){

        $group = $this->Groups_model->check($this->input->post('group_id'));
        $branch_id_value = $this->resolve_loan_branch_id_from_post();
        if (!$branch_id_value) {
            $this->toaster->error('Error, please select a branch for this loan.');
            redirect($_SERVER['HTTP_REFERER']);
            return;
        }

        $loanProductRow = $this->Loan_products_model->get_by_id($this->input->post('loan_type'));
        $productAccessErr = $this->validate_loan_product_access_for_user($loanProductRow);
        if ($productAccessErr !== null) {
            $this->toaster->error($productAccessErr);
            redirect($_SERVER['HTTP_REFERER']);
            return;
        }

        $term_validation_error = $this->get_group_zitsamba_term_validation_error($this->input->post('loan_type'), $this->input->post('months'));
        if ($term_validation_error !== null) {
            $this->toaster->error($term_validation_error);
            redirect($_SERVER['HTTP_REFERER']);
            return;
        }

        $existing_product_loan = $this->Loan_model->get_customer_open_loan_for_product(
            $this->input->post('customer'),
            null,
            $this->input->post('loan_type')
        );
        if ($existing_product_loan) {
            $this->toaster->error('Error, this customer already has an open loan for this loan product.');
            redirect($_SERVER['HTTP_REFERER']);
            return;
        }

        if(!empty($group)){
            $user_gotten = $this->Group_loan_tracker_model->validate($this->input->post('group_id'),$this->input->post('customer'),$group->id);
            if(!empty($user_gotten)){
                $this->toaster->error('Error, Sorry this member has received his shares already from this group');
                redirect($_SERVER['HTTP_REFERER']);
            }else{
                $validate_trans = $this->Group_loan_tracker_model->validate_trans($group->id);
                if(($validate_trans->amount+$this->input->post('amount')) > $group->amount){
                    $this->toaster->error('Error, Sorry this group has no enough amount to create this loan contract, please add smaller amount');
                    redirect($_SERVER['HTTP_REFERER']);
                }else{

                    $result = $this->Loan_model->add_loan(
                        $this->input->post('amount'),
                        $this->input->post('months'),
                        $this->input->post('loan_type'),
                        $this->input->post('loan_date'),
                        $this->input->post('customer'),
                        'individual',
                        $this->input->post('worthness_file'),
                        $this->input->post('narration'),
                        $this->input->post('user'),
                        $branch_id_value,
                        $this->input->post('funds_source')
                    );
                    $data['result'] = $result;
                    $this->toaster->success('Success, customer was created  pending authorisation');
                    $data = array(
                        'disbursement_id' => $group->id,
                        'group_id' => $this->input->post('group_id',TRUE),
                        'customer_id' => $this->input->post('customer',TRUE),
                        'amount' => $this->input->post('amount',TRUE),

                    );

                    $this->Group_loan_tracker_model->insert($data);
                    redirect('loan/track');
                }
            }
        }else{
            $this->toaster->error('Error, Sorry this group has no amount assigned yet , Please assign group amount first');
            redirect($_SERVER['HTTP_REFERER']);
        }





    }
    function initiated(){
        $this->load_paginated_loan_list('loan/loan_list', $this->loan_list_page_options('initiated', array(
            'default_status' => 'RECOMMENDED',
        )));
    }
    public function transaction_reversal()
    {
        $trans_id = $this->input->get('tid');
        $loan_number =  $this->input->get('account');
        $get_account = $this->Tellering_model->get_teller_account($this->session->userdata('user_id'));
        if (empty($get_account) && $this->session->userdata('RoleName') === 'SUPER ADMIN') {
            $get_account = $this->Tellering_model->get_teller_account1();
        }
        $tid = "TR-S" . rand(100, 9999) . date('Y') . date('m') . date('d');
        $date = date("Y-m-d");
        if(empty($get_account)){
            $this->toaster->error('You are not authorized to do this transaction');
            redirect($_SERVER['HTTP_REFERER']);
        }
        else
        {
            $get_transaction =  get_one_where('transaction', 'transaction_id ="'.$trans_id.'" AND account_number ="'.$loan_number.'" ');
            $amount = $get_transaction->credit;
            $recepientt = get_by_id('account', 'collection_account', 'Yes');
            $do_transactions = $this->Account_model->transfer_funds($recepientt->account_number,$loan_number, $amount, $tid, $date);
            if ($do_transactions == 'success') {
                $mode = 'withdraw';
                $res = $this->Account_model->cash_transaction_modified($get_account->account, $loan_number, $amount, $mode, $tid, $date,'reversal');
                $this->db->where('transaction_id',$get_transaction->transaction_id)->update('transaction', array('reversed'=>'Yes'));
                $this->db->where('transaction_id',$tid)->update('transaction', array('reversed'=>'Yes'));
                if ($res == 'success') {
                    $reverse_loans_repayments = $this->db->where('ref',$trans_id)->get('transactions')->result();
                    $schedules_reversed = 0;
                    $affected_loan_id = null;
                    foreach ($reverse_loans_repayments as $to_act){
                        $get_schedules = $this->db->where('loan_id',$to_act->loan_id)->where('payment_number',$to_act->payment_number)
                            ->get('payement_schedules')->row();
                        if (!$get_schedules) continue;
                        $to_remove_amount = $get_schedules->paid_amount;
                        $to_update_amount = max(0, $to_remove_amount - $to_act->amount);
                        if($to_update_amount <= 0){
                            $this->db->where('id',$get_schedules->id)->update('payement_schedules',array('paid_amount'=>0,"status"=>"NOT PAID","partial_paid"=>"NO"));
                            $schedules_reversed++;
                        }else{
                            $this->db->where('id',$get_schedules->id)->update('payement_schedules',array('paid_amount'=>$to_update_amount));
                        }
                        $this->db->where('loan_id',$to_act->loan_id)->update('loan',array('loan_status'=>"ACTIVE"));
                        $affected_loan_id = $to_act->loan_id;
                    }
                    // Recalculate loan_balance for affected loan schedules to fix amortization display
                    if ($affected_loan_id) {
                        $this->Payement_schedules_model->recalculate_loan_balances($affected_loan_id);
                    }

                    // Adjust next_payment_id by subtracting the number of fully reversed schedules
                    if($affected_loan_id && $schedules_reversed > 0){
                        $loan_row = $this->db->where('loan_id', $affected_loan_id)->get('loan')->row();
                        if($loan_row){
                            $new_next = max(1, $loan_row->next_payment_id - $schedules_reversed);
                            $this->db->where('loan_id', $affected_loan_id)->update('loan', array('next_payment_id' => $new_next));
                        }
                    }

                    $this->toaster->success('Transaction reversal was successful');
                    redirect($_SERVER['HTTP_REFERER']);
                }
            }


        }
    }

    function loan_revenue(){
        $data['loan_data'] = $this->Loan_model->get_all_revenue();
        $menu_toggle['toggles'] = 23;
        $this->load->view('admin/header', $menu_toggle);
        $this->load->view('reports/revenue_payments', $data);
        $this->load->view('admin/footer');
    }
    function balances(){
        $product = $this->input->get('product');
        $officer= $this->input->get('officer');
        $loan= $this->input->get('loan');
        $from= $this->input->get('from');
        $to= $this->input->get('to');
        $data['loan_data'] = $this->Loan_model->get_all_balances($product, $officer, $loan, $from, $to);
        $menu_toggle['toggles'] = 23;
        $this->load->view('admin/header', $menu_toggle);
        $this->load->view('reports/outstanding_balances', $data);
        $this->load->view('admin/footer');
    }
    function recommend(){
        $this->load_paginated_loan_list('loan/recommend', $this->loan_list_page_options('recommend', array(
            'default_status' => 'INITIATED',
        )));
    }
    function restructure(){
        $batch = $this->input->get('batch');
        $options = $this->loan_list_page_options('restructure');
        if (!empty($batch) && !$this->loan_list_has_filter_params()) {
            $options['batch'] = $batch;
            $options['batch_filter'] = $batch;
        }
        $this->load_paginated_loan_list('loan/restructure', $options);
    }
    function track(){
        $this->load_paginated_loan_list('loan/track', $this->loan_list_page_options('track', array(
            'default_status' => 'All',
        )));
    }
    function individual_track(){
        $this->load_paginated_loan_list('loan/track', $this->loan_list_page_options('individual_track', array(
            'officer_id' => $this->session->userdata('user_id'),
            'default_status' => 'All',
        )));
    }
    /** Statuses that may receive repayments on loan payment screens. */
    private function loan_repayment_statuses()
    {
        return array('ACTIVE', 'WRITTEN_OFF');
    }

    function loan_repayment(){
        $this->load_paginated_loan_list('loan/loan_repayment', $this->loan_list_page_options('loan_repayment', array(
            'repayment_list' => true,
            'status_in' => $this->loan_repayment_statuses(),
            'menu_toggle' => array('toggles' => 52),
        )));
    }
    function loan_repayment_pay_off(){
        $this->load_paginated_loan_list('loan/loan_repayment_pay_off', $this->loan_list_page_options('loan_repayment_pay_off', array(
            'status_in' => $this->loan_repayment_statuses(),
            'menu_toggle' => array('toggles' => 52),
        )));
    }
    function assign(){
        $this->load_paginated_loan_list('loan/active', $this->loan_list_page_options('assign', array(
            'default_status' => 'All',
        )));
    }
    function closed(){
        $this->load_paginated_loan_list('loan/closed', $this->loan_list_page_options('closed', array(
            'default_status' => 'CLOSED',
        )));
    }

    function approved(){
        $this->load_paginated_loan_list('loan/approved', $this->loan_list_page_options('approved', array(
            'default_status' => 'APPROVED',
        )));
    }
    function disbursed(){
        $this->load_paginated_loan_list('loan/active_loans', $this->loan_list_page_options('disbursed', array(
            'disbursed' => 'Yes',
            'default_status' => 'All',
        )));
    }
    function write_off(){
        $this->load_paginated_loan_list('loan/write_off', $this->loan_list_page_options('write_off', array(
            'default_status' => 'ACTIVE',
        )));
    }
    function rejected(){
        $this->load_paginated_loan_list('loan/approved', $this->loan_list_page_options('rejected', array(
            'default_status' => 'REJECTED',
            'plain_header' => true,
        )));
    }
    function written_off(){
        $this->load_paginated_loan_list('loan/written_off', $this->loan_list_page_options('written_off', array(
            'default_status' => 'WRITTEN_OFF',
        )));
    }
    function write_off_approve(){
        $this->load_paginated_loan_list('loan/write_off_approval', $this->loan_list_page_options('write_off_approve', array(
            'default_status' => 'ACTIVE',
            'written_off_pending' => true,
        )));
    }

    function delete_permanent(){
        $this->load_paginated_loan_list('loan/delete_p', $this->loan_list_page_options('delete_permanent', array(
            'default_status' => 'All',
        )));
    }

    /**
     * List loans archived with status DELETED.
     */
    function deleted_loans(){
        $this->load_paginated_loan_list('loan/deleted_loans', $this->loan_list_page_options('deleted_loans', array(
            'default_status' => 'DELETED',
            'exclude_deleted' => false,
        )));
    }
    public function pay_advance(){
        $loan_number = $this->input->post('loan_id');
        $pay_number = $this->input->post('payment_number');
        $amount = $this->input->post('amount');
        $amount_total = 0;
        for($i=0;$i <count($pay_number);$i++){
            $amount_total += $amount;
        }
        $loan_account = get_by_id('loan','loan_id',$loan_number);
        $recepientt = get_by_id('account','collection_account','Yes');
        $check = $this->Account_model->get_account($loan_account->loan_number);
        if($check->balance >= $amount_total) {

            $result = $this->Payement_schedules_model->pay_advance($loan_number, $amount, $pay_number);
            if ($result) {



                //get loan products id
                $dataloanproductsid = get_by_id2('loan','loan_id ='.$loan_number);
                $remainingschedule = ( $dataloanproductsid ->loan_period-$pay_number);
                $counter=1;
                $pay_number=$pay_number+1;

                $realaccountbalance= $check->balance;
                while($counter <= $remainingschedule)
                {


                    $topup = $this->Payement_schedules_model->topnew_pay($loan_number,$pay_number,$amount,$realaccountbalance);
                    $pay_number=$pay_number+1;
                    $counter=$counter+1;
                }
                // Get loan and customer details for enhanced logging
                $customer_data = $this->get_customer_name_for_loan($loan_number);
                $payment_numbers_str = implode(', ', $pay_number);

                $logger = array(
                    'user_id' => $this->session->userdata('user_id'),
                    'activity' => 'Advance Loan Payment: ' .
                                  ' | Amount: MWK ' . number_format($amount_total, 2) .
                                  ' | Client: ' . $customer_data['customer_name'] .
                                  ' | Loan #: ' . (!empty($customer_data['loan_details']) ? $customer_data['loan_details']->loan_number : $loan_number) .
                                  ' | Payment #s: ' . $payment_numbers_str
                );
                log_activity($logger);

                $this->toaster->success('Success, advance payment was successful');
                redirect($_SERVER['HTTP_REFERER']);
            } else {
                $this->toaster->error('Ops!, Sorry advance payment failed P7');
                redirect($_SERVER['HTTP_REFERER']);
            }
        }else{
            $this->toaster->error('Ops!, Sorry advance payment failed, You dont have enough funds to perform this transactions');
            redirect($_SERVER['HTTP_REFERER']);
        }
    }
    public function finish_loan(){
        $loan_number = $this->input->post('loan_id');
        $pay_number = $this->input->post('payment_number');
        $middlepayment = $this->input->post('middlepayment');
        $repay_amounts = round($this->input->post('repay_amounts'));
        $amount = $this->input->post('amount');
        $totalbalance = $this->input->post('totalbalance');

        $proof = $this->input->post('pay_proof');
        $paid_date = $this->input->post('pdate');

        // --- Payment Reference validation ---
        $payment_reference = trim($this->input->post('payment_reference', TRUE));
        $payment_type      = trim($this->input->post('payment_type', TRUE));

        if (empty($payment_reference)) {
            $this->toaster->error('Payment reference is required. Please enter the Transaction ID (bank) or Receipt Number (cash).');
            redirect($_SERVER['HTTP_REFERER']);
            return;
        }
        if (!in_array($payment_type, ['bank', 'cash'])) {
            $this->toaster->error('Please select a valid payment type (Bank or Cash).');
            redirect($_SERVER['HTTP_REFERER']);
            return;
        }
        $dup = $this->Transactions_model->check_duplicate_reference($payment_reference);
        if (!empty($dup)) {
            $this->toaster->error('Duplicate payment reference detected. Reference "' . htmlspecialchars($payment_reference, ENT_QUOTES, 'UTF-8') . '" has already been used. Please verify the proof of payment and try again.');
            redirect($_SERVER['HTTP_REFERER']);
            return;
        }
        // --- End payment reference validation ---

        $loan_account = get_by_id('loan', 'loan_id', $loan_number);
        $tid = "TR-S" . rand(100, 9999) . date('Y') . date('m') . date('d');
        $get_account = $this->Tellering_model->get_teller_account($this->session->userdata('user_id'));
        if (empty($get_account) && $this->session->userdata('RoleName') === 'SUPER ADMIN') {
            $get_account = $this->Tellering_model->get_teller_account1();
        }
        if(empty($get_account)){
            $this->toaster->error('You are not authorized to do this transaction');
            redirect($_SERVER['HTTP_REFERER']);
        }else {
            $teller_account = $get_account->account;

            $mode = 'deposit';

            $res =	$this->Account_model->cash_transaction_modified($teller_account,$loan_account->loan_number,$amount,$mode,$tid,$paid_date,'deposit');
            if($res=='success') {

                $recepientt = get_by_id('account', 'collection_account', 'Yes');
                $check = $this->Account_model->get_account($loan_account->loan_number);
                if ($check->balance >= $amount) {
                    $do_transactions = $this->Account_model->transfer_funds($loan_account->loan_number, $recepientt->account_number, $amount, $tid, $paid_date);
                    if ($do_transactions == 'success') {
                        $this->Payement_schedules_model->new_pay_new($loan_number, $pay_number, $repay_amounts, $paid_date, $tid);

                        // Record the transaction with payment reference
                        $trans_data = array(
                            'ref'               => $tid,
                            'loan_id'           => $loan_number,
                            'amount'            => $amount,
                            'transaction_type'  => 1,
                            'payment_number'    => $pay_number,
                            'payment_reference' => $payment_reference,
                            'payment_type'      => $payment_type,
                            'added_by'          => $this->session->userdata('user_id'),
                        );
                        $this->Transactions_model->insert($trans_data);

                        $logger = array(
                            'user_id' => $this->session->userdata('user_id'),
                            'activity' => 'Loan Pay-Off: Trans Ref: ' . $tid .
                                          ' | Pay Ref: ' . $payment_reference .
                                          ' | Type: ' . strtoupper($payment_type) .
                                          ' | Loan ID: ' . $loan_number .
                                          ' | Payment #: ' . $pay_number .
                                          ' | Amount: MWK ' . number_format($amount, 2),
                        );
                        log_activity($logger);
                        $this->nullify_schedules($loan_number,$middlepayment,$totalbalance);
                        $this->db->where('loan_id',$loan_number)->update('loan',array('paid_off'=>"YES"));
                        $this->toaster->success('Success, payment was successful');
                        redirect($_SERVER['HTTP_REFERER']);
                    } else {
                        $this->toaster->error('Error!, Sorry Payment failed');
                        redirect($_SERVER['HTTP_REFERER']);
                    }
                } else {
                    $this->toaster->error('Error!, balance not enough for payment');
                    redirect($_SERVER['HTTP_REFERER']);
                }
            }else{
                $this->toaster->error('Error!, deposit module failed');
                redirect($_SERVER['HTTP_REFERER']);
            }

        }


    }
    function nullify_schedules($loan_id,$middle, $totalbalance)
    {
        $loan_id = (int) $loan_id;
        $middle = (int) $middle;

        $this->db->where('loan_id', $loan_id)
            ->where('payment_number', $middle)
            ->update('payement_schedules', array(
                'paid_amount' => $totalbalance,
                'loan_balance' => 0,
                'partial_paid' => 'NO',
                'status' => 'PAID',
            ));
        $this->db->where('loan_id', $loan_id)
            ->where('payment_number >', $middle)
            ->update('payement_schedules', array(
                'amount' => 0,
                'principal' => 0,
                'interest' => 0,
                'padmin_fee' => 0,
                'ploan_cover' => 0,
                'paid_amount' => 0,
                'loan_balance' => 0,
                'partial_paid' => 'NO',
                'status' => 'NOT PAID',
            ));

        $this->db->where('loan_id', $loan_id)->update('loan', array(
            'paid_off' => 'YES',
            'loan_status' => 'CLOSED',
        ));
    }
    function finish_loan_backup(){
        $tid="TR-S".rand(100,9999).date('Y').date('m').date('d');
        $loan_number = $this->input->post('loan_id');
        $pay_number = $this->input->post('payment_number');
        $amount = $this->input->post('amount');
        $proof = $this->input->post('pay_proof');
        $loan_account = get_by_id('loan','loan_id',$loan_number);
        $recepientt = get_by_id('account','collection_account','Yes');
        $check = $this->Account_model->get_account($loan_account->loan_number);
        if($check->balance >= $amount){
            $do_transactions = $this->Account_model->transfer_funds($loan_account->loan_number, $recepientt->account_number, $amount, $tid);
            if($do_transactions=='success'){
                $result = $this->Payement_schedules_model->finish_pay($loan_number,$pay_number,$amount);

                if($result== true){

                    $logger = array(

                        'user_id' => $this->session->userdata('user_id'),
                        'activity' => 'Paid a loan, loan ID:'.' '.$loan_number.' '.' payment number'.' '.$pay_number.
                            ' '.'amount'.' '.$amount,
                        'activity_cate' => 'loan_repayment'

                    );
                    log_activity($logger);
                    $this->toaster->success('Success, payment was successful');
                    redirect($_SERVER['HTTP_REFERER']);
                }else{
                    $this->toaster->error('Ops!, Sorry payment failed P2');
                    redirect($_SERVER['HTTP_REFERER']);
                }
            }else{
                $this->toaster->error('Ops!, Sorry payment failed, Error P2');
                redirect($_SERVER['HTTP_REFERER']);
            }
        } else{
            $this->toaster->error('Ops!, Sorry payment failed loan account savings does not have enough funds');
            redirect($_SERVER['HTTP_REFERER']);
        }
    }
    public function cash_transaction_second($account1,$amount,$tid,$teller_account,$date){

        $mode = 'deposit';
        $this->Account_model->cash_transaction($teller_account,$account1,$amount,$mode,$tid,$date);
        return true;
    }
    /**
     * AJAX endpoint — check whether a payment reference number is already used.
     * Returns JSON: { "duplicate": true|false }
     */
    public function check_payment_reference()
    {
        header('Content-Type: application/json');
        $ref = trim($this->input->post('payment_reference', TRUE));
        if (empty($ref)) {
            echo json_encode(['duplicate' => false]);
            return;
        }
        $existing = $this->Transactions_model->check_duplicate_reference($ref);
        echo json_encode(['duplicate' => !empty($existing)]);
    }

    public function pay_loan(){
        $loan_number = $this->input->post('loan_id');
        $pay_number = $this->input->post('payment_number');
        $number_with_commas = $this->input->post('amount');
        $amount = str_replace(',', '', $number_with_commas);
        $topay_amount = $this->input->post('topay_amount');
        $input_datetime = $this->input->post('pdate');
        $datetime = new DateTime($input_datetime);

// Format the DateTime object to the desired format
        $proof = $datetime->format('Y-m-d H:i:s');

        // --- Payment Reference validation ---
        $payment_reference = trim($this->input->post('payment_reference', TRUE));
        $payment_type      = trim($this->input->post('payment_type', TRUE));

        if (empty($payment_reference)) {
            $this->toaster->error('Payment reference is required. Please enter the Transaction ID (bank) or Receipt Number (cash).');
            redirect($_SERVER['HTTP_REFERER']);
            return;
        }
        if (!in_array($payment_type, ['bank', 'cash'])) {
            $this->toaster->error('Please select a valid payment type (Bank or Cash).');
            redirect($_SERVER['HTTP_REFERER']);
            return;
        }
        $dup = $this->Transactions_model->check_duplicate_reference($payment_reference);
        if (!empty($dup)) {
            $this->toaster->error('Duplicate payment reference detected. Reference "' . htmlspecialchars($payment_reference, ENT_QUOTES, 'UTF-8') . '" has already been used. Please verify the proof of payment and try again.');
            redirect($_SERVER['HTTP_REFERER']);
            return;
        }
        // --- End payment reference validation ---

        $loan_n = get_by_id('loan','loan_id',$loan_number);
        $collection_acccount = get_by_id('account','collection_account','Yes');

        $get_account = $this->Tellering_model->get_teller_account($this->session->userdata('user_id'));
        // SUPER ADMIN bypass: use internal teller account if no teller assigned
        if (empty($get_account) && $this->session->userdata('RoleName') === 'SUPER ADMIN') {
            $get_account = $this->Tellering_model->get_teller_account1();
        }
        $tid = "TR-S" . rand(100, 9999) . date('Y') . date('m') . date('d');
        $mode = 'deposit';
        if(!empty($get_account)){
            // Check if there are late charges for this payment schedule
            $this->db->select('total_late_charge');
            $this->db->from('payement_schedules');
            $this->db->where('loan_id', $loan_number);
            $this->db->where('payment_number', $pay_number);
            $schedule = $this->db->get()->row();

            $has_late_charges = ($schedule && ($schedule->total_late_charge ?? 0) > 0);

            // Use appropriate payment method based on late charges
            if ($has_late_charges) {
                $r = $this->Payement_schedules_model->pay_loan_with_late_charges($loan_number, $pay_number, $amount, $proof, $tid);
            } else {
                $r = $this->Payement_schedules_model->new_pay_new($loan_number, $pay_number, $amount, $proof, $tid);
            }

            // Handle different return types
            $payment_successful = false;
            $allocation_info = "";

            if (is_array($r) && isset($r['success']) && $r['success']) {
                // New payment method with late charges
                $payment_successful = true;
                if (isset($r['allocation_log'])) {
                    $allocation_info = " | Payment Allocation: " . implode(', ', $r['allocation_log']);
                }
            } elseif (!empty($r)) {
                // Traditional payment method
                $payment_successful = true;
            }

            if($payment_successful){
                $teller_account = $get_account->account;
                $this->Account_model->cash_transaction_modified($teller_account,$loan_n->loan_number,$amount,$mode,$tid,$proof,'deposit');


                $this->Account_model->transfer_funds1($loan_n->loan_number, $collection_acccount->account_number,$amount, $tid,$proof);

                // Record the transaction with payment reference for duplicate-prevention tracking
                $trans_data = array(
                    'ref'              => $tid,
                    'loan_id'          => $loan_number,
                    'amount'           => $amount,
                    'transaction_type' => 1,
                    'payment_number'   => $pay_number,
                    'payment_reference'=> $payment_reference,
                    'payment_type'     => $payment_type,
                    'added_by'         => $this->session->userdata('user_id'),
                );
                $this->Transactions_model->insert($trans_data);

                // Get loan and customer details for enhanced logging
                $customer_data = $this->get_customer_name_for_loan($loan_number);

                $logger = array(
                    'user_id' => $this->session->userdata('user_id'),
                    'activity' => 'Loan Payment: Trans Ref: ' . $tid .
                                  ' | Pay Ref: ' . $payment_reference .
                                  ' | Type: ' . strtoupper($payment_type) .
                                  ' | Amount: MWK ' . number_format($amount, 2) .
                                  ' | Client: ' . $customer_data['customer_name'] .
                                  ' | Loan #: ' . (!empty($customer_data['loan_details']) ? $customer_data['loan_details']->loan_number : $loan_number) .
                                  ' | Payment #: ' . $pay_number .
                                  $allocation_info
                );
                log_activity($logger);

                $this->toaster->success('Success, payment was successful');
                redirect($_SERVER['HTTP_REFERER']);
            }
        }
        else{
            $this->toaster->error('Sorry, you are not authorised to make payments');
            redirect($_SERVER['HTTP_REFERER']);
        }





    }

    public function pay_loan_old(){
        $loan_number = $this->input->post('loan_id');
        $pay_number = $this->input->post('payment_number');
        $amount = $this->input->post('amount');
        $topay_amount = $this->input->post('topay_amount');
        $proof = $this->input->post('pdate');

        $tid = "TR-S" . rand(100, 9999) . date('Y') . date('m') . date('d');

        $loan_account = get_by_id('loan', 'loan_id', $loan_number);
        $recepientt = get_by_id('account', 'collection_account', 'Yes');
        $check = $this->Account_model->get_account($loan_account->loan_number);
        if ($topay_amount >= $amount) {
            $do_transactions = $this->Account_model->transfer_funds($loan_account->loan_number, $recepientt->account_number, $amount, $tid);
            if ($do_transactions == 'success') {
                $result = $this->Payement_schedules_model->new_pay($loan_number, $pay_number, $amount, $proof);

                if ($result == true) {

                    $logger = array(

                        'user_id' => $this->session->userdata('user_id'),
                        'activity' => 'Paid a loan, loan ID:' . ' ' . $loan_number . ' ' . ' payment number' . ' ' . $pay_number .
                            ' ' . 'amount' . ' ' . $amount,


                    );
                    log_activity($logger);
                    $this->toaster->success('Success, payment was successful');
                    redirect($_SERVER['HTTP_REFERER']);
                } else {
                    $this->toaster->error('Ops!, Sorry payment failed P2');
                    redirect($_SERVER['HTTP_REFERER']);
                }
            } else {
                $this->toaster->error('Ops!, Sorry payment failed, Error P2');
                redirect($_SERVER['HTTP_REFERER']);
            }
        } elseif ($check->balance > 0 && $check->balance < $amount) {
            $topay_amount = $check->balance;
            $this->Account_model->transfer_funds($loan_account->loan_number, $recepientt->account_number, $topay_amount, $tid);
            $r = $this->Payement_schedules_model->new_pay($loan_number, $pay_number, $topay_amount, $proof);

            $logger = array(

                'user_id' => $this->session->userdata('user_id'),
                'activity' => 'Paid a loan, loan ID:' . ' ' . $loan_number . ' ' . ' payment number' . ' ' . $pay_number .
                    ' ' . 'amount' . ' ' . $topay_amount,


            );
            log_activity($logger);
            $data = array(
                'ref' => "GF." . date('Y') . date('m') . date('d') . '.' . rand(100, 999),
                'loan_id' => $this->input->post('loan_id', TRUE),
                'amount' => $topay_amount,
                'transaction_type' => 2,
                'payment_number' => $this->input->post('payment_number'),
                'added_by' => $this->session->userdata('user_id')

            );

            $this->Transactions_model->insert($data);
            $this->toaster->success('Success, payment was successful');
            redirect($_SERVER['HTTP_REFERER']);

        } else {
            $this->toaster->error('Ops!, Sorry payment failed loan account savings does not have enough funds');
            redirect($_SERVER['HTTP_REFERER']);
        }

    }
    public function pay_loan_r(){
        $loan_number = $this->input->post('loan_id');
        $pay_number = $this->input->post('payment_number');
        $pay_number_r = $this->input->post('payment_numberr');
        $amount = $this->input->post('amount');
        $proof = $this->input->post('pay_proof');
        if($this->input->post('payment_method')=="0") {
            $tid = "TR-S" . rand(100, 9999) . date('Y') . date('m') . date('d');

            $loan_account = get_by_id('loan', 'loan_id', $loan_number);
            $recepientt = get_by_id('account', 'collection_account', 'Yes');
            $check = $this->Account_model->get_account($loan_account->loan_number);
            if ($check->balance >= $amount) {
                $do_transactions = $this->Account_model->transfer_funds($loan_account->loan_number, $recepientt->account_number, $amount, $tid);
                if ($do_transactions == 'success') {
                    $result = $this->Payement_schedules_model->new_pay($loan_number, $pay_number, $amount);

                    if ($result == true) {

                        $logger = array(

                            'user_id' => $this->session->userdata('user_id'),
                            'activity' => 'Paid a loan, loan ID:' . ' ' . $loan_number . ' ' . ' payment number' . ' ' . $pay_number .
                                ' ' . 'amount' . ' ' . $amount,
                            'activity_cate' => 'loan_repayment'

                        );
                        log_activity($logger);
                        $this->toaster->success('Success, payment was successful');
                        redirect($_SERVER['HTTP_REFERER']);
                    } else {
                        $this->toaster->error('Ops!, Sorry payment failed P2');
                        redirect($_SERVER['HTTP_REFERER']);
                    }
                } else {
                    $this->toaster->error('Ops!, Sorry payment failed, Error P2');
                    redirect($_SERVER['HTTP_REFERER']);
                }
            } elseif ($check->balance > 0 && $check->balance < $amount) {
                $topay_amount = $check->balance;
                $this->Account_model->transfer_funds($loan_account->loan_number, $recepientt->account_number, $topay_amount, $tid);
                $r = $this->Payement_schedules_model->new_pay($loan_number, $pay_number, $topay_amount);

                $logger = array(

                    'user_id' => $this->session->userdata('user_id'),
                    'activity' => 'Paid a loan, loan ID:' . ' ' . $loan_number . ' ' . ' payment number' . ' ' . $pay_number .
                        ' ' . 'amount' . ' ' . $topay_amount,
                    'activity_cate' => 'loan_repayment'

                );
                log_activity($logger);
                $data = array(
                    'ref' => "GF." . date('Y') . date('m') . date('d') . '.' . rand(100, 999),
                    'loan_id' => $this->input->post('loan_id', TRUE),
                    'amount' => $topay_amount,
                    'transaction_type' => 2,
                    'payment_number' => $this->input->post('payment_number'),
                    'added_by' => $this->session->userdata('user_id')

                );

                $this->Transactions_model->insert($data);
                $this->toaster->success('Success, payment was successful');
                redirect($_SERVER['HTTP_REFERER']);

            } else {
                $this->toaster->error('Ops!, Sorry payment failed loan account savings does not have enough funds');
                redirect($_SERVER['HTTP_REFERER']);
            }
        }
        else{
            $this->Payement_schedules_model->new_pay($loan_number, $pay_number, $amount);
            $this->Rescheduled_payments_model->new_pay($loan_number, $pay_number_r, $amount);
            $logger = array(

                'user_id' => $this->session->userdata('user_id'),
                'activity' => 'Paid a loan, loan ID:'.$loan_number,
                'activity_cate' => 'loan_repayment'

            );
            log_activity($logger);

            $data = array(
                'ref' => "CF." . date('Y') . date('m') . date('d') . '.' . rand(100, 999),
                'loan_id' => $loan_number,
                'amount' => $amount,
                'transaction_type' => 1,
                'payment_number' => 0,
                'method' => $this->input->post('payment_method'),
                'payment_proof' => $proof,
                'reference' => $this->input->post('reference'),
                'added_by' => $this->session->userdata('user_id')

            );

            $this->Transactions_model->insert($data);
            $this->toaster->success('Success, payment was successful');
            redirect($_SERVER['HTTP_REFERER']);
        }
    }
    public function pay_late_loan(){
        $transid = "TXN.".date('Y').date('m').date('d').'.'.rand(100,999);
        $loan_number = $this->input->post('loan_id');

        $pay_number = $this->input->post('payment_number');

        $lamount = $this->input->post('lamount');
        $amount = $this->input->post('amount');
        $recepientt = get_by_id('account','collection_account','Yes');
        $sender = get_by_id('loan','loan_id',$loan_number);
//     make deductions first
        $get_sender_balance = get_by_id('account','account_number',$sender->loan_number);

        $check_if_paid = $this->Transactions_model->get_by_loan($loan_number);
        if(!empty($check_if_paid)){
            $get_sender_balance2 = get_by_id('account','account_number',$sender->loan_number);

            if($get_sender_balance2->balance >= $amount){

                $this->Account_model->transfer_funds($sender->loan_number, $recepientt->account_number,$amount, $transid);
                $result = $this->Payement_schedules_model->new_pay($loan_number,$pay_number,$amount);
                $logger = array(

                    'user_id' => $this->session->userdata('user_id'),
                    'activity' => 'Paid a loan, loan ID:'.' '.$loan_number.' '.' payment number'.' '.$pay_number.
                        ' '.'amount'.' '.$amount,
                    'activity_cate' => 'loan_repayment'

                );
                log_activity($logger);
                $data = array(
                    'ref' => "GF.".date('Y').date('m').date('d').'.'.rand(100,999),
                    'loan_id' => $this->input->post('loan_id',TRUE),
                    'amount' => $amount,
                    'transaction_type' => 2,
                    'payment_number' =>  $this->input->post('payment_number'),
                    'added_by' => $this->session->userdata('user_id')

                );

                $this->Transactions_model->insert($data);
                $this->toaster->success('Success, payment was successful');
                redirect($_SERVER['HTTP_REFERER']);
            }
            elseif($get_sender_balance2->balance > 0 && $get_sender_balance2->balance < $amount){
                $topay_amount =  $get_sender_balance->balance;
                $this->Account_model->transfer_funds($sender->loan_number, $recepientt->account_number,$topay_amount, $transid);
                $result = $this->Payement_schedules_model->new_pay($loan_number,$pay_number,$topay_amount);
                $logger = array(

                    'user_id' => $this->session->userdata('user_id'),
                    'activity' => 'Paid a loan, loan ID:'.' '.$loan_number.' '.' payment number'.' '.$pay_number.
                        ' '.'amount'.' '.$topay_amount,
                    'activity_cate' => 'loan_repayment'

                );
                log_activity($logger);
                $data = array(
                    'ref' => "GF.".date('Y').date('m').date('d').'.'.rand(100,999),
                    'loan_id' => $this->input->post('loan_id',TRUE),
                    'amount' => $topay_amount,
                    'transaction_type' => 2,
                    'payment_number' =>  $this->input->post('payment_number'),
                    'added_by' => $this->session->userdata('user_id')

                );

                $this->Transactions_model->insert($data);
                $this->toaster->success('Success, payment was successful');
                redirect($_SERVER['HTTP_REFERER']);

            }
            else{
                $this->toaster->error('Ops!, Sorry payment failed loan account  does not have enough funds');
                redirect($_SERVER['HTTP_REFERER']);
            }
        }
        else{
            if($get_sender_balance->balance > $lamount){


                $pay_late_first = $this->Account_model->transfer_funds($sender->loan_number, $recepientt->account_number,$lamount, $transid);


                if($pay_late_first=='success'){

                    $get_sender_balance2 = get_by_id('account','account_number',$sender->loan_number);


                    if($get_sender_balance2->balance > $amount){

                        $this->Account_model->transfer_funds($sender->loan_number, $recepientt->account_number,$amount, $transid);
                        $result = $this->Payement_schedules_model->new_pay($loan_number,$pay_number,$amount);
                        $logger = array(

                            'user_id' => $this->session->userdata('user_id'),
                            'activity' => 'Paid a loan, loan ID:'.' '.$loan_number.' '.' payment number'.' '.$pay_number.
                                ' '.'amount'.' '.$amount,
                            'activity_cate' => 'loan_repayment'

                        );
                        log_activity($logger);
                        $data = array(
                            'ref' => "GF.".date('Y').date('m').date('d').'.'.rand(100,999),
                            'loan_id' => $this->input->post('loan_id',TRUE),
                            'amount' => $amount,
                            'transaction_type' => 2,
                            'payment_number' =>  $this->input->post('payment_number'),
                            'added_by' => $this->session->userdata('user_id')

                        );

                        $this->Transactions_model->insert($data);
                        $this->toaster->success('Success, payment was successful');
                        redirect($_SERVER['HTTP_REFERER']);
                    }
                    elseif($get_sender_balance2->balance > 0 && $get_sender_balance2->balance < $amount){

                        $topay_amount =  $get_sender_balance2->balance;



                        $this->Account_model->transfer_funds($sender->loan_number, $recepientt->account_number,$topay_amount, $transid);
                        $result = $this->Payement_schedules_model->new_pay($loan_number,$pay_number,$topay_amount);
                        $logger = array(

                            'user_id' => $this->session->userdata('user_id'),
                            'activity' => 'Paid a loan, loan ID:'.' '.$loan_number.' '.' payment number'.' '.$pay_number.
                                ' '.'amount'.' '.$topay_amount,
                            'activity_cate' => 'loan_repayment'

                        );
                        log_activity($logger);
                        $data = array(
                            'ref' => "GF.".date('Y').date('m').date('d').'.'.rand(100,999),
                            'loan_id' => $this->input->post('loan_id',TRUE),
                            'amount' => $topay_amount,
                            'transaction_type' => 2,
                            'payment_number' =>  $this->input->post('payment_number'),
                            'added_by' => $this->session->userdata('user_id')

                        );

                        $this->Transactions_model->insert($data);
                        $this->toaster->success('Success, payment was successful');
                        redirect($_SERVER['HTTP_REFERER']);

                    }
                    else{
                        $this->toaster->error('Ops!, Sorry payment failed loan account  does not have enough funds');
                        redirect($_SERVER['HTTP_REFERER']);
                    }


                }else{
                    $this->toaster->error('Ops!, Sorry payment failed');
                    redirect($_SERVER['HTTP_REFERER']);
                }

            }
            else{
                $this->toaster->error('Ops!, Sorry late fee payment failed loan account  does not have enough funds');
                redirect($_SERVER['HTTP_REFERER']);
            }
        }


    }



    function view($id){
        $row = $this->Loan_model->get_by_id($id);

        if (!$row) {
            $this->toaster->error('Loan not found.');
            redirect(site_url('loan'));
            return;
        }

        $payments = $this->Payement_schedules_model->get_all_by_id($row->loan_id);
        $payments = $this->enrich_loan_payments_for_display($payments);
        $payment_balance = $this->build_loan_payment_balance_summary($payments, $row);

        $customer_name = '';
        $preview_url   = '';

        if($row->customer_type=='group'){
            $group = $this->Groups_model->get_by_id($row->loan_customer);
            $customer_name = $group->group_name.'('.$group->group_code.')';
            $preview_url = "Customer_groups/members/";
        }elseif($row->customer_type=='individual'){
            $indi = $this->Individual_customers_model->get_by_id($row->loan_customer);
            $customer_name = $indi->Firstname.' '.$indi->Lastname;
            $preview_url = "Individual_customers/view/";
        }

        $data = array(
            'loan_id' => $row->loan_id,
            'loan_number' => $row->loan_number,
            'loan_product' => $row->product_name,
            'product_code' => $row->product_code,
            'branch' => $row->branch,
            'customer_type' => $row->customer_type,
            'loan_customer' => $customer_name,
            'preview_url' => $preview_url,
            'customer_id' => $row->loan_customer,
            'loan_date' => $row->loan_date,
            'loan_principal' => $row->loan_principal,
            'loan_period' => $row->loan_period,
            'period_type' => $row->period_type,
            'loan_interest' => $row->loan_interest,
            'loan_interest_amount' => $row->loan_interest_amount,
            'admin_fees_amount' => $row->admin_fees_amount,
            'loan_cover_amount' => $row->loan_cover_amount,
            'loan_amount_total' => $row->loan_amount_total,
            'loan_amount_term' => $row->loan_amount_term,
            'next_payment_id' => $row->next_payment_id,
            'loan_added_by' => $row->loan_added_by,
            'loan_approved_by' => $row->loan_approved_by,
            'loan_status' => $row->loan_status,
            'officer' => $row->Firstname." ".$row->Lastname,
            'loan_added_date' => $row->loan_added_date,
            'payments'=>$payments,
            'payment_balance' => $payment_balance,
        );
        $menu_toggle['toggles'] = 23;
        $this->load->view('admin/header', $menu_toggle);
        $this->load->view('loan/view',$data);
        $this->load->view('admin/footer');
    }
    function edit_single_loan_request($id){
        $row = $this->Loan_model->get_by_id($id);
        $payments = $this->Payement_schedules_model->get_all_by_id($row->loan_id);
//        $files = $this->Loan_files_model->get_by_loans($row->loan_id);
//  $scores = $this->Loan_recommendation_model->get_by_loan($row->loan_id);

        if($row->customer_type=='group'){
            $group = $this->Groups_model->get_by_id($row->loan_customer);

            $customer_name = $group->group_name.'('.$group->group_code.')';
            $preview_url = "Customer_groups/members/";
            $view = "edit_loan_group";
        }elseif($row->customer_type=='individual'){
            $indi = $this->Individual_customers_model->get_by_id($row->loan_customer);
            $customer_name = $indi->Firstname.' '.$indi->Lastname;
            $preview_url = "Individual_customers/view/";
            $view = "edit_loan";
        }
        $customers =$this->Individual_customers_model->get_all_active();
        $data = array(
            'loan_id' => $row->loan_id,
            'loan_number' => $row->loan_number,
            'loan_product' => $row->product_name,
            'loan_product_id' => $row->loan_product,
            'customer_type' => $row->customer_type,
            'loan_customer' => $customer_name,
            'preview_url' => $preview_url,
            'customer_id' => $row->loan_customer,
            'loan_date' => $row->loan_date,
            'loan_principal' => $row->loan_principal,
            'loan_period' => $row->loan_period,
            'period_type' => $row->period_type,
            'loan_interest' => $row->loan_interest,
            'loan_interest_amount' => $row->loan_interest_amount,
            'loan_amount_total' => $row->loan_amount_total,
            'loan_amount_term' => $row->loan_amount_term,
            'next_payment_id' => $row->next_payment_id,
            'loan_added_by' => $row->loan_added_by,
            'loan_approved_by' => $row->loan_approved_by,
            'loan_status' => $row->loan_status,
            'loan_added_date' => $row->loan_added_date,
            'payments'=>$payments,
            'customers'=>$customers,
            'customer'=>$row->loan_customer,
            'loan_types' => $this->loan_products_for_edit(),

        );
        $menu_toggle['toggles'] = 23;

        $this->load->view('admin/header', $menu_toggle);
        $this->load->view('loan/'.$view,$data);
        $this->load->view('admin/footer');
    }
    public function edit_action(){

        $row = $this->Loan_model->get_by_id($this->input->post('loan_id'));


        if($row->customer_type=='group'){
            $group = $this->Groups_model->get_by_id($row->loan_customer);

            $customer_name = $group->group_name.'('.$group->group_code.')';
            $preview_url = "Customer_groups/members/";
        }elseif($row->customer_type=='individual'){
            $indi = $this->Individual_customers_model->get_by_id($row->loan_customer);
            $customer_name = $indi->Firstname.' '.$indi->Lastname;
            $preview_url = "Individual_customers/view/";
        }

        $posted_customer_type = trim((string)$this->input->post('customer_type'));
        if ($posted_customer_type === '') {
            $posted_customer_type = $row->customer_type;
        }
        $posted_customer = trim((string)$this->input->post('customer'));
        if ($posted_customer === '') {
            $posted_customer = (string)$row->loan_customer;
        }

        if($posted_customer_type=='group'){
            $group1 = $this->Groups_model->get_by_id($posted_customer);
            if (empty($group1)) {
                $this->toaster->error('Invalid group selected for restructure.');
                redirect($_SERVER['HTTP_REFERER']);
                return;
            }

            $customer_name1 = $group1->group_name.'('.$group1->group_code.')';
            $preview_url1 = "Customer_groups/members/";
        }elseif($posted_customer_type=='individual'){
            $indi1 = $this->Individual_customers_model->get_by_id($posted_customer);
            if (empty($indi1)) {
                $this->toaster->error('Invalid customer selected for restructure.');
                redirect($_SERVER['HTTP_REFERER']);
                return;
            }
            $customer_name1 = $indi1->Firstname.' '.$indi1->Lastname;
            $preview_url1 = "Individual_customers/view/";
        } else {
            $this->toaster->error('Invalid customer type selected for restructure.');
            redirect($_SERVER['HTTP_REFERER']);
            return;
        }
        $loan_number = str_replace(' ', '', $this->input->post('loan_number'));
        $product_n = $this->Loan_products_model->get_by_id($this->input->post('loan_type'));
        $productAccessErr = $this->validate_loan_product_access_for_user($product_n);
        if ($productAccessErr !== null) {
            $this->toaster->error($productAccessErr);
            redirect($_SERVER['HTTP_REFERER']);
            return;
        }
        $added_by1 = get_by_id('employees','id',$this->input->post('user'));
        $result = array(
            'loan_id' => $row->loan_id,
            'loan_number'=> $loan_number,
            'sy_loan_product'=>$this->input->post('loan_type'),
            'loan_product'=>$product_n->product_name,
            'period_type' => ($product_n && !empty($product_n->frequency))
                ? trim((string) $product_n->frequency)
                : ($this->input->post('period_type') ? $this->input->post('period_type') : $row->period_type),
            'sy_loan_customer'=>$posted_customer,
            'loan_customer'=>$customer_name1,
            'customer_type'=> $posted_customer_type,
            'preview_url' => $preview_url1,
            'customer_id' => $row->loan_customer,
            'loan_date'=>$this->input->post('loan_date'),
            'loan_principal'=>$this->input->post('amount'),
            'loan_period'=>$this->input->post('months'),
            'loan_worthness_file'=>$this->input->post('worthness_file'),
            'narration'=>$this->input->post('narration'),
            'sy_added_by'=>$this->input->post('user'),
            'added_by'=>$added_by1->Firstname." ".$added_by1->Lastname,

        );
        $added_by = get_by_id('employees','id',$row->loan_added_by);
        $data = array(
            'loan_id' => $row->loan_id,
            'loan_number' => $row->loan_number,
            'loan_product' => $row->product_name,
            'loan_customer' => $customer_name,
            'customer_type' => $row->customer_type,
            'preview_url' => $preview_url,
            'customer_id' => $row->loan_customer,
            'loan_date' => $row->loan_date,
            'loan_principal' => $row->loan_principal,
            'loan_period' => $row->loan_period,
            'loan_worthness_file'=>$row->worthness_file,
            'narration'=>$row->narration,
            'loan_added_by' => $added_by->Firstname." ".$added_by->Lastname,


        );




        $logger = array(
            'type' => 'Loan edit',
            'old_info' => json_encode($data),
            'new_info' => json_encode($result),
            'id'=> $this->input->post('loan_id'),
            'summary'=> $this->input->post('loan_number'),

            'Initiated_by' => current_session_user_id()

        );
        auth_logger($logger);
        $this->toaster->success('You successfully, initiated loan edit, wait for approval');
        redirect('Loan/restructure');
    }
    public function delete_action($id){

        $row = $this->Loan_model->get_by_id($id);


        if($row->customer_type=='group'){
            $group = $this->Groups_model->get_by_id($row->loan_customer);

            $customer_name = $group->group_name.'('.$group->group_code.')';
            $preview_url = "Customer_groups/members/";
        }elseif($row->customer_type=='individual'){
            $indi = $this->Individual_customers_model->get_by_id($row->loan_customer);
            $customer_name = $indi->Firstname.' '.$indi->Lastname;
            $preview_url = "Individual_customers/view/";
        }


        $added_by = get_by_id('employees','id',$row->loan_added_by);
        $data = array(
            'loan_id' => $row->loan_id,
            'loan_number' => $row->loan_number,
            'loan_product' => $row->product_name,
            'loan_customer' => $customer_name,
            'customer_type' => $row->customer_type,
            'preview_url' => $preview_url,
            'customer_id' => $row->loan_customer,
            'loan_date' => $row->loan_date,
            'loan_principal' => $row->loan_principal,
            'loan_period' => $row->loan_period,
            'loan_worthness_file'=>$row->worthness_file,
            'narration'=>$row->narration,
            'loan_added_by' => $added_by->Firstname." ".$added_by->Lastname,


        );




        $logger = array(
            'type' => 'Loan delete',
            'old_info' => json_encode($data),
            'new_info' => json_encode($data),
            'id'=> $id,
            'summary'=> "Delete of data",

            'Initiated_by' => current_session_user_id()

        );
        auth_logger($logger);
        redirect('Loan/restructure');
    }
    function edit_recommend(){
        $menu_toggle['toggles'] = 23;

        $this->load->view('admin/header', $menu_toggle);
        $this->load->view('loan/edit_recommend');
        $this->load->view('admin/footer');
    }
    function edit_approve(){
        $menu_toggle['toggles'] = 23;

        $this->load->view('admin/header', $menu_toggle);
        $this->load->view('loan/edit_approve');
        $this->load->view('admin/footer');
    }
    function delete_recommend(){
        $menu_toggle['toggles'] = 23;

        $this->load->view('admin/header', $menu_toggle);
        $this->load->view('loan/delete_recommend');
        $this->load->view('admin/footer');
    }
    function delete_approve(){
        $menu_toggle['toggles'] = 23;

        $this->load->view('admin/header', $menu_toggle);
        $this->load->view('loan/delete_approve');
        $this->load->view('admin/footer');
    }
    /**
     * Repair a specific loan's payment schedules so paid amounts match actual transactions.
     * Access: /loan/repair_loan_schedules/<loan_number>
     * Only accessible by SUPER ADMIN.
     */
    public function repair_loan_schedules($loan_number = null)
    {
        if ($this->session->userdata('RoleName') !== 'SUPER ADMIN') {
            $this->toaster->error('You are not authorised to perform this action.');
            redirect($_SERVER['HTTP_REFERER']);
            return;
        }

        if (empty($loan_number)) {
            $this->toaster->error('Loan number is required.');
            redirect($_SERVER['HTTP_REFERER']);
            return;
        }

        // Resolve loan number (alphanumeric) to internal loan_id.
        $loan = $this->db->where('loan_number', $loan_number)->get('loan')->row();
        if (!$loan) {
            // Try treating the parameter as a direct loan_id integer.
            $loan = $this->db->where('loan_id', (int)$loan_number)->get('loan')->row();
        }

        if (!$loan) {
            $this->toaster->error('Loan not found: ' . htmlspecialchars($loan_number));
            redirect($_SERVER['HTTP_REFERER']);
            return;
        }

        $result = $this->Payement_schedules_model->repair_loan_payment_state($loan->loan_id);

        if (isset($result['error'])) {
            $this->toaster->error('Repair failed: ' . $result['error']);
        } else {
            $applied   = number_format($result['applied'], 2);
            $txn_total = number_format($result['total_txn_paid'], 2);
            $this->toaster->success(
                "Schedules repaired for loan {$loan->loan_number}. " .
                "Transactions total: MWK{$txn_total} — Applied to schedules: MWK{$applied}."
            );
        }

        redirect('loan/repayment_view/' . $loan->loan_id);
    }

    /**
     * Find and repair ALL loans with payment schedule mismatches in bulk.
     * Access: /loan/bulk_repair_all_loans
     * Only accessible by SUPER ADMIN.
     * Returns JSON summary.
     */
    public function bulk_repair_all_loans()
    {
        if ($this->session->userdata('RoleName') !== 'SUPER ADMIN') {
            http_response_code(403);
            echo json_encode(array('error' => 'Unauthorised'));
            return;
        }

        // Find all loans where total transactions exceed total schedule paid.
        $mismatched_loans = $this->db->query("
            SELECT
                l.loan_id,
                l.loan_number,
                l.loan_status,
                COALESCE(SUM(ps.amount), 0)       AS total_schedule_amount,
                COALESCE(SUM(ps.paid_amount), 0)  AS total_schedule_paid,
                COALESCE(SUM(t_max.max_amount), 0) AS total_txn_paid
            FROM loan l
            LEFT JOIN payement_schedules ps ON ps.loan_id = l.loan_id
            LEFT JOIN (
                SELECT loan_id, SUM(max_per_ref) AS max_amount
                FROM (
                    SELECT loan_id, ref, MAX(amount) AS max_per_ref
                    FROM transactions
                    WHERE transaction_type = 3
                    GROUP BY loan_id, ref
                ) AS unique_refs
                GROUP BY loan_id
            ) t_max ON t_max.loan_id = l.loan_id
            WHERE l.loan_status IN ('ACTIVE', 'CLOSED')
            GROUP BY l.loan_id, l.loan_number, l.loan_status
            HAVING COALESCE(SUM(t_max.max_amount), 0) > COALESCE(SUM(ps.paid_amount), 0.01)
            ORDER BY l.loan_id
        ")->result();

        $summary = array(
            'total_mismatched' => 0,
            'repaired_count'   => 0,
            'errors_count'     => 0,
            'total_txn_diff'   => 0.0,
            'details'          => array(),
        );

        foreach ($mismatched_loans as $loan_row) {
            $summary['total_mismatched']++;

            $result = $this->Payement_schedules_model->repair_loan_payment_state($loan_row->loan_id);

            if (isset($result['error'])) {
                $summary['errors_count']++;
                $summary['details'][] = array(
                    'loan_id'     => $loan_row->loan_id,
                    'loan_number' => $loan_row->loan_number,
                    'status'      => 'ERROR',
                    'message'     => $result['error'],
                );
            } else {
                $summary['repaired_count']++;
                $diff = $result['total_txn_paid'] - $loan_row->total_schedule_paid;
                $summary['total_txn_diff'] += $diff;

                $summary['details'][] = array(
                    'loan_id'          => $loan_row->loan_id,
                    'loan_number'      => $loan_row->loan_number,
                    'loan_status'      => $loan_row->loan_status,
                    'status'           => 'REPAIRED',
                    'txn_total'        => round($result['total_txn_paid'], 2),
                    'schedule_amount'  => round($result['total_schedule_amount'], 2),
                    'applied'          => round($result['applied'], 2),
                    'diff'             => round($diff, 2),
                );
            }
        }

        $summary['total_txn_diff'] = round($summary['total_txn_diff'], 2);

        header('Content-Type: application/json');
        echo json_encode($summary, JSON_PRETTY_PRINT | JSON_NUMERIC_CHECK);
    }

    function repayment_view($id){
        $row = $this->Loan_model->get_by_id($id);
        $payments = $this->Payement_schedules_model->get_all_by_id($row->loan_id);
        $payments = $this->enrich_loan_payments_for_display($payments);
        $payment_balance = $this->build_loan_payment_balance_summary($payments, $row);

        if($row->customer_type=='group'){
            $group = $this->Groups_model->get_by_id($row->loan_customer);

            $customer_name = $group->group_name.'('.$group->group_code.')';
            $preview_url = "Customer_groups/members/";
        }elseif($row->customer_type=='individual'){
            $indi = $this->Individual_customers_model->get_by_id($row->loan_customer);
            $customer_name = $indi->Firstname.' '.$indi->Lastname;
            $preview_url = "Individual_customers/view/";
        }

        $data = array(
            'loan_id' => $row->loan_id,
            'loan_number' => $row->loan_number,
            'loan_product' => $row->product_name,
            'customer_type' => $row->customer_type,
            'loan_customer' => $customer_name,
            'preview_url' => $preview_url,
            'customer_id' => $row->loan_customer,
            'loan_date' => $row->loan_date,
            'loan_principal' => $row->loan_principal,
            'loan_period' => $row->loan_period,
            'period_type' => $row->period_type,
            'loan_interest' => $row->loan_interest,
            'loan_interest_amount' => $row->loan_interest_amount,
            'admin_fees_amount' => $row->admin_fees_amount,
            'loan_cover_amount' => $row->loan_cover_amount,
            'loan_amount_total' => $row->loan_amount_total,
            'loan_amount_term' => $row->loan_amount_term,
            'next_payment_id' => $row->next_payment_id,
            'loan_added_by' => $row->loan_added_by,
            'loan_approved_by' => $row->loan_approved_by,
            'loan_status' => $row->loan_status,
            'loan_added_date' => $row->loan_added_date,
            'payments'=>$payments,
            'payment_balance' => $payment_balance,
            'officer'=>$row->Firstname.' '.$row->Lastname
        );
        $menu_toggle['toggles'] = 52;
        $this->load->view('admin/header', $menu_toggle);
        $this->load->view('loan/loan_repayment_view',$data);
        $this->load->view('admin/footer');
    }
    function repayment_view_pay_off($id){
        $row = $this->Loan_model->get_by_id($id);
        $payments = $this->Payement_schedules_model->get_all_by_id($row->loan_id);
        $payments = $this->enrich_loan_payments_for_display($payments);
        $payment_balance = $this->build_loan_payment_balance_summary($payments, $row);

        if($row->customer_type=='group'){
            $group = $this->Groups_model->get_by_id($row->loan_customer);

            $customer_name = $group->group_name.'('.$group->group_code.')';
            $preview_url = "Customer_groups/members/";
        }elseif($row->customer_type=='individual'){
            $indi = $this->Individual_customers_model->get_by_id($row->loan_customer);
            $customer_name = $indi->Firstname.' '.$indi->Lastname;
            $preview_url = "Individual_customers/view/";
        }

        $data = array(
            'loan_id' => $row->loan_id,
            'loan_number' => $row->loan_number,
            'loan_product' => $row->product_name,
            'customer_type' => $row->customer_type,
            'loan_customer' => $customer_name,
            'preview_url' => $preview_url,
            'customer_id' => $row->loan_customer,
            'loan_date' => $row->loan_date,
            'loan_principal' => $row->loan_principal,
            'loan_period' => $row->loan_period,
            'period_type' => $row->period_type,
            'loan_interest' => $row->loan_interest,
            'loan_interest_amount' => $row->loan_interest_amount,
            'admin_fees_amount' => $row->admin_fees_amount,
            'loan_cover_amount' => $row->loan_cover_amount,
            'loan_amount_total' => $row->loan_amount_total,
            'loan_amount_term' => $row->loan_amount_term,
            'next_payment_id' => $row->next_payment_id,
            'loan_added_by' => $row->loan_added_by,
            'loan_approved_by' => $row->loan_approved_by,
            'loan_status' => $row->loan_status,
            'loan_added_date' => $row->loan_added_date,
            'payments'=>$payments,
            'payment_balance' => $payment_balance,
            'officer'=>$row->Firstname.' '.$row->Lastname
        );
        $menu_toggle['toggles'] = 52;
        $this->load->view('admin/header', $menu_toggle);
        $this->load->view('loan/loan_repayment_view_pay_off',$data);
        $this->load->view('admin/footer');
    }


    /**
     * Build report/statement view data for one loan (same layout as loan/report).
     */
    private function build_loan_report_data($loan_id)
    {
        $row = $this->Loan_model->get_by_id_r($loan_id);
        if (!$row) {
            return null;
        }

        $payments = $this->Payement_schedules_model->get_all_by_id($row->loan_id);
        $maturity_date = $this->Payement_schedules_model->get_last_payment($row->loan_id);
        $first_payment = $this->Payement_schedules_model->get_first_payment($row->loan_id);

        $customer_name = '';
        if ($row->customer_type === 'group') {
            $group = $this->Groups_model->get_by_id($row->loan_customer);
            if ($group) {
                $customer_name = $group->group_name . '(' . $group->group_code . ')';
            }
        } elseif ($row->customer_type === 'individual') {
            $indi = $this->Individual_customers_model->get_by_id($row->loan_customer);
            if ($indi) {
                $customer_name = trim($indi->Firstname . ' ' . $indi->Lastname);
            }
        }

        $branchname = get_by_id('branches', 'id', $row->branch);
        $bname = $branchname ? $branchname->BranchName : 'N/A';

        return array(
            'loan_id' => $row->loan_id,
            'maturity_date' => $maturity_date ? $maturity_date->payment_schedule : 'N/A',
            'maturity_pay' => $maturity_date ? $maturity_date->amount : 0,
            'first_payment' => $first_payment ? $first_payment->amount : 0,
            'first_payment_date' => $first_payment ? $first_payment->payment_schedule : 'N/A',
            'loan_number' => $row->loan_number,
            'loan_product' => $row->product_name,
            'branch_name' => $bname,
            'loan_customer' => $customer_name,
            'customer_id' => $row->id,
            'loan_date' => $row->loan_date,
            'loan_principal' => $row->loan_principal,
            'loan_period' => $row->loan_period,
            'period_type' => $row->period_type,
            'loan_interest' => $row->loan_interest,
            'loan_amount_total' => $row->loan_amount_total,
            'loan_amount_term' => $row->loan_amount_term,
            'next_payment_id' => $row->next_payment_id,
            'loan_added_by' => $row->loan_added_by,
            'loan_approved_by' => $row->loan_approved_by,
            'loan_status' => $row->loan_status,
            'loan_added_date' => $row->loan_added_date,
            'officer' => trim($row->efname . ' ' . $row->elname),
            'payments' => $payments,
            'product_name' => $row->product_name,
        );
    }

    /**
     * Loans in a batch for reporting (excludes DELETED).
     */
    private function get_batch_loans_for_reports($batch, $loan_ids = null)
    {
        $batch = trim((string) $batch);
        $this->db->select('loan.loan_id, loan.loan_number, loan.loan_status, loan.loan_customer, loan.batch,
            ic.Firstname, ic.Lastname, ic.ClientId,
            groups.group_name, groups.group_code')
            ->from('loan')
            ->join('individual_customers ic', 'ic.id = loan.loan_customer', 'left')
            ->join('groups', 'groups.group_id = loan.group_id', 'left')
            ->where('loan.batch', $batch)
            ->where('loan.loan_status !=', 'DELETED')
            ->order_by('loan.loan_id', 'ASC');

        if (is_array($loan_ids) && !empty($loan_ids)) {
            $loan_ids = array_map('intval', $loan_ids);
            $loan_ids = array_filter($loan_ids, function ($id) {
                return $id > 0;
            });
            if (!empty($loan_ids)) {
                $this->db->where_in('loan.loan_id', $loan_ids);
            }
        }

        return $this->db->get()->result();
    }

    function report($id)
    {
        $data = $this->build_loan_report_data($id);
        if (!$data) {
            show_404();
            return;
        }

        $this->load->library('Pdf');
        $html = $this->load->view('loan/report', $data, true);
        $this->pdf->createPDF($html, $data['loan_customer'] . ' loan report as on ' . date('Y-m-d'), true);
    }

    /**
     * Batch statement page: all loan account transactions in a batch with filters and export.
     * Legacy URL batch_statements redirects here.
     */
    public function batch_statements($batch = null)
    {
        $query = $this->input->server('QUERY_STRING');
        $suffix = ($query !== null && $query !== '') ? '?' . $query : '';
        redirect('loan/batch_statement/' . rawurlencode(urldecode((string) $batch)) . $suffix);
    }

    public function batch_statement($batch = null)
    {
        if (empty($batch)) {
            show_404();
            return;
        }

        $batch = urldecode($batch);
        $batch_members = $this->get_batch_members_for_statement($batch);
        if (empty($batch_members)) {
            show_error('No loans found for batch: ' . htmlspecialchars($batch));
            return;
        }

        $filters = $this->parse_batch_statement_filters();
        $member_statements = $this->build_batch_member_statements($batch_members, $filters);

        $data = array(
            'batch' => $batch,
            'group_name' => !empty($batch_members[0]->group_name) ? $batch_members[0]->group_name : 'N/A',
            'group_code' => !empty($batch_members[0]->group_code) ? $batch_members[0]->group_code : 'N/A',
            'filters' => $filters,
            'batch_members' => $batch_members,
            'member_statements' => $member_statements,
            'batch_page_url' => base_url('loan/group_batch_loans/' . rawurlencode($batch)),
        );

        $menu_toggle['toggles'] = 41;
        $this->load->view('admin/header', $menu_toggle);
        $this->load->view('loan/batch_statement', $data);
        $this->load->view('admin/footer');
    }

    /**
     * PDF account statements for all (or filtered) batch members — same as transactions/report per loan.
     */
    public function batch_statement_report($batch = null)
    {
        if (empty($batch)) {
            show_404();
            return;
        }

        $batch = urldecode($batch);
        $batch_members = $this->get_batch_members_for_statement($batch);
        if (empty($batch_members)) {
            show_error('No loans found for batch: ' . htmlspecialchars($batch));
            return;
        }

        $filters = $this->parse_batch_statement_filters();
        $filters['run'] = true;
        $statements = array();

        foreach ($batch_members as $member) {
            if (!empty($filters['loan_id']) && (int) $filters['loan_id'] !== (int) $member->loan_id) {
                continue;
            }

            $report_data = $this->build_account_statement_report_data($member->loan_id);
            if ($report_data) {
                $statements[] = $report_data;
            }
        }

        if (empty($statements)) {
            show_error('No statements could be generated for this batch.');
            return;
        }

        @set_time_limit(600);
        @ini_set('memory_limit', '512M');

        $group_name = !empty($batch_members[0]->group_name) ? $batch_members[0]->group_name : 'N/A';
        $group_code = !empty($batch_members[0]->group_code) ? $batch_members[0]->group_code : 'N/A';

        $html = $this->load->view('loan/batch_print_transactions', array(
            'batch' => $batch,
            'group_name' => $group_name,
            'group_code' => $group_code,
            'statements' => $statements,
        ), true);

        $this->load->library('Pdf');
        $filename = 'Batch ' . $batch . ' account statements ' . date('Y-m-d');
        $this->pdf->createPDF($html, $filename, true);
    }

    private function parse_batch_statement_filters()
    {
        return array(
            'run' => $this->input->get('run', true) === '1',
            'from' => trim((string) $this->input->get('from', true)),
            'to' => trim((string) $this->input->get('to', true)),
            'loan_id' => trim((string) $this->input->get('loan_id', true)),
        );
    }

    private function build_batch_member_statements(array $batch_members, array $filters)
    {
        if (!$filters['run']) {
            return array();
        }

        $this->load->model('Transactions_model');
        $statements = array();

        foreach ($batch_members as $member) {
            if (!empty($filters['loan_id']) && (int) $filters['loan_id'] !== (int) $member->loan_id) {
                continue;
            }

            $rows = $this->Transactions_model->search2_filtered(
                $member->loan_id,
                $filters['from'],
                $filters['to']
            );

            $statements[] = array(
                'loan_id' => $member->loan_id,
                'loan_number' => $member->loan_number,
                'member_name' => $member->member_name,
                'loan_status' => $member->loan_status,
                'rows' => $rows,
            );
        }

        return $statements;
    }

    /**
     * Same data bundle as Transactions::report() for print_transactions view.
     */
    private function build_account_statement_report_data($loan_id)
    {
        $loan_id = (int) $loan_id;
        if ($loan_id <= 0) {
            return null;
        }

        $this->load->model('Transactions_model');
        $row = $this->Loan_model->get_by_id($loan_id);
        if (!$row) {
            return null;
        }

        $payments = $this->Payement_schedules_model->get_all_by_id($row->loan_id);
        $maturity_date = $this->Payement_schedules_model->get_last_payment($row->loan_id);
        $first_payment = $this->Payement_schedules_model->get_first_payment($row->loan_id);
        $trans = $this->Transactions_model->search($loan_id);
        $datadeposits = $this->Transactions_model->search2($loan_id);

        if ($row->customer_type === 'individual') {
            $inddata = get_by_id('individual_customers', 'id', $row->loan_customer);
            $customer = $inddata ? trim($inddata->Firstname . ' ' . $inddata->Lastname) : 'Unknown';
        } else {
            $groupdata = get_by_id('groups', 'group_id', $row->loan_customer);
            $customer = $groupdata ? $groupdata->group_name . ' ' . $groupdata->group_code : 'Unknown';
        }

        return array(
            'loan_id' => $row->loan_id,
            'transa' => $trans,
            'datadeposits' => $datadeposits,
            'maturity_date' => $maturity_date ? $maturity_date->payment_schedule : 'N/A',
            'maturity_pay' => $maturity_date ? $maturity_date->amount : 0,
            'first_payment' => $first_payment ? $first_payment->amount : 0,
            'first_payment_date' => $first_payment ? $first_payment->payment_schedule : 'N/A',
            'loan_number' => $row->loan_number,
            'loan_product' => $row->product_name,
            'loan_customer' => $customer,
            'customer_id' => $row->id,
            'loan_date' => $row->loan_date,
            'loan_principal' => $row->loan_principal,
            'loan_period' => $row->loan_period,
            'period_type' => $row->period_type,
            'loan_interest' => $row->loan_interest,
            'loan_amount_total' => $row->loan_amount_total,
            'loan_amount_term' => $row->loan_amount_term,
            'next_payment_id' => $row->next_payment_id,
            'loan_added_by' => $row->loan_added_by,
            'loan_approved_by' => $row->loan_approved_by,
            'loan_status' => $row->loan_status,
            'loan_added_date' => $row->loan_added_date,
            'payments' => $payments,
        );
    }

    private function get_batch_members_for_statement($batch)
    {
        $batch = trim((string) $batch);
        $this->db->select('loan.loan_id, loan.loan_number, loan.loan_status, loan.loan_customer,
            ic.Firstname, ic.Lastname, ic.ClientId,
            groups.group_name, groups.group_code')
            ->from('loan')
            ->join('individual_customers ic', 'ic.id = loan.loan_customer', 'left')
            ->join('groups', 'groups.group_id = loan.group_id', 'left')
            ->where('loan.batch', $batch)
            ->where('loan.loan_status !=', 'DELETED')
            ->order_by('loan.loan_id', 'ASC');

        $rows = $this->db->get()->result();
        foreach ($rows as $loan_row) {
            $member_name = trim((string) $loan_row->Firstname . ' ' . (string) $loan_row->Lastname);
            if ($member_name === '' && !empty($loan_row->ClientId)) {
                $member_name = (string) $loan_row->ClientId;
            }
            if ($member_name === '') {
                $member_name = 'Member #' . (int) $loan_row->loan_id;
            }
            $loan_row->member_name = $member_name;
        }

        return $rows;
    }

    
    function batch_report($batch){
        // Get all loans for this batch with group info
        $this->db->select('l.*, p.product_name, p.product_code, ic.Firstname, ic.Lastname, ic.ClientId,
                          g.group_name, g.group_code, e.Firstname as efname, e.Lastname as elname,
                          grp.group_name as batch_group_name, grp.group_code as batch_group_code');
        $this->db->from('loan l');
        $this->db->join('loan_products p', 'l.loan_product = p.loan_product_id');
        $this->db->join('individual_customers ic', 'l.loan_customer = ic.id', 'left');
        $this->db->join('groups g', 'l.loan_customer = g.group_id AND l.customer_type = "group"', 'left');
        $this->db->join('groups grp', 'l.group_id = grp.group_id', 'left');
        $this->db->join('employees e', 'l.loan_added_by = e.id', 'left');
        $this->db->where('l.batch', $batch);
        $this->db->order_by('l.loan_id', 'ASC');
        $loans = $this->db->get()->result();

        if(empty($loans)) {
            show_error('No loans found for batch: ' . $batch);
            return;
        }

        // Get batch group info from first loan
        $batch_group_name = !empty($loans[0]->batch_group_name) ? $loans[0]->batch_group_name : 'N/A';
        $batch_group_code = !empty($loans[0]->batch_group_code) ? $loans[0]->batch_group_code : 'N/A';

        $batch_data = array();
        $payment_schedules = array();

        foreach($loans as $loan) {
            // Get payment schedules
            $payments = $this->Payement_schedules_model->get_all_by_id($loan->loan_id);
            $payment_schedules[$loan->loan_id] = $payments;

            $maturity_date = $this->Payement_schedules_model->get_last_payment($loan->loan_id);
            $first_payment = $this->Payement_schedules_model->get_first_payment($loan->loan_id);

            if($loan->customer_type=='group'){
                $group = $this->Groups_model->get_by_id($loan->loan_customer);
                $customer_name = $group->group_name.'('.$group->group_code.')';
            }elseif($loan->customer_type=='individual'){
                $indi = $this->Individual_customers_model->get_by_id($loan->loan_customer);
                $customer_name = $indi->Firstname.' '.$indi->Lastname;
            }

            $branchname = get_branch_for_loan_value($loan->branch);
            $bname = $branchname ? $branchname->BranchName : 'N/A';

            $loan_obj = (object) array(
                'loan_id' => $loan->loan_id,
                'maturity_date' => $maturity_date ? $maturity_date->payment_schedule : 'N/A',
                'maturity_pay' => $maturity_date ? $maturity_date->amount : 0,
                'first_payment' => $first_payment ? $first_payment->amount : 0,
                'first_payment_date' => $first_payment ? $first_payment->payment_schedule : 'N/A',
                'loan_number' => $loan->loan_number,
                'loan_product' => $loan->product_name,
                'branch_name' => $bname,
                'loan_customer' => $customer_name,
                'customer_id' => $loan->loan_customer,
                'loan_date' => $loan->loan_date,
                'loan_principal' => $loan->loan_principal,
                'loan_period' => $loan->loan_period,
                'period_type' => $loan->period_type,
                'loan_interest' => $loan->loan_interest,
                'loan_interest_amount' => $loan->loan_interest_amount,
                'admin_fee' => $loan->admin_fee,
                'loan_cover' => $loan->loan_cover,
                'loan_amount_total' => $loan->loan_amount_total,
                'loan_amount_term' => $loan->loan_amount_term,
                'next_payment_id' => $loan->next_payment_id,
                'loan_added_by' => $loan->loan_added_by,
                'loan_approved_by' => $loan->loan_approved_by,
                'loan_status' => $loan->loan_status,
                'loan_added_date' => $loan->loan_added_date,
                'officer_name' => $loan->efname." ".$loan->elname,
                'Firstname' => $loan->Firstname,
                'Lastname' => $loan->Lastname,
                'ClientId' => $loan->ClientId,
                'product_name' => $loan->product_name,
                'customer_type' => $loan->customer_type
            );

            $batch_data[] = $loan_obj;
        }

        $data = array(
            'batch' => $batch,
            'batch_number' => $batch,
            'loans' => $batch_data,
            'payment_schedules' => $payment_schedules,
            'group_name' => $batch_group_name,
            'group_code' => $batch_group_code,
            'total_loans' => count($loans),
            'total_amount' => array_sum(array_column($batch_data, 'loan_principal')),
            'branch_name' => !empty($loans) && !empty($loans[0]->branch) ? (($b = get_branch_for_loan_value($loans[0]->branch)) ? $b->BranchName : 'N/A') : 'N/A'
        );

        $this->load->library('Pdf');
        $html = $this->load->view('loan/batch_report', $data, true);
        $this->pdf->createPDF($html, "Batch ".$batch." loan report as on ".date('Y-m-d'), true);
    }
    
    function pv(){
        $this->load->view('testv');
    }
    function approval_action(){
        $action = $this->input->post('action');
        if ($action === null || $action === '') {
            $action = $this->input->get('action');
        }
        $id = $this->input->post('id');
        if ($id === null || $id === '') {
            $id = $this->input->get('id');
        }
        $customer = $this->Loan_model->loan_user($id);
        $by = 'loan_approved_by';
        $by_date = 'approved_date';
        if($action =="REJECTED"){
            $by = 'rejected_by';
            $by_date = 'rejected_date';
        }
        if($action =="WRITTEN_OFF"){
            $by = 'written_off_by';
            $by_date = 'written_off_date';
        }
        if($action =="WRITE_OFF"){
            $by = 'written_off_by';
            $by_date = 'written_off_date';
        }
        $logger = array(
            'user_id' => $this->session->userdata('user_id'),
            'activity' => $action.' '.' a loan'

        );
        log_activity($logger);
        $notify = get_by_id('sms_settings','id','1');
        if($action =="ACTIVE"){

            $by = 'disbursed_by';
            $by_date = 'disbursed_date';

            $disbursement_datetime = $this->resolve_disbursement_datetime();
            $transaction_date = date('Y-m-d', strtotime($disbursement_datetime));
            $this->cash_transaction($id, $transaction_date);
            $this->pay_off_loan($id, $disbursement_datetime);
            $this->Payement_schedules_model->correct_premature_loan_closure($id);
        }else{
            $this->Loan_model->update($id,array('loan_status'=>$action,$by=>$this->session->userdata('user_id'),$by_date =>date('Y-m-d H:i:s')));
        }
        if($notify->loan_disbursement=='Yes' && $action =="ACTIVE"){
            send_sms($customer->PhoneNumber,'Dear customer, loan has been approved, you can call numbers below for more');
        }
        $this->toaster->success('Success, your action successful');
        $this->redirect_after_approval_action($action);
    }
    public function getMedianSchedule($totalSchedules) {
        // Validate that totalSchedules is an integer


        // Calculate the middle index
        if ($totalSchedules % 2 == 0) {
            // For even number of schedules, return the higher middle index
            $medianScheduleIndex = $totalSchedules / 2;
        } else {
            // For odd number of schedules, return the middle index
            $medianScheduleIndex = round($totalSchedules / 2);
        }

        return (int)$medianScheduleIndex;
    }
    public function pay_off_loan($loan_id, $disbursement_date = null){
        $exist = $this->db->select("*")->from('loan')->where('loan_id',$loan_id)->get()->row();
        if (!$exist) {
            return false;
        }

        // Do not auto-payoff other loans in the same group batch (sibling member loans).
        $this->db->select("*")->from('loan')
            ->where('loan_customer', $exist->loan_customer)
            ->where('loan_product', $exist->loan_product)
            ->where('loan_status', 'ACTIVE')
            ->where('loan_id !=', $loan_id);
        if (!empty($exist->batch)) {
            $this->db->where('batch !=', $exist->batch);
        }
        $has_loan = $this->db->get()->row();
        $by = 'disbursed_by';
        $by_date = 'disbursed_date';
        $action = "ACTIVE";
        $disbursed_dt = $this->resolve_disbursement_datetime($disbursement_date);
        $effective_loan_date = date('Y-m-d', strtotime($disbursed_dt));
        if(empty($has_loan)){
            $this->Loan_model->update($loan_id,array('loan_status'=>$action,'disbursed'=>'Yes',$by=>$this->session->userdata('user_id'),$by_date =>$disbursed_dt,'loan_date' => $effective_loan_date));
            $this->Loan_model->add_loan_rerun(
                $exist->loan_principal,
                $exist->loan_period,
                $exist->loan_product,
                $effective_loan_date,
                $exist->loan_customer,
                $exist->customer_type,
                $exist->loan_number,
                $loan_id
            );
            $this->db->where('loan_id', $loan_id)->update('payement_schedules', array('loan_date' => $effective_loan_date));
            $this->Payement_schedules_model->correct_premature_loan_closure($loan_id);
        }
        else {

            $this->Loan_model->update($loan_id,array('loan_status'=>$action,'disbursed'=>'Yes',$by=>$this->session->userdata('user_id'),$by_date =>$disbursed_dt,'loan_date' => $effective_loan_date));
            $this->Loan_model->add_loan_rerun(
                $exist->loan_principal,
                $exist->loan_period,
                $exist->loan_product,
                $effective_loan_date,
                $exist->loan_customer,
                $exist->customer_type,
                $exist->loan_number,
                $loan_id
            );
            $this->db->where('loan_id', $loan_id)->update('payement_schedules', array('loan_date' => $effective_loan_date));

//    $halfSchedules = $has_loan->loan_period / 2;
            $total_payoff = 0;
            $v = $this->getMedianSchedule($has_loan->loan_period);
            $loan_number = $has_loan->loan_number;
            $pay_number = $has_loan->next_payment_id;
            $middlepayment = $v;

            $get_middle_schedule = get_by_id2('payement_schedules', 'payment_number =' . $v . ' AND loan_id =' . $has_loan->loan_id);
            $payments = $this->db->select("*")->from('payement_schedules')->where('loan_id',$has_loan->loan_id)->get()->result();
            // Check if the current schedule is less than 50% of the total schedules
            if ($has_loan->next_payment_id <= $v) {
                // Ensure $v is an integer (you can adjust this logic based on your requirements)

                // Loop through only $v schedules
                for ($i = 0; $i < $v; $i++) {
                    // Assuming $payments is your array of payment objects
                    $total_payoff += $payments[$i]->amount;
                }
            } else {
                $total_payoff = $get_middle_schedule->amount;
            }



            $repay_amounts = $total_payoff;

            $amount = $total_payoff + $get_middle_schedule->loan_balance;
            $totalbalance = $get_middle_schedule->loan_balance + $get_middle_schedule->amount;
//    echo $has_loan->loan_id.",";
//    echo $repay_amounts.",";
//    echo $amount.",";
//    echo $totalbalance.",";
//
//    exit();

            $paid_date = date('Y-m-d');
            $loan_account = get_by_id('loan', 'loan_id', $has_loan->loan_id);
            $tid = "TR-S" . rand(100, 9999) . date('Y') . date('m') . date('d');
            $get_account = $this->Tellering_model->get_teller_account($this->session->userdata('user_id'));
            if (empty($get_account)) {
                $get_account = $this->Tellering_model->get_teller_account1();
            }

            if (empty($get_account)) {
                log_message('error', 'pay_off_loan: No teller account found for user ' . $this->session->userdata('user_id'));
                $this->Payement_schedules_model->correct_premature_loan_closure($loan_id);
                return;
            }

            $teller_account = $get_account->account;

            $mode = 'deposit';

            $res = $this->Account_model->cash_transaction_modified($teller_account, $loan_account->loan_number, $amount, $mode, $tid, $paid_date, 'deposit');
            if ($res == 'success') {

                $recepientt = get_by_id('account', 'collection_account', 'Yes');
                $check = $this->Account_model->get_account($loan_account->loan_number);
                if ($check->balance >= $amount) {
                    $do_transactions = $this->Account_model->transfer_funds($loan_account->loan_number, $recepientt->account_number, $amount, $tid, $paid_date);
                    if ($do_transactions == 'success') {


                        $this->Payement_schedules_model->new_pay_new($has_loan->loan_id, $pay_number, $repay_amounts, $paid_date, $tid);
                        $logger = array(

                            'user_id' => $this->session->userdata('user_id'),
                            'activity' => 'Paid a loan, loan ID:' . ' ' . $loan_number . ' ' . ' from payment number' . ' ' . $pay_number .
                                ' ' . 'amount' . ' ' . $amount


                        );
                        log_activity($logger);
                        $this->nullify_schedules($has_loan->loan_id, $middlepayment, $totalbalance);
                        $this->Payement_schedules_model->correct_premature_loan_closure($loan_id);
                        return true;
                    } else {
                        $this->Payement_schedules_model->correct_premature_loan_closure($loan_id);
                        return false;
                    }
                } else {

                    $this->toaster->error('Error!, balance not enough for payment');
                    $this->Payement_schedules_model->correct_premature_loan_closure($loan_id);
                    return false;
                }
            } else {
                $this->toaster->error('Error!, deposit module failed');

                $this->Payement_schedules_model->correct_premature_loan_closure($loan_id);
                return false;
            }


        }

        $this->Payement_schedules_model->correct_premature_loan_closure($loan_id);
    }
    public function cash_transaction($loan_id, $transaction_date = null){
        $tid="TR-S".rand(100,9999).date('Y').date('m').date('d');
        $result = array();
        $get_account = $this->Tellering_model->get_teller_account1();
        $teller_account = $get_account->account;
        $get_l = get_by_id('loan','loan_id',$loan_id);
        $charge = get_by_id('loan_products','loan_product_id', $get_l->loan_product);
        $charge_value =  ($charge->processing_fees/100)*$get_l->loan_principal;

        $account = $get_l->loan_number;
        $amount = $charge_value;
        $mode = 'deposit';
        $txn_date = $transaction_date ? date('Y-m-d', strtotime($transaction_date)) : date('Y-m-d');
        $res =	$this->Account_model->cash_transaction($teller_account,$account,$amount,$mode,$tid,$txn_date);
        if($res=='success'){
            $result['status']= 'success';

            $tid="TR-S".rand(100,9999).date('Y').date('m').date('d');


            $this->Account_model->transfer_funds($account, $teller_account, $amount, $tid);

            $data = array(
                'ref' => "GF." . date('Y') . date('m') . date('d') . '.' . rand(100, 999),
                'loan_id' => $loan_id,
                'amount' => $amount,
                'transaction_type' => 1,
                'payment_number' => 0,
                'added_by' => $this->session->userdata('user_id')

            );

            $this->Transactions_model->insert($data);





        }



    }
    function write_action(){
        $action = $this->input->get('action');
        $id= $this->input->get('id');
        $by = 'loan_approved_by';
        $by_date = 'approved_date';
        if($action =="REJECTED"){
            $by = 'rejected_by';
            $by_date = 'rejected_date';
        }
        if($action =="WRITTEN_OFF"){
            $by = 'write_off_approved_by';
            $by_date = 'write_off_approval_date';
        }
        if($action =="WRITE_OFF"){
            $by = 'written_off_by';
            $by_date = 'written_off_date';
        }
        $logger = array(
            'user_id' => $this->session->userdata('user_id'),
            'activity' => $action.' '.' a loan'

        );
        log_activity($logger);
        if($action =="ACTIVE"){
            $by = 'written_off_by';

            $by_date = 'written_off_date';
            $this->Loan_model->update($id,array('loan_status'=>$action, $by=>$this->session->userdata('user_id'),$by_date =>date('Y-m-d H:i:s')));
        }else{
            $this->Loan_model->update($id,array('loan_status'=>$action,$by=>$this->session->userdata('user_id'),$by_date =>date('Y-m-d H:i:s')));
        }

        $this->toaster->success('Success, your action successful');
        redirect($_SERVER['HTTP_REFERER']);
    }
    public function index()
    {
        $q = urldecode($this->input->get('q', TRUE));
        $start = intval($this->input->get('start'));

        if ($q <> '') {
            $config['base_url'] = base_url() . 'loan/index.html?q=' . urlencode($q);
            $config['first_url'] = base_url() . 'loan/index.html?q=' . urlencode($q);
        } else {
            $config['base_url'] = base_url() . 'loan/index.html';
            $config['first_url'] = base_url() . 'loan/index.html';
        }

        $config['per_page'] = 10;
        $config['page_query_string'] = TRUE;
        $config['total_rows'] = $this->Loan_model->total_rows($q);
        $loan = $this->Loan_model->get_limit_data($config['per_page'], $start, $q);

        $this->load->library('pagination');
        $this->pagination->initialize($config);

        $data = array(
            'loan_data' => $loan,
            'q' => $q,
            'pagination' => $this->pagination->create_links(),
            'total_rows' => $config['total_rows'],
            'start' => $start,
        );
        $this->load->view('admin/header');
        $this->load->view('loan/loan_list', $data);
        $this->load->view('admin/footer');

    }

    public function read($id)
    {
        $row = $this->Loan_model->get_by_id($id);
        if ($row) {
            $data = array(
                'loan_id' => $row->loan_id,
                'loan_number' => $row->loan_number,
                'loan_product' => $row->loan_product,
                'loan_customer' => $row->loan_customer,
                'loan_date' => $row->loan_date,
                'loan_principal' => $row->loan_principal,
                'loan_period' => $row->loan_period,
                'period_type' => $row->period_type,
                'loan_interest' => $row->loan_interest,
                'loan_amount_total' => $row->loan_amount_total,
                'next_payment_id' => $row->next_payment_id,
                'loan_added_by' => $row->loan_added_by,
                'loan_approved_by' => $row->loan_approved_by,
                'loan_status' => $row->loan_status,
                'loan_added_date' => $row->loan_added_date,
            );
            $this->load->view('loan/loan_read', $data);
        } else {
            $this->session->set_flashdata('message', 'Record Not Found');
            redirect(site_url('loan'));
        }
    }

    public function create()
    {
        $data = array(
            'button' => 'Create',
            'action' => site_url('loan/create_action'),
            'loan_id' => set_value('loan_id'),
            'loan_number' => set_value('loan_number'),
            'loan_product' => set_value('loan_product'),
            'loan_customer' => set_value('loan_customer'),
            'loan_date' => set_value('loan_date'),
            'loan_principal' => set_value('loan_principal'),
            'loan_period' => set_value('loan_period'),
            'period_type' => set_value('period_type'),
            'loan_interest' => set_value('loan_interest'),
            'loan_amount_total' => set_value('loan_amount_total'),
            'next_payment_id' => set_value('next_payment_id'),
            'loan_added_by' => set_value('loan_added_by'),
            'loan_approved_by' => set_value('loan_approved_by'),
            'loan_status' => set_value('loan_status'),
            'loan_added_date' => set_value('loan_added_date'),
        );
        $this->load->view('loan/loan_form', $data);
    }

    public function create_action()
    {
        $this->_rules();

        if ($this->form_validation->run() == FALSE) {
            $this->create();
        } else {
            $data = array(
                'loan_number' => $this->input->post('loan_number',TRUE),
                'loan_product' => $this->input->post('loan_product',TRUE),
                'loan_customer' => $this->input->post('loan_customer',TRUE),
                'loan_date' => $this->input->post('loan_date',TRUE),
                'loan_principal' => $this->input->post('loan_principal',TRUE),
                'loan_period' => $this->input->post('loan_period',TRUE),
                'period_type' => $this->input->post('period_type',TRUE),
                'loan_interest' => $this->input->post('loan_interest',TRUE),
                'loan_amount_total' => $this->input->post('loan_amount_total',TRUE),
                'next_payment_id' => $this->input->post('next_payment_id',TRUE),
                'loan_added_by' => $this->input->post('loan_added_by',TRUE),
                'loan_approved_by' => $this->input->post('loan_approved_by',TRUE),
                'loan_status' => $this->input->post('loan_status',TRUE),
                'loan_added_date' => $this->input->post('loan_added_date',TRUE),
            );

            $this->Loan_model->insert($data);
            $this->session->set_flashdata('message', 'Create Record Success');
            redirect(site_url('loan'));
        }
    }

    public function update($id)
    {
        $row = $this->Loan_model->get_by_id($id);

        if ($row) {
            $data = array(
                'button' => 'Update',
                'action' => site_url('loan/update_action'),
                'loan_id' => set_value('loan_id', $row->loan_id),
                'loan_number' => set_value('loan_number', $row->loan_number),
                'loan_product' => set_value('loan_product', $row->loan_product),
                'loan_customer' => set_value('loan_customer', $row->loan_customer),
                'loan_date' => set_value('loan_date', $row->loan_date),
                'loan_principal' => set_value('loan_principal', $row->loan_principal),
                'loan_period' => set_value('loan_period', $row->loan_period),
                'period_type' => set_value('period_type', $row->period_type),
                'loan_interest' => set_value('loan_interest', $row->loan_interest),
                'loan_amount_total' => set_value('loan_amount_total', $row->loan_amount_total),
                'next_payment_id' => set_value('next_payment_id', $row->next_payment_id),
                'loan_added_by' => set_value('loan_added_by', $row->loan_added_by),
                'loan_approved_by' => set_value('loan_approved_by', $row->loan_approved_by),
                'loan_status' => set_value('loan_status', $row->loan_status),
                'loan_added_date' => set_value('loan_added_date', $row->loan_added_date),
            );
            $this->load->view('loan/loan_form', $data);
        } else {
            $this->session->set_flashdata('message', 'Record Not Found');
            redirect(site_url('loan'));
        }
    }

    public function update_action()
    {
        $this->_rules();

        if ($this->form_validation->run() == FALSE) {
            $this->update($this->input->post('loan_id', TRUE));
        } else {
            $data = array(
                'loan_number' => $this->input->post('loan_number',TRUE),
                'loan_product' => $this->input->post('loan_product',TRUE),
                'loan_customer' => $this->input->post('loan_customer',TRUE),
                'loan_date' => $this->input->post('loan_date',TRUE),
                'loan_principal' => $this->input->post('loan_principal',TRUE),
                'loan_period' => $this->input->post('loan_period',TRUE),
                'period_type' => $this->input->post('period_type',TRUE),
                'loan_interest' => $this->input->post('loan_interest',TRUE),
                'loan_amount_total' => $this->input->post('loan_amount_total',TRUE),
                'next_payment_id' => $this->input->post('next_payment_id',TRUE),
                'loan_added_by' => $this->input->post('loan_added_by',TRUE),
                'loan_approved_by' => $this->input->post('loan_approved_by',TRUE),
                'loan_status' => $this->input->post('loan_status',TRUE),
                'loan_added_date' => $this->input->post('loan_added_date',TRUE),
                'loan_added_by'=>$this->input->post('loan_added_by', TRUE)
            );

            $this->Loan_model->update($this->input->post('loan_id', TRUE), $data);
            $this->session->set_flashdata('message', 'Update Record Success');
            redirect(site_url('loan'));
        }
    }
    public function update_action2()
    {

        $data = array(
            'loan_added_by'=>$this->input->post('loan_added_by', TRUE)
        );

        $this->Loan_model->update($this->input->post('loan_id', TRUE), $data);
        $this->session->set_flashdata('success', 'Update Record Success');
        redirect($_SERVER['HTTP_REFERER']);

    }

    public function delete($id)
    {
        $row = $this->Loan_model->get_by_id($id);

        if ($row) {
            $this->Loan_model->delete($id);
            $this->toaster->success('Success, your action successful');
            redirect($_SERVER['HTTP_REFERER']);
        } else {
            $this->session->set_flashdata('message', 'Record Not Found');
            redirect($_SERVER['HTTP_REFERER']);
        }
    }

    public function delete_data($id)
    {
        $row = $this->Loan_model->get_by_id($id);

        if ($row) {
            $this->Loan_model->delete_data($id);
            $this->toaster->success('Success, your action successful');
            redirect($_SERVER['HTTP_REFERER']);
        } else {
            $this->session->set_flashdata('message', 'Record Not Found');
            redirect($_SERVER['HTTP_REFERER']);
        }
    }
    function loan_report(){
        $data['loan_data'] = array();
        $this->load->view('admin/header');
        $this->load->view('loan/loan_report',$data);
        $this->load->view('admin/footer');
    }
    function loan_report_write_off(){
        $data['loan_data'] = array();
        $this->load->view('admin/header');
        $this->load->view('loan/loan_report_written_off',$data);
        $this->load->view('admin/footer');
    }
    /**
     * Send a request to generate a loan portfolio report
     * This function collects filter parameters, sends them to the Node.js backend,
     * and redirects the user to the reports page
     */
    public function portfolio_filter()
    {
        // Collect all the filter parameters from the POST request
        $product = $this->input->post('productid');
        $officer = $this->input->post('officer');
        $branch = $this->input->post('branch');
        $status = $this->input->post('status');
        if ($status === 'WRITTEN-OFF') {
            $status = 'WRITTEN_OFF';
        }
        $date_from = $this->input->post('date_from');
        $date_to = $this->input->post('date_to');

        // Initialize cURL session
        $ch = curl_init();

        // Set the URL of the endpoint through the shared helper
        $url = report_service_url('generate-report-portfolio');

        // Prepare the data to be sent to the Node.js backend
        $data = array_merge([
            "report_type" => "Loan Portfolio Report",
            "user" => $this->session->userdata('Firstname')." ".$this->session->userdata('Lastname'),
            "user_id" => $this->session->userdata('user_id'),
            "officer" => $officer,
            "product" => $product,
            "branch" => $branch,
            "status" => $status,
            "from_date" => $date_from,
            "to_date" => $date_to,
            "branchgp" => $branch // Using the same branch ID for groups - adjust if needed
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
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        // Check for errors
        if (curl_errno($ch)) {
            // Handle cURL error
            $this->toaster->error('Error: ' . curl_error($ch));
            redirect(site_url('report'));
        } else if ($http_code < 200 || $http_code >= 300) {
            $this->toaster->error('Error, report service returned HTTP ' . $http_code . '. Please retry.');
            redirect(site_url('report'));
        } else {
            // Display success message and redirect
            $this->toaster->success('Success, Loan Portfolio Report is being processed. You may continue with other tasks and check back later for the completed report.');
            redirect(site_url('report'));
        }

        // Close the cURL session
        curl_close($ch);
    }
    public function portfolio_filter_write_off()
    {
        // Collect all the filter parameters from the POST request
        $product = $this->input->post('productid');
        $officer = $this->input->post('officer');
        $branch = $this->input->post('branch');
        $status = $this->input->post('status');
        if ($status === 'WRITTEN-OFF') {
            $status = 'WRITTEN_OFF';
        }
        $date_from = $this->input->post('date_from');
        $date_to = $this->input->post('date_to');

        // Initialize cURL session
        $ch = curl_init();

        // Set the URL of the endpoint through the shared helper
        $url = report_service_url('generate-report-portfolio-write-off');

        // Prepare the data to be sent to the Node.js backend
        $data = array_merge([
            "report_type" => "Loan Portfolio Write-Off Report",
            "user" => $this->session->userdata('Firstname')." ".$this->session->userdata('Lastname'),
            "user_id" => $this->session->userdata('user_id'),
            "officer" => $officer,
            "product" => $product,
            "branch" => $branch,
            "status" => $status,
            "from_date" => $date_from,
            "to_date" => $date_to,
            "branchgp" => $branch // Using the same branch ID for groups - adjust if needed
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
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        // Check for errors
        if (curl_errno($ch)) {
            // Handle cURL error
            $this->toaster->error('Error: ' . curl_error($ch));
            redirect(site_url('report'));
        } else if ($http_code < 200 || $http_code >= 300) {
            $this->toaster->error('Error, write-off report service returned HTTP ' . $http_code . '. Please retry.');
            redirect(site_url('report'));
        } else {
            // Display success message and redirect
            $this->toaster->success('Success, Loan Portfolio Write Off Report is being processed. You may continue with other tasks and check back later for the completed report.');
            redirect(site_url('report'));
        }

        // Close the cURL session
        curl_close($ch);
    }

    function loan_report_search(){
        $user = $this->input->get('user');
        $branch = $this->input->get('branch');
        $product = $this->input->get('product');
        $status = $this->input->get('status');
        $from = $this->input->get('from');
        $to = $this->input->get('to');
        $search = $this->input->get('search');
        if($branch==7042){

            $branchgp=7;
        }
        else{
            $branchgp=6;
        }

        if($search=="filter"){
            $data['loan_data'] = $this->Loan_model->get_filter($user,$branch,$branchgp,$product,$status,$from,$to);
            $this->load->view('admin/header');
            $this->load->view('loan/loan_report',$data);
            $this->load->view('admin/footer');
        }elseif($search=='pdf'){
            $data['loan_data'] = $this->Loan_model->get_filter($user,$branch,$branchgp,$product,$status,$from,$to);
            $data['officer'] = ($user=="All") ? "All Officers" : get_by_id('employees','id',$user)->Firstname;
            $data['product'] =($product=="All") ? "All Products" : get_by_id('loan_products','loan_product_id',$product)->product_name;
            $data['from'] = $from;
            $data['to'] = $to;
            $this->load->library('Pdf');
            $html = $this->load->view('loan/loan_report_pdf', $data,true);
            $this->pdf->createPDF($html, "loan report as on".date('Y-m-d'), true,'A4','landscape');
        }
        elseif($search=='excel'){
            $data['loan_data'] = $this->Loan_model->get_filter($user,$branch,$branchgp,$product,$status,$from,$to);
            $data['officer'] = ($user=="All") ? "All Officers" : get_by_id('employees','id',$user)->Firstname;
            $data['product'] =($product=="All") ? "All Products" : get_by_id('loan_products','loan_product_id',$product)->product_name;
            $data['from'] = $from;
            $data['to'] = $to;

            $html_tableloanreport = $this->load->view('reports/loan_report_excel', $data, true); // Load the view with the data
            $this->output->set_content_type('text/html')->set_output($html_tableloanreport);


        }

    }
    function loan_report_projection(){

        $this->load->view('admin/header');
        $this->load->view('loan/loan_report_projectn');
        $this->load->view('admin/footer');
    }
    function loan_report_search_projection(){
//		$user = $this->input->get('user');
//		$product = $this->input->get('product');
//		$status = $this->input->get('status');
        $from = $this->input->get('from');
        $to = $this->input->get('to');
        $search = $this->input->get('search');
        if($search=="filter"){


            $result = $this->Payement_schedules_model->get_filter_projection($from,$to);
            $amount= $this->Payement_schedules_model->get_filter_projections($from,$to);
            $pri= $this->Payement_schedules_model->get_filter_projection_principal($from,$to);
            $inter= $this->Payement_schedules_model->get_filter_projection_interest($from,$to);
            $data = array(
                'amount'=>$amount['amount'],
                'interest'=>$inter['interest'],
                'principal'=>$pri['principal'],
                'paid_amount'=>$result['paid_amount']

            );

            $this->load->view('admin/header');
            $this->load->view('loan/loan_report_projections',$data);
            $this->load->view('admin/footer');
        }elseif($search=='pdf'){
            $data['loan_data'] = $this->Loan_model->get_filter($from,$to);
//			$data['officer'] = ($user=="All") ? "All Officers" : get_by_id('employees','id',$user)->Firstname;
//			$data['product'] =($product=="All") ? "All Products" : get_by_id('loan_products','loan_product_id',$product)->product_name;
            $data['from'] = $from;
            $data['to'] = $to;
            $this->load->library('Pdf');
            $html = $this->load->view('loan/loan_report_pdf', $data,true);
            $this->pdf->createPDF($html, "loan report as on".date('Y-m-d'), true,'A4','landscape');
        }

    }

    public function _rules()
    {
        $this->form_validation->set_rules('loan_number', 'loan number', 'trim|required');
        $this->form_validation->set_rules('loan_product', 'loan product', 'trim|required');
        $this->form_validation->set_rules('loan_customer', 'loan customer', 'trim|required');
        $this->form_validation->set_rules('loan_date', 'loan date', 'trim|required');
        $this->form_validation->set_rules('loan_principal', 'loan principal', 'trim|required|numeric');
        $this->form_validation->set_rules('loan_period', 'loan period', 'trim|required');
        $this->form_validation->set_rules('period_type', 'period type', 'trim|required');
        $this->form_validation->set_rules('loan_interest', 'loan interest', 'trim|required');
        $this->form_validation->set_rules('loan_amount_total', 'loan amount total', 'trim|required|numeric');
        $this->form_validation->set_rules('next_payment_id', 'next payment id', 'trim|required');
        $this->form_validation->set_rules('loan_added_by', 'loan added by', 'trim|required');
        $this->form_validation->set_rules('loan_approved_by', 'loan approved by', 'trim|required');
        $this->form_validation->set_rules('loan_status', 'loan status', 'trim|required');
        $this->form_validation->set_rules('loan_added_date', 'loan added date', 'trim|required');

        $this->form_validation->set_rules('loan_id', 'loan_id', 'trim');
        $this->form_validation->set_error_delimiters('<span class="text-danger">', '</span>');
    }
    function edit(){
        $this->Loan_model->edits();
    }

    function initiate_edit_loan()
    {
        $this->load_paginated_loan_list('loan/initiate_edit_loan', $this->loan_list_page_options('initiate_edit_loan', array(
            'default_status' => 'All',
        )));
    }

    public function excel($edata)
    {
        $this->loan_list_excel_export($edata);
    }

    private function loan_export_customer_name($data)
    {
        if (!empty($data->customer_display_name)) {
            return $data->customer_display_name;
        }
        if (!empty($data->customer_nam)) {
            return $data->customer_nam;
        }
        if (isset($data->customer_type) && $data->customer_type == 'group') {
            $group = $this->Groups_model->get_by_id($data->loan_customer);
            return $group ? $group->group_name . '(' . $group->group_code . ')' : 'Unknown';
        }
        if (isset($data->customer_type) && $data->customer_type == 'individual') {
            $indi = $this->Individual_customers_model->get_by_id($data->loan_customer);
            return $indi ? $indi->Firstname . ' ' . $indi->Lastname . ' (' . $indi->ClientId . ')' : 'Unknown';
        }
        return 'Unknown';
    }

    private function loan_list_excel_export($edata)
    {
        $filename = 'loan-report_' . date('Y-m-d') . '.xls';
        xlsHeaders($filename);

        $tablehead = 0;
        $tablebody = 1;
        $nourut = 1;

        xlsBOF();

        $kolomhead = 0;
        xlsWriteLabel($tablehead, $kolomhead++, 'No');
        xlsWriteLabel($tablehead, $kolomhead++, 'Loan Number');
        xlsWriteLabel($tablehead, $kolomhead++, 'Loan Product');
        xlsWriteLabel($tablehead, $kolomhead++, 'Loan Customer');
        xlsWriteLabel($tablehead, $kolomhead++, 'Loan Date');
        xlsWriteLabel($tablehead, $kolomhead++, 'Loan Principal');
        xlsWriteLabel($tablehead, $kolomhead++, 'Loan Period');
        xlsWriteLabel($tablehead, $kolomhead++, 'Period Type');
        xlsWriteLabel($tablehead, $kolomhead++, 'Loan Interest Rate');
        xlsWriteLabel($tablehead, $kolomhead++, 'Loan Interest Amount');
        xlsWriteLabel($tablehead, $kolomhead++, 'Admin Fee Rate');
        xlsWriteLabel($tablehead, $kolomhead++, 'Admin Fees Amount');
        xlsWriteLabel($tablehead, $kolomhead++, 'Loan Cover Rate');
        xlsWriteLabel($tablehead, $kolomhead++, 'Loan Cover Amount');
        xlsWriteLabel($tablehead, $kolomhead++, 'Loan Amount Term');
        xlsWriteLabel($tablehead, $kolomhead++, 'Loan Amount Total');
        xlsWriteLabel($tablehead, $kolomhead++, 'Next Payment Id');
        xlsWriteLabel($tablehead, $kolomhead++, 'Worthness File');
        xlsWriteLabel($tablehead, $kolomhead++, 'Narration');
        xlsWriteLabel($tablehead, $kolomhead++, 'Loan Added By');
        xlsWriteLabel($tablehead, $kolomhead++, 'Loan Approved By');
        xlsWriteLabel($tablehead, $kolomhead++, 'Approved Date');
        xlsWriteLabel($tablehead, $kolomhead++, 'Rejected By');
        xlsWriteLabel($tablehead, $kolomhead++, 'Rejected Date');
        xlsWriteLabel($tablehead, $kolomhead++, 'Loan Status');
        xlsWriteLabel($tablehead, $kolomhead++, 'Disbursed');
        xlsWriteLabel($tablehead, $kolomhead++, 'Disbursed By');
        xlsWriteLabel($tablehead, $kolomhead++, 'Disbursed Date');
        xlsWriteLabel($tablehead, $kolomhead++, 'Written Off By');
        xlsWriteLabel($tablehead, $kolomhead++, 'Write Off Approved By');
        xlsWriteLabel($tablehead, $kolomhead++, 'Write Off Approval Date');
        xlsWriteLabel($tablehead, $kolomhead++, 'Written Off Date');
        xlsWriteLabel($tablehead, $kolomhead++, 'Loan Added Date');

        foreach ($edata as $data) {
            $kolombody = 0;
            $customer_name = $this->loan_export_customer_name($data);

            xlsWriteNumber($tablebody, $kolombody++, $nourut);
            xlsWriteLabel($tablebody, $kolombody++, isset($data->loan_number) ? $data->loan_number : '');
            xlsWriteLabel($tablebody, $kolombody++, isset($data->product_name) ? $data->product_name : '');
            xlsWriteLabel($tablebody, $kolombody++, $customer_name);
            xlsWriteLabel($tablebody, $kolombody++, isset($data->loan_date) ? $data->loan_date : '');
            xlsWriteNumber($tablebody, $kolombody++, isset($data->loan_principal) ? $data->loan_principal : 0);
            xlsWriteNumber($tablebody, $kolombody++, isset($data->loan_period) ? $data->loan_period : 0);
            xlsWriteLabel($tablebody, $kolombody++, isset($data->period_type) ? $data->period_type : '');
            xlsWriteNumber($tablebody, $kolombody++, isset($data->loan_interest) ? $data->loan_interest : 0);
            xlsWriteNumber($tablebody, $kolombody++, isset($data->loan_interest_amount) ? $data->loan_interest_amount : 0);
            xlsWriteNumber($tablebody, $kolombody++, isset($data->admin_fee) ? $data->admin_fee : 0);
            xlsWriteNumber($tablebody, $kolombody++, isset($data->admin_fees_amount) ? $data->admin_fees_amount : 0);
            xlsWriteNumber($tablebody, $kolombody++, isset($data->loan_cover) ? $data->loan_cover : 0);
            xlsWriteNumber($tablebody, $kolombody++, isset($data->loan_cover_amount) ? $data->loan_cover_amount : 0);
            xlsWriteNumber($tablebody, $kolombody++, isset($data->loan_amount_term) ? $data->loan_amount_term : 0);
            xlsWriteNumber($tablebody, $kolombody++, isset($data->loan_amount_total) ? $data->loan_amount_total : 0);
            xlsWriteNumber($tablebody, $kolombody++, isset($data->next_payment_id) ? $data->next_payment_id : 0);
            xlsWriteLabel($tablebody, $kolombody++, isset($data->worthness_file) ? $data->worthness_file : '');
            xlsWriteLabel($tablebody, $kolombody++, isset($data->narration) ? $data->narration : '');
            xlsWriteLabel($tablebody, $kolombody++, trim((isset($data->efname) ? $data->efname : '') . ' ' . (isset($data->elname) ? $data->elname : '')));
            xlsWriteLabel($tablebody, $kolombody++, trim((isset($data->approverfname) ? $data->approverfname : '') . ' ' . (isset($data->approverlname) ? $data->approverlname : '')));
            xlsWriteLabel($tablebody, $kolombody++, isset($data->approved_date) ? $data->approved_date : '');
            xlsWriteLabel($tablebody, $kolombody++, trim((isset($data->rejecterfname) ? $data->rejecterfname : '') . ' ' . (isset($data->rejecterlname) ? $data->rejecterlname : '')));
            xlsWriteLabel($tablebody, $kolombody++, isset($data->rejected_date) ? $data->rejected_date : '');
            xlsWriteLabel($tablebody, $kolombody++, isset($data->loan_status) ? $data->loan_status : '');
            xlsWriteLabel($tablebody, $kolombody++, isset($data->disbursed) ? $data->disbursed : '');
            xlsWriteLabel($tablebody, $kolombody++, trim((isset($data->disburserfname) ? $data->disburserfname : '') . ' ' . (isset($data->disburserlname) ? $data->disburserlname : '')));
            xlsWriteLabel($tablebody, $kolombody++, isset($data->disbursed_date) ? $data->disbursed_date : '');
            xlsWriteLabel($tablebody, $kolombody++, isset($data->written_off_by) ? $data->written_off_by : '');
            xlsWriteLabel($tablebody, $kolombody++, isset($data->write_off_approved_by) ? $data->write_off_approved_by : '');
            xlsWriteLabel($tablebody, $kolombody++, isset($data->write_off_approval_date) ? $data->write_off_approval_date : '');
            xlsWriteLabel($tablebody, $kolombody++, isset($data->written_off_date) ? $data->written_off_date : '');
            xlsWriteLabel($tablebody, $kolombody++, isset($data->loan_added_date) ? $data->loan_added_date : '');

            $tablebody++;
            $nourut++;
        }

        xlsEOF();
        exit();
    }

    public function word()
    {
        header("Content-type: application/vnd.ms-word");
        header("Content-Disposition: attachment;Filename=loan.doc");

        $data = array(
            'loan_data' => $this->Loan_model->get_all(),
            'start' => 0
        );

        $this->load->view('loan/loan_doc',$data);
    }
    public  function fixdate(){
        $this->db->select('*');
        $this->db->from('transaction');
        $this->db->group_by('transaction_id');
        $query = $this->db->get();
        $result = $query->result();

// Now $result contains all records associated with distinct transaction_id values

        foreach ($result as $r){
            $this->db->where('transaction_id',$r->transaction_id)->update('transaction',array('system_time'=>$r->server_time));
        }
// Now $result contains distinct transaction_id values

    }

}
