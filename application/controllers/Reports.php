<?php
//require 'vendor/autoload.php';

//use PhpOffice\PhpSpreadsheet\Spreadsheet;
//use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class Reports extends CI_Controller
{
    const SUMMARY_CACHE_TTL = 600;
    const SUMMARY_SESSION_CACHE_KEY = 'summary_dashboard_cache';

		public  function __construct()
	{
		parent::__construct();
		$this->load->model('Payement_schedules_model');
		$this->load->model('Loan_model');
		$this->load->model('Groups_model');
		$this->load->model('Employees_model');
		$this->load->model('Individual_customers_model');
		$this->load->model('Transactions_model');
		$this->load->model('Global_config_model');
		$this->load->model('Borrowed_repayements_model');
        $this->load->model('Collateral_model');
        $this->load->helper('report_service');

    }
public function parfilter(){
	$officerid= $this->input->get('id');
    $productid= $this->input->get('productid');
	$this->session->set_userdata('officerid',$officerid);
    $this->session->set_userdata('productid', $productid);

	$this->load->view('admin/header');
	$this->load->view('reports/par_report');
	$this->load->view('admin/footer');
}

public function portfolio_filter(){
	$officerid= $this->input->get('id');
    $productid= $this->input->get('productid');
	$this->session->set_userdata('officerid',$officerid);
    $this->session->set_userdata('productid', $productid);

	$this->load->view('admin/header');
	$this->load->view('reports/portfolio_analysis');
	$this->load->view('admin/footer');
}

public function portfolio_dashboard(){
    $data = array(
        'settings'      => get_by_id('settings', 'settings_id', '1'),
        'node_base_url' => 'http://localhost:4500',
        'dashboard_data_url' => base_url('reports/portfolio_dashboard_data'),
    );
    $this->load->view('admin/header', $data);
    $this->load->view('reports/portfolio_dashboard', $data);
    $this->load->view('admin/footer');
}

private function portfolio_dashboard_map_loan_row($r, $par_rate = null)
{
    $gross = (float) $r->principal;
    $outstanding = (float) $r->outstanding;
    $accrued = isset($r->accrued_charges)
        ? (float) $r->accrued_charges
        : max($outstanding - $gross, 0.0);

    return array(
        'loan_id' => (int) $r->loan_id,
        'customer_name' => $r->customer_name,
        'customer_group' => isset($r->customer_group) ? $r->customer_group : 'N/A',
        'loan_number' => $r->loan_number,
        'product_name' => $r->product_name,
        'branch_name' => $r->branch_name,
        'officer_name' => $r->officer_name,
        'relationship_supervisor' => isset($r->relationship_supervisor) ? $r->relationship_supervisor : 'N/A',
        'loan_date' => isset($r->loan_date) ? $r->loan_date : $r->disbursement_date,
        'loan_principal_amount' => isset($r->loan_principal_amount) ? (float) $r->loan_principal_amount : 0.0,
        'gross_loan_portfolio' => $gross,
        'principal' => $gross,
        'period' => $r->period,
        'interest_rate' => (float) $r->interest_rate,
        'total_loan_amount' => isset($r->total_loan_amount) ? (float) $r->total_loan_amount : 0.0,
        'installment_amount' => (float) $r->installment_amount,
        'accrued_charges' => $accrued,
        'outstanding' => $outstanding,
        'arrears' => (float) $r->arrears,
        'days_arrears' => (int) $r->days_arrears,
        'par_classification' => isset($r->par_classification) ? $r->par_classification : 'Current',
        'rbm_classification' => isset($r->rbm_classification) ? $r->rbm_classification : 'Standard',
        'payments_in_arrears' => isset($r->payments_in_arrears) ? (int) $r->payments_in_arrears : 0,
        'collection_rate' => (float) $r->collection_rate,
        'last_payment_date' => isset($r->last_payment_date) ? $r->last_payment_date : null,
        'collateral_value' => isset($r->collateral_value) ? (float) $r->collateral_value : 0.0,
        'maturity_date' => isset($r->maturity_date) ? $r->maturity_date : null,
        'total_expected' => (float) $r->total_expected,
        'actual_payments' => isset($r->actual_payments) ? (float) $r->actual_payments : 0.0,
        'loan_status' => $r->loan_status,
        'loan_added_date' => isset($r->loan_added_date) ? $r->loan_added_date : null,
        'disbursement_date' => $r->disbursement_date,
        'par_rate' => $par_rate,
    );
}

public function portfolio_dashboard_data()
{
    $snapshot = $this->db->select('*')
        ->from('pd_snapshots')
        ->order_by('snapshot_time', 'DESC')
        ->limit(1)
        ->get()
        ->row();

    if (!$snapshot) {
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(array(
                'success' => false,
                'message' => 'No portfolio dashboard snapshot found yet.',
            ), JSON_INVALID_UTF8_SUBSTITUTE));
        return;
    }

    $sid = (int) $snapshot->id;
    $branches = $this->db->select('*')->from('pd_branches')->where('snapshot_id', $sid)->order_by('outstanding', 'DESC')->get()->result();
    $products = $this->db->select('*')->from('pd_products')->where('snapshot_id', $sid)->order_by('outstanding_km', 'DESC')->get()->result();
    $officers = $this->db->select('*')->from('pd_officers')->where('snapshot_id', $sid)->order_by('outstanding', 'DESC')->get()->result();
    $trend = $this->db->select('*')->from('pd_trend')->where('snapshot_id', $sid)->order_by('month_year', 'ASC')->get()->result();
    $aging = $this->db->query(
        "SELECT * FROM pd_aging WHERE snapshot_id = ? ORDER BY FIELD(bucket,'1-30d','31-60d','61-90d','91-180d','>180d')",
        array($sid)
    )->result();
    $loans = $this->db->select('*')->from('pd_loans')->where('snapshot_id', $sid)->get()->result();

    $payload = array(
        'success' => true,
        'snapshot' => array(
            'id' => (int) $snapshot->id,
            'snapshot_time' => $snapshot->snapshot_time,
            'as_of_date' => $snapshot->as_of_date,
            'active_loans' => (int) $snapshot->active_loans,
            'total_principal' => (float) $snapshot->total_principal,
            'total_outstanding' => (float) $snapshot->total_outstanding,
            'total_arrears' => (float) $snapshot->total_arrears,
            'loans_in_arrears' => (int) $snapshot->loans_in_arrears,
            'par30' => (float) $snapshot->par30,
            'par90' => (float) $snapshot->par90,
            'collection_rate' => (float) $snapshot->collection_rate,
        ),
        'branches' => array_map(function ($r) {
            return array(
                'branch_name' => $r->branch_name,
                'loans_count' => (int) $r->loans_count,
                'outstanding' => (float) $r->outstanding,
                'arrears' => (float) $r->arrears,
                'par30' => (float) $r->par30,
                'par90' => (float) $r->par90,
                'collection_rate' => (float) $r->collection_rate,
            );
        }, $branches),
        'products' => array_map(function ($r) {
            return array(
                'product_name' => $r->product_name,
                'loans_count' => (int) $r->loans_count,
                'outstanding_km' => (float) $r->outstanding_km,
                'arrears_km' => (float) $r->arrears_km,
                'par_rate' => (float) $r->par_rate,
            );
        }, $products),
        'officers' => array_map(function ($r) {
            return array(
                'officer_name' => $r->officer_name,
                'loans_count' => (int) $r->loans_count,
                'outstanding' => (float) $r->outstanding,
                'arrears' => (float) $r->arrears,
                'par_rate' => (float) $r->par_rate,
                'collection_rate' => (float) $r->collection_rate,
            );
        }, $officers),
        'trend' => array_map(function ($r) {
            return array(
                'month_year' => $r->month_year,
                'loan_count' => (int) $r->loan_count,
                'principal_km' => (float) $r->principal_km,
            );
        }, $trend),
        'aging' => array_map(function ($r) {
            return array(
                'bucket' => $r->bucket,
                'loan_count' => (int) $r->loan_count,
                'arrears_km' => (float) $r->arrears_km,
                'outstanding_balance' => (float) $r->outstanding_balance,
            );
        }, $aging),
        'loans' => array_map(function ($r) {
            return $this->portfolio_dashboard_map_loan_row($r);
        }, $loans),
    );

    $this->output
        ->set_content_type('application/json')
        ->set_output(json_encode($payload, JSON_INVALID_UTF8_SUBSTITUTE));
}

public function portfolio_dashboard_filtered_data()
{
    $snapshot = $this->db->select('*')->from('pd_snapshots')->order_by('snapshot_time', 'DESC')->limit(1)->get()->row();
    if (!$snapshot) {
        $this->output->set_content_type('application/json')->set_output(json_encode(array('success' => false, 'message' => 'No snapshot found.'), JSON_INVALID_UTF8_SUBSTITUTE));
        return;
    }

    $sid = (int) $snapshot->id;
    $branch = trim((string) $this->input->get('branch', true));
    $product = trim((string) $this->input->get('product', true));
    $officer = trim((string) $this->input->get('officer', true));
    $risk = trim((string) $this->input->get('risk', true));
    $q = trim((string) $this->input->get('q', true));
    $min_collection = (float) $this->input->get('min_collection', true);

    $this->db->select('*')->from('pd_loans')->where('snapshot_id', $sid);
    if ($branch !== '') {
        $this->db->where('branch_name', $branch);
    }
    if ($product !== '') {
        $this->db->where('product_name', $product);
    }
    if ($officer !== '') {
        $this->db->where('officer_name', $officer);
    }
    if ($q !== '') {
        $this->db->group_start()
            ->like('loan_number', $q)
            ->or_like('customer_name', $q)
            ->or_like('product_name', $q)
            ->or_like('branch_name', $q)
            ->or_like('officer_name', $q)
            ->group_end();
    }
    if ($min_collection > 0) {
        $this->db->where('collection_rate >=', $min_collection);
    }
    $rows = $this->db->get()->result();

    $loans = array();
    foreach ($rows as $r) {
        $principal = (float) $r->principal;
        $out = (float) $r->outstanding;
        $arr = (float) $r->arrears;
        $par_rate = $out > 0 ? (($arr / $out) * 100.0) : 0.0;
        if ($risk === 'healthy' && $par_rate >= 10) {
            continue;
        }
        if ($risk === 'monitor' && !($par_rate >= 10 && $par_rate < 20)) {
            continue;
        }
        if ($risk === 'critical' && $par_rate < 20) {
            continue;
        }

        $loans[] = $this->portfolio_dashboard_map_loan_row($r, $par_rate);
    }

    $summary = array(
        'id' => $sid,
        'snapshot_time' => $snapshot->snapshot_time,
        'as_of_date' => $snapshot->as_of_date,
        'active_loans' => count($loans),
        'total_principal' => 0.0,
        'total_outstanding' => 0.0,
        'total_arrears' => 0.0,
        'loans_in_arrears' => 0,
        'par30' => 0.0,
        'par90' => 0.0,
        'collection_rate' => 0.0,
    );
    $par30_principal = 0.0;
    $par90_principal = 0.0;
    $gross_total = 0.0;
    foreach ($loans as $l) {
        $summary['total_principal'] += $l['principal'];
        $summary['total_outstanding'] += $l['outstanding'];
        $summary['total_arrears'] += $l['arrears'];
        $summary['collection_rate'] += $l['collection_rate'];
        $gross_total += $l['principal'];
        if ((int) $l['days_arrears'] > 0) {
            $summary['loans_in_arrears']++;
        }
        if ((int) $l['days_arrears'] > 30) {
            $par30_principal += $l['principal'];
        }
        if ((int) $l['days_arrears'] > 90) {
            $par90_principal += $l['principal'];
        }
    }
    if ($summary['active_loans'] > 0) {
        $summary['collection_rate'] = $summary['collection_rate'] / $summary['active_loans'];
    }
    if ($gross_total > 0) {
        $summary['par30'] = ($par30_principal / $gross_total) * 100.0;
        $summary['par90'] = ($par90_principal / $gross_total) * 100.0;
    }

    $groupBy = function ($items, $key) {
        $map = array();
        foreach ($items as $i) {
            $name = $i[$key] !== '' ? $i[$key] : 'Unknown';
            if (!isset($map[$name])) {
                $map[$name] = array('name' => $name, 'loans_count' => 0, 'outstanding' => 0.0, 'arrears' => 0.0, 'collection_rate' => 0.0, 'par30_glp' => 0.0, 'par90_glp' => 0.0, 'gross_total' => 0.0);
            }
            $map[$name]['loans_count']++;
            $map[$name]['outstanding'] += $i['outstanding'];
            $map[$name]['arrears'] += $i['arrears'];
            $map[$name]['collection_rate'] += $i['collection_rate'];
            $map[$name]['gross_total'] += $i['principal'];
            if ((int) $i['days_arrears'] > 30) {
                $map[$name]['par30_glp'] += $i['principal'];
            }
            if ((int) $i['days_arrears'] > 90) {
                $map[$name]['par90_glp'] += $i['principal'];
            }
        }
        $rows = array();
        foreach ($map as $m) {
            $gross = $m['gross_total'];
            $rows[] = array(
                'name' => $m['name'],
                'loans_count' => (int) $m['loans_count'],
                'outstanding' => (float) $m['outstanding'],
                'arrears' => (float) $m['arrears'],
                'par30' => $gross > 0 ? ($m['par30_glp'] / $gross) * 100.0 : 0.0,
                'par90' => $gross > 0 ? ($m['par90_glp'] / $gross) * 100.0 : 0.0,
                'collection_rate' => $m['loans_count'] > 0 ? ($m['collection_rate'] / $m['loans_count']) : 0.0,
            );
        }
        usort($rows, function ($a, $b) {
            return $b['outstanding'] <=> $a['outstanding'];
        });
        return $rows;
    };

    $branches = array_map(function ($r) {
        return array(
            'branch_name' => $r['name'],
            'loans_count' => $r['loans_count'],
            'outstanding' => $r['outstanding'],
            'arrears' => $r['arrears'],
            'par30' => $r['par30'],
            'par90' => $r['par90'],
            'collection_rate' => $r['collection_rate'],
        );
    }, $groupBy($loans, 'branch_name'));
    $products = array_map(function ($r) {
        return array(
            'product_name' => $r['name'],
            'loans_count' => $r['loans_count'],
            'outstanding_km' => $r['outstanding'] / 1000000.0,
            'arrears_km' => $r['arrears'] / 1000000.0,
            'par_rate' => $r['par30'],
        );
    }, $groupBy($loans, 'product_name'));
    $officers = array_map(function ($r) {
        return array(
            'officer_name' => $r['name'],
            'loans_count' => $r['loans_count'],
            'outstanding' => $r['outstanding'],
            'arrears' => $r['arrears'],
            'par_rate' => $r['par30'],
            'collection_rate' => $r['collection_rate'],
        );
    }, $groupBy($loans, 'officer_name'));

    $trendMap = array();
    foreach ($loans as $l) {
        if (empty($l['disbursement_date'])) {
            continue;
        }
        $ts = strtotime($l['disbursement_date']);
        if ($ts === false) {
            continue;
        }
        $ym = date('Y-m', $ts);
        if (!isset($trendMap[$ym])) {
            $trendMap[$ym] = array('month_year' => $ym, 'loan_count' => 0, 'principal_km' => 0.0);
        }
        $trendMap[$ym]['loan_count']++;
        $trendMap[$ym]['principal_km'] += ($l['principal'] / 1000000.0);
    }
    ksort($trendMap);
    $trend = array_values($trendMap);

    $agingBuckets = array(
        array('bucket' => '1-30d', 'lo' => 1, 'hi' => 30),
        array('bucket' => '31-60d', 'lo' => 31, 'hi' => 60),
        array('bucket' => '61-90d', 'lo' => 61, 'hi' => 90),
        array('bucket' => '91-180d', 'lo' => 91, 'hi' => 180),
        array('bucket' => '>180d', 'lo' => 181, 'hi' => 999999),
    );
    $aging = array();
    foreach ($agingBuckets as $b) {
        $cnt = 0;
        $arr = 0.0;
        $glp = 0.0;
        foreach ($loans as $l) {
            $d = (int) $l['days_arrears'];
            if ($d >= $b['lo'] && $d <= $b['hi']) {
                $cnt++;
                $arr += $l['arrears'];
                $glp += $l['principal'];
            }
        }
        $aging[] = array(
            'bucket' => $b['bucket'],
            'loan_count' => $cnt,
            'arrears_km' => $arr / 1000000.0,
            'outstanding_balance' => $glp,
        );
    }

    $payload = array(
        'success' => true,
        'snapshot' => $summary,
        'branches' => $branches,
        'products' => $products,
        'officers' => $officers,
        'trend' => $trend,
        'aging' => $aging,
        'loans' => $loans,
    );

    $this->output
        ->set_content_type('application/json')
        ->set_output(json_encode($payload, JSON_INVALID_UTF8_SUBSTITUTE));
}

public function caparfilter(){
	$officerid= $this->input->get('id');
    $productid= $this->input->get('productid');
	$this->session->set_userdata('officerid',$officerid);
    $this->session->set_userdata('productid', $productid);

	$this->load->view('admin/header');
	$this->load->view('reports/capar_report');
	$this->load->view('admin/footer');
}
public function summary(){
    $summary_cache_state = $this->get_summary_cache_state(false);
    $summary_payload = $summary_cache_state['payload'];

	$data = array(
		'logs' => get_logs('activity_logger','user_id',$this->session->userdata('user_id')),
		'settings' => get_by_id('settings','settings_id','1'),
        'summary_stats' => $summary_payload['summary_stats'],
        'product_balances' => $summary_payload['product_balances'],
        'summary_source' => $summary_cache_state['source'],
        'summary_needs_refresh' => $summary_cache_state['needs_refresh'],
	);

	$this->load->view('admin/header');
	$this->load->view('reports/summary', $data);
	$this->load->view('admin/footer');
}

public function summary_data()
{
    $summary_cache_state = $this->get_summary_cache_state(true);

    $this->output
        ->set_content_type('application/json')
        ->set_output(json_encode(array(
            'success' => true,
            'source' => $summary_cache_state['source'],
            'needs_refresh' => $summary_cache_state['needs_refresh'],
            'summary_stats' => $summary_cache_state['payload']['summary_stats'],
            'product_balances' => $summary_cache_state['payload']['product_balances'],
        )));
}

private function get_summary_cache_state($refresh_if_needed = true)
{
    $default_payload = $this->normalize_summary_payload(array(
        'summary_stats' => $this->get_default_summary_metrics(),
        'product_balances' => array(),
    ));

    $cached_summary = $this->read_summary_cache();
    if ($cached_summary && $cached_summary['is_fresh']) {
        return array(
            'payload' => $cached_summary['payload'],
            'source' => 'fresh-cache',
            'needs_refresh' => false,
        );
    }

    if (!$refresh_if_needed) {
        return array(
            'payload' => $cached_summary ? $cached_summary['payload'] : $default_payload,
            'source' => $cached_summary ? 'stale-cache' : 'default',
            'needs_refresh' => true,
        );
    }

    $payload = $this->normalize_summary_payload(array(
        'summary_stats' => $this->get_summary_metrics(),
        'product_balances' => $this->get_summary_product_balances(),
    ));

    $this->write_summary_cache($payload);

    return array(
        'payload' => $payload,
        'source' => 'fresh-query',
        'needs_refresh' => false,
    );
}

private function read_summary_cache()
{
    $session_cache = $this->read_summary_session_cache();
    if ($session_cache !== null) {
        return $session_cache;
    }

    $cache_file = $this->get_summary_cache_file();
    if (!file_exists($cache_file)) {
        return null;
    }

    $raw_cache = @file_get_contents($cache_file);
    $cached_payload = json_decode((string) $raw_cache, true);
    if (
        !is_array($cached_payload)
        || !isset($cached_payload['generated_at'], $cached_payload['payload'])
        || !isset($cached_payload['payload']['summary_stats'], $cached_payload['payload']['product_balances'])
    ) {
        return null;
    }

    return $this->normalize_summary_cache_payload($cached_payload);
}

private function write_summary_cache($payload)
{
    $cache_payload = array(
        'generated_at' => time(),
        'payload' => $payload,
    );

    $this->session->set_userdata(self::SUMMARY_SESSION_CACHE_KEY, $cache_payload);
    @file_put_contents($this->get_summary_cache_file(), json_encode($cache_payload));
}

private function get_summary_cache_file()
{
    return APPPATH . 'cache/summary_dashboard_cache.php';
}

private function read_summary_session_cache()
{
    $cached_payload = $this->session->userdata(self::SUMMARY_SESSION_CACHE_KEY);
    if (!is_array($cached_payload)) {
        return null;
    }

    return $this->normalize_summary_cache_payload($cached_payload);
}

private function normalize_summary_cache_payload($cached_payload)
{
    if (
        !is_array($cached_payload)
        || !isset($cached_payload['generated_at'], $cached_payload['payload'])
        || !isset($cached_payload['payload']['summary_stats'], $cached_payload['payload']['product_balances'])
    ) {
        return null;
    }

    return array(
        'payload' => $this->normalize_summary_payload($cached_payload['payload']),
        'is_fresh' => (time() - (int) $cached_payload['generated_at']) < self::SUMMARY_CACHE_TTL,
    );
}

private function normalize_summary_payload($payload)
{
    $payload = is_array($payload) ? $payload : array();
    $summary_stats = isset($payload['summary_stats']) && is_array($payload['summary_stats'])
        ? $payload['summary_stats']
        : array();
    $product_balances = isset($payload['product_balances']) && is_array($payload['product_balances'])
        ? $payload['product_balances']
        : array();

    return array(
        'summary_stats' => array_merge($this->get_default_summary_metrics(), $summary_stats),
        'product_balances' => array_map(array($this, 'normalize_summary_product_balance_item'), $product_balances),
    );
}

private function normalize_summary_product_balance_item($product_balance)
{
    if (is_object($product_balance)) {
        return $product_balance;
    }

    if (!is_array($product_balance)) {
        $product_balance = array();
    }

    return (object) array(
        'loan_product_id' => isset($product_balance['loan_product_id']) ? $product_balance['loan_product_id'] : '',
        'product_name' => isset($product_balance['product_name']) ? $product_balance['product_name'] : '',
        'product_code' => isset($product_balance['product_code']) ? $product_balance['product_code'] : '',
        'outstanding_principal' => isset($product_balance['outstanding_principal']) ? $product_balance['outstanding_principal'] : 0,
    );
}

private function get_default_summary_metrics()
{
    return array(
        'paid_interest' => 0,
        'paid_lc' => 0,
        'paid_af' => 0,
        'outstanding_interest' => 0,
        'outstanding_lc' => 0,
        'outstanding_af' => 0,
        'total_unpaid' => 0,
        'gross_loan_portfolio' => 0,
        'accrued_charges' => 0,
        'total_outstanding_balance' => 0,
        'total_arrears' => 0,
        'one_day_arrears' => 0,
        'three_day_arrears' => 0,
        'week_arrears' => 0,
        'month_arrears' => 0,
        'two_month_arrears' => 0,
        'three_month_arrears' => 0,
        'par_percentage' => 0,
        'payments_today' => 0,
        'payments_week' => 0,
        'payments_month' => 0,
    );
}

private function get_summary_metrics()
{
	date_default_timezone_set('Africa/Blantyre');

    $stats = $this->get_default_summary_metrics();

    $today = date('Y-m-d');
    $tomorrow = date('Y-m-d', strtotime('+1 day'));
    $weekStart = date('Y-m-d', strtotime('last Sunday'));
    $weekEndExclusive = date('Y-m-d', strtotime('next Sunday +1 day'));
    $monthStart = date('Y-m-01');
    $nextMonthStart = date('Y-m-01', strtotime('first day of next month'));

    $unpaidPrincipal = sql_unpaid_principal_expr('ps');
    $accruedChargesCase = sql_portfolio_accrued_charges_case('ps', "'{$today}'");
    $arrearsCase = sql_portfolio_arrears_case('ps', 'CURDATE()');

    $metricsSql = "SELECT
			SUM(CASE WHEN ps.status = 'PAID' THEN COALESCE(ps.interest, 0) ELSE 0 END) AS paid_interest,
			SUM(CASE WHEN ps.status = 'PAID' THEN COALESCE(ps.ploan_cover, 0) ELSE 0 END) AS paid_lc,
			SUM(CASE WHEN ps.status = 'PAID' THEN COALESCE(ps.padmin_fee, 0) ELSE 0 END) AS paid_af,
			SUM(CASE WHEN l.loan_status = 'ACTIVE' THEN {$unpaidPrincipal} ELSE 0 END) AS gross_loan_portfolio,
			SUM(CASE WHEN l.loan_status = 'ACTIVE' THEN {$accruedChargesCase} ELSE 0 END) AS accrued_charges,
			SUM(CASE WHEN l.loan_status = 'ACTIVE' THEN {$unpaidPrincipal} ELSE 0 END)
			    + SUM(CASE WHEN l.loan_status = 'ACTIVE' THEN {$accruedChargesCase} ELSE 0 END) AS total_outstanding_balance,
            SUM(CASE WHEN l.loan_status = 'ACTIVE' THEN {$unpaidPrincipal} ELSE 0 END) AS total_unpaid,
            SUM(CASE WHEN l.loan_status = 'ACTIVE' THEN {$arrearsCase} ELSE 0 END) AS total_arrears,
            SUM(CASE WHEN l.loan_status = 'ACTIVE' AND ps.status IN ('NOT PAID', 'PARTIAL PAID') AND ps.payment_schedule >= DATE_SUB(CURDATE(), INTERVAL 1 DAY) AND ps.payment_schedule < CURDATE() THEN COALESCE(ps.amount, 0) - COALESCE(ps.paid_amount, 0) ELSE 0 END) AS one_day_arrears,
            SUM(CASE WHEN l.loan_status = 'ACTIVE' AND ps.status IN ('NOT PAID', 'PARTIAL PAID') AND ps.payment_schedule >= DATE_SUB(CURDATE(), INTERVAL 3 DAY) AND ps.payment_schedule < CURDATE() THEN COALESCE(ps.amount, 0) - COALESCE(ps.paid_amount, 0) ELSE 0 END) AS three_day_arrears,
            SUM(CASE WHEN l.loan_status = 'ACTIVE' AND ps.status IN ('NOT PAID', 'PARTIAL PAID') AND ps.payment_schedule >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) AND ps.payment_schedule < CURDATE() THEN COALESCE(ps.amount, 0) - COALESCE(ps.paid_amount, 0) ELSE 0 END) AS week_arrears,
            SUM(CASE WHEN l.loan_status = 'ACTIVE' AND ps.status IN ('NOT PAID', 'PARTIAL PAID') AND ps.payment_schedule >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) AND ps.payment_schedule < CURDATE() THEN COALESCE(ps.amount, 0) - COALESCE(ps.paid_amount, 0) ELSE 0 END) AS month_arrears,
            SUM(CASE WHEN l.loan_status = 'ACTIVE' AND ps.status IN ('NOT PAID', 'PARTIAL PAID') AND ps.payment_schedule >= DATE_SUB(CURDATE(), INTERVAL 60 DAY) AND ps.payment_schedule < CURDATE() THEN COALESCE(ps.amount, 0) - COALESCE(ps.paid_amount, 0) ELSE 0 END) AS two_month_arrears,
            SUM(CASE WHEN l.loan_status = 'ACTIVE' AND ps.status IN ('NOT PAID', 'PARTIAL PAID') AND ps.payment_schedule >= DATE_SUB(CURDATE(), INTERVAL 90 DAY) AND ps.payment_schedule < CURDATE() THEN COALESCE(ps.amount, 0) - COALESCE(ps.paid_amount, 0) ELSE 0 END) AS three_month_arrears,
            ROUND(
                (
                    SUM(CASE WHEN l.loan_status = 'ACTIVE' THEN {$arrearsCase} ELSE 0 END)
                    /
                    NULLIF(SUM(CASE WHEN l.loan_status = 'ACTIVE' AND ps.status IN ('NOT PAID', 'PARTIAL PAID') THEN {$unpaidPrincipal} ELSE 0 END), 0)
                ) * 100,
                2
            ) AS par_percentage,
			SUM(CASE WHEN l.loan_status = 'ACTIVE' AND ps.status IN ('NOT PAID', 'PARTIAL PAID') AND ps.payment_schedule >= ? AND ps.payment_schedule < ? THEN COALESCE(ps.amount, 0) ELSE 0 END) AS payments_today,
			SUM(CASE WHEN l.loan_status = 'ACTIVE' AND ps.status IN ('NOT PAID', 'PARTIAL PAID') AND ps.payment_schedule >= ? AND ps.payment_schedule < ? THEN COALESCE(ps.amount, 0) ELSE 0 END) AS payments_week,
			SUM(CASE WHEN l.loan_status = 'ACTIVE' AND ps.status IN ('NOT PAID', 'PARTIAL PAID') AND ps.payment_schedule >= ? AND ps.payment_schedule < ? THEN COALESCE(ps.amount, 0) ELSE 0 END) AS payments_month
		FROM payement_schedules ps
		JOIN loan l ON l.loan_id = ps.loan_id";

    $metrics = $this->run_summary_query($metricsSql, array(
		$today,
		$tomorrow,
		$weekStart,
		$weekEndExclusive,
		$monthStart,
		$nextMonthStart,
	));

    if ($metrics) {
        $stats['paid_interest'] = (float) $metrics->paid_interest;
        $stats['paid_lc'] = (float) $metrics->paid_lc;
        $stats['paid_af'] = (float) $metrics->paid_af;
        $stats['gross_loan_portfolio'] = (float) $metrics->gross_loan_portfolio;
        $stats['accrued_charges'] = (float) $metrics->accrued_charges;
        $stats['total_outstanding_balance'] = (float) $metrics->total_outstanding_balance;
        $stats['outstanding_interest'] = (float) $metrics->accrued_charges;
        $stats['outstanding_lc'] = 0;
        $stats['outstanding_af'] = 0;
        $stats['total_unpaid'] = (float) $metrics->total_unpaid;
        $stats['total_arrears'] = (float) $metrics->total_arrears;
        $stats['one_day_arrears'] = (float) $metrics->one_day_arrears;
        $stats['three_day_arrears'] = (float) $metrics->three_day_arrears;
        $stats['week_arrears'] = (float) $metrics->week_arrears;
        $stats['month_arrears'] = (float) $metrics->month_arrears;
        $stats['two_month_arrears'] = (float) $metrics->two_month_arrears;
        $stats['three_month_arrears'] = (float) $metrics->three_month_arrears;
        $stats['par_percentage'] = (float) $metrics->par_percentage;
        $stats['payments_today'] = (float) $metrics->payments_today;
        $stats['payments_week'] = (float) $metrics->payments_week;
        $stats['payments_month'] = (float) $metrics->payments_month;
	}

	return $stats;
}

private function get_summary_product_balances()
{
    $unpaidPrincipal = sql_unpaid_principal_expr('ps');
	$sql = "SELECT
			lp.loan_product_id,
			lp.product_name,
			lp.product_code,
            COALESCE(product_balances.outstanding_principal, 0) AS outstanding_principal
		FROM loan_products lp
        LEFT JOIN (
            SELECT
                l.loan_product,
                SUM({$unpaidPrincipal}) AS outstanding_principal
            FROM loan l
            JOIN payement_schedules ps ON ps.loan_id = l.loan_id
            WHERE l.loan_status = 'ACTIVE'
            GROUP BY l.loan_product
        ) product_balances ON product_balances.loan_product = lp.loan_product_id
		ORDER BY lp.product_name ASC";

	$query = $this->run_summary_query($sql, array(), true);
	return $query ? $query : array();
}

private function run_summary_query($sql, $binds = array(), $multiple = false)
{
	$query = function_exists('safe_db_query') ? safe_db_query($sql, $binds) : $this->db->query($sql, $binds);
	if (!$query) {
		return $multiple ? array() : null;
	}

	return $multiple ? $query->result() : $query->row();
}
    function crb()

    {

        $page_number = $this->input->get('page') ? (int)$this->input->get('page') : 1;
        $rows_per_page = 3;

        $data = rbm_report($page_number, $rows_per_page);
        $data['page_number'] = $page_number;
        $data['rows_per_page'] = $rows_per_page;
        $data['total_pages'] = ceil($data['total_rows'] / $rows_per_page);
        $from = $this->input->get('from');
        $to = $this->input->get('to');
        $search = $this->input->get('search');


        $this->load->view('admin/header');
        $this->load->view('reports/crb', $data);
        $this->load->view('admin/footer');
    }
    public function par_filter()
    {
        $product = $this->input->post('productid');
        $officer = $this->input->post('officer');
        $branch = $this->input->post('branch');
        $date_from = $this->input->post('date_from');
        $date_to = $this->input->post('date_to');

        // Initialize cURL session
        $ch = curl_init();

        // Set the URL of the endpoint through the shared helper
        $url = report_service_url('generate-report-par-principal-balance');

        // Prepare the data to be sent
        $data = array_merge([
            "report_type" => "PAR reports",
            "user" => $this->session->userdata('Firstname')." ".$this->session->userdata('Lastname'),
            "user_id" => $this->session->userdata('user_id'),
            "officer" => $officer,
            "product" => $product,
            "branch" => $branch,
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
            echo 'Error:' . curl_error($ch);
        } else {
            // Print the response
            $this->toaster->success('Success, Report is being processed you may do other things and come back for progress');
            redirect(site_url('report'));
        }

        // Close the cURL session
        curl_close($ch);
    }

    public function par_filter_portfolio()
    {
        $product = $this->input->post('productid');
        $officer = $this->input->post('officer');
        $branch = $this->input->post('branch');
        $date_from = $this->input->post('date_from');
        $date_to = $this->input->post('date_to');

        // Initialize cURL session
        $ch = curl_init();

        // Set the URL of the endpoint through the shared helper
        $url = report_service_url('generate-report-par-v2');

        // Prepare the data to be sent
        $data = array_merge([
            "report_type" => "Portfolio Loan Book",
            "user" => $this->session->userdata('Firstname')." ".$this->session->userdata('Lastname'),
            "user_id" => $this->session->userdata('user_id'),
            "officer" => $officer,
            "product" => $product,
            "branch" => $branch,
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
            echo 'Error:' . curl_error($ch);
        } else {
            // Print the response
            $this->toaster->success('Success, Portfolio Analysis Report is being processed you may do other things and come back for progress');
            redirect(site_url('report'));
        }

        // Close the cURL session
        curl_close($ch);
    }
    public function export_crb()
    {


// Initialize cURL session
        $ch = curl_init();

// Set the URL of the endpoint through the shared helper
    $url = report_service_url('generate-report-crb');

// Prepare the data to be sent
        $data = [
            "report_type" => "CRB reports",
            "user" => $this->session->userdata('Firstname')." ".$this->session->userdata('Lastname'),
            "user_id" => $this->session->userdata('user_id')
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
            // Print the response
            $this->toaster->success('Success, Report is being processed you may do other things and come back for progress');
            redirect(site_url('report'));

        }

// Close the cURL session
        curl_close($ch);


    }
public function period_analysis(){


	$from = $this->input->get('from');
	$to = $this->input->get('to');
	$search = $this->input->get('search');
	if($search=="filter"){
		$data['total_loan_principal'] = $this->Loan_model->sum_loans($from,$to);
		$data['total_loans'] = $this->Loan_model->count_disbursed_loans($from,$to);
		$data['customers'] = $this->Individual_customers_model->count_active($from,$to);
		$data['customers_male'] = $this->Individual_customers_model->count_active_gender($from,$to,"Male");
		$data['customers_female'] = $this->Individual_customers_model->count_active_gender($from,$to,"Female");
		$data['employees'] = $this->Employees_model->count_active($from,$to);
		$data['groups'] = $this->Groups_model->count_groups($from,$to);
		$this->load->view('admin/header');
		$this->load->view('reports/period_analysis',$data);
		$this->load->view('admin/footer');
	}elseif($search=='pdf'){
		$data['total_loan_principal'] = $this->Loan_model->sum_loans($from,$to);
		$data['total_loans'] = $this->Loan_model->count_disbursed_loans($from,$to);
		$data['customers'] = $this->Individual_customers_model->count_active($from,$to);
		$data['employees'] = $this->Employees_model->count_active($from,$to);
		$data['product'] ='Report';
		$data['from'] = $from;
		$data['to'] = $to;
		$this->load->library('Pdf');
		$html = $this->load->view('reports/analysis_pdf', $data,true);
		$this->pdf->createPDF($html, "Period analysis report as on".date('Y-m-d'), true,'A4','landscape');
	}elseif($search=='excel'){

	}else {
		$data['total_loan_principal'] = $this->Loan_model->sum_loans($from,$to);
		$data['total_loans'] = $this->Loan_model->count_disbursed_loans($from,$to);
		$data['customers'] = $this->Individual_customers_model->count_active($from,$to);
		$data['customers_male'] = $this->Individual_customers_model->count_active_gender($from,$to,"Male");
		$data['customers_female'] = $this->Individual_customers_model->count_active_gender($from,$to,"Female");
		$data['employees'] = $this->Employees_model->count_active($from,$to);
		$data['groups'] = $this->Groups_model->count_groups($from,$to);
		$this->load->view('admin/header');
		$this->load->view('reports/period_analysis', $data);
		$this->load->view('admin/footer');
	}
}
	public function financial_analysis(){


		$from = $this->input->get('from');
		$to = $this->input->get('to');
		$search = $this->input->get('search');
		if($search=="filter"){
			$data['interests_income'] = $this->Payement_schedules_model->sum_interests($from,$to);
			$data['loan_cover'] = $this->Payement_schedules_model->sum_cover($from,$to);
			$data['admin_fee'] = $this->Payement_schedules_model->sum_admin($from,$to);
			$data['admin_income'] = $this->Transactions_model->sum_admin_charges($from,$to);
			$data['late_fee'] = $this->Transactions_model->sum_admin_charges_late($from,$to);
			$data['bad_debits'] = $this->Payement_schedules_model->bad_debits($from,$to);
			$data['commissions'] = 0;
			$data['interest_paid'] = $this->Borrowed_repayements_model->sum_interest_paid($from,$to);
			$data['expenses'] = $this->Transactions_model->sum_expenses($from,$to);
			$this->load->view('admin/header');
			$this->load->view('reports/financial_analysis', $data);
			$this->load->view('admin/footer');
		}elseif($search=='pdf'){
			$data['interests_income'] = $this->Payement_schedules_model->sum_interests($from,$to);
            $data['loan_cover'] = $this->Payement_schedules_model->sum_cover($from,$to);
            $data['admin_fee'] = $this->Payement_schedules_model->sum_admin($from,$to);
			$data['admin_income'] = $this->Transactions_model->sum_admin_charges($from,$to);
			$data['late_fee'] = $this->Transactions_model->sum_admin_charges_late($from,$to);
			$data['bad_debits'] = $this->Payement_schedules_model->bad_debits($from,$to);
			$data['commissions'] = 0;
			$data['interest_paid'] = $this->Borrowed_repayements_model->sum_interest_paid($from,$to);
			$data['expenses'] = $this->Transactions_model->sum_expenses($from,$to);
			$this->load->library('Pdf');
			$html = $this->load->view('reports/financial_analysis_pdf', $data,true);
			$this->pdf->createPDF($html, "Financial analysis report as on".date('Y-m-d'), true,'A4','landscape');
		}elseif($search=='excel'){

		}else {
			$data['interests_income'] = $this->Payement_schedules_model->sum_interests($from,$to);
            $data['loan_cover'] = $this->Payement_schedules_model->sum_cover($from,$to);
            $data['admin_fee'] = $this->Payement_schedules_model->sum_admin($from,$to);
			$data['admin_income'] = $this->Transactions_model->sum_admin_charges($from,$to);
			$data['late_fee'] = $this->Transactions_model->sum_admin_charges_late($from,$to);
			$data['bad_debits'] = $this->Payement_schedules_model->bad_debits($from,$to);
			$data['commissions'] = 0;
			$data['interest_paid'] = $this->Borrowed_repayements_model->sum_interest_paid($from,$to);
			$data['expenses'] = $this->Transactions_model->sum_expenses($from,$to);
			$this->load->view('admin/header');
			$this->load->view('reports/financial_analysis', $data);
			$this->load->view('admin/footer');
		}
	}
public function tray(){
		$this->Loan_model->update_defaulters();
//	$Date = "2010-09-17";
//	echo date('Y-m-d', strtotime($Date. ' + 1 days'));
//	echo date('Y-m-d', strtotime($Date. ' + 2 days'));
}
	public function arrears(){

		$product = $this->input->get('loan');

		$from = $this->input->get('from');
		$to = $this->input->get('to');
		$search = $this->input->get('search');
		$by_date = $this->input->get('by_date');
        $idofficer = $this->input->get('idofficer');
        $supervisor_id = report_supervisor_input_value('get');
		if($search=="filter"){
			$data['loan_data'] = $this->Payement_schedules_model->arrears($product,$from,$to,$by_date,$idofficer, $supervisor_id);
			$data['arrears_total'] = $this->Payement_schedules_model->sum_institutional_arrears($product, $from, $to, $by_date, $idofficer, $supervisor_id);
			$menu_toggle['toggles'] = 40;

			$this->load->view('admin/header', $menu_toggle);
			$this->load->view('reports/arrears',$data);
			$this->load->view('admin/footer');
		}elseif($search=='pdf'){
			$data['loan_data'] = $this->Payement_schedules_model->arrears($product,$from,$to,$by_date,$idofficer, $supervisor_id);

			$data['product'] =($product=="All") ? "All loans" : get_by_id('loan','loan_id',$product)->loan_number;
			$data['from'] = $from;
			$data['to'] = $to;
			$this->load->library('Pdf');
			$html = $this->load->view('reports/arrears_pdf', $data,true);
			$this->pdf->createPDF($html, "Arrears report as on".date('Y-m-d'), true,'A4','landscape');
		}elseif($search=='excel'){
            $data['loan_data'] = $this->Payement_schedules_model->arrears($product,$from,$to,$by_date,$idofficer, $supervisor_id);
$this->excel_arrears($data);
		}else {
			$data['loan_data'] = $this->Payement_schedules_model->arrears($product,$from,$to,$by_date,$idofficer, $supervisor_id);
			$data['arrears_total'] = $this->Payement_schedules_model->sum_institutional_arrears($product, $from, $to, $by_date, $idofficer, $supervisor_id);
			$menu_toggle['toggles'] = 40;

			$this->load->view('admin/header', $menu_toggle);
			$this->load->view('reports/arrears', $data);
			$this->load->view('admin/footer');
		}

	}
	public function to_pay_today()
    {


        $search = $this->input->get('search');
        if ($search == 'pdf') {
            $data['loan_data'] = $this->Payement_schedules_model->payment_today();
            $this->load->library('Pdf');
            $html = $this->load->view('reports/to_pay_today_pdf', $data, true);
            $this->pdf->createPDF($html, "Collection report as on" . date('Y-m-d'), true, 'A4', 'landscape');
        } elseif ($search == 'excel') {




        } else {
            $data['loan_data'] = $this->Payement_schedules_model->payment_today();
            $menu_toggle['toggles'] = 50;
            $data['d_title'] = "Collection today";
            $this->load->view('admin/header', $menu_toggle);
            $this->load->view('reports/to_pay_today', $data);
            $this->load->view('admin/footer');
        }


    }








	public function collection_date(){

		$dates = $this->input->get();
		$search = $this->input->get('search');
	if($search=='filter'){
		$data['loan_data'] = $this->Payement_schedules_model->payment_date($dates['from'],$dates['to'],$dates['user'],$dates['product'],$dates['branch'], report_supervisor_input_value('get'));
		$menu_toggle['toggles'] = 50;
		$data['d_title'] = "Collection by dates";
		$this->load->view('admin/header', $menu_toggle);
		$this->load->view('reports/collection_date', $data);
		$this->load->view('admin/footer');
		}elseif($search=='export excel'){
		$info['ldata']  = $this->Payement_schedules_model->payment_date($dates['from'],$dates['to'],$dates['user'],$dates['product']);
		$this->excel_collection('from'.$dates['from'].'to'.$dates['to'],$info);
		}else {
		$data['loan_data'] = $this->Payement_schedules_model->payment_month();
		$menu_toggle['toggles'] = 50;
		$data['d_title'] = "Collection custom dates";
		$this->load->view('admin/header', $menu_toggle);
		$this->load->view('reports/collection_date', $data);
		$this->load->view('admin/footer');
		}

	}
	public function to_pay_month(){

		$search = $this->input->get('search');
	if($search=='pdf'){
			$data['loan_data'] = $this->Payement_schedules_model->payment_month();
			$this->load->library('Pdf');
			$html = $this->load->view('reports/to_pay_today_pdf', $data,true);
			$this->pdf->createPDF($html, "Monthly Collection report as on".date('Y-m-d'), true,'A4','landscape');
		}elseif($search=='excel'){

        $info['loan_data'] = $this->Payement_schedules_model->payment_month();
        $html_tablemonth = $this->load->view('reports/monthly_payments_excel', $info, true); // Load the view with the data
        $this->output->set_content_type('text/html')->set_output($html_tablemonth);
		}else {
			$data['loan_data'] = $this->Payement_schedules_model->payment_month();
		$menu_toggle['toggles'] = 50;
		$data['d_title'] = "Collection this month";
		$this->load->view('admin/header', $menu_toggle);
			$this->load->view('reports/to_pay_month', $data);
			$this->load->view('admin/footer');
		}

	}
public function to_pay_week(){


		$search = $this->input->get('search');
	if($search=='pdf'){
			$data['loan_data'] = $this->Payement_schedules_model->payment_week();
			$this->load->library('Pdf');
			$html = $this->load->view('reports/to_pay_today_pdf', $data,true);
			$this->pdf->createPDF($html, "Weekly Collection report as report as on".date('Y-m-d'), true,'A4','landscape');
		}elseif($search=='excel'){
		$info['loan_data'] = $this->Payement_schedules_model->payment_week();

        $html_tableweekly = $this->load->view('reports/weekly_payments_excel', $info, true); // Load the view with the data
        $this->output->set_content_type('text/html')->set_output($html_tableweekly);


		}else {
			$data['loan_data'] = $this->Payement_schedules_model->payment_week();
		$menu_toggle['toggles'] = 50;
		$data['d_title'] = "Collection this week";
		$this->load->view('admin/header', $menu_toggle);
			$this->load->view('reports/to_pay_week', $data);
			$this->load->view('admin/footer');
		}

	}

    public function get_weekly_data() {
        // Get the weekly payment data
        $loan_data = $this->Payement_schedules_model->payment_week();

        // Transform the data to include customer names
        $formatted_data = array_map(function($loan) {
            if ($loan->customer_type == 'group') {
                $group = $this->Groups_model->get_by_id($loan->loan_customer);
                $customer_name = $group->group_name . '(' . $group->group_code . ')';
            } else {
                $indi = $this->Individual_customers_model->get_by_id($loan->loan_customer);
                $customer_name = $indi->Firstname . ' ' . $indi->Lastname;
            }

            $loan->customer_name = $customer_name;
            return $loan;
        }, $loan_data);

        // Return JSON response
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($formatted_data));
    }
public function par_report(){


		$search = $this->input->get('search');
//	if($search=='pdf'){
//			$data['loan_data'] = $this->Payement_schedules_model->payment_today();
//			$this->load->library('Pdf');
//			$html = $this->load->view('reports/to_pay_today_pdf', $data,true);
//			$this->pdf->createPDF($html, "Arrears report as on".date('Y-m-d'), true,'A4','landscape');
//		}elseif($search=='excel'){
//
//		}else {
//			$data['loan_data'] = $this->Payement_schedules_model->payment_week();
			$this->load->view('admin/header');
			$this->load->view('reports/par_report_two');
			$this->load->view('admin/footer');
//		}

	}

	public function portfolio_analysis(){


		$search = $this->input->get('search');
			$this->load->view('admin/header');
			$this->load->view('reports/portfolio_analysis');
			$this->load->view('admin/footer');

	}
    public function revenue(){

            $menu_toggle['toggles'] = 50;
			$this->load->view('admin/header',$menu_toggle);
			$this->load->view('reports/revenue');
			$this->load->view('admin/footer');
            
	} public function all_revenue(){

            $menu_toggle['toggles'] = 50;
			$this->load->view('admin/header',$menu_toggle);
			$this->load->view('reports/revenue_uncollected');
			$this->load->view('admin/footer');

	}
public function payments_filter() {
        // Get filter parameters from form submission
        $branch = $this->input->post('branch');
        $transaction_type_id = $this->input->post('transaction_type_id');
        $loan = $this->input->post('loan');
        $product = $this->input->post('product');
        $officer = $this->input->post('officer');
        $from = $this->input->post('from');
        $to = $this->input->post('to');

        // Initialize cURL session
        $ch = curl_init();

        // Set the URL of the Node.js endpoint through the shared helper
        $url = report_service_url('generate-report-transactions');

        // Prepare the data to be sent
        $data = array_merge([
            "report_type" => "PAYMENTS_TRANSACTIONS",
            "user" => $this->session->userdata('Firstname')." ".$this->session->userdata('Lastname'),
            "user_id" => $this->session->userdata('user_id'),
            "branch" => $branch,
            "transaction_type_id" => $transaction_type_id,
            "loan" => $loan,
            "product" => $product,
            "officer" => $officer,
            "from" => $from,
            "to" => $to
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
            echo 'Error:' . curl_error($ch);
        } else {
            // Show success message and redirect
            $this->toaster->success('Success! Report is being processed. You may do other things and come back to check progress.');
            redirect(site_url('report'));
        }

        // Close the cURL session
        curl_close($ch);
    }
	public function payments(){
            $data = array();
	        $this->load->view('admin/header');
			$this->load->view('reports/payments', $data);
			$this->load->view('admin/footer');

	}
    /**
     * Displays the RBM Loan Classification report filter form
     *
     * This function loads the form that allows users to select filters
     * for generating the RBM loan classification report
     */
    public function rbm_classification_report() {
        // Load required data for dropdown options
        $data['branches'] = $this->db->get('branches')->result();
        $data['products'] = $this->db->get('loan_products')->result();
        $data['employees'] = $this->db->get('employees')->result();

        // Load view with the filter form
        $this->load->view('admin/header');
        $this->load->view('reports/rbm_classification_filter', $data);
        $this->load->view('admin/footer');
    }

    /**
     * Processes RBM Loan Classification report request by sending it to Node.js API
     *
     * This function collects report filter parameters and sends them to the
     * Node.js background processing API to generate the report asynchronously
     */
    public function rbm_classification_filter() {
        // Get filter parameters from form submission
        $branch = $this->input->post('branch');
        $product = $this->input->post('product');
        $officer = $this->input->post('officer');

        // Initialize cURL session
        $ch = curl_init();

        // Set the URL of the Node.js endpoint through the shared helper
        $url = report_service_url('generate-report-rbm-classification');

        // Prepare the data to be sent
        $data = array_merge([
            "report_type" => "RBM_CLASSIFICATION",
            "user" => $this->session->userdata('Firstname')." ".$this->session->userdata('Lastname'),
            "user_id" => $this->session->userdata('user_id'),
            "branch" => $branch,
            "product" => $product,
            "officer" => $officer,
            "base_url" => base_url(),
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
            echo 'Error:' . curl_error($ch);
        } else {
            // Show success message and redirect
            $this->toaster->success('Success! RBM Classification Report is being processed. You may do other things and come back to check progress.');
            redirect(site_url('report'));
        }

        // Close the cURL session
        curl_close($ch);
    }
    /**
     * View loan details with RBM classification and risk management options
     *
     * @param int $loan_id The ID of the loan to view
     * @return void
     */
    public function rbm_loan_details($loan_id) {
        // Check if user is logged in and has appropriate permissions
        if (!$this->session->userdata('user_id')) {
            redirect('auth/logout');
        }

        // Load necessary models

        // Get loan details
        $data['loan'] = $this->Loan_model->get_loan_with_classification($loan_id);

        if (!$data['loan']) {
            $this->session->set_flashdata('error', 'Loan not found');
            redirect('reports');
        }

        // Get customer details based on customer type
        if ($data['loan']->customer_type == 'individual') {
            $customer = $this->Individual_customers_model->get_by_id($data['loan']->loan_customer);
            $data['customer_name'] = $customer->Firstname . ' ' . $customer->Lastname;
        } else {
            $group = $this->Groups_model->get_by_id($data['loan']->loan_customer);
            $data['customer_name'] = $group->group_name . ' (' . $group->group_code . ')';
        }

        // Get risk officer (if assigned)
        $data['risk_officer'] = null;
        if ($data['loan']->risk_officer_id) {
            $risk_officer = $this->Employees_model->get_by_id($data['loan']->risk_officer_id);
            $data['risk_officer'] = $risk_officer;
        }

        // Get all employees for risk officer assignment dropdown
        $data['employees'] = $this->Employees_model->get_all();

        // Get collaterals for this loan
        $data['collaterals'] = $this->Collateral_model->get_by_loan_id($loan_id);

        // Calculate days in arrears for RBM classification
        $data['days_in_arrears'] = $this->Loan_model->get_days_in_arrears($loan_id);

        // Determine RBM classification based on days in arrears
        $data['rbm_classification'] = $this->determine_rbm_classification($data['days_in_arrears']);

        // Load views
        $this->load->view('admin/header');
        $this->load->view('reports/rbm_loan_details', $data);
        $this->load->view('admin/footer');
    }

    /**
     * Process risk officer assignment and comments for a loan
     *
     * @return void
     */
    public function assign_risk_officer() {
        // Check if user is logged in and has appropriate permissions
       

        // Get form data
        $loan_id = $this->input->post('loan_id');
       echo $risk_officer_id = $this->input->post('risk_officer_id');
        $risk_comments = $this->input->post('risk_comments');
        $write_off_recommendation = $this->input->post('write_off_recommendation') ? 1 : 0;

        // Update loan with risk information
       
        $result = $this->Loan_model->update_risk_info($loan_id, array(
            'risk_officer_id' => $risk_officer_id,
            'risk_comments' => $risk_comments,
            'write_off_recommendation' => $write_off_recommendation
        ));

        if ($result) {
            $this->session->set_flashdata('success', 'Risk officer and comments updated successfully');
        } else {
            $this->session->set_flashdata('error', 'Failed to update risk information');
        }

        // Redirect back to loan details page
        redirect('reports/rbm_loan_details/' . $loan_id);
    }

    /**
     * Helper function to determine RBM classification based on days in arrears
     *
     * @param int $days_in_arrears Number of days in arrears
     * @return string RBM classification
     */
    private function determine_rbm_classification($days_in_arrears) {
        return determine_rbm_classification($days_in_arrears);
    }

    /**
     * View all loans with risk management information
     *
     * @return void
     */
    public function rbm_risk_management() {
        // Check if user is logged in and has appropriate permissions


        // Load necessary models


        // Get all loans with risk information
        $data['loans'] = $this->Loan_model->get_all_loans_with_risk_info();

        // Load views
        $this->load->view('admin/header');
        $this->load->view('reports/rbm_risk_management', $data);
        $this->load->view('admin/footer');
    }
    /**
     * Risk Recovery Report - Shows loans that are at risk or recommended for write-off
     *
     * @return void
     */
    public function risk_recovery_report() {
        // Check if user is logged in and has appropriate permissions


        // Get filter values
        $risk_category = $this->input->get('risk_category');
        $officer = $this->input->get('officer');
        $branch = $this->input->get('branch');
        $writeoff = $this->input->get('writeoff');

        // Get branches, employees for filter dropdowns
        $data['branches'] = $this->db->get('branches')->result();
        $data['employees'] = $this->Employees_model->get_all();

        // Get risk recovery loans
        $data['loans'] = $this->Loan_model->get_risk_recovery_loans($risk_category, $officer, $branch, $writeoff);

        // Prepare summary data
        $data['summary'] = [
            'total_count' => count($data['loans']),
            'total_principal' => array_sum(array_column($data['loans'], 'principal_balance')),
            'total_interest' => array_sum(array_column($data['loans'], 'interest_balance')),
            'total_writeoffs' => count(array_filter($data['loans'], function($loan) {
                return $loan->write_off_recommendation == 1;
            }))
        ];

        // Load views
        $this->load->view('admin/header');
        $this->load->view('reports/risk_recovery_report', $data);
        $this->load->view('admin/footer');
    }
    /**
     * Get collaterals for a loan (AJAX)
     *
     * @return void
     */
    public function get_collaterals() {

        $loan_id = $this->input->get('loan_id');

        if (!$loan_id) {
            $response = [
                'success' => false,
                'message' => 'Loan ID is required'
            ];
            echo json_encode($response);
            return;
        }



        // Get collaterals
        $collaterals = $this->Collateral_model->get_by_loan_id($loan_id);

        $response = [
            'success' => true,
            'collaterals' => $collaterals
        ];

        echo json_encode($response);
    }


	function try_export(){
		$this->load->view('export');

	}
	public function excel_collection($name,$result)
	{
  
		$this->load->helper('exportexcel');
		$namaFile = $name."collection_report.xls";
		$judul = "Collection_report";
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
		xlsWriteLabel($tablehead, $kolomhead++, "Loan Customer");
		xlsWriteLabel($tablehead, $kolomhead++, "Loan Number");

		xlsWriteLabel($tablehead, $kolomhead++, "Loan product");
		xlsWriteLabel($tablehead, $kolomhead++, "Payment Schedule Date");
		xlsWriteLabel($tablehead, $kolomhead++, "Amount to Collect");
		xlsWriteLabel($tablehead, $kolomhead++, "Payment number");
		xlsWriteLabel($tablehead, $kolomhead++, "Officer");
	
		xlsWriteLabel($tablehead, $kolomhead++, "Payment Status");
	

		foreach ($result['ldata'] as $data) {
			if($data->customer_type=='group'){
				$group = $this->Groups_model->get_by_id($data->loan_customer);

				$customer_name = $group->group_name.'('.$group->group_code.')';
				
			}elseif($data->customer_type=='individual'){
				$indi = $this->Individual_customers_model->get_by_id($data->loan_customer);
				$customer_name = $indi->Firstname.' '.$indi->Lastname;
				
			}
			$kolombody = 0;

			//ubah xlsWriteLabel menjadi xlsWriteNumber untuk kolom numeric
			xlsWriteNumber($tablebody, $kolombody++, $nourut);
			xlsWriteLabel($tablebody, $kolombody++, $customer_name);
			xlsWriteLabel($tablebody, $kolombody++, $data->loan_number);
			xlsWriteLabel($tablebody, $kolombody++, $data->product_name);
			xlsWriteLabel($tablebody, $kolombody++, $data->payment_schedule);
			xlsWriteLabel($tablebody, $kolombody++, $data->amount);
			xlsWriteLabel($tablebody, $kolombody++, $data->payment_number);
			
			xlsWriteLabel($tablebody, $kolombody++, $data->efname.' '.$data->elname);
			xlsWriteLabel($tablebody, $kolombody++, $data->status);


			$tablebody++;
			$nourut++;
		}

		xlsEOF();
		exit();
	}
    public function excel_arrears($result)
    {

        $this->load->helper('exportexcel');
        $namaFile = "arrears_report.xls";
        $judul = "arrears_report";
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
        xlsWriteLabel($tablehead, $kolomhead++, "Loan Customer");
        xlsWriteLabel($tablehead, $kolomhead++, "Loan Number");

        xlsWriteLabel($tablehead, $kolomhead++, "Loan product");
        xlsWriteLabel($tablehead, $kolomhead++, "Payment Due Date");
        xlsWriteLabel($tablehead, $kolomhead++, "Amount Due");
        xlsWriteLabel($tablehead, $kolomhead++, "Payment number");
        xlsWriteLabel($tablehead, $kolomhead++, "Officer");




        foreach ($result['loan_data'] as $data) {
            if($data->customer_type=='group'){
                $group = $this->Groups_model->get_by_id($data->loan_customer);

                $customer_name = $group->group_name.'('.$group->group_code.')';

            }elseif($data->customer_type=='individual'){
                $indi = $this->Individual_customers_model->get_by_id($data->loan_customer);
                $customer_name = $indi->Firstname.' '.$indi->Lastname;

            }
            $kolombody = 0;

            //ubah xlsWriteLabel menjadi xlsWriteNumber untuk kolom numeric
            xlsWriteNumber($tablebody, $kolombody++, $nourut);
            xlsWriteLabel($tablebody, $kolombody++, $customer_name);
            xlsWriteLabel($tablebody, $kolombody++, $data->loan_number);
            xlsWriteLabel($tablebody, $kolombody++, $data->product_name);
            xlsWriteLabel($tablebody, $kolombody++, $data->payment_schedule);
            $amount_due = isset($data->amount_due)
                ? (float) $data->amount_due
                : ((float) $data->amount - (float) $data->paid_amount);
            xlsWriteNumber($tablebody, $kolombody++, $amount_due);
            xlsWriteLabel($tablebody, $kolombody++, $data->payment_number);

            xlsWriteLabel($tablebody, $kolombody++, $data->efname.' '.$data->elname);



            $tablebody++;
            $nourut++;
        }

        xlsEOF();
        exit();
    }


    // In your Reports controller (application/controllers/Reports.php)
    public function export_excel() {
        // Fetch data from the model
        $results = rbm_report(1, 10);

        // Create the HTML table
        $html = '<table class="tableCss crb">';
        $html .= '<thead><tr>';
        $html .= '<th>#</th>';
        $html .= '<th>Salutation</th>';
        // Add all other headers here
        $html .= '</tr></thead>';
        $html .= '<tbody>';

        $n = 0;
        foreach ($results as $r) {
            $html .= '<tr>';
            $html .= '<td>' . ++$n . '</td>';
            $html .= '<td>' . $r->Salutation . '</td>';
            // Add other data fields similarly
            $html .= '</tr>';
        }

        $html .= '</tbody></table>';

        // Save HTML content to a temporary file
        $tempFile = tempnam(sys_get_temp_dir(), 'export');
        file_put_contents($tempFile, $html);

        // Return JSON response
        $response = array(
            'status' => 'success',
            'file' => $tempFile, // Store temporary file path for later download
        );
        echo json_encode($response);
    }

    public function download_excel() {
        $tempFilePath = $this->input->get('file'); // Retrieve the temporary file path

        if ($tempFilePath && file_exists($tempFilePath)) {
            // Set headers to force download
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="exported_file.xlsx"');
            header('Content-Length: ' . filesize($tempFilePath));
            readfile($tempFilePath);

            // Delete temporary file after download
            unlink($tempFilePath);
            exit;
        } else {
            // Handle file not found scenario
            die('Error: The file does not exist.');
        }
    }
    public function excel_payments($result)
    {

        $this->load->helper('exportexcel');
        $namaFile = "payments_report.xls";
        $judul = "payments_report";
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
        xlsWriteLabel($tablehead, $kolomhead++, "Transaction ref");
        xlsWriteLabel($tablehead, $kolomhead++, "Loan Number");
        xlsWriteLabel($tablehead, $kolomhead++, "Transactions type");
        xlsWriteLabel($tablehead, $kolomhead++, "Payment number(when applicable)");
        xlsWriteLabel($tablehead, $kolomhead++, "Amount");
        xlsWriteLabel($tablehead, $kolomhead++, "Proof");
        xlsWriteLabel($tablehead, $kolomhead++, "Payment Date");
        xlsWriteLabel($tablehead, $kolomhead++, "Officer");




        foreach ($result['payments_data'] as $data) {

            $kolombody = 0;

            //ubah xlsWriteLabel menjadi xlsWriteNumber untuk kolom numeric
            xlsWriteNumber($tablebody, $kolombody++, $nourut);
            xlsWriteLabel($tablebody, $kolombody++, $data->ref);
            xlsWriteLabel($tablebody, $kolombody++, $data->loan_number);
            xlsWriteLabel($tablebody, $kolombody++, $data->name);
            xlsWriteLabel($tablebody, $kolombody++, $data->payment_number);
            xlsWriteLabel($tablebody, $kolombody++, $data->amount);
            xlsWriteLabel($tablebody, $kolombody++, $data->ref);
            xlsWriteLabel($tablebody, $kolombody++, $data->date_stamp);
            xlsWriteLabel($tablebody, $kolombody++, $data->Firstname.' '.$data->Lastname);

            $tablebody++;
            $nourut++;
        }

        xlsEOF();
        exit();
    }
	function export_it()
	{

		$filename = $this->input->get('filename');
		$search = $this->input->get('search');


		if($search == 'filter'){
			$data['toexport'] = $this->Global_config_model->get_all() ;
			$this->load->view('export', $data);
		}elseif($search=='export'){
			$namaFile = "agent_cro_report.xls";

			$tablehead = 0;
			$tablebody = 1;
			$nourut = 1;
			//penulisan header
			xlsHeaders($namaFile);

			xlsBOF();

			$kolomhead = 0;
			xlsWriteLabel($tablehead, $kolomhead++, "No");
			xlsWriteLabel($tablehead, $kolomhead++, "Repayment Automatic");
			xlsWriteLabel($tablehead, $kolomhead++, "cron path");
			$toe = $this->Global_config_model->get_all() ;
			foreach ($toe as $data) {
				$kolombody = 0;

				//ubah xlsWriteLabel menjadi xlsWriteNumber untuk kolom numeric
				xlsWriteNumber($tablebody, $kolombody++, $nourut);
				xlsWriteLabel($tablebody, $kolombody++, $data->repayment_automatic);
				xlsWriteLabel($tablebody, $kolombody++, $data->cron_path);


				$tablebody++;
				$nourut++;
			}

			xlsEOF();
			exit();
		}else{
			$data['toexport'] = array();
			$this->load->view('export', $data);
		}



	}

}
