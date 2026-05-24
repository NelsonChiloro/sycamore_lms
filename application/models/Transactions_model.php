<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Transactions_model extends CI_Model
{

    public $table = 'transactions';
    public $id = 'transaction_id';
    public $order = 'DESC';
    private $table_fields = null;

    function __construct()
    {
        parent::__construct();
    }

    // get all
    function get_all()
    {
        $this->db->order_by($this->id, $this->order);
        return $this->db->get($this->table)->result();
    }

    // get data by id
    function get_by_id($id)
    {
        $this->db->where($this->id, $id);
        return $this->db->get($this->table)->row();
    }
    function get_by_loan($id)
    {
        $this->db->where('loan_id', $id);
        return $this->db->get($this->table)->row();
    }
       function search($id)
    {
        $this->db->where('transactions.loan_id', $id);
        $this->db->where('transaction_type !=','4');
        $this->db->join('employees','employees.id=transactions.added_by');
        $this->db->join('loan', 'loan.loan_id=transactions.loan_id');
        return $this->db->get($this->table)->result();
    }
    function search2($id)
    {
<<<<<<< HEAD
        return $this->search2_filtered($id);
    }

    /**
     * Account statement rows (transaction ledger) for a loan account.
     */
    function search2_filtered($loan_id, $from = null, $to = null)
    {
        $r = get_by_id('loan', 'loan_id', $loan_id);
        if (!$r || empty($r->loan_number)) {
            return array();
        }

        $this->db->order_by('transaction.system_time', $this->order);
        $this->db->where('transaction.account_number', $r->loan_number);

        $from = trim((string) $from);
        $to = trim((string) $to);
        if ($from !== '') {
            $this->db->where('DATE(transaction.system_time) >=', date('Y-m-d', strtotime($from)));
        }
        if ($to !== '') {
            $this->db->where('DATE(transaction.system_time) <=', date('Y-m-d', strtotime($to)));
        }

=======
        $r = get_by_id('loan','loan_id', $id);
        $this->db->order_by('system_time', $this->order);
        $this->db->where('transaction.account_number', $r->loan_number);

>>>>>>> 808554ff5caea0db9a21de0721b02d4d60db333d
        return $this->db->get('transaction')->result();
    }
	public function sum_admin_charges($from,$to){
		$this->db->select('SUM(amount) as amount');
		$this->db->from('transactions')->where('transaction_type','1');

		// $this->db->join('lend_payments','lend_payments.borrower_loan_id=lend_borrower_loans.id');

		if($from !="" && $to !=""){
			$this->db->where('date_stamp BETWEEN "'. date('Y-m-d', strtotime($from)). '" and "'. date('Y-m-d', strtotime($to)).'"');

		}

		return $this->db->get()->row();
	}
	public function get_expenses($from,$to)
	{

		$this->db->select("*")
			->from($this->table)
			->join('loan', 'loan.loan_id=transactions.loan_id')
			->join('transaction_type', 'transaction_type.transaction_type_id=transactions.transaction_type')
			->join('employees', 'employees.id = transactions.added_by');
		$this->db->where('transactions.transaction_type', '4');

		if ($from != "" && $to != "") {
			$this->db->where('date_stamp BETWEEN "' . date('Y-m-d', strtotime($from)) . '" and "' . date('Y-m-d', strtotime($to)) . '"');

		}
		$re = $this->db->get()->result();
		return $re;
	}
	public function sum_admin_charges_late($from,$to){
		$this->db->select('SUM(amount) as amount');
		$this->db->from('transactions')->where('transaction_type','2');

		// $this->db->join('lend_payments','lend_payments.borrower_loan_id=lend_borrower_loans.id');

		if($from !="" && $to !=""){
			$this->db->where('date_stamp BETWEEN "'. date('Y-m-d', strtotime($from)). '" and "'. date('Y-m-d', strtotime($to)).'"');

		}

		return $this->db->get()->row();
	}public function sum_expenses($from,$to){
		$this->db->select('SUM(amount) as amount');
		$this->db->from('transactions')->where('transaction_type','4');

		// $this->db->join('lend_payments','lend_payments.borrower_loan_id=lend_borrower_loans.id');

		if($from !="" && $to !=""){
			$this->db->where('date_stamp BETWEEN "'. date('Y-m-d', strtotime($from)). '" and "'. date('Y-m-d', strtotime($to)).'"');

		}

		return $this->db->get()->row();
	}
    // get total rows
    function total_rows($q = NULL) {
        $this->db->like('transaction_id', $q);
	$this->db->or_like('ref', $q);
	$this->db->or_like('loan_id', $q);
	$this->db->or_like('amount', $q);
	$this->db->or_like('payment_number', $q);
	$this->db->or_like('date_stamp', $q);
	$this->db->from($this->table);
        return $this->db->count_all_results();
    }

    // get data with limit and search
    function get_limit_data($limit, $start = 0, $q = NULL) {
        $this->db->order_by($this->id, $this->order);
        $this->db->like('transaction_id', $q);
	$this->db->or_like('ref', $q);
	$this->db->or_like('loan_id', $q);
	$this->db->or_like('amount', $q);
	$this->db->or_like('payment_number', $q);
	$this->db->or_like('date_stamp', $q);
	$this->db->limit($limit, $start);
        return $this->db->get($this->table)->result();
    }

    // insert data
    function insert($data)
    {
        $fields = $this->get_table_fields();

        $fallback_ref = '';
        if (isset($data['payment_reference']) && trim((string)$data['payment_reference']) !== '') {
            $fallback_ref = trim((string)$data['payment_reference']);
        } elseif (isset($data['reference']) && trim((string)$data['reference']) !== '') {
            $fallback_ref = trim((string)$data['reference']);
        } elseif (isset($data['ref']) && trim((string)$data['ref']) !== '') {
            $fallback_ref = trim((string)$data['ref']);
        } else {
            $fallback_ref = 'SYS-' . uniqid('', true);
        }

        // Backward-compatible mapping for environments that use legacy columns.
        if (isset($data['payment_reference']) && !in_array('payment_reference', $fields) && in_array('reference', $fields)) {
            $data['reference'] = $data['payment_reference'];
        }

        if (isset($data['payment_type']) && !in_array('payment_type', $fields) && in_array('method', $fields)) {
            $type = strtolower(trim((string)$data['payment_type']));
            $data['method'] = ($type === 'bank') ? 1 : 0;
        }

        if (in_array('reference', $fields) && (!isset($data['reference']) || trim((string)$data['reference']) === '')) {
            $data['reference'] = $fallback_ref;
        }

        if (!isset($data['method']) && in_array('method', $fields)) {
            $data['method'] = 0;
        }

        if (in_array('payment_reference', $fields) && (!isset($data['payment_reference']) || trim((string)$data['payment_reference']) === '')) {
            $data['payment_reference'] = $fallback_ref;
        }

        $filtered = array();
        foreach ($data as $key => $value) {
            if (in_array($key, $fields)) {
                $filtered[$key] = $value;
            }
        }

        $this->db->insert($this->table, $filtered);
    }

    /**
     * Check whether a payment reference number is already used.
     * Returns the existing transaction row if found, or NULL if the reference is free.
     */
    function check_duplicate_reference($payment_reference)
    {
        $fields = $this->get_table_fields();

        if (in_array('payment_reference', $fields)) {
            $this->db->where('payment_reference', $payment_reference);
            return $this->db->get($this->table)->row();
        }

        if (in_array('reference', $fields)) {
            $this->db->where('reference', $payment_reference);
            return $this->db->get($this->table)->row();
        }

        return null;
    }

    public function get_tracked_transactions($filters = array())
    {
        $fields = $this->get_table_fields();
        $reference_column = in_array('payment_reference', $fields) ? 'payment_reference' : (in_array('reference', $fields) ? 'reference' : '');
        $payment_type_column = in_array('payment_type', $fields) ? 'payment_type' : (in_array('method', $fields) ? 'method' : '');

        if ($reference_column !== '') {
            $payment_reference_select = "CASE
                WHEN transactions." . $reference_column . " IS NOT NULL AND TRIM(transactions." . $reference_column . ") <> '' THEN transactions." . $reference_column . "
                ELSE ''
            END AS payment_reference_value";
        } else {
            $payment_reference_select = '"" AS payment_reference_value';
        }

        if ($payment_type_column === 'method') {
            $payment_type_select = "CASE
                WHEN transactions.method = 1 THEN 'Bank'
                WHEN transactions.method = 0 THEN ''
                WHEN transactions.method IS NULL THEN ''
                ELSE ''
            END AS payment_type_value";
        } elseif ($payment_type_column !== '') {
            $payment_type_select = "CASE
                WHEN transactions." . $payment_type_column . " IS NULL THEN ''
                WHEN TRIM(transactions." . $payment_type_column . ") = '' THEN ''
                ELSE CONCAT(UCASE(LEFT(TRIM(transactions." . $payment_type_column . "), 1)), LCASE(SUBSTRING(TRIM(transactions." . $payment_type_column . "), 2)))
            END AS payment_type_value";
        } else {
            $payment_type_select = '"" AS payment_type_value';
        }

        $this->db->select("transactions.transaction_id,
            transactions.transaction_type,
            transactions.ref,
            transactions.loan_id,
            transactions.amount,
            transactions.payment_number,
            CASE
                WHEN transactions.date_stamp IS NULL OR transactions.date_stamp = '0000-00-00 00:00:00' OR transactions.date_stamp = '0000-00-00' THEN NULL
                ELSE transactions.date_stamp
            END AS display_date_stamp,
            IFNULL(transaction_type.name, CONCAT('Type ', transactions.transaction_type)) AS transaction_type_name,
            " . $payment_reference_select . ",
            " . $payment_type_select . ",
            COALESCE(
                loan.loan_number,
                (
                    SELECT l2.loan_number
                    FROM loan l2
                    WHERE l2.loan_customer = transactions.loan_id OR l2.group_id = transactions.loan_id
                    ORDER BY l2.loan_id DESC
                    LIMIT 1
                ),
                CAST(transactions.loan_id AS CHAR)
            ) AS loan_number,
            CASE
                WHEN groups.group_id IS NOT NULL THEN CONCAT(groups.group_name, ' (', groups.group_code, ')')
                WHEN individual_customers.id IS NOT NULL THEN CONCAT(individual_customers.Firstname, ' ', individual_customers.Lastname)
                WHEN legacy_individual_customers.id IS NOT NULL THEN CONCAT(legacy_individual_customers.Firstname, ' ', legacy_individual_customers.Lastname)
                WHEN legacy_groups.group_id IS NOT NULL THEN CONCAT(legacy_groups.group_name, ' (', legacy_groups.group_code, ')')
                ELSE ''
            END AS customer_name,
            CONCAT_WS(' ', employees.Firstname, employees.Lastname) AS added_by_name");
        $this->db->from($this->table);
        $this->db->join('loan', 'loan.loan_id = transactions.loan_id', 'left');
        $this->db->join('transaction_type', 'transaction_type.transaction_type_id = transactions.transaction_type', 'left');
        $this->db->join('groups', 'groups.group_id = loan.group_id', 'left');
        $this->db->join('individual_customers', 'individual_customers.id = loan.loan_customer', 'left');
        $this->db->join('groups legacy_groups', 'legacy_groups.group_id = transactions.loan_id', 'left');
        $this->db->join('individual_customers legacy_individual_customers', 'legacy_individual_customers.id = transactions.loan_id', 'left');
        $this->db->join('employees', 'employees.id = transactions.added_by', 'left');

        if (!empty($filters['from'])) {
            $this->db->where('DATE(transactions.date_stamp) >=', date('Y-m-d', strtotime($filters['from'])));
        }

        if (!empty($filters['to'])) {
            $this->db->where('DATE(transactions.date_stamp) <=', date('Y-m-d', strtotime($filters['to'])));
        }

        if (!empty($filters['loan_number'])) {
            $this->db->like('loan.loan_number', trim($filters['loan_number']));
        }

        if (!empty($filters['transaction_type'])) {
            $this->db->where('transactions.transaction_type', $filters['transaction_type']);
        }

        if (!empty($filters['customer_name'])) {
            $customer_name = trim($filters['customer_name']);
            $this->db->group_start();
            $this->db->like('groups.group_name', $customer_name);
            $this->db->or_like('groups.group_code', $customer_name);
            $this->db->or_like('individual_customers.Firstname', $customer_name);
            $this->db->or_like('individual_customers.Lastname', $customer_name);
            $this->db->or_like('legacy_groups.group_name', $customer_name);
            $this->db->or_like('legacy_groups.group_code', $customer_name);
            $this->db->or_like('legacy_individual_customers.Firstname', $customer_name);
            $this->db->or_like('legacy_individual_customers.Lastname', $customer_name);
            $this->db->or_like("CONCAT(individual_customers.Firstname, ' ', individual_customers.Lastname)", $customer_name, 'both', false);
            $this->db->or_like("CONCAT(legacy_individual_customers.Firstname, ' ', legacy_individual_customers.Lastname)", $customer_name, 'both', false);
            $this->db->group_end();
        }

        $this->db->order_by('transactions.date_stamp', 'DESC');

        return $this->db->get()->result();
    }

    private function get_table_fields()
    {
        if ($this->table_fields === null) {
            $this->table_fields = $this->db->list_fields($this->table);
        }
        return $this->table_fields;
    }

    // update data
    function update($id, $data)
    {
        $this->db->where($this->id, $id);
        $this->db->update($this->table, $data);
    }

    // delete data
    function delete($id)
    {
        $this->db->where($this->id, $id);
        $this->db->delete($this->table);
    }
    function  report($branch,$type,$loan,$product,$officer,$from,$to){

    	$this->db->select("*")
			->from($this->table)
			->join('loan','loan.loan_id=transactions.loan_id')
			->join('transaction_type','transaction_type.transaction_type_id=transactions.transaction_type')
		->join('employees','employees.id = loan.loan_added_by')
		->join('loan_products','loan_products.loan_product_id = loan.loan_product')
		->join('branches','branches.id = loan.branch');
        $this->db->order_by($this->id, $this->order);
    	if($type !=""){
    		$this->db->where('transactions.transaction_type',$type);
						 }
    	if($product !=""){
    		$this->db->where('loan.loan_product',$product);
		}
        if($officer !=""){
    		$this->db->where('loan.loan_added_by',$officer);
		}
        if($branch !=""){
    		$this->db->where('loan.branch',$branch);
		}
		if($from !="" && $to !=""){
			$this->db->where('date_stamp BETWEEN "'. date('Y-m-d', strtotime($from)). '" and "'. date('Y-m-d', strtotime($to)).'"');

		}
		$re= $this->db->get()->result();
    	return $re;
	}


}


