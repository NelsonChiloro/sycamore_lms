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
        $data['officers'] = $this->Employees_model->get_report_loan_officers();

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
        $filters=$this->read_revenue_filters('post');
        $start_date=$filters['start_date'];$end_date=$filters['end_date'];
        $officer_id=$filters['officer_id'];$branch_id=$filters['branch_id'];$supervisor_id=$filters['supervisor'];
        $data['supervisor_id']=$supervisor_id;
        // Prepare data for the view
        $data['start_date'] = $start_date;
        $data['end_date'] = $end_date;
        $data['officer_id'] = $officer_id;
        $data['branch_id'] = $branch_id;

        // Set default date display for empty dates
        $display_start_date = !empty($start_date) ? date('d M Y', strtotime($start_date)) : 'All time';
        $display_end_date = !empty($end_date) ? date('d M Y', strtotime($end_date)) : 'Present';
        $data['date_range_display'] = "$display_start_date to $display_end_date";

        // Get officer / supervisor label for report header
        if ($supervisor_id) {
            $name = report_supervisor_display_name($supervisor_id);
            $data['officer_name'] = $name !== '' ? ('Supervisor: ' . $name) : 'Supervisor filter';
        } elseif (!empty($officer_id)) {
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
        $data['revenue_data'] = $this->get_revenue_data($start_date, $end_date, $officer_id, $branch_id, $supervisor_id);

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
    private function get_revenue_data($start_date = null, $end_date = null, $officer_id = null, $branch_id = null, $supervisor_id = null) {
        // Resolve before constructing the query: the employee resolver uses
        // the shared query builder and resets its current SELECT.
        $supervisor_officers=$supervisor_id?report_resolve_supervisor_officer_ids($supervisor_id):null;
        // Resolve each loan to exactly one branch: real ID first, then legacy
        // internal Code, then BranchCode. String comparisons avoid MySQL's
        // numeric coercion (for example a nonnumeric code matching zero).
        $branch_sql="(SELECT rb.id FROM branches rb
            WHERE TRIM(CAST(l.branch AS CHAR))<>'' AND (
                CAST(rb.id AS CHAR)=TRIM(CAST(l.branch AS CHAR))
                OR CAST(rb.Code AS CHAR)=TRIM(CAST(l.branch AS CHAR))
                OR CAST(rb.BranchCode AS CHAR)=TRIM(CAST(l.branch AS CHAR)))
            ORDER BY CASE WHEN CAST(rb.id AS CHAR)=TRIM(CAST(l.branch AS CHAR)) THEN 0
                WHEN CAST(rb.Code AS CHAR)=TRIM(CAST(l.branch AS CHAR)) THEN 1 ELSE 2 END, rb.id
            LIMIT 1)";
        $this->db->select('l.loan_id,l.loan_product,b.id AS branch_id,b.BranchName AS branch_name,
            COALESCE(lp.product_name,CONCAT("Product ",l.loan_product)) AS product_name,
            COALESCE(l.loan_interest_amount,0) AS interest,COALESCE(l.admin_fees_amount,0) AS admin_fees,
            COALESCE(l.loan_cover_amount,0) AS loan_cover,
            COALESCE((SELECT SUM(CAST(t.amount AS DECIMAL(18,2))) FROM transactions t
                WHERE t.loan_id=l.loan_id AND t.transaction_type=1 AND t.payment_number=0
                AND t.ref LIKE "GF.%"
                AND NOT EXISTS(SELECT 1 FROM transaction a WHERE a.transaction_id=t.ref AND a.reversed="YES")),0) AS processing_fees,
            COALESCE((SELECT SUM(penalty_amount) FROM loan_penalties WHERE loan_id=l.loan_id),0) AS penalties,
            CASE WHEN l.loan_status="WRITTEN_OFF" THEN l.loan_principal ELSE 0 END AS write_off',false);
        $this->db->from('loan l');
        $this->db->join('branches b','b.id='.$branch_sql,'inner',false);
        $this->db->join('loan_products lp','lp.loan_product_id=l.loan_product','left');
        $this->db->where('l.disbursed','Yes');
        if ($branch_id!==null && $branch_id!=='') $this->db->where('b.id',(int)$branch_id);
        if ($start_date) $this->db->where('l.loan_date >=',$start_date);
        if ($end_date) $this->db->where('l.loan_date <=',$end_date);
        if ($supervisor_id) {
            if ($supervisor_officers) $this->db->where_in('l.loan_added_by',$supervisor_officers);
            else $this->db->where('1=0',null,false);
        }
        elseif ($officer_id) $this->db->where('l.loan_added_by',(int)$officer_id);
        $this->db->order_by('b.BranchName','ASC')->order_by('product_name','ASC')->order_by('l.loan_id','ASC');
        $query=$this->db->get();
        if (!$query) throw new RuntimeException('Revenue query failed; check the application database log.');
        $result=array();
        foreach($query->result_array() as $loan) {
            $key=$loan['branch_id'].'|'.$loan['loan_product'];
            if (!isset($result[$key])) {
                $result[$key]=array('product_id'=>$loan['loan_product'],'product_name'=>$loan['product_name'],
                    'branch_id'=>$loan['branch_id'],'branch_name'=>$loan['branch_name'],'interest'=>0,'admin_fees'=>0,
                    'loan_cover'=>0,'processing_fees'=>0,'penalties'=>0,'write_off'=>0,'total'=>0,'loans_count'=>0);
            }
            foreach(array('interest','admin_fees','loan_cover','processing_fees','penalties','write_off') as $field) {
                $result[$key][$field]+=(float)$loan[$field];
                $result[$key]['total']+=(float)$loan[$field];
            }
            $result[$key]['loans_count']++;
        }
        foreach($result as &$item) foreach(array('interest','admin_fees','loan_cover','processing_fees','penalties','write_off','total') as $field) $item[$field]=round($item[$field],2);
        unset($item);
        return array_values($result);
    }
    /**
     * Export the report to Excel
     */
    public function export_excel() {
        $filters=$this->read_revenue_filters('get');
        $revenue_data=$this->get_revenue_data($filters['start_date'],$filters['end_date'],$filters['officer_id'],$filters['branch_id'],$filters['supervisor']);
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

        $rows=array(array('Branch','Product','Interest','Admin Fees','Loan Cover','Processing Fees','Penalties','Write Off Income','Total','Loans Count'));
        foreach($revenue_data as $item) $rows[]=array($item['branch_name'],$item['product_name'],$item['interest'],$item['admin_fees'],
            $item['loan_cover'],$item['processing_fees'],$item['penalties'],$item['write_off'],$item['total'],(int)$item['loans_count']);
        $rows[]=array('TOTALS','',$totals['interest'],$totals['admin_fees'],$totals['loan_cover'],$totals['processing_fees'],
            $totals['penalties'],$totals['write_off'],$totals['total'],array_sum(array_column($revenue_data,'loans_count')));
        $this->load->helper('report_xlsx');
        $bytes=report_xlsx_bytes($rows,'Revenue Analysis',array(2,3,4,5,6,7,8));
        $this->output->set_content_type('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->set_header('Content-Disposition: attachment; filename="Revenue_Analysis_Report_'.date('Y-m-d').'.xlsx"')
            ->set_output($bytes);
    }

    private function read_revenue_filters($method) {
        $filters=array();
        foreach(array('start_date','end_date','officer_id','branch_id','supervisor') as $field) {
            $value=$this->input->$method($field,true);
            if ($value!==null && !is_scalar($value)) {show_error('Invalid revenue filter.',400);return array();}
            $value=trim((string)$value);
            if ($value==='' || $value==='All') {$filters[$field]=null;continue;}
            if (in_array($field,array('start_date','end_date'),true)) {
                $date=DateTime::createFromFormat('!Y-m-d',$value);
                if (!$date || $date->format('Y-m-d')!==$value) {show_error('Invalid revenue report date.',400);return array();}
            } elseif (!preg_match('/^[1-9][0-9]*$/',$value)) {show_error('Invalid revenue report selection.',400);return array();}
            $filters[$field]=$value;
        }
        if ($filters['start_date'] && $filters['end_date'] && $filters['start_date']>$filters['end_date']) {show_error('Start date must not exceed end date.',400);return array();}
        if ($filters['supervisor']) $filters['officer_id']=null;
        return $filters;
    }
}
