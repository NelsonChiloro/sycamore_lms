<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class Loan_model extends CI_Model
{

	public $table = 'loan';
	public $table_d = array('loan', 'transactions', 'payement_schedules');
	public $id = 'loan_id';
	public $order = 'DESC';

	function __construct()
	{
		parent::__construct();
	}

	/**
	 * Open loan statuses used when enforcing one loan per customer per product.
	 */
	public function get_open_loan_statuses()
	{
		return array('INITIATED', 'RECOMMENDED', 'APPROVED', 'ACTIVE');
	}

	/**
	 * Returns an open loan for the customer and product, if any.
	 *
	 * @param string|int $loan_customer
	 * @param string $customer_type
	 * @param int|string $product_id loan_products.loan_product_id
	 * @param array|null $statuses
	 * @return object|null
	 */
	public function get_customer_open_loan_for_product($loan_customer, $customer_type, $product_id, $statuses = null)
	{
		if ($statuses === null) {
			$statuses = $this->get_open_loan_statuses();
		}

		if (empty($loan_customer) || empty($product_id) || empty($statuses)) {
			return null;
		}

		$this->db->select('*')
			->from($this->table)
			->where('loan_customer', $loan_customer)
			->where('loan_product', (int)$product_id)
			->where_in('loan_status', $statuses);

		if ($customer_type !== null && $customer_type !== '') {
			$this->db->where('customer_type', $customer_type);
		}

		return $this->db->order_by($this->id, $this->order)->limit(1)->get()->row();
	}

	/**
	 * Ensure schedule date is unique for this loan (prevents duplicate months - Issue 2)
	 */
	private function _ensure_schedule_date_unique($loan_id, $date) {
		$exists = $this->db->where('loan_id', $loan_id)->where('payment_schedule', $date)->count_all_results('payement_schedules');
		while ($exists > 0) {
			$date = date('Y-m-d', strtotime('+1 day', strtotime($date)));
			if (date('N', strtotime($date)) == 7) $date = date('Y-m-d', strtotime('+1 day', strtotime($date)));
			$exists = $this->db->where('loan_id', $loan_id)->where('payment_schedule', $date)->count_all_results('payement_schedules');
		}
		return $date;
	}

    private function normalize_frequency_label($frequency)
    {
        $raw = trim((string)$frequency);
        $normalized = strtolower(preg_replace('/\s+/', ' ', $raw));

        switch ($normalized) {
            case 'bi weekly':
            case 'bi-weekly':
            case 'biweekly':
                return 'Bi weekly';
            case 'weekly':
                return 'Weekly';
            case 'monthly':
                return 'Monthly';
            default:
                return $raw;
        }
    }

    private function resolve_effective_frequency_for_existing_loan($loan_id, $fallbackFrequency)
    {
        $fallback = $this->normalize_frequency_label($fallbackFrequency);
        $existing = $this->db->select('period_type')->where('loan_id', $loan_id)->get($this->table)->row();
        if (!$existing || empty($existing->period_type)) {
            return $fallback;
        }

        $stored = $this->normalize_frequency_label($existing->period_type);
        if (in_array($stored, array('Monthly', 'Weekly', 'Bi weekly'), true)) {
            return $stored;
        }

        return $fallback;
    }

    /**
     * Use frequency from the selected loan_products row (source of truth).
     */
    private function resolve_frequency_for_loan_edit($loan_id, $product_id, $product_frequency, $period_type_override = null)
    {
        $from_product = $this->normalize_frequency_label($product_frequency);
        if ($from_product !== '') {
            return $from_product;
        }

        if ($period_type_override !== null && $period_type_override !== '') {
            return $this->normalize_frequency_label($period_type_override);
        }

        return $this->resolve_effective_frequency_for_existing_loan($loan_id, $product_frequency);
    }

    private function normalize_numeric_value($value)
    {
        if (is_int($value) || is_float($value)) {
            return (float)$value;
        }

        if ($value === null) {
            return 0.0;
        }

        $normalized = trim((string)$value);
        if ($normalized === '') {
            return 0.0;
        }

        // Handle common formatted money inputs like "1,200.50" or "MWK 1,200.50".
        $normalized = str_replace(array(',', ' '), '', $normalized);
        $normalized = preg_replace('/[^0-9.\-]/', '', $normalized);

        if ($normalized === '' || $normalized === '-' || $normalized === '.' || $normalized === '-.') {
            return 0.0;
        }

        return (float)$normalized;
    }

    /**
     * Amortized installment; avoids division by zero when period or total rate is zero.
     */
    private function calculate_amortized_installment($amount, $months, $total_deduction)
    {
        $amount = (float) $amount;
        $months = (int) $months;
        $total_deduction = (float) $total_deduction;

        if ($months <= 0 || $amount <= 0) {
            return 0.0;
        }

        if ($total_deduction <= 0) {
            return $amount / $months;
        }

        $rate = $total_deduction / 12;
        $denominator = pow(1 + $rate, $months) - 1;
        if ($denominator <= 0) {
            return $amount / $months;
        }

        return $amount * $rate * pow(1 + $rate, $months) / $denominator;
    }

    private function normalize_date_value($value)
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        $raw = trim((string)$value);
        if ($raw === '') {
            $session_date = '';
            if (isset($this->session)) {
                $session_date = trim((string)$this->session->userdata('system_date'));
            }

            if ($session_date !== '') {
                $session_ts = strtotime($session_date);
                if ($session_ts !== false) {
                    return date('Y-m-d', $session_ts);
                }
            }

            // Final fallback keeps the workflow running even when loan_date is omitted.
            return date('Y-m-d');
        }

        $formats = array(
            'Y-m-d',
            'Y/m/d',
            'd/m/Y',
            'm/d/Y',
            'd-m-Y',
            'm-d-Y',
            'Y-m-d H:i:s',
            'Y-m-d\TH:i',
            'Y-m-d\TH:i:s',
        );

        foreach ($formats as $format) {
            $dt = DateTime::createFromFormat($format, $raw);
            if ($dt instanceof DateTime) {
                return $dt->format('Y-m-d');
            }
        }

        $ts = strtotime($raw);
        if ($ts === false) {
            throw new Exception('Invalid loan date format provided: ' . $raw);
        }

        return date('Y-m-d', $ts);
    }

    /**
     * Backward-compatible helper for list renderers that still call this method.
     * Returns a display name for either individual or group borrowers.
     */
    public function resolve_customer_display_name($loan_row)
    {
        if (!$loan_row) {
            return 'Unknown';
        }

        if (!empty($loan_row->customer_display_name)) {
            return (string)$loan_row->customer_display_name;
        }

        if ((string)($loan_row->customer_type ?? '') === 'group') {
            $group = get_by_id('groups', 'group_id', $loan_row->loan_customer);
            if ($group) {
                $group_name = trim((string)($group->group_name ?? ''));
                $group_code = trim((string)($group->group_code ?? ''));
                if ($group_name !== '' || $group_code !== '') {
                    return $group_name . ($group_code !== '' ? '(' . $group_code . ')' : '');
                }
            }
            return 'Unknown';
        }

        if ((string)($loan_row->customer_type ?? '') === 'individual') {
            $customer = get_by_id('individual_customers', 'id', $loan_row->loan_customer);
            if ($customer) {
                $full_name = trim((string)($customer->Firstname ?? '') . ' ' . (string)($customer->Lastname ?? ''));
                $client_id = trim((string)($customer->ClientId ?? ''));
                if ($full_name !== '') {
                    return $client_id !== '' ? $full_name . ' (' . $client_id . ')' : $full_name;
                }
                if ($client_id !== '') {
                    return $client_id;
                }
            }
        }

        return !empty($loan_row->customer_nam) ? (string)$loan_row->customer_nam : 'Unknown';
    }

    /**
     * Backward-compatible helper for legacy views that expect a preview URL resolver.
     */
    public function resolve_customer_preview_url($loan_row)
    {
        $customer_type = '';
        if (is_object($loan_row) && isset($loan_row->customer_type)) {
            $customer_type = strtolower(trim((string)$loan_row->customer_type));
        }

        return $customer_type === 'group'
            ? 'Customer_groups/members/'
            : 'Individual_customers/view/';
    }

    /**
     * Atomically generate a unique (loanid, counter) pair.
     *
     * Uses MySQL's GET_LOCK() to serialise access so that concurrent
     * requests cannot read the same MAX(counter) and produce a collision.
     */
    private function generate_loan_number()
    {
        // Acquire an application-level advisory lock (timeout = 10 s).
        $lock = $this->db
            ->query("SELECT GET_LOCK('sycamore_loan_number_gen', 10) AS got_lock")
            ->row();

        if (empty($lock) || (int)$lock->got_lock !== 1) {
            throw new Exception('Could not acquire lock for loan number generation. Please retry.');
        }

        try {
            $row = $this->db
                ->select('MAX(counter) as max_c')
                ->get('loan')
                ->row();

            $max_counter = (int)($row->max_c ?? 0);

            // Keep incrementing until we find a loanid not yet in use.
            do {
                $max_counter++;
                $loanid = 'SCL' . date('Ymd') . (100 + $max_counter - 1);
                $exists = $this->db
                    ->select('loan_id')
                    ->from('loan')
                    ->where('loan_number', $loanid)
                    ->limit(1)
                    ->get()
                    ->row();
            } while (!empty($exists));

        } finally {
            $this->db->query("DO RELEASE_LOCK('sycamore_loan_number_gen')");
        }

        return array('loanid' => $loanid, 'fcounter' => $max_counter);
    }

    private function build_month_end_schedule_date($base_date, $offset_months = 0)
    {
        $normalized_base_date = $this->normalize_date_value($base_date);
        $date = new DateTime($normalized_base_date);
        $base_day = (int)$date->format('d');
        $effective_offset = (int)$offset_months;

        if ($base_day > 15) {
            $effective_offset++;
        }

        $date->modify('first day of this month');

        if ($effective_offset !== 0) {
            $date->modify('+' . $effective_offset . ' months');
        }

        $date->modify('last day of this month');

        return $date->format('Y-m-d');
    }

    private function build_masamba_promotion_schedule_date($disbursement_date, $installment_number)
    {
        $base_date = $this->normalize_date_value($disbursement_date);
        $base = new DateTime($base_date);
        $anchor_day = (int)$base->format('d');
        $offset_months = max(1, (int)$installment_number);

        $target = new DateTime($base->format('Y-m-01'));
        $target->modify('+' . $offset_months . ' months');

        $days_in_month = (int)$target->format('t');
        $target_day = min($anchor_day, $days_in_month);
        $target->setDate((int)$target->format('Y'), (int)$target->format('m'), $target_day);

        // Business rule: if anchored date is Sunday, move to Monday.
        if ($target->format('N') === '7') {
            $target->modify('+1 day');
        }

        return $target->format('Y-m-d');
    }

    private function is_masamba_promotion_for_lingwe_blantyre($loan, $branch = null)
    {
        if (!$loan) {
            return false;
        }

        $product_name = strtoupper(trim((string)($loan->product_name ?? '')));
        $product_code = strtoupper(trim((string)($loan->product_code ?? '')));
        $normalized_name = preg_replace('/[^A-Z0-9]/', '', $product_name);
        $is_masamba_product = in_array($product_code, array('MASPROLL', 'MASPROBT', 'MASLL', 'MASBT', 'MAIICLL', 'MAIICBT'), true)
            || $normalized_name === 'MASAMBAPROMOTION'
            || $normalized_name === 'MASAMBAMAIIC';

        // Handle legacy Masamba variants like "Masamba (MasLL)" and "Masamba (MasBT)".
        // Those become "MASAMBAMASLL"/"MASAMBAMASBT" after normalization.
        $is_masamba_product = $is_masamba_product
            || in_array($normalized_name, array('MASAMBAMASLL', 'MASAMBAMASBT'), true);

        if (!$is_masamba_product) {
            return false;
        }

        // Product code already anchors LL/BT variants even if branch metadata is missing.
        if (in_array($product_code, array('MASPROLL', 'MASPROBT', 'MASLL', 'MASBT', 'MAIICLL', 'MAIICBT'), true)) {
            return true;
        }

        $branch_tokens = array();
        $branch_row = null;

        if (is_numeric($branch)) {
            $branch_row = $this->db->select('BranchName, BranchCode')->from('branches')->where('id', (int)$branch)->get()->row();
        } elseif (is_object($branch)) {
            $branch_row = $branch;
        } elseif (!empty($branch)) {
            $branch_tokens[] = strtoupper(trim((string)$branch));
        }

        if ($branch_row) {
            $branch_tokens[] = strtoupper(trim((string)($branch_row->BranchName ?? '')));
            $branch_tokens[] = strtoupper(trim((string)($branch_row->BranchCode ?? '')));
        }

        $product_branch = strtoupper(trim((string)($loan->branch ?? '')));
        if ($product_branch !== '') {
            $branch_tokens[] = $product_branch;
        }

        foreach ($branch_tokens as $token) {
            if ($token === '') {
                continue;
            }
            if (strpos($token, 'LINGWE') !== false || strpos($token, 'BLANTYRE') !== false || $token === 'LL' || $token === 'BT') {
                return true;
            }
        }

        return false;
    }


    private function build_group_zitsamba_monthly_schedule_date($disbursement_date, $installment_number)
    {
        $base_date = $this->normalize_date_value($disbursement_date);
        $base = new DateTime($base_date);
        $anchor_day = (int)$base->format('d');
        $offset_months = max(1, (int)$installment_number);

        $target = new DateTime($base->format('Y-m-01'));
        $target->modify('+' . $offset_months . ' months');

        $days_in_month = (int)$target->format('t');
        $target_day = min($anchor_day, $days_in_month);
        $target->setDate((int)$target->format('Y'), (int)$target->format('m'), $target_day);

        return $target->format('Y-m-d');
    }

    private function is_group_zitsamba_monthly_flat_product($loan)
    {
        if (!$loan) {
            return false;
        }

        $product_name = strtoupper(trim((string)($loan->product_name ?? '')));
        $product_code = strtoupper(trim((string)($loan->product_code ?? '')));

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

    /**
     * Any loan product whose name/code identifies as Zitsamba (group or individual).
     */
    private function is_zitsamba_product($loan)
    {
        if (!$loan) {
            return false;
        }

        if ($this->is_group_zitsamba_monthly_flat_product($loan)) {
            return true;
        }

        $product_name = strtoupper(trim((string) ($loan->product_name ?? '')));
        $product_code = strtoupper(trim((string) ($loan->product_code ?? '')));
        $normalized_name = preg_replace('/[^A-Z0-9]/', '', $product_name);

        if (strpos($normalized_name, 'ZITSAMBA') !== false) {
            return true;
        }

        return (bool) preg_match('/ZITSAMBA/i', $product_code);
    }

    private function is_zitsamba_biweekly_product($loan)
    {
        if (!$this->is_zitsamba_product($loan)) {
            return false;
        }

        return $this->normalize_frequency_label($loan->frequency ?? '') === 'Bi weekly';
    }

    /**
     * Zitsamba bi-weekly: first repayment 14 days after loan date, then every 14 days.
     * Sundays roll forward to Monday for that installment only.
     */
    private function build_zitsamba_biweekly_schedule_date($loan_date, $installment_number)
    {
        $loan_date = $this->normalize_date_value($loan_date);
        $installment_number = max(1, (int) $installment_number);

        $payment_date = new DateTime($loan_date);
        $payment_date->modify('+' . ($installment_number * 14) . ' days');

        return $this->adjust_zitsamba_biweekly_schedule_date_for_sunday($payment_date->format('Y-m-d'));
    }

    private function adjust_zitsamba_biweekly_schedule_date_for_sunday($date)
    {
        $payment_date = new DateTime($date);
        if ((int) $payment_date->format('N') === 7) {
            $payment_date->modify('+1 day');
        }

        return $payment_date->format('Y-m-d');
    }

    private function build_group_zitsamba_flat_terms($principal, $requested_months, $processing_fee_percent = 32)
    {
        $principal = $this->normalize_numeric_value($principal);
        $months = max(1, min(4, (int)$requested_months));
        // Business rule: Group Zitsamba LL/BT always use 32% processing fee.
        $processing_fee_percent = 32;

        $processing_fee = $principal * ($processing_fee_percent / 100);
        $total_payment = $principal + $processing_fee;

        return array(
            'months' => $months,
            'processing_fee_percent' => $processing_fee_percent,
            'processing_fee' => $processing_fee,
            'total_payment' => $total_payment,
            'amount_per_term' => $total_payment / $months,
            'principal_per_term' => $total_payment / $months,
        );
    }

    private function add_group_zitsamba_monthly_flat_loan($amount, $months, $product_id, $loan_date, $loan_customer, $customer_type, $worthness_file, $narration, $added_by, $branch, $funds_source, $batch, $from_group, $group_id, $loanid, $fcounter, $loan)
    {
        $terms = $this->build_group_zitsamba_flat_terms($amount, $months, 32);

        $loan_data = array(
            'loan_number' => $loanid,
            'loan_product' => $product_id,
            'loan_customer' => $loan_customer,
            'customer_type' => $customer_type,
            'loan_date' => $loan_date,
            'loan_principal' => $amount,
            'loan_period' => $terms['months'],
            'worthness_file' => $worthness_file,
            'narration' => $narration,
            'period_type' => 'Monthly',
            'loan_amount_term' => $terms['amount_per_term'],
            'loan_interest' => 0,
            'loan_interest_amount' => 0,
            'admin_fee' => 0,
            'admin_fees_amount' => 0,
            'loan_cover' => 0,
            'loan_cover_amount' => 0,
            'loan_amount_total' => $terms['total_payment'],
            'next_payment_id' => 1,
            'loan_added_by' => $added_by,
            'counter' => $fcounter,
            'branch' => $branch,
            'funds_source' => $funds_source,
            'batch' => $batch,
            'from_group' => $from_group,
            'group_id' => $group_id,
        );

        $this->db->insert($this->table, $loan_data);
        $db_error = $this->db->error();
        if (!empty($db_error['code'])) {
            throw new Exception('Failed to create Group Zitsamba loan: ' . $db_error['message']);
        }

        $id = $this->db->insert_id();
        if (!$id) {
            throw new Exception('Failed to create Group Zitsamba loan: no loan ID was generated.');
        }

        $remaining_principal = $terms['total_payment'];

        for ($ii = 1; $ii <= $terms['months']; $ii++) {
            $schedule_date = $this->build_group_zitsamba_monthly_schedule_date($loan_date, $ii);

            if ($ii === $terms['months']) {
                $principal_portion = $remaining_principal;
            } else {
                $principal_portion = $terms['principal_per_term'];
            }

            $installment_amount = $principal_portion;
            $remaining_principal -= $principal_portion;

            $this->db->insert('payement_schedules', array(
                'customer' => $loan_customer,
                'customer_type' => $customer_type,
                'loan_id' => $id,
                'payment_schedule' => $schedule_date,
                'payment_number' => $ii,
                'amount' => $installment_amount,
                'principal' => $principal_portion,
                'interest' => 0,
                'padmin_fee' => 0,
                'ploan_cover' => 0,
                'paid_amount' => 0,
                'loan_balance' => max(0, $remaining_principal),
                'loan_date' => $loan_date,
            ));
            $db_error = $this->db->error();
            if (!empty($db_error['code'])) {
                throw new Exception('Failed to create Group Zitsamba repayment schedule: ' . $db_error['message']);
            }
        }

        $data_account = array(
            'client_id' => $loan_customer,
            'account_number' => $loanid,
            'balance' => 0,
            'account_type' => 2,
            'account_type_product' => $product_id,
            'added_by' => $added_by,
        );
        $this->db->insert('account', $data_account);
        $db_error = $this->db->error();
        if (!empty($db_error['code'])) {
            throw new Exception('Failed to create Group Zitsamba account: ' . $db_error['message']);
        }

        return $id;
    }

    private function rerun_group_zitsamba_monthly_flat_loan($amount, $months, $product_id, $loan_date, $loan_customer, $customer_type, $loan_number, $loan_id)
    {
        $loan = $this->db->select('processing_fees')->from('loan_products')->where('loan_product_id', $product_id)->get()->row();
        $terms = $this->build_group_zitsamba_flat_terms($amount, $months, 32);

        $this->db->where($this->id, $loan_id)->update($this->table, array(
            'loan_number' => $loan_number,
            'loan_product' => $product_id,
            'loan_customer' => $loan_customer,
            'customer_type' => $customer_type,
            'loan_date' => $loan_date,
            'loan_principal' => $amount,
            'loan_period' => $terms['months'],
            'period_type' => 'Monthly',
            'loan_amount_term' => $terms['amount_per_term'],
            'loan_interest' => 0,
            'loan_interest_amount' => 0,
            'admin_fee' => 0,
            'admin_fees_amount' => 0,
            'loan_cover' => 0,
            'loan_cover_amount' => 0,
            'loan_amount_total' => $terms['total_payment'],
            'next_payment_id' => 1,
        ));

        $remaining_principal = $terms['total_payment'];

        for ($ii = 1; $ii <= $terms['months']; $ii++) {
            $schedule_date = $this->build_group_zitsamba_monthly_schedule_date($loan_date, $ii);

            if ($ii === $terms['months']) {
                $principal_portion = $remaining_principal;
            } else {
                $principal_portion = $terms['principal_per_term'];
            }

            $installment_amount = $principal_portion;
            $remaining_principal -= $principal_portion;

            $schedule_payload = array(
                'customer' => $loan_customer,
                'customer_type' => $customer_type,
                'payment_schedule' => $schedule_date,
                'amount' => $installment_amount,
                'principal' => $principal_portion,
                'interest' => 0,
                'padmin_fee' => 0,
                'ploan_cover' => 0,
                'loan_balance' => max(0, $remaining_principal),
                'loan_date' => $loan_date,
            );

            $existing_schedule = $this->db->select('id')
                ->from('payement_schedules')
                ->where('loan_id', $loan_id)
                ->where('payment_number', $ii)
                ->get()
                ->row();

            if ($existing_schedule) {
                $this->db->where('id', $existing_schedule->id)->update('payement_schedules', $schedule_payload);
            } else {
                $this->db->insert('payement_schedules', array_merge($schedule_payload, array(
                    'loan_id' => $loan_id,
                    'payment_number' => $ii,
                    'paid_amount' => 0,
                )));
            }
        }

        $this->db->where('loan_id', $loan_id)->where('payment_number >', $terms['months'])->delete('payement_schedules');

        return $loan_id;
    }

    private function calculate_group_zitsamba_monthly_flat_table($amount, $months, $loan, $loan_date)
    {
        $terms = $this->build_group_zitsamba_flat_terms($amount, $months, 32);

        $table = '<div id="calculator"><h3>Loan Info</h3>';
        $table .= '<table border="1" class="table">';
        $table .= '<tr><td>Loan Product:</td><td>' . $loan->product_name . '</td></tr>';
        $table .= '<tr><td>Loan Method:</td><td>Flat monthly</td></tr>';
        $table .= '<tr><td>Processing fee:</td><td>' . number_format($terms['processing_fee_percent'], 2) . '%</td></tr>';
        $table .= '<tr><td>Loan Term:</td><td>' . $terms['months'] . '/Monthly</td></tr>';
        $table .= '<tr><td>Loan start date:</td><td>' . $loan_date . '</td></tr>';
        $table .= '<tr><td>First repayment date:</td><td>' . $this->build_group_zitsamba_monthly_schedule_date($loan_date, 1) . '</td></tr>';
        $table .= '</table>';

        $table .= '<h3>Computation</h3><table>';
        $table .= '<tr><td>Loan Amount:</td><td> ' . $this->config->item('currency_symbol') . number_format($amount, 2, '.', ',') . '</td></tr>';
        $table .= '<tr><td>Processing fee:</td><td> ' . $this->config->item('currency_symbol') . number_format($terms['processing_fee'], 2, '.', ',') . '</td></tr>';
        $table .= '<tr><td>Amount Per Term:</td><td> ' . $this->config->item('currency_symbol') . number_format($terms['amount_per_term'], 2, '.', ',') . '</td></tr>';
        $table .= '<tr><td>Total Payment:</td><td> ' . $this->config->item('currency_symbol') . number_format($terms['total_payment'], 2, '.', ',') . '</td></tr>';
        $table .= '</table>';

        $table .= '<table class="table">';
        $table .= '<tr><th width="30" align="center"><b>Pmt</b></th><th width="120" align="center"><b>Date</b></th><th width="80" align="center"><b>Payment</b></th><th width="80" align="center"><b>Principal</b></th><th width="80" align="center"><b>Interest</b></th><th width="80" align="center"><b>Balance</b></th></tr>';

        $remaining_principal = $terms['total_payment'];
        for ($ii = 1; $ii <= $terms['months']; $ii++) {
            if ($ii === $terms['months']) {
                $principal_portion = $remaining_principal;
            } else {
                $principal_portion = $terms['principal_per_term'];
            }
            $installment_amount = $principal_portion;
            $remaining_principal -= $principal_portion;
            $schedule_date = $this->build_group_zitsamba_monthly_schedule_date($loan_date, $ii);

            $table .= '<tr class="table_info">';
            $table .= '<td>' . $ii . '</td>';
            $table .= '<td>' . $schedule_date . '</td>';
            $table .= '<td>' . number_format($installment_amount, 2) . '</td>';
            $table .= '<td>' . number_format($principal_portion, 2) . '</td>';
            $table .= '<td>0.00</td>';
            $table .= '<td>' . number_format(max(0, $remaining_principal), 2) . '</td>';
            $table .= '</tr>';
        }

        $table .= '</table></div>';
        return $table;
    }

    private function capture_schedule_payment_state($loan_id)
    {
        $rows = $this->db->select('payment_number, paid_amount, paid_date, status, partial_paid')
            ->from('payement_schedules')
            ->where('loan_id', $loan_id)
            ->order_by('payment_number', 'ASC')
            ->get()
            ->result();

        $state_by_payment_number = array();
        $ordered_allocations = array();
        foreach ($rows as $row) {
            $paid_amount = $this->normalize_numeric_value($row->paid_amount);
            $state_by_payment_number[(int)$row->payment_number] = array(
                'paid_amount' => $paid_amount,
                'paid_date' => $row->paid_date,
                'status' => strtoupper(trim((string)$row->status)),
                'partial_paid' => strtoupper(trim((string)$row->partial_paid)),
            );

            if ($paid_amount > 0) {
                $ordered_allocations[] = array(
                    'paid_amount' => $paid_amount,
                    'paid_date' => $row->paid_date,
                );
            }
        }

        // Keep backward compatibility with payment_number lookup while also preserving
        // ordered paid buckets for deterministic re-allocation after schedule rebuilds.
        $state_by_payment_number['__ordered_allocations'] = $ordered_allocations;

        return $state_by_payment_number;
    }

    private function is_valid_paid_date($date_value)
    {
        if (empty($date_value)) {
            return false;
        }

        return $date_value !== '0000-00-00' && $date_value !== '0000-00-00 00:00:00';
    }

    private function extract_ordered_paid_allocations(array $schedule_state)
    {
        $ordered_allocations = array();

        if (isset($schedule_state['__ordered_allocations']) && is_array($schedule_state['__ordered_allocations'])) {
            foreach ($schedule_state['__ordered_allocations'] as $allocation) {
                $paid_amount = isset($allocation['paid_amount']) ? $this->normalize_numeric_value($allocation['paid_amount']) : 0;
                if ($paid_amount <= 0) {
                    continue;
                }

                $ordered_allocations[] = array(
                    'remaining_amount' => $paid_amount,
                    'paid_date' => isset($allocation['paid_date']) ? $allocation['paid_date'] : null,
                );
            }

            return $ordered_allocations;
        }

        // Legacy fallback: rebuild ordered buckets from payment-number keyed snapshots.
        $legacy_rows = array();
        foreach ($schedule_state as $key => $snapshot) {
            if (!is_numeric($key) || !is_array($snapshot)) {
                continue;
            }

            $payment_number = (int)$key;
            $paid_amount = isset($snapshot['paid_amount']) ? $this->normalize_numeric_value($snapshot['paid_amount']) : 0;
            if ($paid_amount <= 0) {
                continue;
            }

            $legacy_rows[$payment_number] = array(
                'remaining_amount' => $paid_amount,
                'paid_date' => isset($snapshot['paid_date']) ? $snapshot['paid_date'] : null,
            );
        }

        if (!empty($legacy_rows)) {
            ksort($legacy_rows);
            $ordered_allocations = array_values($legacy_rows);
        }

        return $ordered_allocations;
    }

    private function restore_schedule_payment_state($loan_id, array $schedule_state)
    {
        $schedules = $this->db->select('id, payment_number, amount')
            ->from('payement_schedules')
            ->where('loan_id', $loan_id)
            ->order_by('payment_number', 'ASC')
            ->get()
            ->result();

        $ordered_allocations = $this->extract_ordered_paid_allocations($schedule_state);
        $allocation_index = 0;

        foreach ($schedules as $schedule) {
            $schedule_amount = max(0.0, (float)$schedule->amount);
            $allocated_paid_amount = 0.0;
            $latest_paid_date = null;

            while (($allocated_paid_amount + 0.0001) < $schedule_amount && isset($ordered_allocations[$allocation_index])) {
                $bucket_remaining = isset($ordered_allocations[$allocation_index]['remaining_amount'])
                    ? (float)$ordered_allocations[$allocation_index]['remaining_amount']
                    : 0.0;

                if ($bucket_remaining <= 0.0001) {
                    $allocation_index++;
                    continue;
                }

                $amount_to_allocate = min($schedule_amount - $allocated_paid_amount, $bucket_remaining);
                $allocated_paid_amount += $amount_to_allocate;
                $ordered_allocations[$allocation_index]['remaining_amount'] = $bucket_remaining - $amount_to_allocate;

                if ($this->is_valid_paid_date($ordered_allocations[$allocation_index]['paid_date'])) {
                    $latest_paid_date = $ordered_allocations[$allocation_index]['paid_date'];
                }

                if ($ordered_allocations[$allocation_index]['remaining_amount'] <= 0.0001) {
                    $allocation_index++;
                }
            }

            $update_data = array(
                'paid_amount' => 0,
                'paid_date' => null,
                'status' => 'NOT PAID',
                'partial_paid' => 'NO',
            );

            if ($allocated_paid_amount > 0) {
                $update_data['paid_amount'] = $allocated_paid_amount;

                if ($latest_paid_date !== null) {
                    $update_data['paid_date'] = $latest_paid_date;
                }

                if (($allocated_paid_amount + 0.0001) >= $schedule_amount) {
                    $update_data['status'] = 'PAID';
                    $update_data['partial_paid'] = 'NO';
                } else {
                    $update_data['status'] = 'PARTIAL PAID';
                    $update_data['partial_paid'] = 'YES';
                }
            }

            $this->db->where('id', $schedule->id)->update('payement_schedules', $update_data);
        }

        $next_payment = $this->db->select('payment_number')
            ->from('payement_schedules')
            ->where('loan_id', $loan_id)
            ->where('status !=', 'PAID')
            ->order_by('payment_number', 'ASC')
            ->limit(1)
            ->get()
            ->row();

        if ($next_payment) {
            $next_payment_id = (int)$next_payment->payment_number;
        } else {
            $last_schedule = $this->db->select_max('payment_number')
                ->from('payement_schedules')
                ->where('loan_id', $loan_id)
                ->get()
                ->row();
            $next_payment_id = $last_schedule ? ((int)$last_schedule->payment_number + 1) : 1;
        }

        $this->db->where('loan_id', $loan_id)->update($this->table, array('next_payment_id' => $next_payment_id));
    }

    public  function delete_replace_loans()
    {
        $jsonData = '[
    {
        "No": "1",
        "New  Loan Number": "SCL202409241904",
        "Old Loan Number": "SCL202408281788",
        "Loan Product": "Mizu",
        "Loan Customer": "Davison Phwitiko (4963800)",
        "Loan Date": "2024-08-28",
        "Loan Principal": " 300,000.00 ",
        "Loan Period": "6",
        "Period Type": "Monthly",
        "Loan Interest Rate": "4.5",
        "Loan Interest Amount": "54331.83",
        "Admin Fee Rate": "3.5",
        "Admin Fees Amount": "39924.76",
        "Loan Cover Rate": "3",
        "Loan Cover Amount": "34221.22",
        "Loan Amount Term": "71412.97",
        "Loan Amount Total": "428477.81",
        "Next Payment Id": "4",
        "Worthness File": "66cf3ac9219377335.png",
        "Narration": "",
        "Loan Added By": "Sarudzai Zodetsa",
        "Loan Approved By": "Patrick Kalowekamo",
        "Approved Date": "2024-08-29 09:06:33",
        "Rejected By": "",
        "Rejected Date": "",
        "Loan Status": "CLOSED",
        "Disbursed": "Yes",
        "Disbursed By": "Harold Mwala",
        "Disbursed Date": "2024-09-16 07:34:28",
        "Written Off By": "",
        "Write Off Approved By": "",
        "Write Off Approval Date": "",
        "Written Off Date": "",
        "Loan Added Date": "2024-08-28 16:57:16",
        "": "",
        "status": "done"
    },
    {
        "No": "2",
        "New  Loan Number": "SCL202409241905",
        "Old Loan Number": "SCL202408281787",
        "Loan Product": "Mizu",
        "Loan Customer": "Zinho Chiotcha (7573077)",
        "Loan Date": "2024-08-28",
        "Loan Principal": " 200,000.00 ",
        "Loan Period": "6",
        "Period Type": "Monthly",
        "Loan Interest Rate": "4.5",
        "Loan Interest Amount": "36221.22",
        "Admin Fee Rate": "3.5",
        "Admin Fees Amount": "26616.51",
        "Loan Cover Rate": "3",
        "Loan Cover Amount": "22814.15",
        "Loan Amount Term": "47608.65",
        "Loan Amount Total": "285651.88",
        "Next Payment Id": "4",
        "Worthness File": "66cf365a5133d6041.png",
        "Narration": "",
        "Loan Added By": "Sarudzai Zodetsa",
        "Loan Approved By": "Patrick Kalowekamo",
        "Approved Date": "2024-08-29 09:06:26",
        "Rejected By": "",
        "Rejected Date": "",
        "Loan Status": "CLOSED",
        "Disbursed": "Yes",
        "Disbursed By": "Harold Mwala",
        "Disbursed Date": "2024-09-16 07:34:37",
        "Written Off By": "",
        "Write Off Approved By": "",
        "Write Off Approval Date": "",
        "Written Off Date": "",
        "Loan Added Date": "2024-08-28 16:38:25",
        "": "",
        "status": ""
    },
    {
        "No": "3",
        "New  Loan Number": "SCL202409251908",
        "Old Loan Number": "SCL202408281786",
        "Loan Product": "Mizu",
        "Loan Customer": "Wanangwa Saka (5953238)",
        "Loan Date": "2024-08-28",
        "Loan Principal": " 400,000.00 ",
        "Loan Period": "1",
        "Period Type": "Monthly",
        "Loan Interest Rate": "4.5",
        "Loan Interest Amount": "22000",
        "Admin Fee Rate": "3.5",
        "Admin Fees Amount": "14000",
        "Loan Cover Rate": "3",
        "Loan Cover Amount": "12000",
        "Loan Amount Term": "448000",
        "Loan Amount Total": "448000",
        "Next Payment Id": "2",
        "Worthness File": "66cf360ab52081379.png",
        "Narration": "",
        "Loan Added By": "Sarudzai Zodetsa",
        "Loan Approved By": "Patrick Kalowekamo",
        "Approved Date": "2024-08-29 09:06:22",
        "Rejected By": "",
        "Rejected Date": "",
        "Loan Status": "CLOSED",
        "Disbursed": "Yes",
        "Disbursed By": "Harold Mwala",
        "Disbursed Date": "2024-09-16 07:34:46",
        "Written Off By": "",
        "Write Off Approved By": "",
        "Write Off Approval Date": "",
        "Written Off Date": "",
        "Loan Added Date": "2024-08-28 16:37:04",
        "": "",
        "status": ""
    },
    {
        "No": "4",
        "New  Loan Number": "SCL202409251910",
        "Old Loan Number": "SCL202408281782",
        "Loan Product": "Mizu",
        "Loan Customer": "Christopher Phiri (2786184)",
        "Loan Date": "2024-08-28",
        "Loan Principal": " 500,000.00 ",
        "Loan Period": "3",
        "Period Type": "Monthly",
        "Loan Interest Rate": "4.5",
        "Loan Interest Amount": "51562.57",
        "Admin Fee Rate": "3.5",
        "Admin Fees Amount": "36215.33",
        "Loan Cover Rate": "3",
        "Loan Cover Amount": "31041.71",
        "Loan Amount Term": "206273.2",
        "Loan Amount Total": "618819.6",
        "Next Payment Id": "3",
        "Worthness File": "66cf2f1a86b603513.png",
        "Narration": "",
        "Loan Added By": "Sarudzai Zodetsa",
        "Loan Approved By": "Patrick Kalowekamo",
        "Approved Date": "2024-08-29 09:06:07",
        "Rejected By": "",
        "Rejected Date": "",
        "Loan Status": "CLOSED",
        "Disbursed": "Yes",
        "Disbursed By": "Harold Mwala",
        "Disbursed Date": "2024-09-16 07:35:26",
        "Written Off By": "",
        "Write Off Approved By": "",
        "Write Off Approval Date": "",
        "Written Off Date": "",
        "Loan Added Date": "2024-08-28 16:07:29",
        "": "",
        "status": ""
    },
    {
        "No": "5",
        "New  Loan Number": "SCL202409251913",
        "Old Loan Number": "SCL202408231773",
        "Loan Product": "Tsinde",
        "Loan Customer": "AUDRICK MPHEPO (9129007)",
        "Loan Date": "2024-08-22",
        "Loan Principal": " 500,000.00 ",
        "Loan Period": "6",
        "Period Type": "Monthly",
        "Loan Interest Rate": "6",
        "Loan Interest Amount": "130218.88",
        "Admin Fee Rate": "3.5",
        "Admin Fees Amount": "67211.02",
        "Loan Cover Rate": "3",
        "Loan Cover Amount": "57609.44",
        "Loan Amount Term": "125839.89",
        "Loan Amount Total": "755039.34",
        "Next Payment Id": "3",
        "Worthness File": "66c8ab4b063c64321.jpeg",
        "Narration": "",
        "Loan Added By": "Sarudzai Zodetsa",
        "Loan Approved By": "Patrick Kalowekamo",
        "Approved Date": "2024-08-29 09:05:38",
        "Rejected By": "",
        "Rejected Date": "",
        "Loan Status": "CLOSED",
        "Disbursed": "Yes",
        "Disbursed By": "Harold Mwala",
        "Disbursed Date": "2024-09-16 07:37:15",
        "Written Off By": "",
        "Write Off Approved By": "",
        "Write Off Approval Date": "",
        "Written Off Date": "",
        "Loan Added Date": "2024-08-23 17:31:38",
        "": "",
        "status": ""
    },
    {
        "No": "6",
        "New  Loan Number": "SCL202409251914",
        "Old Loan Number": "SCL202408231771",
        "Loan Product": "Tsinde",
        "Loan Customer": "WILLIAM CHAMANGWANA (2619706)",
        "Loan Date": "2024-08-22",
        "Loan Principal": " 500,000.00 ",
        "Loan Period": "6",
        "Period Type": "Monthly",
        "Loan Interest Rate": "6",
        "Loan Interest Amount": "130218.88",
        "Admin Fee Rate": "3.5",
        "Admin Fees Amount": "67211.02",
        "Loan Cover Rate": "3",
        "Loan Cover Amount": "57609.44",
        "Loan Amount Term": "125839.89",
        "Loan Amount Total": "755039.34",
        "Next Payment Id": "3",
        "Worthness File": "66c8a9c95525e1289.jpg",
        "Narration": "",
        "Loan Added By": "Sarudzai Zodetsa",
        "Loan Approved By": "Patrick Kalowekamo",
        "Approved Date": "2024-08-29 09:05:31",
        "Rejected By": "",
        "Rejected Date": "",
        "Loan Status": "CLOSED",
        "Disbursed": "Yes",
        "Disbursed By": "Harold Mwala",
        "Disbursed Date": "2024-09-16 07:37:32",
        "Written Off By": "",
        "Write Off Approved By": "",
        "Write Off Approval Date": "",
        "Written Off Date": "",
        "Loan Added Date": "2024-08-23 17:25:13",
        "": "",
        "status": ""
    },
    {
        "No": "7",
        "New  Loan Number": "SCL202409251915",
        "Old Loan Number": "SCL202408231770",
        "Loan Product": "Mizu",
        "Loan Customer": "MATHEWS KABANGO (1498005)",
        "Loan Date": "2024-08-20",
        "Loan Principal": " 1,000,000.00 ",
        "Loan Period": "6",
        "Period Type": "Monthly",
        "Loan Interest Rate": "4.5",
        "Loan Interest Amount": "201106.11",
        "Admin Fee Rate": "3.5",
        "Admin Fees Amount": "133082.53",
        "Loan Cover Rate": "3",
        "Loan Cover Amount": "114070.74",
        "Loan Amount Term": "241376.56",
        "Loan Amount Total": "1448259.38",
        "Next Payment Id": "3",
        "Worthness File": "66c8a367337915091.jpeg",
        "Narration": "",
        "Loan Added By": "Bridget Nyanga",
        "Loan Approved By": "Patrick Kalowekamo",
        "Approved Date": "2024-08-29 09:05:27",
        "Rejected By": "",
        "Rejected Date": "",
        "Loan Status": "CLOSED",
        "Disbursed": "Yes",
        "Disbursed By": "Harold Mwala",
        "Disbursed Date": "2024-09-16 07:37:40",
        "Written Off By": "",
        "Write Off Approved By": "",
        "Write Off Approval Date": "",
        "Written Off Date": "",
        "Loan Added Date": "2024-08-23 16:58:06",
        "": "",
        "status": ""
    },
    {
        "No": "8",
        "New  Loan Number": "SCL202409251916",
        "Old Loan Number": "SCL202408231767",
        "Loan Product": "Zitsamba",
        "Loan Customer": "CHILEMBWE(813)",
        "Loan Date": "2024-08-23",
        "Loan Principal": " 950,000.00 ",
        "Loan Period": "12",
        "Period Type": "Weekly",
        "Loan Interest Rate": "0",
        "Loan Interest Amount": "0",
        "Admin Fee Rate": "0",
        "Admin Fees Amount": "0",
        "Loan Cover Rate": "0",
        "Loan Cover Amount": "0",
        "Loan Amount Term": "98958.33",
        "Loan Amount Total": "1187500",
        "Next Payment Id": "6",
        "Worthness File": "66c894384cf744537.jpg",
        "Narration": "",
        "Loan Added By": "Temwa Chawinga",
        "Loan Approved By": "Patrick Kalowekamo",
        "Approved Date": "2024-08-29 09:05:04",
        "Rejected By": "",
        "Rejected Date": "",
        "Loan Status": "CLOSED",
        "Disbursed": "Yes",
        "Disbursed By": "Harold Mwala",
        "Disbursed Date": "2024-09-16 07:38:06",
        "Written Off By": "",
        "Write Off Approved By": "",
        "Write Off Approval Date": "",
        "Written Off Date": "",
        "Loan Added Date": "2024-08-23 15:53:10",
        "": "",
        "status": ""
    },
    {
        "No": "9",
        "New  Loan Number": "SCL20230817101",
        "Old Loan Number": "SCL202408221764",
        "Loan Product": "Zitsamba",
        "Loan Customer": "BAWA GROUP(995)",
        "Loan Date": "2024-08-20",
        "Loan Principal": " 450,000.00 ",
        "Loan Period": "8",
        "Period Type": "Weekly",
        "Loan Interest Rate": "0",
        "Loan Interest Amount": "0",
        "Admin Fee Rate": "0",
        "Admin Fees Amount": "0",
        "Loan Cover Rate": "0",
        "Loan Cover Amount": "0",
        "Loan Amount Term": "70312.5",
        "Loan Amount Total": "562500",
        "Next Payment Id": "5",
        "Worthness File": "66c6f67cbdb319979.jpg",
        "Narration": "",
        "Loan Added By": "Jane Malopa",
        "Loan Approved By": "Patrick Kalowekamo",
        "Approved Date": "2024-08-29 09:04:53",
        "Rejected By": "",
        "Rejected Date": "",
        "Loan Status": "CLOSED",
        "Disbursed": "Yes",
        "Disbursed By": "Harold Mwala",
        "Disbursed Date": "2024-09-16 07:38:34",
        "Written Off By": "",
        "Write Off Approved By": "",
        "Write Off Approval Date": "",
        "Written Off Date": "",
        "Loan Added Date": "2024-08-22 10:28:01",
        "": "",
        "status": ""
    },
    {
        "No": "10",
        "New  Loan Number": "SCL202409261934",
        "Old Loan Number": "SCL202408211763",
        "Loan Product": "Zitsamba",
        "Loan Customer": "KONDA MNZAKO(300)",
        "Loan Date": "2024-08-20",
        "Loan Principal": " 300,000.00 ",
        "Loan Period": "8",
        "Period Type": "Weekly",
        "Loan Interest Rate": "0",
        "Loan Interest Amount": "0",
        "Admin Fee Rate": "0",
        "Admin Fees Amount": "0",
        "Loan Cover Rate": "0",
        "Loan Cover Amount": "0",
        "Loan Amount Term": "46875",
        "Loan Amount Total": "375000",
        "Next Payment Id": "5",
        "Worthness File": "66c601f30c6637457.jpg",
        "Narration": "",
        "Loan Added By": "Jane Malopa",
        "Loan Approved By": "Patrick Kalowekamo",
        "Approved Date": "2024-08-29 09:04:48",
        "Rejected By": "",
        "Rejected Date": "",
        "Loan Status": "CLOSED",
        "Disbursed": "Yes",
        "Disbursed By": "Harold Mwala",
        "Disbursed Date": "2024-09-16 07:38:44",
        "Written Off By": "",
        "Write Off Approved By": "",
        "Write Off Approval Date": "",
        "Written Off Date": "",
        "Loan Added Date": "2024-08-21 17:04:39",
        "": "",
        "status": ""
    },
    {
        "No": "11",
        "New  Loan Number": "SCL202409261935",
        "Old Loan Number": "SCL202408211762",
        "Loan Product": "Zitsamba",
        "Loan Customer": "ZIPATSO GROUP(373)",
        "Loan Date": "2024-08-20",
        "Loan Principal": " 450,000.00 ",
        "Loan Period": "8",
        "Period Type": "Weekly",
        "Loan Interest Rate": "0",
        "Loan Interest Amount": "0",
        "Admin Fee Rate": "0",
        "Admin Fees Amount": "0",
        "Loan Cover Rate": "0",
        "Loan Cover Amount": "0",
        "Loan Amount Term": "70312.5",
        "Loan Amount Total": "562500",
        "Next Payment Id": "5",
        "Worthness File": "66c5fad3dbd5f5618.jpg",
        "Narration": "",
        "Loan Added By": "Jane Malopa",
        "Loan Approved By": "Patrick Kalowekamo",
        "Approved Date": "2024-08-29 09:04:44",
        "Rejected By": "",
        "Rejected Date": "",
        "Loan Status": "CLOSED",
        "Disbursed": "Yes",
        "Disbursed By": "Harold Mwala",
        "Disbursed Date": "2024-09-16 07:38:52",
        "Written Off By": "",
        "Write Off Approved By": "",
        "Write Off Approval Date": "",
        "Written Off Date": "",
        "Loan Added Date": "2024-08-21 16:34:12",
        "": "",
        "status": ""
    },
    {
        "No": "12",
        "New  Loan Number": "SCL202409271938",
        "Old Loan Number": "SCL202408211760",
        "Loan Product": "Zitsamba",
        "Loan Customer": "NYEKHERERA  GROUP(805)",
        "Loan Date": "2024-08-16",
        "Loan Principal": " 900,000.00 ",
        "Loan Period": "8",
        "Period Type": "Weekly",
        "Loan Interest Rate": "0",
        "Loan Interest Amount": "0",
        "Admin Fee Rate": "0",
        "Admin Fees Amount": "0",
        "Loan Cover Rate": "0",
        "Loan Cover Amount": "0",
        "Loan Amount Term": "140625",
        "Loan Amount Total": "1125000",
        "Next Payment Id": "5",
        "Worthness File": "66c5855563fc09091.jpg",
        "Narration": "",
        "Loan Added By": "Grey Magombo",
        "Loan Approved By": "Patrick Kalowekamo",
        "Approved Date": "2024-08-29 09:04:41",
        "Rejected By": "",
        "Rejected Date": "",
        "Loan Status": "CLOSED",
        "Disbursed": "Yes",
        "Disbursed By": "Harold Mwala",
        "Disbursed Date": "2024-09-16 07:39:12",
        "Written Off By": "",
        "Write Off Approved By": "",
        "Write Off Approval Date": "",
        "Written Off Date": "",
        "Loan Added Date": "2024-08-21 08:13:10",
        "": "",
        "status": ""
    },
    {
        "No": "13",
        "New  Loan Number": "SCL202409271939",
        "Old Loan Number": "SCL202408191754",
        "Loan Product": "Masamba",
        "Loan Customer": "FROLENCE SIYABU (9423545)",
        "Loan Date": "2024-08-19",
        "Loan Principal": " 700,000.00 ",
        "Loan Period": "6",
        "Period Type": "Monthly",
        "Loan Interest Rate": "6",
        "Loan Interest Amount": "161306.44",
        "Admin Fee Rate": "3.5",
        "Admin Fees Amount": "94095.42",
        "Loan Cover Rate": "3",
        "Loan Cover Amount": "80653.22",
        "Loan Amount Term": "172675.85",
        "Loan Amount Total": "1036055.08",
        "Next Payment Id": "4",
        "Worthness File": "66c3480e400d92395.jpg",
        "Narration": "",
        "Loan Added By": "Mphatso Kachere",
        "Loan Approved By": "Patrick Kalowekamo",
        "Approved Date": "2024-08-29 09:03:59",
        "Rejected By": "",
        "Rejected Date": "",
        "Loan Status": "CLOSED",
        "Disbursed": "Yes",
        "Disbursed By": "Harold Mwala",
        "Disbursed Date": "2024-09-16 07:39:57",
        "Written Off By": "",
        "Write Off Approved By": "",
        "Write Off Approval Date": "",
        "Written Off Date": "",
        "Loan Added Date": "2024-08-19 15:26:46",
        "": "",
        "status": ""
    },
    {
        "No": "14",
        "New  Loan Number": "SCL202409271940",
        "Old Loan Number": "SCL202408191753",
        "Loan Product": "Masamba",
        "Loan Customer": "Martha Mkandawire (1321992)",
        "Loan Date": "2024-08-19",
        "Loan Principal": " 1,000,000.00 ",
        "Loan Period": "6",
        "Period Type": "Monthly",
        "Loan Interest Rate": "6",
        "Loan Interest Amount": "230437.77",
        "Admin Fee Rate": "3.5",
        "Admin Fees Amount": "134422.03",
        "Loan Cover Rate": "3",
        "Loan Cover Amount": "115218.88",
        "Loan Amount Term": "246679.78",
        "Loan Amount Total": "1480078.69",
        "Next Payment Id": "3",
        "Worthness File": "66c346b061f512120.jpg",
        "Narration": "",
        "Loan Added By": "Mphatso Kachere",
        "Loan Approved By": "Patrick Kalowekamo",
        "Approved Date": "2024-08-29 09:03:53",
        "Rejected By": "",
        "Rejected Date": "",
        "Loan Status": "CLOSED",
        "Disbursed": "Yes",
        "Disbursed By": "Harold Mwala",
        "Disbursed Date": "2024-09-16 07:40:07",
        "Written Off By": "",
        "Write Off Approved By": "",
        "Write Off Approval Date": "",
        "Written Off Date": "",
        "Loan Added Date": "2024-08-19 15:21:02",
        "": "",
        "status": ""
    },
    {
        "No": "15",
        "New  Loan Number": "SCL202409271941",
        "Old Loan Number": "SCL202408161752",
        "Loan Product": "Zitsamba",
        "Loan Customer": "Mugonja(525)",
        "Loan Date": "2024-08-16",
        "Loan Principal": " 900,000.00 ",
        "Loan Period": "12",
        "Period Type": "Weekly",
        "Loan Interest Rate": "0",
        "Loan Interest Amount": "0",
        "Admin Fee Rate": "0",
        "Admin Fees Amount": "0",
        "Loan Cover Rate": "0",
        "Loan Cover Amount": "0",
        "Loan Amount Term": "93750",
        "Loan Amount Total": "1125000",
        "Next Payment Id": "7",
        "Worthness File": "66bf4e5d04b882379.png",
        "Narration": "",
        "Loan Added By": "Christina Tauzie",
        "Loan Approved By": "Patrick Kalowekamo",
        "Approved Date": "2024-08-29 09:03:49",
        "Rejected By": "",
        "Rejected Date": "",
        "Loan Status": "CLOSED",
        "Disbursed": "Yes",
        "Disbursed By": "Harold Mwala",
        "Disbursed Date": "2024-09-16 07:40:14",
        "Written Off By": "",
        "Write Off Approved By": "",
        "Write Off Approval Date": "",
        "Written Off Date": "",
        "Loan Added Date": "2024-08-16 15:04:32",
        "": "",
        "status": ""
    },
    {
        "No": "16",
        "New  Loan Number": "SCL202409271942",
        "Old Loan Number": "SCL202408161751",
        "Loan Product": "Zitsamba",
        "Loan Customer": "Worshippers(382)",
        "Loan Date": "2024-08-16",
        "Loan Principal": " 350,000.00 ",
        "Loan Period": "8",
        "Period Type": "Weekly",
        "Loan Interest Rate": "0",
        "Loan Interest Amount": "0",
        "Admin Fee Rate": "0",
        "Admin Fees Amount": "0",
        "Loan Cover Rate": "0",
        "Loan Cover Amount": "0",
        "Loan Amount Term": "54687.5",
        "Loan Amount Total": "437500",
        "Next Payment Id": "5",
        "Worthness File": "66bf4a1b3b3132250.png",
        "Narration": "",
        "Loan Added By": "Christina Tauzie",
        "Loan Approved By": "Patrick Kalowekamo",
        "Approved Date": "2024-08-29 09:03:44",
        "Rejected By": "",
        "Rejected Date": "",
        "Loan Status": "CLOSED",
        "Disbursed": "Yes",
        "Disbursed By": "Harold Mwala",
        "Disbursed Date": "2024-09-16 07:40:24",
        "Written Off By": "",
        "Write Off Approved By": "",
        "Write Off Approval Date": "",
        "Written Off Date": "",
        "Loan Added Date": "2024-08-16 14:46:24",
        "": "",
        "status": ""
    },
    {
        "No": "17",
        "New  Loan Number": "SCL202409271943",
        "Old Loan Number": "SCL202408161750",
        "Loan Product": "Zitsamba",
        "Loan Customer": "Ndife Amadzi(569)",
        "Loan Date": "2024-08-16",
        "Loan Principal": " 730,000.00 ",
        "Loan Period": "8",
        "Period Type": "Weekly",
        "Loan Interest Rate": "0",
        "Loan Interest Amount": "0",
        "Admin Fee Rate": "0",
        "Admin Fees Amount": "0",
        "Loan Cover Rate": "0",
        "Loan Cover Amount": "0",
        "Loan Amount Term": "114062.5",
        "Loan Amount Total": "912500",
        "Next Payment Id": "5",
        "Worthness File": "66bf42c51768e5563.png",
        "Narration": "",
        "Loan Added By": "Jane Malopa",
        "Loan Approved By": "Patrick Kalowekamo",
        "Approved Date": "2024-08-29 09:03:40",
        "Rejected By": "",
        "Rejected Date": "",
        "Loan Status": "CLOSED",
        "Disbursed": "Yes",
        "Disbursed By": "Harold Mwala",
        "Disbursed Date": "2024-09-16 07:40:35",
        "Written Off By": "",
        "Write Off Approved By": "",
        "Write Off Approval Date": "",
        "Written Off Date": "",
        "Loan Added Date": "2024-08-16 14:15:05",
        "": "",
        "status": ""
    },
    {
        "No": "18",
        "New  Loan Number": "SCL202409271944",
        "Old Loan Number": "SCL202408161749",
        "Loan Product": "Zitsamba",
        "Loan Customer": "Admarc Sisankha Mbewu(590)",
        "Loan Date": "2024-08-16",
        "Loan Principal": " 550,000.00 ",
        "Loan Period": "8",
        "Period Type": "Weekly",
        "Loan Interest Rate": "0",
        "Loan Interest Amount": "0",
        "Admin Fee Rate": "0",
        "Admin Fees Amount": "0",
        "Loan Cover Rate": "0",
        "Loan Cover Amount": "0",
        "Loan Amount Term": "85937.5",
        "Loan Amount Total": "687500",
        "Next Payment Id": "5",
        "Worthness File": "66bf42738a007463.png",
        "Narration": "",
        "Loan Added By": "Jane Malopa",
        "Loan Approved By": "Patrick Kalowekamo",
        "Approved Date": "2024-08-29 09:03:35",
        "Rejected By": "",
        "Rejected Date": "",
        "Loan Status": "CLOSED",
        "Disbursed": "Yes",
        "Disbursed By": "Harold Mwala",
        "Disbursed Date": "2024-09-16 07:40:43",
        "Written Off By": "",
        "Write Off Approved By": "",
        "Write Off Approval Date": "",
        "Written Off Date": "",
        "Loan Added Date": "2024-08-16 14:13:44",
        "": "",
        "status": ""
    },
    {
        "No": "19",
        "New  Loan Number": "SCL202409271945",
        "Old Loan Number": "SCL202408161746",
        "Loan Product": "Zitsamba",
        "Loan Customer": "BANGWE FRESH FISH(494)",
        "Loan Date": "2024-08-15",
        "Loan Principal": " 500,000.00 ",
        "Loan Period": "8",
        "Period Type": "Weekly",
        "Loan Interest Rate": "0",
        "Loan Interest Amount": "0",
        "Admin Fee Rate": "0",
        "Admin Fees Amount": "0",
        "Loan Cover Rate": "0",
        "Loan Cover Amount": "0",
        "Loan Amount Term": "78125",
        "Loan Amount Total": "625000",
        "Next Payment Id": "5",
        "Worthness File": "",
        "Narration": "",
        "Loan Added By": "Jane Malopa",
        "Loan Approved By": "Patrick Kalowekamo",
        "Approved Date": "2024-08-29 09:03:27",
        "Rejected By": "",
        "Rejected Date": "",
        "Loan Status": "CLOSED",
        "Disbursed": "Yes",
        "Disbursed By": "Harold Mwala",
        "Disbursed Date": "2024-09-16 07:41:02",
        "Written Off By": "",
        "Write Off Approved By": "",
        "Write Off Approval Date": "",
        "Written Off Date": "",
        "Loan Added Date": "2024-08-16 11:43:19",
        "": "",
        "status": ""
    },
    {
        "No": "20",
        "New  Loan Number": "SCL202409271946",
        "Old Loan Number": "SCL202408131733",
        "Loan Product": "Masamba",
        "Loan Customer": "Joyce Mwandidya (3504598)",
        "Loan Date": "2024-08-13",
        "Loan Principal": " 1,500,000.00 ",
        "Loan Period": "6",
        "Period Type": "Monthly",
        "Loan Interest Rate": "6",
        "Loan Interest Amount": "345656.65",
        "Admin Fee Rate": "3.5",
        "Admin Fees Amount": "201633.05",
        "Loan Cover Rate": "3",
        "Loan Cover Amount": "172828.33",
        "Loan Amount Term": "370019.67",
        "Loan Amount Total": "2220118.03",
        "Next Payment Id": "4",
        "Worthness File": "66bb6fd2047167599.PNG",
        "Narration": "",
        "Loan Added By": "Bright Chinkombero",
        "Loan Approved By": "RAPHAEL MSHALI",
        "Approved Date": "2024-08-19 15:21:27",
        "Rejected By": "",
        "Rejected Date": "",
        "Loan Status": "CLOSED",
        "Disbursed": "Yes",
        "Disbursed By": "Harold Mwala",
        "Disbursed Date": "2024-09-16 07:42:07",
        "Written Off By": "",
        "Write Off Approved By": "",
        "Write Off Approval Date": "",
        "Written Off Date": "",
        "Loan Added Date": "2024-08-13 16:38:17",
        "": "",
        "status": ""
    },
    {
        "No": "21",
        "New  Loan Number": "SCL202409271947",
        "Old Loan Number": "SCL202408121731",
        "Loan Product": "Masamba",
        "Loan Customer": "Adshon Liphava (4626974)",
        "Loan Date": "2024-08-12",
        "Loan Principal": " 500,000.00 ",
        "Loan Period": "6",
        "Period Type": "Monthly",
        "Loan Interest Rate": "6",
        "Loan Interest Amount": "115218.88",
        "Admin Fee Rate": "3.5",
        "Admin Fees Amount": "67211.02",
        "Loan Cover Rate": "3",
        "Loan Cover Amount": "57609.44",
        "Loan Amount Term": "123339.89",
        "Loan Amount Total": "740039.34",
        "Next Payment Id": "3",
        "Worthness File": "66ba18a80f8ed1692.PNG",
        "Narration": "",
        "Loan Added By": "Mphatso Kachere",
        "Loan Approved By": "RAPHAEL MSHALI",
        "Approved Date": "2024-08-19 15:21:05",
        "Rejected By": "",
        "Rejected Date": "",
        "Loan Status": "CLOSED",
        "Disbursed": "Yes",
        "Disbursed By": "Harold Mwala",
        "Disbursed Date": "2024-09-16 07:42:19",
        "Written Off By": "",
        "Write Off Approved By": "",
        "Write Off Approval Date": "",
        "Written Off Date": "",
        "Loan Added Date": "2024-08-12 16:14:02",
        "": "",
        "status": ""
    },
    {
        "No": "22",
        "New  Loan Number": "SCL202409271948",
        "Old Loan Number": "SCL202408121730",
        "Loan Product": "Masamba",
        "Loan Customer": "Nellie Mchizampheta (8586455)",
        "Loan Date": "2024-08-12",
        "Loan Principal": " 500,000.00 ",
        "Loan Period": "6",
        "Period Type": "Monthly",
        "Loan Interest Rate": "6",
        "Loan Interest Amount": "115218.88",
        "Admin Fee Rate": "3.5",
        "Admin Fees Amount": "67211.02",
        "Loan Cover Rate": "3",
        "Loan Cover Amount": "57609.44",
        "Loan Amount Term": "123339.89",
        "Loan Amount Total": "740039.34",
        "Next Payment Id": "3",
        "Worthness File": "66ba1878bc56d1448.PNG",
        "Narration": "",
        "Loan Added By": "Mphatso Kachere",
        "Loan Approved By": "RAPHAEL MSHALI",
        "Approved Date": "2024-08-19 15:20:32",
        "Rejected By": "",
        "Rejected Date": "",
        "Loan Status": "CLOSED",
        "Disbursed": "Yes",
        "Disbursed By": "Harold Mwala",
        "Disbursed Date": "2024-09-16 07:42:27",
        "Written Off By": "",
        "Write Off Approved By": "",
        "Write Off Approval Date": "",
        "Written Off Date": "",
        "Loan Added Date": "2024-08-12 16:13:17",
        "": "",
        "status": ""
    },
    {
        "No": "23",
        "New  Loan Number": "SCL202409271949",
        "Old Loan Number": "SCL202408121728",
        "Loan Product": "Tsinde",
        "Loan Customer": "Nuru Alide (2119282)",
        "Loan Date": "2024-08-12",
        "Loan Principal": " 3,500,000.00 ",
        "Loan Period": "9",
        "Period Type": "Monthly",
        "Loan Interest Rate": "6",
        "Loan Interest Amount": "1096351.26",
        "Admin Fee Rate": "3.5",
        "Admin Fees Amount": "706913.24",
        "Loan Cover Rate": "3",
        "Loan Cover Amount": "605925.63",
        "Loan Amount Term": "656576.68",
        "Loan Amount Total": "5909190.13",
        "Next Payment Id": "5",
        "Worthness File": "66ba1478ad8906735.PNG",
        "Narration": "",
        "Loan Added By": "Sarudzai Zodetsa",
        "Loan Approved By": "Patrick Kalowekamo",
        "Approved Date": "2024-08-13 07:55:40",
        "Rejected By": "",
        "Rejected Date": "",
        "Loan Status": "CLOSED",
        "Disbursed": "Yes",
        "Disbursed By": "Patrick Kalowekamo",
        "Disbursed Date": "2024-08-13 08:46:06",
        "Written Off By": "",
        "Write Off Approved By": "",
        "Write Off Approval Date": "",
        "Written Off Date": "",
        "Loan Added Date": "2024-08-12 15:56:53",
        "": "",
        "status": ""
    },
    {
        "No": "24",
        "New  Loan Number": "SCL202409271950",
        "Old Loan Number": "SCL202408121727",
        "Loan Product": "Zitsamba",
        "Loan Customer": "Takonzeka(894)",
        "Loan Date": "2024-08-12",
        "Loan Principal": " 1,850,000.00 ",
        "Loan Period": "12",
        "Period Type": "Weekly",
        "Loan Interest Rate": "0",
        "Loan Interest Amount": "0",
        "Admin Fee Rate": "0",
        "Admin Fees Amount": "0",
        "Loan Cover Rate": "0",
        "Loan Cover Amount": "0",
        "Loan Amount Term": "192708.33",
        "Loan Amount Total": "2312500",
        "Next Payment Id": "7",
        "Worthness File": "66ba06bcbf7725565.jpg",
        "Narration": "",
        "Loan Added By": "Temwa Chawinga",
        "Loan Approved By": "Patrick Kalowekamo",
        "Approved Date": "2024-08-13 07:55:07",
        "Rejected By": "",
        "Rejected Date": "",
        "Loan Status": "CLOSED",
        "Disbursed": "Yes",
        "Disbursed By": "Patrick Kalowekamo",
        "Disbursed Date": "2024-08-13 09:50:55",
        "Written Off By": "",
        "Write Off Approved By": "",
        "Write Off Approval Date": "",
        "Written Off Date": "",
        "Loan Added Date": "2024-08-12 14:57:53",
        "": "",
        "status": ""
    },
    {
        "No": "25",
        "New  Loan Number": "SCL202409271951",
        "Old Loan Number": "SCL202408121726",
        "Loan Product": "Zitsamba",
        "Loan Customer": "Miracle(663)",
        "Loan Date": "2024-08-12",
        "Loan Principal": " 600,000.00 ",
        "Loan Period": "12",
        "Period Type": "Weekly",
        "Loan Interest Rate": "0",
        "Loan Interest Amount": "0",
        "Admin Fee Rate": "0",
        "Admin Fees Amount": "0",
        "Loan Cover Rate": "0",
        "Loan Cover Amount": "0",
        "Loan Amount Term": "62500",
        "Loan Amount Total": "750000",
        "Next Payment Id": "7",
        "Worthness File": "66ba05d5d5f5e5497.jpg",
        "Narration": "",
        "Loan Added By": "Christina Tauzie",
        "Loan Approved By": "Patrick Kalowekamo",
        "Approved Date": "2024-08-13 07:56:49",
        "Rejected By": "",
        "Rejected Date": "",
        "Loan Status": "CLOSED",
        "Disbursed": "Yes",
        "Disbursed By": "Harold Mwala",
        "Disbursed Date": "2024-09-16 07:42:40",
        "Written Off By": "",
        "Write Off Approved By": "",
        "Write Off Approval Date": "",
        "Written Off Date": "",
        "Loan Added Date": "2024-08-12 14:53:59",
        "": "",
        "status": ""
    },
    {
        "No": "26",
        "New  Loan Number": "SCL202409271952",
        "Old Loan Number": "SCL202408091719",
        "Loan Product": "Tsinde",
        "Loan Customer": "Timothy Mbewe (3355473)",
        "Loan Date": "2024-08-09",
        "Loan Principal": " 500,000.00 ",
        "Loan Period": "6",
        "Period Type": "Monthly",
        "Loan Interest Rate": "6",
        "Loan Interest Amount": "103218.88",
        "Admin Fee Rate": "3.5",
        "Admin Fees Amount": "67211.02",
        "Loan Cover Rate": "3",
        "Loan Cover Amount": "57609.44",
        "Loan Amount Term": "121339.89",
        "Loan Amount Total": "728039.34",
        "Next Payment Id": "3",
        "Worthness File": "66b63838809da8688.PNG",
        "Narration": "",
        "Loan Added By": "Bridget Nyanga",
        "Loan Approved By": "Patrick Kalowekamo",
        "Approved Date": "2024-08-13 07:56:44",
        "Rejected By": "",
        "Rejected Date": "",
        "Loan Status": "CLOSED",
        "Disbursed": "Yes",
        "Disbursed By": "Patrick Kalowekamo",
        "Disbursed Date": "2024-08-13 07:59:49",
        "Written Off By": "",
        "Write Off Approved By": "",
        "Write Off Approval Date": "",
        "Written Off Date": "",
        "Loan Added Date": "2024-08-09 17:39:39",
        "": "",
        "status": ""
    },
    {
        "No": "27",
        "New  Loan Number": "SCL202409271953",
        "Old Loan Number": "SCL202408091718",
        "Loan Product": "Masamba",
        "Loan Customer": "Magret Namajagali (9828098)",
        "Loan Date": "2024-08-09",
        "Loan Principal": " 600,000.00 ",
        "Loan Period": "6",
        "Period Type": "Monthly",
        "Loan Interest Rate": "6",
        "Loan Interest Amount": "138262.66",
        "Admin Fee Rate": "3.5",
        "Admin Fees Amount": "80653.22",
        "Loan Cover Rate": "3",
        "Loan Cover Amount": "69131.33",
        "Loan Amount Term": "148007.87",
        "Loan Amount Total": "888047.21",
        "Next Payment Id": "4",
        "Worthness File": "66b636b5643487989.PNG",
        "Narration": "",
        "Loan Added By": "ALEX KAMPUTA",
        "Loan Approved By": "Patrick Kalowekamo",
        "Approved Date": "2024-08-13 07:56:38",
        "Rejected By": "",
        "Rejected Date": "",
        "Loan Status": "CLOSED",
        "Disbursed": "Yes",
        "Disbursed By": "Patrick Kalowekamo",
        "Disbursed Date": "2024-08-13 07:59:58",
        "Written Off By": "",
        "Write Off Approved By": "",
        "Write Off Approval Date": "",
        "Written Off Date": "",
        "Loan Added Date": "2024-08-09 17:33:14",
        "": "",
        "status": ""
    },
    {
        "No": "28",
        "New  Loan Number": "SCL202409271954",
        "Old Loan Number": "SCL202408091715",
        "Loan Product": "Masamba",
        "Loan Customer": "Annie Zingwe (9472036)",
        "Loan Date": "2024-08-09",
        "Loan Principal": " 3,500,000.00 ",
        "Loan Period": "6",
        "Period Type": "Monthly",
        "Loan Interest Rate": "6",
        "Loan Interest Amount": "806532.19",
        "Admin Fee Rate": "3.5",
        "Admin Fees Amount": "470477.11",
        "Loan Cover Rate": "3",
        "Loan Cover Amount": "403266.1",
        "Loan Amount Term": "863379.23",
        "Loan Amount Total": "5180275.4",
        "Next Payment Id": "4",
        "Worthness File": "66b6341fb6da27923.PNG",
        "Narration": "",
        "Loan Added By": "Mphatso Kachere",
        "Loan Approved By": "Patrick Kalowekamo",
        "Approved Date": "2024-08-13 07:55:59",
        "Rejected By": "",
        "Rejected Date": "",
        "Loan Status": "CLOSED",
        "Disbursed": "Yes",
        "Disbursed By": "Patrick Kalowekamo",
        "Disbursed Date": "2024-08-13 07:59:15",
        "Written Off By": "",
        "Write Off Approved By": "",
        "Write Off Approval Date": "",
        "Written Off Date": "",
        "Loan Added Date": "2024-08-09 17:22:11",
        "": "",
        "status": ""
    },
    {
        "No": "29",
        "New  Loan Number": "SCL202409271955",
        "Old Loan Number": "SCL202408071713",
        "Loan Product": "Masamba",
        "Loan Customer": "Alindine Mulikha (4911410)",
        "Loan Date": "2024-08-07",
        "Loan Principal": " 1,500,000.00 ",
        "Loan Period": "6",
        "Period Type": "Monthly",
        "Loan Interest Rate": "6",
        "Loan Interest Amount": "345656.65",
        "Admin Fee Rate": "3.5",
        "Admin Fees Amount": "201633.05",
        "Loan Cover Rate": "3",
        "Loan Cover Amount": "172828.33",
        "Loan Amount Term": "370019.67",
        "Loan Amount Total": "2220118.03",
        "Next Payment Id": "4",
        "Worthness File": "66b34b02cbd522340.PNG",
        "Narration": "",
        "Loan Added By": "Bright Chinkombero",
        "Loan Approved By": "Patrick Kalowekamo",
        "Approved Date": "2024-08-13 07:55:55",
        "Rejected By": "",
        "Rejected Date": "",
        "Loan Status": "CLOSED",
        "Disbursed": "Yes",
        "Disbursed By": "Patrick Kalowekamo",
        "Disbursed Date": "2024-08-13 08:00:42",
        "Written Off By": "",
        "Write Off Approved By": "",
        "Write Off Approval Date": "",
        "Written Off Date": "",
        "Loan Added Date": "2024-08-07 12:23:01",
        "": "",
        "status": ""
    },
    {
        "No": "30",
        "New  Loan Number": "SCL202409271957",
        "Old Loan Number": "SCL202408071712",
        "Loan Product": "Masamba",
        "Loan Customer": "Trizer Banda (3866503)",
        "Loan Date": "2024-08-07",
        "Loan Principal": " 2,000,000.00 ",
        "Loan Period": "6",
        "Period Type": "Monthly",
        "Loan Interest Rate": "6",
        "Loan Interest Amount": "460875.54",
        "Admin Fee Rate": "3.5",
        "Admin Fees Amount": "268844.06",
        "Loan Cover Rate": "3",
        "Loan Cover Amount": "230437.77",
        "Loan Amount Term": "493359.56",
        "Loan Amount Total": "2960157.37",
        "Next Payment Id": "3",
        "Worthness File": "66b338cdc158a9947.PNG",
        "Narration": "",
        "Loan Added By": "Kelvin Chaguza",
        "Loan Approved By": "Patrick Kalowekamo",
        "Approved Date": "2024-08-13 07:55:37",
        "Rejected By": "",
        "Rejected Date": "",
        "Loan Status": "CLOSED",
        "Disbursed": "Yes",
        "Disbursed By": "Patrick Kalowekamo",
        "Disbursed Date": "2024-08-13 08:00:20",
        "Written Off By": "",
        "Write Off Approved By": "",
        "Write Off Approval Date": "",
        "Written Off Date": "",
        "Loan Added Date": "2024-08-07 11:05:25",
        "": "",
        "status": ""
    },
    {
        "No": "31",
        "New  Loan Number": "SCL202409271959",
        "Old Loan Number": "SCL202408071711",
        "Loan Product": "Masamba",
        "Loan Customer": "Liviel Juliano (3143157)",
        "Loan Date": "2024-08-07",
        "Loan Principal": " 1,000,000.00 ",
        "Loan Period": "6",
        "Period Type": "Monthly",
        "Loan Interest Rate": "6",
        "Loan Interest Amount": "230437.77",
        "Admin Fee Rate": "3.5",
        "Admin Fees Amount": "134422.03",
        "Loan Cover Rate": "3",
        "Loan Cover Amount": "115218.88",
        "Loan Amount Term": "246679.78",
        "Loan Amount Total": "1480078.69",
        "Next Payment Id": "3",
        "Worthness File": "66b33868e9f9e6191.PNG",
        "Narration": "",
        "Loan Added By": "ALEX KAMPUTA",
        "Loan Approved By": "Patrick Kalowekamo",
        "Approved Date": "2024-08-13 07:55:48",
        "Rejected By": "",
        "Rejected Date": "",
        "Loan Status": "CLOSED",
        "Disbursed": "Yes",
        "Disbursed By": "Patrick Kalowekamo",
        "Disbursed Date": "2024-08-13 08:00:09",
        "Written Off By": "",
        "Write Off Approved By": "",
        "Write Off Approval Date": "",
        "Written Off Date": "",
        "Loan Added Date": "2024-08-07 11:03:41",
        "": "",
        "status": ""
    },
    {
        "No": "32",
        "New  Loan Number": "SCL202409271960",
        "Old Loan Number": "SCL202408061706",
        "Loan Product": "Masamba",
        "Loan Customer": "Mphatso Mulolo (4225567)",
        "Loan Date": "2024-08-06",
        "Loan Principal": " 1,000,000.00 ",
        "Loan Period": "6",
        "Period Type": "Monthly",
        "Loan Interest Rate": "6",
        "Loan Interest Amount": "230437.77",
        "Admin Fee Rate": "3.5",
        "Admin Fees Amount": "134422.03",
        "Loan Cover Rate": "3",
        "Loan Cover Amount": "115218.88",
        "Loan Amount Term": "246679.78",
        "Loan Amount Total": "1480078.69",
        "Next Payment Id": "3",
        "Worthness File": "66b1f14673bda255.PNG",
        "Narration": "",
        "Loan Added By": "Bright Chinkombero",
        "Loan Approved By": "Patrick Kalowekamo",
        "Approved Date": "2024-08-13 07:55:24",
        "Rejected By": "",
        "Rejected Date": "",
        "Loan Status": "CLOSED",
        "Disbursed": "Yes",
        "Disbursed By": "Patrick Kalowekamo",
        "Disbursed Date": "2024-08-13 08:01:41",
        "Written Off By": "",
        "Write Off Approved By": "",
        "Write Off Approval Date": "",
        "Written Off Date": "",
        "Loan Added Date": "2024-08-06 11:47:54",
        "": "",
        "status": ""
    },
    {
        "No": "33",
        "New  Loan Number": "SCL202409271961",
        "Old Loan Number": "SCL202408061705",
        "Loan Product": "Masamba",
        "Loan Customer": "Emmanuel Mandiza (1591128)",
        "Loan Date": "2024-08-06",
        "Loan Principal": " 500,000.00 ",
        "Loan Period": "6",
        "Period Type": "Monthly",
        "Loan Interest Rate": "6",
        "Loan Interest Amount": "115218.88",
        "Admin Fee Rate": "3.5",
        "Admin Fees Amount": "67211.02",
        "Loan Cover Rate": "3",
        "Loan Cover Amount": "57609.44",
        "Loan Amount Term": "123339.89",
        "Loan Amount Total": "740039.34",
        "Next Payment Id": "3",
        "Worthness File": "66b1ea20405f0531.PNG",
        "Narration": "",
        "Loan Added By": "Bright Chinkombero",
        "Loan Approved By": "Patrick Kalowekamo",
        "Approved Date": "2024-08-13 07:55:16",
        "Rejected By": "",
        "Rejected Date": "",
        "Loan Status": "CLOSED",
        "Disbursed": "Yes",
        "Disbursed By": "Patrick Kalowekamo",
        "Disbursed Date": "2024-08-13 08:02:00",
        "Written Off By": "",
        "Write Off Approved By": "",
        "Write Off Approval Date": "",
        "Written Off Date": "",
        "Loan Added Date": "2024-08-06 11:19:27",
        "": "",
        "status": ""
    },
    {
        "No": "34",
        "New  Loan Number": "SCL202409271962",
        "Old Loan Number": "SCL202408061704",
        "Loan Product": "Tsinde",
        "Loan Customer": "Siseghe Mwenifumbo (1219899)",
        "Loan Date": "2024-08-06",
        "Loan Principal": " 550,000.00 ",
        "Loan Period": "6",
        "Period Type": "Monthly",
        "Loan Interest Rate": "6",
        "Loan Interest Amount": "118490.77",
        "Admin Fee Rate": "3.5",
        "Admin Fees Amount": "73932.12",
        "Loan Cover Rate": "3",
        "Loan Cover Amount": "63370.39",
        "Loan Amount Term": "134298.88",
        "Loan Amount Total": "805793.28",
        "Next Payment Id": "4",
        "Worthness File": "66b1e94cc3c5f5823.PNG",
        "Narration": "",
        "Loan Added By": "Sarudzai Zodetsa",
        "Loan Approved By": "Patrick Kalowekamo",
        "Approved Date": "2024-08-13 07:55:12",
        "Rejected By": "",
        "Rejected Date": "",
        "Loan Status": "CLOSED",
        "Disbursed": "Yes",
        "Disbursed By": "Patrick Kalowekamo",
        "Disbursed Date": "2024-08-13 08:02:19",
        "Written Off By": "",
        "Write Off Approved By": "",
        "Write Off Approval Date": "",
        "Written Off Date": "",
        "Loan Added Date": "2024-08-06 11:13:52",
        "": "",
        "status": ""
    },
    {
        "No": "35",
        "New  Loan Number": "SCL202409271963",
        "Old Loan Number": "SCL202408051702",
        "Loan Product": "Mizu",
        "Loan Customer": "Owen Manyamba (1786805)",
        "Loan Date": "2024-08-05",
        "Loan Principal": " 159,000.00 ",
        "Loan Period": "6",
        "Period Type": "Monthly",
        "Loan Interest Rate": "4.5",
        "Loan Interest Amount": "25615.87",
        "Admin Fee Rate": "3.5",
        "Admin Fees Amount": "21160.12",
        "Loan Cover Rate": "3",
        "Loan Cover Amount": "18137.25",
        "Loan Amount Term": "37318.87",
        "Loan Amount Total": "223913.24",
        "Next Payment Id": "4",
        "Worthness File": "66b0f2a4840674949.PNG",
        "Narration": "",
        "Loan Added By": "Bridget Nyanga",
        "Loan Approved By": "Patrick Kalowekamo",
        "Approved Date": "2024-08-13 07:54:59",
        "Rejected By": "",
        "Rejected Date": "",
        "Loan Status": "CLOSED",
        "Disbursed": "Yes",
        "Disbursed By": "Patrick Kalowekamo",
        "Disbursed Date": "2024-08-13 08:02:32",
        "Written Off By": "",
        "Write Off Approved By": "",
        "Write Off Approval Date": "",
        "Written Off Date": "",
        "Loan Added Date": "2024-08-05 17:41:30",
        "": "",
        "status": ""
    },
    {
        "No": "36",
        "New  Loan Number": "SCL202409271964",
        "Old Loan Number": "SCL202408051701",
        "Loan Product": "Mizu",
        "Loan Customer": "Francis Kanduwa (1357065)",
        "Loan Date": "2024-08-05",
        "Loan Principal": " 4,500,000.00 ",
        "Loan Period": "6",
        "Period Type": "Monthly",
        "Loan Interest Rate": "4.5",
        "Loan Interest Amount": "724977.5",
        "Admin Fee Rate": "3.5",
        "Admin Fees Amount": "598871.39",
        "Loan Cover Rate": "3",
        "Loan Cover Amount": "513318.33",
        "Loan Amount Term": "1056194.54",
        "Loan Amount Total": "6337167.22",
        "Next Payment Id": "4",
        "Worthness File": "66b0f017b8dce8159.PNG",
        "Narration": "",
        "Loan Added By": "Rose Samba",
        "Loan Approved By": "Patrick Kalowekamo",
        "Approved Date": "2024-08-13 07:54:51",
        "Rejected By": "",
        "Rejected Date": "",
        "Loan Status": "CLOSED",
        "Disbursed": "Yes",
        "Disbursed By": "Harold Mwala",
        "Disbursed Date": "2024-09-16 08:54:28",
        "Written Off By": "",
        "Write Off Approved By": "",
        "Write Off Approval Date": "",
        "Written Off Date": "",
        "Loan Added Date": "2024-08-05 17:30:38",
        "": "",
        "status": ""
    },
    {
        "No": "37",
        "New  Loan Number": "SCL202409271965",
        "Old Loan Number": "SCL202408051700",
        "Loan Product": "Mizu",
        "Loan Customer": "Symon Juma (2272308)",
        "Loan Date": "2024-08-05",
        "Loan Principal": " 424,000.00 ",
        "Loan Period": "6",
        "Period Type": "Monthly",
        "Loan Interest Rate": "4.5",
        "Loan Interest Amount": "68308.99",
        "Admin Fee Rate": "3.5",
        "Admin Fees Amount": "56426.99",
        "Loan Cover Rate": "3",
        "Loan Cover Amount": "48365.99",
        "Loan Amount Term": "99517",
        "Loan Amount Total": "597101.98",
        "Next Payment Id": "4",
        "Worthness File": "66b0ef2465856606.PNG",
        "Narration": "",
        "Loan Added By": "Bridget Nyanga",
        "Loan Approved By": "Patrick Kalowekamo",
        "Approved Date": "2024-08-13 07:54:45",
        "Rejected By": "",
        "Rejected Date": "",
        "Loan Status": "CLOSED",
        "Disbursed": "Yes",
        "Disbursed By": "Patrick Kalowekamo",
        "Disbursed Date": "2024-08-13 08:02:48",
        "Written Off By": "",
        "Write Off Approved By": "",
        "Write Off Approval Date": "",
        "Written Off Date": "",
        "Loan Added Date": "2024-08-05 17:26:34",
        "": "",
        "status": ""
    },
    {
        "No": "38",
        "New  Loan Number": "SCL202409271966",
        "Old Loan Number": "SCL202408021699",
        "Loan Product": "Zitsamba",
        "Loan Customer": "Mtengo Umodzi(986)",
        "Loan Date": "2024-08-02",
        "Loan Principal": " 1,500,000.00 ",
        "Loan Period": "12",
        "Period Type": "Weekly",
        "Loan Interest Rate": "0",
        "Loan Interest Amount": "0",
        "Admin Fee Rate": "0",
        "Admin Fees Amount": "0",
        "Loan Cover Rate": "0",
        "Loan Cover Amount": "0",
        "Loan Amount Term": "156250",
        "Loan Amount Total": "1875000",
        "Next Payment Id": "7",
        "Worthness File": "66acdb4860e233471.jpg",
        "Narration": "",
        "Loan Added By": "Temwa Chawinga",
        "Loan Approved By": "Patrick Kalowekamo",
        "Approved Date": "2024-08-02 17:01:17",
        "Rejected By": "",
        "Rejected Date": "",
        "Loan Status": "CLOSED",
        "Disbursed": "Yes",
        "Disbursed By": "Patrick Kalowekamo",
        "Disbursed Date": "2024-08-13 08:06:07",
        "Written Off By": "",
        "Write Off Approved By": "",
        "Write Off Approval Date": "",
        "Written Off Date": "",
        "Loan Added Date": "2024-08-02 15:13:08",
        "": "",
        "status": ""
    },
    {
        "No": "39",
        "New  Loan Number": "SCL202409271967",
        "Old Loan Number": "SCL202408021698",
        "Loan Product": "Zitsamba",
        "Loan Customer": "Razik(901)",
        "Loan Date": "2024-08-02",
        "Loan Principal": " 700,000.00 ",
        "Loan Period": "8",
        "Period Type": "Weekly",
        "Loan Interest Rate": "0",
        "Loan Interest Amount": "0",
        "Admin Fee Rate": "0",
        "Admin Fees Amount": "0",
        "Loan Cover Rate": "0",
        "Loan Cover Amount": "0",
        "Loan Amount Term": "109375",
        "Loan Amount Total": "875000",
        "Next Payment Id": "5",
        "Worthness File": "66acd9e8241b07557.jpg",
        "Narration": "",
        "Loan Added By": "Temwa Chawinga",
        "Loan Approved By": "Patrick Kalowekamo",
        "Approved Date": "2024-08-02 17:01:05",
        "Rejected By": "",
        "Rejected Date": "",
        "Loan Status": "CLOSED",
        "Disbursed": "Yes",
        "Disbursed By": "Patrick Kalowekamo",
        "Disbursed Date": "2024-08-13 08:08:04",
        "Written Off By": "",
        "Write Off Approved By": "",
        "Write Off Approval Date": "",
        "Written Off Date": "",
        "Loan Added Date": "2024-08-02 15:07:24",
        "": "",
        "status": ""
    },
    {
        "No": "40",
        "New  Loan Number": "SCL202409271968",
        "Old Loan Number": "SCL202408021695",
        "Loan Product": "Masamba",
        "Loan Customer": "Scholarstica Masangano (2852596)",
        "Loan Date": "2024-07-15",
        "Loan Principal": " 500,000.00 ",
        "Loan Period": "6",
        "Period Type": "Monthly",
        "Loan Interest Rate": "6",
        "Loan Interest Amount": "115218.88",
        "Admin Fee Rate": "3.5",
        "Admin Fees Amount": "67211.02",
        "Loan Cover Rate": "3",
        "Loan Cover Amount": "57609.44",
        "Loan Amount Term": "123339.89",
        "Loan Amount Total": "740039.34",
        "Next Payment Id": "3",
        "Worthness File": "66acad1b30f326448.PNG",
        "Narration": "",
        "Loan Added By": "ALEX KAMPUTA",
        "Loan Approved By": "Patrick Kalowekamo",
        "Approved Date": "2024-08-02 14:17:27",
        "Rejected By": "",
        "Rejected Date": "",
        "Loan Status": "CLOSED",
        "Disbursed": "Yes",
        "Disbursed By": "Patrick Kalowekamo",
        "Disbursed Date": "2024-08-13 08:08:17",
        "Written Off By": "",
        "Write Off Approved By": "",
        "Write Off Approval Date": "",
        "Written Off Date": "",
        "Loan Added Date": "2024-08-02 11:55:42",
        "": "",
        "status": ""
    },
    {
        "No": "41",
        "New  Loan Number": "SCL202409271969",
        "Old Loan Number": "SCL202408021694",
        "Loan Product": "Tsinde",
        "Loan Customer": "Mary Chisi (9901669)",
        "Loan Date": "2024-07-15",
        "Loan Principal": " 744,539.34 ",
        "Loan Period": "6",
        "Period Type": "Monthly",
        "Loan Interest Rate": "6",
        "Loan Interest Amount": "209541.49",
        "Admin Fee Rate": "3.5",
        "Admin Fees Amount": "100082.49",
        "Loan Cover Rate": "3",
        "Loan Cover Amount": "85784.99",
        "Loan Amount Term": "189991.39",
        "Loan Amount Total": "1139948.31",
        "Next Payment Id": "4",
        "Worthness File": "66acacc8374cf7872.PNG",
        "Narration": "",
        "Loan Added By": "Sarudzai Zodetsa",
        "Loan Approved By": "Patrick Kalowekamo",
        "Approved Date": "2024-08-02 14:17:14",
        "Rejected By": "",
        "Rejected Date": "",
        "Loan Status": "CLOSED",
        "Disbursed": "Yes",
        "Disbursed By": "Patrick Kalowekamo",
        "Disbursed Date": "2024-08-13 08:08:46",
        "Written Off By": "",
        "Write Off Approved By": "",
        "Write Off Approval Date": "",
        "Written Off Date": "",
        "Loan Added Date": "2024-08-02 11:54:22",
        "": "",
        "status": ""
    },
    {
        "No": "42",
        "New  Loan Number": "SCL202409271971",
        "Old Loan Number": "SCL202408021692",
        "Loan Product": "Zitsamba",
        "Loan Customer": "Zuze(435)",
        "Loan Date": "2024-07-15",
        "Loan Principal": " 2,000,000.00 ",
        "Loan Period": "12",
        "Period Type": "Weekly",
        "Loan Interest Rate": "0",
        "Loan Interest Amount": "0",
        "Admin Fee Rate": "0",
        "Admin Fees Amount": "0",
        "Loan Cover Rate": "0",
        "Loan Cover Amount": "0",
        "Loan Amount Term": "208333.33",
        "Loan Amount Total": "2500000",
        "Next Payment Id": "7",
        "Worthness File": "66aca2153cbca1233.PNG",
        "Narration": "",
        "Loan Added By": "Temwa Chawinga",
        "Loan Approved By": "Patrick Kalowekamo",
        "Approved Date": "2024-08-02 14:16:05",
        "Rejected By": "",
        "Rejected Date": "",
        "Loan Status": "CLOSED",
        "Disbursed": "Yes",
        "Disbursed By": "Patrick Kalowekamo",
        "Disbursed Date": "2024-08-13 08:08:57",
        "Written Off By": "",
        "Write Off Approved By": "",
        "Write Off Approval Date": "",
        "Written Off Date": "",
        "Loan Added Date": "2024-08-02 11:08:47",
        "": "",
        "status": ""
    },
    {
        "No": "43",
        "New  Loan Number": "SCL202409271972",
        "Old Loan Number": "SCL202408021691",
        "Loan Product": "Zitsamba",
        "Loan Customer": "Onions(947)",
        "Loan Date": "2024-07-15",
        "Loan Principal": " 550,000.00 ",
        "Loan Period": "8",
        "Period Type": "Weekly",
        "Loan Interest Rate": "0",
        "Loan Interest Amount": "0",
        "Admin Fee Rate": "0",
        "Admin Fees Amount": "0",
        "Loan Cover Rate": "0",
        "Loan Cover Amount": "0",
        "Loan Amount Term": "85937.5",
        "Loan Amount Total": "687500",
        "Next Payment Id": "5",
        "Worthness File": "66aca1bbcf5961641.PNG",
        "Narration": "",
        "Loan Added By": "Temwa Chawinga",
        "Loan Approved By": "Patrick Kalowekamo",
        "Approved Date": "2024-08-02 14:15:57",
        "Rejected By": "",
        "Rejected Date": "",
        "Loan Status": "CLOSED",
        "Disbursed": "Yes",
        "Disbursed By": "Patrick Kalowekamo",
        "Disbursed Date": "2024-08-13 08:09:21",
        "Written Off By": "",
        "Write Off Approved By": "",
        "Write Off Approval Date": "",
        "Written Off Date": "",
        "Loan Added Date": "2024-08-02 11:07:12",
        "": "",
        "status": ""
    },
    {
        "No": "44",
        "New  Loan Number": "SCL20230817101",
        "Old Loan Number": "SCL202408021690",
        "Loan Product": "Zitsamba",
        "Loan Customer": "MAKANDE GROUP(762)",
        "Loan Date": "2024-07-12",
        "Loan Principal": " 600,000.00 ",
        "Loan Period": "8",
        "Period Type": "Weekly",
        "Loan Interest Rate": "0",
        "Loan Interest Amount": "0",
        "Admin Fee Rate": "0",
        "Admin Fees Amount": "0",
        "Loan Cover Rate": "0",
        "Loan Cover Amount": "0",
        "Loan Amount Term": "93750",
        "Loan Amount Total": "750000",
        "Next Payment Id": "5",
        "Worthness File": "66ac9d9241c9f7471.PNG",
        "Narration": "",
        "Loan Added By": "Temwa Chawinga",
        "Loan Approved By": "Patrick Kalowekamo",
        "Approved Date": "2024-08-02 10:59:08",
        "Rejected By": "",
        "Rejected Date": "",
        "Loan Status": "CLOSED",
        "Disbursed": "Yes",
        "Disbursed By": "Patrick Kalowekamo",
        "Disbursed Date": "2024-08-02 10:59:25",
        "Written Off By": "",
        "Write Off Approved By": "",
        "Write Off Approval Date": "",
        "Written Off Date": "",
        "Loan Added Date": "2024-08-02 10:49:48",
        "": "",
        "status": ""
    },
    {
        "No": "45",
        "New  Loan Number": "SCL202409271975",
        "Old Loan Number": "SCL202408021689",
        "Loan Product": "Zitsamba",
        "Loan Customer": "Lufeyoh(188)",
        "Loan Date": "2024-07-11",
        "Loan Principal": " 1,200,000.00 ",
        "Loan Period": "12",
        "Period Type": "Weekly",
        "Loan Interest Rate": "0",
        "Loan Interest Amount": "0",
        "Admin Fee Rate": "0",
        "Admin Fees Amount": "0",
        "Loan Cover Rate": "0",
        "Loan Cover Amount": "0",
        "Loan Amount Term": "125000",
        "Loan Amount Total": "1500000",
        "Next Payment Id": "7",
        "Worthness File": "66ac9a541b3a56451.PNG",
        "Narration": "",
        "Loan Added By": "Grey Magombo",
        "Loan Approved By": "Patrick Kalowekamo",
        "Approved Date": "2024-08-02 10:59:02",
        "Rejected By": "",
        "Rejected Date": "",
        "Loan Status": "CLOSED",
        "Disbursed": "Yes",
        "Disbursed By": "Patrick Kalowekamo",
        "Disbursed Date": "2024-08-02 10:59:35",
        "Written Off By": "",
        "Write Off Approved By": "",
        "Write Off Approval Date": "",
        "Written Off Date": "",
        "Loan Added Date": "2024-08-02 10:35:35",
        "": "",
        "status": ""
    },
    {
        "No": "46",
        "New  Loan Number": "SCL202409271976",
        "Old Loan Number": "SCL202408021688",
        "Loan Product": "Masamba",
        "Loan Customer": "Lameck kalavina (6371708)",
        "Loan Date": "2024-07-12",
        "Loan Principal": " 6,000,000.00 ",
        "Loan Period": "6",
        "Period Type": "Monthly",
        "Loan Interest Rate": "6",
        "Loan Interest Amount": "1382626.62",
        "Admin Fee Rate": "3.5",
        "Admin Fees Amount": "806532.19",
        "Loan Cover Rate": "3",
        "Loan Cover Amount": "691313.31",
        "Loan Amount Term": "1480078.69",
        "Loan Amount Total": "8880472.12",
        "Next Payment Id": "4",
        "Worthness File": "66ac999e419518997.PNG",
        "Narration": "",
        "Loan Added By": "Bright Chinkombero",
        "Loan Approved By": "Patrick Kalowekamo",
        "Approved Date": "2024-08-02 10:58:56",
        "Rejected By": "",
        "Rejected Date": "",
        "Loan Status": "CLOSED",
        "Disbursed": "Yes",
        "Disbursed By": "Patrick Kalowekamo",
        "Disbursed Date": "2024-08-02 11:00:09",
        "Written Off By": "",
        "Write Off Approved By": "",
        "Write Off Approval Date": "",
        "Written Off Date": "",
        "Loan Added Date": "2024-08-02 10:32:36",
        "": "",
        "status": ""
    },
    {
        "No": "47",
        "New  Loan Number": "SCL202409271977",
        "Old Loan Number": "SCL202408021686",
        "Loan Product": "Mizu",
        "Loan Customer": "Steve Nyangatayani (1976918)",
        "Loan Date": "2024-07-10",
        "Loan Principal": " 800,000.00 ",
        "Loan Period": "6",
        "Period Type": "Monthly",
        "Loan Interest Rate": "4.5",
        "Loan Interest Amount": "118884.89",
        "Admin Fee Rate": "3.5",
        "Admin Fees Amount": "106466.02",
        "Loan Cover Rate": "3",
        "Loan Cover Amount": "91256.59",
        "Loan Amount Term": "186101.25",
        "Loan Amount Total": "1116607.51",
        "Next Payment Id": "4",
        "Worthness File": "66ac982a345658955.PNG",
        "Narration": "",
        "Loan Added By": "Bridget Nyanga",
        "Loan Approved By": "Patrick Kalowekamo",
        "Approved Date": "2024-08-02 10:58:22",
        "Rejected By": "",
        "Rejected Date": "",
        "Loan Status": "CLOSED",
        "Disbursed": "Yes",
        "Disbursed By": "Patrick Kalowekamo",
        "Disbursed Date": "2024-08-02 11:01:22",
        "Written Off By": "",
        "Write Off Approved By": "",
        "Write Off Approval Date": "",
        "Written Off Date": "",
        "Loan Added Date": "2024-08-02 10:26:22",
        "": "",
        "status": ""
    },
    {
        "No": "48",
        "New  Loan Number": "SCL202409271978",
        "Old Loan Number": "SCL202408021685",
        "Loan Product": "Masamba",
        "Loan Customer": "Joan Kalawa (9582539)",
        "Loan Date": "2024-07-10",
        "Loan Principal": " 1,000,000.00 ",
        "Loan Period": "6",
        "Period Type": "Monthly",
        "Loan Interest Rate": "6",
        "Loan Interest Amount": "230437.77",
        "Admin Fee Rate": "3.5",
        "Admin Fees Amount": "134422.03",
        "Loan Cover Rate": "3",
        "Loan Cover Amount": "115218.88",
        "Loan Amount Term": "246679.78",
        "Loan Amount Total": "1480078.69",
        "Next Payment Id": "3",
        "Worthness File": "66ac97d6beeae8808.PNG",
        "Narration": "",
        "Loan Added By": "Kelvin Chaguza",
        "Loan Approved By": "Patrick Kalowekamo",
        "Approved Date": "2024-08-02 10:58:12",
        "Rejected By": "",
        "Rejected Date": "",
        "Loan Status": "CLOSED",
        "Disbursed": "Yes",
        "Disbursed By": "Patrick Kalowekamo",
        "Disbursed Date": "2024-08-02 11:01:37",
        "Written Off By": "",
        "Write Off Approved By": "",
        "Write Off Approval Date": "",
        "Written Off Date": "",
        "Loan Added Date": "2024-08-02 10:24:58",
        "": "",
        "status": ""
    },
    {
        "No": "49",
        "New  Loan Number": "SCL202409271979",
        "Old Loan Number": "SCL202408021684",
        "Loan Product": "Mizu",
        "Loan Customer": "Vitumbiko Chirwa (1535456)",
        "Loan Date": "2024-07-10",
        "Loan Principal": " 740,000.00 ",
        "Loan Period": "6",
        "Period Type": "Monthly",
        "Loan Interest Rate": "4.5",
        "Loan Interest Amount": "109968.52",
        "Admin Fee Rate": "3.5",
        "Admin Fees Amount": "98481.07",
        "Loan Cover Rate": "3",
        "Loan Cover Amount": "84412.35",
        "Loan Amount Term": "172143.66",
        "Loan Amount Total": "1032861.94",
        "Next Payment Id": "3",
        "Worthness File": "66ac978cbd2851504.PNG",
        "Narration": "",
        "Loan Added By": "Bridget Nyanga",
        "Loan Approved By": "Patrick Kalowekamo",
        "Approved Date": "2024-08-02 10:58:02",
        "Rejected By": "",
        "Rejected Date": "",
        "Loan Status": "CLOSED",
        "Disbursed": "Yes",
        "Disbursed By": "Patrick Kalowekamo",
        "Disbursed Date": "2024-08-02 11:01:47",
        "Written Off By": "",
        "Write Off Approved By": "",
        "Write Off Approval Date": "",
        "Written Off Date": "",
        "Loan Added Date": "2024-08-02 10:23:45",
        "": "",
        "status": ""
    },
    {
        "No": "50",
        "New  Loan Number": "SCL202409271980",
        "Old Loan Number": "SCL202408021683",
        "Loan Product": "Mizu",
        "Loan Customer": "Blessings Witman (7604798)",
        "Loan Date": "2024-07-10",
        "Loan Principal": " 106,000.00 ",
        "Loan Period": "3",
        "Period Type": "Monthly",
        "Loan Interest Rate": "4.5",
        "Loan Interest Amount": "7486.26",
        "Admin Fee Rate": "3.5",
        "Admin Fees Amount": "7677.65",
        "Loan Cover Rate": "3",
        "Loan Cover Amount": "6580.84",
        "Loan Amount Term": "42581.59",
        "Loan Amount Total": "127744.76",
        "Next Payment Id": "3",
        "Worthness File": "66ac95b1f39c79611.PNG",
        "Narration": "",
        "Loan Added By": "Bridget Nyanga",
        "Loan Approved By": "Patrick Kalowekamo",
        "Approved Date": "2024-08-02 10:57:56",
        "Rejected By": "",
        "Rejected Date": "",
        "Loan Status": "CLOSED",
        "Disbursed": "Yes",
        "Disbursed By": "Patrick Kalowekamo",
        "Disbursed Date": "2024-08-02 11:01:59",
        "Written Off By": "",
        "Write Off Approved By": "",
        "Write Off Approval Date": "",
        "Written Off Date": "",
        "Loan Added Date": "2024-08-02 10:15:51",
        "": "",
        "status": ""
    },
    {
        "No": "51",
        "New  Loan Number": "SCL202409271981",
        "Old Loan Number": "SCL202408021682",
        "Loan Product": "Mizu",
        "Loan Customer": "Chikondi Kalavina (1666119)",
        "Loan Date": "2024-07-10",
        "Loan Principal": " 106,000.00 ",
        "Loan Period": "3",
        "Period Type": "Monthly",
        "Loan Interest Rate": "4.5",
        "Loan Interest Amount": "7486.26",
        "Admin Fee Rate": "3.5",
        "Admin Fees Amount": "7677.65",
        "Loan Cover Rate": "3",
        "Loan Cover Amount": "6580.84",
        "Loan Amount Term": "42581.59",
        "Loan Amount Total": "127744.76",
        "Next Payment Id": "3",
        "Worthness File": "66ac953263c3b5125.PNG",
        "Narration": "",
        "Loan Added By": "Bridget Nyanga",
        "Loan Approved By": "Patrick Kalowekamo",
        "Approved Date": "2024-08-02 10:57:51",
        "Rejected By": "",
        "Rejected Date": "",
        "Loan Status": "CLOSED",
        "Disbursed": "Yes",
        "Disbursed By": "Patrick Kalowekamo",
        "Disbursed Date": "2024-08-02 11:02:10",
        "Written Off By": "",
        "Write Off Approved By": "",
        "Write Off Approval Date": "",
        "Written Off Date": "",
        "Loan Added Date": "2024-08-02 10:13:50",
        "": "",
        "status": ""
    },
    {
        "No": "52",
        "New  Loan Number": "SCL202409271982",
        "Old Loan Number": "SCL202408021681",
        "Loan Product": "Zitsamba",
        "Loan Customer": "Sapesa(681)",
        "Loan Date": "2024-07-10",
        "Loan Principal": " 750,000.00 ",
        "Loan Period": "12",
        "Period Type": "Weekly",
        "Loan Interest Rate": "0",
        "Loan Interest Amount": "0",
        "Admin Fee Rate": "0",
        "Admin Fees Amount": "0",
        "Loan Cover Rate": "0",
        "Loan Cover Amount": "0",
        "Loan Amount Term": "78125",
        "Loan Amount Total": "937500",
        "Next Payment Id": "7",
        "Worthness File": "66ac92bd4bed49510.PNG",
        "Narration": "",
        "Loan Added By": "Grey Magombo",
        "Loan Approved By": "Patrick Kalowekamo",
        "Approved Date": "2024-08-02 10:57:35",
        "Rejected By": "",
        "Rejected Date": "",
        "Loan Status": "CLOSED",
        "Disbursed": "Yes",
        "Disbursed By": "Patrick Kalowekamo",
        "Disbursed Date": "2024-08-02 11:02:19",
        "Written Off By": "",
        "Write Off Approved By": "",
        "Write Off Approval Date": "",
        "Written Off Date": "",
        "Loan Added Date": "2024-08-02 10:03:18",
        "": "",
        "status": ""
    },
    {
        "No": "53",
        "New  Loan Number": "SCL202409271983",
        "Old Loan Number": "SCL202408011680",
        "Loan Product": "Zitsamba",
        "Loan Customer": "Tonse(558)",
        "Loan Date": "2024-07-10",
        "Loan Principal": " 1,050,000.00 ",
        "Loan Period": "12",
        "Period Type": "Weekly",
        "Loan Interest Rate": "0",
        "Loan Interest Amount": "0",
        "Admin Fee Rate": "0",
        "Admin Fees Amount": "0",
        "Loan Cover Rate": "0",
        "Loan Cover Amount": "0",
        "Loan Amount Term": "109375",
        "Loan Amount Total": "1312500",
        "Next Payment Id": "7",
        "Worthness File": "66ab9550a96879658.PNG",
        "Narration": "",
        "Loan Added By": "Grey Magombo",
        "Loan Approved By": "Patrick Kalowekamo",
        "Approved Date": "2024-08-02 10:57:30",
        "Rejected By": "",
        "Rejected Date": "",
        "Loan Status": "CLOSED",
        "Disbursed": "Yes",
        "Disbursed By": "Patrick Kalowekamo",
        "Disbursed Date": "2024-08-02 11:02:30",
        "Written Off By": "",
        "Write Off Approved By": "",
        "Write Off Approval Date": "",
        "Written Off Date": "",
        "Loan Added Date": "2024-08-01 16:01:59",
        "": "",
        "status": ""
    },
    {
        "No": "54",
        "New  Loan Number": "SCL202409271984",
        "Old Loan Number": "SCL202408011679",
        "Loan Product": "Zitsamba",
        "Loan Customer": "Maoni(890)",
        "Loan Date": "2024-07-10",
        "Loan Principal": " 1,700,000.00 ",
        "Loan Period": "12",
        "Period Type": "Weekly",
        "Loan Interest Rate": "0",
        "Loan Interest Amount": "0",
        "Admin Fee Rate": "0",
        "Admin Fees Amount": "0",
        "Loan Cover Rate": "0",
        "Loan Cover Amount": "0",
        "Loan Amount Term": "177083.33",
        "Loan Amount Total": "2125000",
        "Next Payment Id": "7",
        "Worthness File": "66ab94f9dace7973.PNG",
        "Narration": "",
        "Loan Added By": "Temwa Chawinga",
        "Loan Approved By": "Patrick Kalowekamo",
        "Approved Date": "2024-08-02 10:57:25",
        "Rejected By": "",
        "Rejected Date": "",
        "Loan Status": "CLOSED",
        "Disbursed": "Yes",
        "Disbursed By": "Patrick Kalowekamo",
        "Disbursed Date": "2024-08-02 11:02:46",
        "Written Off By": "",
        "Write Off Approved By": "",
        "Write Off Approval Date": "",
        "Written Off Date": "",
        "Loan Added Date": "2024-08-01 16:00:31",
        "": "",
        "status": ""
    },
    {
        "No": "55",
        "New  Loan Number": "SCL202409271985",
        "Old Loan Number": "SCL202408011678",
        "Loan Product": "Zitsamba",
        "Loan Customer": "Gombwa(233)",
        "Loan Date": "2024-07-10",
        "Loan Principal": " 1,500,000.00 ",
        "Loan Period": "12",
        "Period Type": "Weekly",
        "Loan Interest Rate": "0",
        "Loan Interest Amount": "0",
        "Admin Fee Rate": "0",
        "Admin Fees Amount": "0",
        "Loan Cover Rate": "0",
        "Loan Cover Amount": "0",
        "Loan Amount Term": "156250",
        "Loan Amount Total": "1875000",
        "Next Payment Id": "7",
        "Worthness File": "66ab947e94f0b2732.PNG",
        "Narration": "",
        "Loan Added By": "Temwa Chawinga",
        "Loan Approved By": "Patrick Kalowekamo",
        "Approved Date": "2024-08-02 10:57:19",
        "Rejected By": "",
        "Rejected Date": "",
        "Loan Status": "CLOSED",
        "Disbursed": "Yes",
        "Disbursed By": "Patrick Kalowekamo",
        "Disbursed Date": "2024-08-02 11:03:01",
        "Written Off By": "",
        "Write Off Approved By": "",
        "Write Off Approval Date": "",
        "Written Off Date": "",
        "Loan Added Date": "2024-08-01 15:58:29",
        "": "",
        "status": ""
    },
    {
        "No": "56",
        "New  Loan Number": "SCL202409271986",
        "Old Loan Number": "SCL202408011677",
        "Loan Product": "Masamba",
        "Loan Customer": "Mary Matiya (6948990)",
        "Loan Date": "2024-07-26",
        "Loan Principal": " 3,000,000.00 ",
        "Loan Period": "6",
        "Period Type": "Monthly",
        "Loan Interest Rate": "6",
        "Loan Interest Amount": "691313.31",
        "Admin Fee Rate": "3.5",
        "Admin Fees Amount": "403266.1",
        "Loan Cover Rate": "3",
        "Loan Cover Amount": "345656.65",
        "Loan Amount Term": "740039.34",
        "Loan Amount Total": "4440236.06",
        "Next Payment Id": "4",
        "Worthness File": "66ab8cf93f9798546.PNG",
        "Narration": "",
        "Loan Added By": "Reuben Ngomwa",
        "Loan Approved By": "Patrick Kalowekamo",
        "Approved Date": "2024-08-01 15:28:05",
        "Rejected By": "",
        "Rejected Date": "",
        "Loan Status": "CLOSED",
        "Disbursed": "Yes",
        "Disbursed By": "Patrick Kalowekamo",
        "Disbursed Date": "2024-08-01 15:28:26",
        "Written Off By": "",
        "Write Off Approved By": "",
        "Write Off Approval Date": "",
        "Written Off Date": "",
        "Loan Added Date": "2024-08-01 15:26:24",
        "": "",
        "status": ""
    },
    {
        "No": "57",
        "New  Loan Number": "SCL202409271987",
        "Old Loan Number": "SCL202408011676",
        "Loan Product": "Zitsamba",
        "Loan Customer": "kadokota(299)",
        "Loan Date": "2024-07-05",
        "Loan Principal": " 290,000.00 ",
        "Loan Period": "8",
        "Period Type": "Weekly",
        "Loan Interest Rate": "0",
        "Loan Interest Amount": "0",
        "Admin Fee Rate": "0",
        "Admin Fees Amount": "0",
        "Loan Cover Rate": "0",
        "Loan Cover Amount": "0",
        "Loan Amount Term": "45312.5",
        "Loan Amount Total": "362500",
        "Next Payment Id": "5",
        "Worthness File": "66ab8a96010399336.PNG",
        "Narration": "",
        "Loan Added By": "Mabvuto Jere",
        "Loan Approved By": "Patrick Kalowekamo",
        "Approved Date": "2024-08-01 15:28:16",
        "Rejected By": "",
        "Rejected Date": "",
        "Loan Status": "CLOSED",
        "Disbursed": "Yes",
        "Disbursed By": "Patrick Kalowekamo",
        "Disbursed Date": "2024-08-01 15:28:37",
        "Written Off By": "",
        "Write Off Approved By": "",
        "Write Off Approval Date": "",
        "Written Off Date": "",
        "Loan Added Date": "2024-08-01 15:16:34",
        "": "",
        "status": ""
    },
    {
        "No": "58",
        "New  Loan Number": "SCL202409271988",
        "Old Loan Number": "SCL202408011675",
        "Loan Product": "Zitsamba",
        "Loan Customer": "Mayera(618)",
        "Loan Date": "2024-07-05",
        "Loan Principal": " 210,000.00 ",
        "Loan Period": "8",
        "Period Type": "Weekly",
        "Loan Interest Rate": "0",
        "Loan Interest Amount": "0",
        "Admin Fee Rate": "0",
        "Admin Fees Amount": "0",
        "Loan Cover Rate": "0",
        "Loan Cover Amount": "0",
        "Loan Amount Term": "32812.5",
        "Loan Amount Total": "262500",
        "Next Payment Id": "5",
        "Worthness File": "66ab8a1a804fc1784.PNG",
        "Narration": "",
        "Loan Added By": "Mabvuto Jere",
        "Loan Approved By": "Patrick Kalowekamo",
        "Approved Date": "2024-08-01 15:28:10",
        "Rejected By": "",
        "Rejected Date": "",
        "Loan Status": "CLOSED",
        "Disbursed": "Yes",
        "Disbursed By": "Patrick Kalowekamo",
        "Disbursed Date": "2024-08-01 15:28:48",
        "Written Off By": "",
        "Write Off Approved By": "",
        "Write Off Approval Date": "",
        "Written Off Date": "",
        "Loan Added Date": "2024-08-01 15:14:10",
        "": "",
        "status": ""
    },
    {
        "No": "59",
        "New  Loan Number": "SCL202409271989",
        "Old Loan Number": "SCL202408011674",
        "Loan Product": "Masamba",
        "Loan Customer": "Shira Mtaya (6662408)",
        "Loan Date": "2024-07-05",
        "Loan Principal": " 700,000.00 ",
        "Loan Period": "6",
        "Period Type": "Monthly",
        "Loan Interest Rate": "6",
        "Loan Interest Amount": "161306.44",
        "Admin Fee Rate": "3.5",
        "Admin Fees Amount": "94095.42",
        "Loan Cover Rate": "3",
        "Loan Cover Amount": "80653.22",
        "Loan Amount Term": "172675.85",
        "Loan Amount Total": "1036055.08",
        "Next Payment Id": "4",
        "Worthness File": "66ab89a288a9b8811.PNG",
        "Narration": "",
        "Loan Added By": "Yankho Chidule",
        "Loan Approved By": "Patrick Kalowekamo",
        "Approved Date": "2024-08-01 15:27:58",
        "Rejected By": "",
        "Rejected Date": "",
        "Loan Status": "CLOSED",
        "Disbursed": "Yes",
        "Disbursed By": "Patrick Kalowekamo",
        "Disbursed Date": "2024-08-01 15:28:59",
        "Written Off By": "",
        "Write Off Approved By": "",
        "Write Off Approval Date": "",
        "Written Off Date": "",
        "Loan Added Date": "2024-08-01 15:12:07",
        "": "",
        "status": ""
    },
    {
        "No": "60",
        "New  Loan Number": "SCL202409271990",
        "Old Loan Number": "SCL202408011673",
        "Loan Product": "Masamba",
        "Loan Customer": "Judith Wengawenga (7679178)",
        "Loan Date": "2024-07-23",
        "Loan Principal": " 1,000,000.00 ",
        "Loan Period": "6",
        "Period Type": "Monthly",
        "Loan Interest Rate": "6",
        "Loan Interest Amount": "230437.77",
        "Admin Fee Rate": "3.5",
        "Admin Fees Amount": "134422.03",
        "Loan Cover Rate": "3",
        "Loan Cover Amount": "115218.88",
        "Loan Amount Term": "246679.78",
        "Loan Amount Total": "1480078.69",
        "Next Payment Id": "3",
        "Worthness File": "66ab459acc06a4198.PNG",
        "Narration": "",
        "Loan Added By": "Kelvin Chaguza",
        "Loan Approved By": "Patrick Kalowekamo",
        "Approved Date": "2024-08-01 15:27:53",
        "Rejected By": "",
        "Rejected Date": "",
        "Loan Status": "CLOSED",
        "Disbursed": "Yes",
        "Disbursed By": "Patrick Kalowekamo",
        "Disbursed Date": "2024-08-01 15:29:11",
        "Written Off By": "",
        "Write Off Approved By": "",
        "Write Off Approval Date": "",
        "Written Off Date": "",
        "Loan Added Date": "2024-08-01 10:21:53",
        "": "",
        "status": ""
    },
    {
        "No": "61",
        "New  Loan Number": "SCL202409271991",
        "Old Loan Number": "SCL202407311672",
        "Loan Product": "Zitsamba",
        "Loan Customer": "Pakachere(432)",
        "Loan Date": "2024-07-02",
        "Loan Principal": " 270,000.00 ",
        "Loan Period": "8",
        "Period Type": "Weekly",
        "Loan Interest Rate": "0",
        "Loan Interest Amount": "0",
        "Admin Fee Rate": "0",
        "Admin Fees Amount": "0",
        "Loan Cover Rate": "0",
        "Loan Cover Amount": "0",
        "Loan Amount Term": "42187.5",
        "Loan Amount Total": "337500",
        "Next Payment Id": "5",
        "Worthness File": "66aa563e78b443951.PNG",
        "Narration": "",
        "Loan Added By": "Mabvuto Jere",
        "Loan Approved By": "Patrick Kalowekamo",
        "Approved Date": "2024-08-01 07:37:27",
        "Rejected By": "",
        "Rejected Date": "",
        "Loan Status": "CLOSED",
        "Disbursed": "Yes",
        "Disbursed By": "Patrick Kalowekamo",
        "Disbursed Date": "2024-08-01 14:26:05",
        "Written Off By": "",
        "Write Off Approved By": "",
        "Write Off Approval Date": "",
        "Written Off Date": "",
        "Loan Added Date": "2024-07-31 17:20:34",
        "": "",
        "status": ""
    },
    {
        "No": "62",
        "New  Loan Number": "SCL202409271992",
        "Old Loan Number": "SCL202407311671",
        "Loan Product": "Zitsamba",
        "Loan Customer": "Haviyola(898)",
        "Loan Date": "2024-07-02",
        "Loan Principal": " 480,000.00 ",
        "Loan Period": "8",
        "Period Type": "Weekly",
        "Loan Interest Rate": "0",
        "Loan Interest Amount": "0",
        "Admin Fee Rate": "0",
        "Admin Fees Amount": "0",
        "Loan Cover Rate": "0",
        "Loan Cover Amount": "0",
        "Loan Amount Term": "75000",
        "Loan Amount Total": "600000",
        "Next Payment Id": "5",
        "Worthness File": "66aa55cb4f5f64204.PNG",
        "Narration": "",
        "Loan Added By": "Grey Magombo",
        "Loan Approved By": "Patrick Kalowekamo",
        "Approved Date": "2024-08-01 07:37:21",
        "Rejected By": "",
        "Rejected Date": "",
        "Loan Status": "CLOSED",
        "Disbursed": "Yes",
        "Disbursed By": "Patrick Kalowekamo",
        "Disbursed Date": "2024-08-01 14:26:20",
        "Written Off By": "",
        "Write Off Approved By": "",
        "Write Off Approval Date": "",
        "Written Off Date": "",
        "Loan Added Date": "2024-07-31 17:18:45",
        "": "",
        "status": ""
    },
    {
        "No": "63",
        "New  Loan Number": "SCL202409271993",
        "Old Loan Number": "SCL202407311670",
        "Loan Product": "Zitsamba",
        "Loan Customer": "Mphatso(999)",
        "Loan Date": "2024-07-02",
        "Loan Principal": " 700,000.00 ",
        "Loan Period": "8",
        "Period Type": "Weekly",
        "Loan Interest Rate": "0",
        "Loan Interest Amount": "0",
        "Admin Fee Rate": "0",
        "Admin Fees Amount": "0",
        "Loan Cover Rate": "0",
        "Loan Cover Amount": "0",
        "Loan Amount Term": "109375",
        "Loan Amount Total": "875000",
        "Next Payment Id": "5",
        "Worthness File": "66aa54da35da1985.PNG",
        "Narration": "",
        "Loan Added By": "Grey Magombo",
        "Loan Approved By": "Patrick Kalowekamo",
        "Approved Date": "2024-08-01 07:37:15",
        "Rejected By": "",
        "Rejected Date": "",
        "Loan Status": "CLOSED",
        "Disbursed": "Yes",
        "Disbursed By": "Patrick Kalowekamo",
        "Disbursed Date": "2024-08-01 14:26:30",
        "Written Off By": "",
        "Write Off Approved By": "",
        "Write Off Approval Date": "",
        "Written Off Date": "",
        "Loan Added Date": "2024-07-31 17:14:38",
        "": "",
        "status": ""
    },
    {
        "No": "64",
        "New  Loan Number": "SCL202409271993",
        "Old Loan Number": "SCL202407311668",
        "Loan Product": "Zitsamba",
        "Loan Customer": "Nkolokosa(992)",
        "Loan Date": "2024-07-02",
        "Loan Principal": " 800,000.00 ",
        "Loan Period": "8",
        "Period Type": "Weekly",
        "Loan Interest Rate": "0",
        "Loan Interest Amount": "0",
        "Admin Fee Rate": "0",
        "Admin Fees Amount": "0",
        "Loan Cover Rate": "0",
        "Loan Cover Amount": "0",
        "Loan Amount Term": "125000",
        "Loan Amount Total": "1000000",
        "Next Payment Id": "5",
        "Worthness File": "66aa52e0bd6ff439.PNG",
        "Narration": "",
        "Loan Added By": "Grey Magombo",
        "Loan Approved By": "Patrick Kalowekamo",
        "Approved Date": "2024-08-01 07:37:04",
        "Rejected By": "",
        "Rejected Date": "",
        "Loan Status": "CLOSED",
        "Disbursed": "Yes",
        "Disbursed By": "Patrick Kalowekamo",
        "Disbursed Date": "2024-08-01 15:29:33",
        "Written Off By": "",
        "Write Off Approved By": "",
        "Write Off Approval Date": "",
        "Written Off Date": "",
        "Loan Added Date": "2024-07-31 17:06:36",
        "": "",
        "status": ""
    },
    {
        "No": "65",
        "New  Loan Number": "SCL202409271994",
        "Old Loan Number": "SCL202407311666",
        "Loan Product": "Zitsamba",
        "Loan Customer": "Nyama(717)",
        "Loan Date": "2024-07-31",
        "Loan Principal": " 3,100,000.00 ",
        "Loan Period": "12",
        "Period Type": "Weekly",
        "Loan Interest Rate": "0",
        "Loan Interest Amount": "0",
        "Admin Fee Rate": "0",
        "Admin Fees Amount": "0",
        "Loan Cover Rate": "0",
        "Loan Cover Amount": "0",
        "Loan Amount Term": "322916.67",
        "Loan Amount Total": "3875000",
        "Next Payment Id": "7",
        "Worthness File": "66aa25f3429d99318.jpg",
        "Narration": "",
        "Loan Added By": "Christina Tauzie",
        "Loan Approved By": "Patrick Kalowekamo",
        "Approved Date": "2024-08-01 07:36:52",
        "Rejected By": "",
        "Rejected Date": "",
        "Loan Status": "CLOSED",
        "Disbursed": "Yes",
        "Disbursed By": "Patrick Kalowekamo",
        "Disbursed Date": "2024-08-13 08:09:34",
        "Written Off By": "",
        "Write Off Approved By": "",
        "Write Off Approval Date": "",
        "Written Off Date": "",
        "Loan Added Date": "2024-07-31 13:54:49",
        "": "",
        "status": ""
    },
    {
        "No": "66",
        "New  Loan Number": "SCL202409271995",
        "Old Loan Number": "SCL202407311665",
        "Loan Product": "Masamba",
        "Loan Customer": "Geoffrey Malukula (2069222)",
        "Loan Date": "2024-07-01",
        "Loan Principal": " 800,000.00 ",
        "Loan Period": "6",
        "Period Type": "Monthly",
        "Loan Interest Rate": "6",
        "Loan Interest Amount": "184350.22",
        "Admin Fee Rate": "3.5",
        "Admin Fees Amount": "107537.63",
        "Loan Cover Rate": "3",
        "Loan Cover Amount": "92175.11",
        "Loan Amount Term": "197343.82",
        "Loan Amount Total": "1184062.95",
        "Next Payment Id": "3",
        "Worthness File": "66a9f6214142f3315.PNG",
        "Narration": "",
        "Loan Added By": "Bright Chinkombero",
        "Loan Approved By": "Patrick Kalowekamo",
        "Approved Date": "2024-08-01 07:36:45",
        "Rejected By": "",
        "Rejected Date": "",
        "Loan Status": "CLOSED",
        "Disbursed": "Yes",
        "Disbursed By": "Patrick Kalowekamo",
        "Disbursed Date": "2024-08-01 15:30:04",
        "Written Off By": "",
        "Write Off Approved By": "",
        "Write Off Approval Date": "",
        "Written Off Date": "",
        "Loan Added Date": "2024-07-31 10:30:31",
        "": "",
        "status": ""
    },
    {
        "No": "67",
        "New  Loan Number": "SCL202409271996",
        "Old Loan Number": "SCL202407311664",
        "Loan Product": "Mizu",
        "Loan Customer": "Donald Mwenda (4599559)",
        "Loan Date": "2024-07-03",
        "Loan Principal": " 1,060,000.00 ",
        "Loan Period": "6",
        "Period Type": "Monthly",
        "Loan Interest Rate": "4.5",
        "Loan Interest Amount": "176072.48",
        "Admin Fee Rate": "3.5",
        "Admin Fees Amount": "141067.48",
        "Loan Cover Rate": "3",
        "Loan Cover Amount": "120914.98",
        "Loan Amount Term": "249675.82",
        "Loan Amount Total": "1498054.94",
        "Next Payment Id": "3",
        "Worthness File": "66a9f570956c66202.PNG",
        "Narration": "",
        "Loan Added By": "Bridget Nyanga",
        "Loan Approved By": "Patrick Kalowekamo",
        "Approved Date": "2024-08-01 07:36:17",
        "Rejected By": "",
        "Rejected Date": "",
        "Loan Status": "CLOSED",
        "Disbursed": "Yes",
        "Disbursed By": "Patrick Kalowekamo",
        "Disbursed Date": "2024-08-01 15:30:18",
        "Written Off By": "",
        "Write Off Approved By": "",
        "Write Off Approval Date": "",
        "Written Off Date": "",
        "Loan Added Date": "2024-07-31 10:27:51",
        "": "",
        "status": ""
    },
    {
        "No": "68",
        "New  Loan Number": "SCL202409292002",
        "Old Loan Number": "SCL202407311663",
        "Loan Product": "Mizu",
        "Loan Customer": "Kennedy Kumbukani (5307069)",
        "Loan Date": "2024-07-01",
        "Loan Principal": " 180,000.00 ",
        "Loan Period": "4",
        "Period Type": "Monthly",
        "Loan Interest Rate": "4.5",
        "Loan Interest Amount": "21303.4",
        "Admin Fee Rate": "3.5",
        "Admin Fees Amount": "16569.31",
        "Loan Cover Rate": "3",
        "Loan Cover Amount": "14202.27",
        "Loan Amount Term": "58018.74",
        "Loan Amount Total": "232074.97",
        "Next Payment Id": "3",
        "Worthness File": "66a9f347e5cd67265.PNG",
        "Narration": "",
        "Loan Added By": "Bridget Nyanga",
        "Loan Approved By": "Patrick Kalowekamo",
        "Approved Date": "2024-08-01 07:36:11",
        "Rejected By": "",
        "Rejected Date": "",
        "Loan Status": "CLOSED",
        "Disbursed": "Yes",
        "Disbursed By": "Patrick Kalowekamo",
        "Disbursed Date": "2024-08-01 15:31:06",
        "Written Off By": "",
        "Write Off Approved By": "",
        "Write Off Approval Date": "",
        "Written Off Date": "",
        "Loan Added Date": "2024-07-31 10:18:21",
        "": "",
        "status": ""
    },
    {
        "No": "69",
        "New  Loan Number": "SCL202409292003",
        "Old Loan Number": "SCL202407311662",
        "Loan Product": "Masamba",
        "Loan Customer": "Chancy Ngwira (7732652)",
        "Loan Date": "2024-07-05",
        "Loan Principal": " 2,000,000.00 ",
        "Loan Period": "6",
        "Period Type": "Monthly",
        "Loan Interest Rate": "6",
        "Loan Interest Amount": "460875.54",
        "Admin Fee Rate": "3.5",
        "Admin Fees Amount": "268844.06",
        "Loan Cover Rate": "3",
        "Loan Cover Amount": "230437.77",
        "Loan Amount Term": "493359.56",
        "Loan Amount Total": "2960157.37",
        "Next Payment Id": "3",
        "Worthness File": "66a9f243d90499907.PNG",
        "Narration": "",
        "Loan Added By": "ALEX KAMPUTA",
        "Loan Approved By": "Patrick Kalowekamo",
        "Approved Date": "2024-08-01 07:36:04",
        "Rejected By": "",
        "Rejected Date": "",
        "Loan Status": "CLOSED",
        "Disbursed": "Yes",
        "Disbursed By": "Patrick Kalowekamo",
        "Disbursed Date": "2024-08-01 15:31:16",
        "Written Off By": "",
        "Write Off Approved By": "",
        "Write Off Approval Date": "",
        "Written Off Date": "",
        "Loan Added Date": "2024-07-31 10:14:00",
        "": "",
        "status": ""
    },
    {
        "No": "70",
        "New  Loan Number": "SCL202409292004",
        "Old Loan Number": "SCL202407311661",
        "Loan Product": "Masamba",
        "Loan Customer": "Lucy Moto (1931074)",
        "Loan Date": "2024-07-16",
        "Loan Principal": " 7,000,000.00 ",
        "Loan Period": "6",
        "Period Type": "Monthly",
        "Loan Interest Rate": "6",
        "Loan Interest Amount": "1613064.39",
        "Admin Fee Rate": "3.5",
        "Admin Fees Amount": "940954.23",
        "Loan Cover Rate": "3",
        "Loan Cover Amount": "806532.19",
        "Loan Amount Term": "1726758.47",
        "Loan Amount Total": "10360550.8",
        "Next Payment Id": "4",
        "Worthness File": "66a9ee559a2b98424.PNG",
        "Narration": "",
        "Loan Added By": "Bright Chinkombero",
        "Loan Approved By": "Patrick Kalowekamo",
        "Approved Date": "2024-07-31 09:58:28",
        "Rejected By": "",
        "Rejected Date": "",
        "Loan Status": "CLOSED",
        "Disbursed": "Yes",
        "Disbursed By": "Patrick Kalowekamo",
        "Disbursed Date": "2024-08-01 15:31:30",
        "Written Off By": "",
        "Write Off Approved By": "",
        "Write Off Approval Date": "",
        "Written Off Date": "",
        "Loan Added Date": "2024-07-31 09:57:13",
        "": "",
        "status": ""
    },
    {
        "No": "71",
        "New  Loan Number": "SCL202409292005",
        "Old Loan Number": "SCL202407261654",
        "Loan Product": "Zitsamba",
        "Loan Customer": "BASKET GROUP(874)",
        "Loan Date": "2024-07-26",
        "Loan Principal": " 2,400,000.00 ",
        "Loan Period": "12",
        "Period Type": "Weekly",
        "Loan Interest Rate": "0",
        "Loan Interest Amount": "0",
        "Admin Fee Rate": "0",
        "Admin Fees Amount": "0",
        "Loan Cover Rate": "0",
        "Loan Cover Amount": "0",
        "Loan Amount Term": "250000",
        "Loan Amount Total": "3000000",
        "Next Payment Id": "7",
        "Worthness File": "66a3ab6944dfd2205.jpg",
        "Narration": "",
        "Loan Added By": "Christina Tauzie",
        "Loan Approved By": "Patrick Kalowekamo",
        "Approved Date": "2024-07-30 08:14:32",
        "Rejected By": "",
        "Rejected Date": "",
        "Loan Status": "CLOSED",
        "Disbursed": "Yes",
        "Disbursed By": "Patrick Kalowekamo",
        "Disbursed Date": "2024-08-13 08:09:47",
        "Written Off By": "",
        "Write Off Approved By": "",
        "Write Off Approval Date": "",
        "Written Off Date": "",
        "Loan Added Date": "2024-07-26 15:58:22",
        "": "",
        "status": ""
    },
    {
        "No": "72",
        "New  Loan Number": "SCL202409292006",
        "Old Loan Number": "SCL202407261653",
        "Loan Product": "Zitsamba",
        "Loan Customer": "Women of Vour(904)",
        "Loan Date": "2024-07-26",
        "Loan Principal": " 1,800,000.00 ",
        "Loan Period": "12",
        "Period Type": "Weekly",
        "Loan Interest Rate": "0",
        "Loan Interest Amount": "0",
        "Admin Fee Rate": "0",
        "Admin Fees Amount": "0",
        "Loan Cover Rate": "0",
        "Loan Cover Amount": "0",
        "Loan Amount Term": "187500",
        "Loan Amount Total": "2250000",
        "Next Payment Id": "7",
        "Worthness File": "66a3a9698151b1569.jpg",
        "Narration": "",
        "Loan Added By": "Temwa Chawinga",
        "Loan Approved By": "Patrick Kalowekamo",
        "Approved Date": "2024-07-30 08:14:38",
        "Rejected By": "",
        "Rejected Date": "",
        "Loan Status": "CLOSED",
        "Disbursed": "Yes",
        "Disbursed By": "Patrick Kalowekamo",
        "Disbursed Date": "2024-08-13 08:09:59",
        "Written Off By": "",
        "Write Off Approved By": "",
        "Write Off Approval Date": "",
        "Written Off Date": "",
        "Loan Added Date": "2024-07-26 15:49:45",
        "": "",
        "status": ""
    },
    {
        "No": "73",
        "New  Loan Number": "SCL202409292007",
        "Old Loan Number": "SCL202407251648",
        "Loan Product": "Zitsamba",
        "Loan Customer": "Alibe Tsankho(272)",
        "Loan Date": "2024-07-25",
        "Loan Principal": " 800,000.00 ",
        "Loan Period": "12",
        "Period Type": "Weekly",
        "Loan Interest Rate": "0",
        "Loan Interest Amount": "0",
        "Admin Fee Rate": "0",
        "Admin Fees Amount": "0",
        "Loan Cover Rate": "0",
        "Loan Cover Amount": "0",
        "Loan Amount Term": "83333.33",
        "Loan Amount Total": "1000000",
        "Next Payment Id": "6",
        "Worthness File": "66a23e3606fbe1651.jpg",
        "Narration": "",
        "Loan Added By": "Christina Tauzie",
        "Loan Approved By": "Patrick Kalowekamo",
        "Approved Date": "2024-07-30 08:11:40",
        "Rejected By": "",
        "Rejected Date": "",
        "Loan Status": "CLOSED",
        "Disbursed": "Yes",
        "Disbursed By": "Patrick Kalowekamo",
        "Disbursed Date": "2024-08-13 08:10:11",
        "Written Off By": "",
        "Write Off Approved By": "",
        "Write Off Approval Date": "",
        "Written Off Date": "",
        "Loan Added Date": "2024-07-25 14:00:34",
        "": "",
        "status": ""
    },
    {
        "No": "74",
        "New  Loan Number": "SCL202409292008",
        "Old Loan Number": "SCL202407251647",
        "Loan Product": "Zitsamba",
        "Loan Customer": "Somba(489)",
        "Loan Date": "2024-07-25",
        "Loan Principal": " 650,000.00 ",
        "Loan Period": "12",
        "Period Type": "Weekly",
        "Loan Interest Rate": "0",
        "Loan Interest Amount": "0",
        "Admin Fee Rate": "0",
        "Admin Fees Amount": "0",
        "Loan Cover Rate": "0",
        "Loan Cover Amount": "0",
        "Loan Amount Term": "67708.33",
        "Loan Amount Total": "812500",
        "Next Payment Id": "6",
        "Worthness File": "66a23d038309f6165.jpg",
        "Narration": "",
        "Loan Added By": "Christina Tauzie",
        "Loan Approved By": "Patrick Kalowekamo",
        "Approved Date": "2024-07-26 17:27:39",
        "Rejected By": "",
        "Rejected Date": "",
        "Loan Status": "CLOSED",
        "Disbursed": "Yes",
        "Disbursed By": "Patrick Kalowekamo",
        "Disbursed Date": "2024-08-13 08:10:56",
        "Written Off By": "",
        "Write Off Approved By": "",
        "Write Off Approval Date": "",
        "Written Off Date": "",
        "Loan Added Date": "2024-07-25 13:55:05",
        "": "",
        "status": ""
    },
    {
        "No": "75",
        "New  Loan Number": "SCL202409292009",
        "Old Loan Number": "SCL202407251645",
        "Loan Product": "Tsinde",
        "Loan Customer": "Michael Ndaferankhande (9145311)",
        "Loan Date": "2024-07-04",
        "Loan Principal": " 6,000,000.00 ",
        "Loan Period": "6",
        "Period Type": "Monthly",
        "Loan Interest Rate": "6",
        "Loan Interest Amount": "1328626.62",
        "Admin Fee Rate": "3.5",
        "Admin Fees Amount": "806532.19",
        "Loan Cover Rate": "3",
        "Loan Cover Amount": "691313.31",
        "Loan Amount Term": "1471078.69",
        "Loan Amount Total": "8826472.12",
        "Next Payment Id": "4",
        "Worthness File": "66a22246e080c7575.PNG",
        "Narration": "",
        "Loan Added By": "Rose Samba",
        "Loan Approved By": "Patrick Kalowekamo",
        "Approved Date": "2024-07-26 17:27:27",
        "Rejected By": "",
        "Rejected Date": "",
        "Loan Status": "CLOSED",
        "Disbursed": "Yes",
        "Disbursed By": "Patrick Kalowekamo",
        "Disbursed Date": "2024-08-01 15:32:04",
        "Written Off By": "",
        "Write Off Approved By": "",
        "Write Off Approval Date": "",
        "Written Off Date": "",
        "Loan Added Date": "2024-07-25 12:00:43",
        "": "",
        "status": ""
    },
    {
        "No": "76",
        "New  Loan Number": "SCL202409292010",
        "Old Loan Number": "SCL202407241643",
        "Loan Product": "Zitsamba",
        "Loan Customer": "Mthuzi Wowala(978)",
        "Loan Date": "2024-07-24",
        "Loan Principal": " 1,250,000.00 ",
        "Loan Period": "12",
        "Period Type": "Weekly",
        "Loan Interest Rate": "0",
        "Loan Interest Amount": "0",
        "Admin Fee Rate": "0",
        "Admin Fees Amount": "0",
        "Loan Cover Rate": "0",
        "Loan Cover Amount": "0",
        "Loan Amount Term": "130208.33",
        "Loan Amount Total": "1562500",
        "Next Payment Id": "6",
        "Worthness File": "66a10a3e126ed518.jpg",
        "Narration": "",
        "Loan Added By": "Christina Tauzie",
        "Loan Approved By": "Patrick Kalowekamo",
        "Approved Date": "2024-07-26 17:27:20",
        "Rejected By": "",
        "Rejected Date": "",
        "Loan Status": "CLOSED",
        "Disbursed": "Yes",
        "Disbursed By": "Patrick Kalowekamo",
        "Disbursed Date": "2024-08-13 08:08:33",
        "Written Off By": "",
        "Write Off Approved By": "",
        "Write Off Approval Date": "",
        "Written Off Date": "",
        "Loan Added Date": "2024-07-24 16:06:02",
        "": "",
        "status": ""
    },
    {
        "No": "77",
        "New  Loan Number": "SCL202409292011",
        "Old Loan Number": "SCL202407231640",
        "Loan Product": "Masamba",
        "Loan Customer": "Ethel Ndafakale (3898589)",
        "Loan Date": "2024-07-04",
        "Loan Principal": " 2,000,000.00 ",
        "Loan Period": "6",
        "Period Type": "Monthly",
        "Loan Interest Rate": "6",
        "Loan Interest Amount": "460875.54",
        "Admin Fee Rate": "3.5",
        "Admin Fees Amount": "268844.06",
        "Loan Cover Rate": "3",
        "Loan Cover Amount": "230437.77",
        "Loan Amount Term": "493359.56",
        "Loan Amount Total": "2960157.37",
        "Next Payment Id": "3",
        "Worthness File": "669fc774191718529.PNG",
        "Narration": "",
        "Loan Added By": "Bright Chinkombero",
        "Loan Approved By": "Patrick Kalowekamo",
        "Approved Date": "2024-07-23 17:29:03",
        "Rejected By": "",
        "Rejected Date": "",
        "Loan Status": "CLOSED",
        "Disbursed": "Yes",
        "Disbursed By": "Patrick Kalowekamo",
        "Disbursed Date": "2024-08-01 15:37:34",
        "Written Off By": "",
        "Write Off Approved By": "",
        "Write Off Approval Date": "",
        "Written Off Date": "",
        "Loan Added Date": "2024-07-23 17:08:41",
        "": "",
        "status": ""
    },
    {
        "No": "78",
        "New  Loan Number": "SCL202409292012",
        "Old Loan Number": "SCL202407231639",
        "Loan Product": "Zitsamba",
        "Loan Customer": "Kumunda(686)",
        "Loan Date": "2024-07-23",
        "Loan Principal": " 1,500,000.00 ",
        "Loan Period": "12",
        "Period Type": "Weekly",
        "Loan Interest Rate": "0",
        "Loan Interest Amount": "0",
        "Admin Fee Rate": "0",
        "Admin Fees Amount": "0",
        "Loan Cover Rate": "0",
        "Loan Cover Amount": "0",
        "Loan Amount Term": "156250",
        "Loan Amount Total": "1875000",
        "Next Payment Id": "7",
        "Worthness File": "669fa636751b55457.jpg",
        "Narration": "",
        "Loan Added By": "Temwa Chawinga",
        "Loan Approved By": "Patrick Kalowekamo",
        "Approved Date": "2024-07-23 17:29:18",
        "Rejected By": "",
        "Rejected Date": "",
        "Loan Status": "CLOSED",
        "Disbursed": "Yes",
        "Disbursed By": "Patrick Kalowekamo",
        "Disbursed Date": "2024-08-13 08:05:53",
        "Written Off By": "",
        "Write Off Approved By": "",
        "Write Off Approval Date": "",
        "Written Off Date": "",
        "Loan Added Date": "2024-07-23 14:47:23",
        "": "",
        "status": ""
    },
    {
        "No": "79",
        "New  Loan Number": "SCL202409292013",
        "Old Loan Number": "SCL202407231638",
        "Loan Product": "Zitsamba",
        "Loan Customer": "Panganani(398)",
        "Loan Date": "2024-07-23",
        "Loan Principal": " 1,850,000.00 ",
        "Loan Period": "12",
        "Period Type": "Weekly",
        "Loan Interest Rate": "0",
        "Loan Interest Amount": "0",
        "Admin Fee Rate": "0",
        "Admin Fees Amount": "0",
        "Loan Cover Rate": "0",
        "Loan Cover Amount": "0",
        "Loan Amount Term": "192708.33",
        "Loan Amount Total": "2312500",
        "Next Payment Id": "7",
        "Worthness File": "669fa4b9955485801.jpg",
        "Narration": "",
        "Loan Added By": "Temwa Chawinga",
        "Loan Approved By": "Patrick Kalowekamo",
        "Approved Date": "2024-07-23 17:28:52",
        "Rejected By": "",
        "Rejected Date": "",
        "Loan Status": "CLOSED",
        "Disbursed": "Yes",
        "Disbursed By": "Patrick Kalowekamo",
        "Disbursed Date": "2024-08-13 08:05:30",
        "Written Off By": "",
        "Write Off Approved By": "",
        "Write Off Approval Date": "",
        "Written Off Date": "",
        "Loan Added Date": "2024-07-23 14:41:03",
        "": "",
        "status": ""
    },
    {
        "No": "80",
        "New  Loan Number": "SCL202409292014",
        "Old Loan Number": "SCL202407221631",
        "Loan Product": "Zitsamba",
        "Loan Customer": "Namkumba(141)",
        "Loan Date": "2024-07-22",
        "Loan Principal": " 1,150,000.00 ",
        "Loan Period": "12",
        "Period Type": "Weekly",
        "Loan Interest Rate": "0",
        "Loan Interest Amount": "0",
        "Admin Fee Rate": "0",
        "Admin Fees Amount": "0",
        "Loan Cover Rate": "0",
        "Loan Cover Amount": "0",
        "Loan Amount Term": "119791.67",
        "Loan Amount Total": "1437500",
        "Next Payment Id": "7",
        "Worthness File": "669e6423e238c8625.jpg",
        "Narration": "",
        "Loan Added By": "Grey Magombo",
        "Loan Approved By": "Patrick Kalowekamo",
        "Approved Date": "2024-07-22 18:27:48",
        "Rejected By": "",
        "Rejected Date": "",
        "Loan Status": "CLOSED",
        "Disbursed": "Yes",
        "Disbursed By": "Patrick Kalowekamo",
        "Disbursed Date": "2024-08-01 15:35:12",
        "Written Off By": "",
        "Write Off Approved By": "",
        "Write Off Approval Date": "",
        "Written Off Date": "",
        "Loan Added Date": "2024-07-22 15:53:35",
        "": "",
        "status": ""
    },
    {
        "No": "81",
        "New  Loan Number": "SCL202409292015",
        "Old Loan Number": "SCL202407221628",
        "Loan Product": "Masamba",
        "Loan Customer": "Ireen Manowa (7478481)",
        "Loan Date": "2024-07-22",
        "Loan Principal": " 5,000,000.00 ",
        "Loan Period": "6",
        "Period Type": "Monthly",
        "Loan Interest Rate": "6",
        "Loan Interest Amount": "1152188.85",
        "Admin Fee Rate": "3.5",
        "Admin Fees Amount": "672110.16",
        "Loan Cover Rate": "3",
        "Loan Cover Amount": "576094.42",
        "Loan Amount Term": "1233398.91",
        "Loan Amount Total": "7400393.43",
        "Next Payment Id": "3",
        "Worthness File": "669e242909f5f1425.PNG",
        "Narration": "",
        "Loan Added By": "Reuben Ngomwa",
        "Loan Approved By": "RAPHAEL MSHALI",
        "Approved Date": "2024-07-22 17:46:03",
        "Rejected By": "",
        "Rejected Date": "",
        "Loan Status": "CLOSED",
        "Disbursed": "Yes",
        "Disbursed By": "Patrick Kalowekamo",
        "Disbursed Date": "2024-08-01 15:33:37",
        "Written Off By": "",
        "Write Off Approved By": "",
        "Write Off Approval Date": "",
        "Written Off Date": "",
        "Loan Added Date": "2024-07-22 11:20:16",
        "": "",
        "status": ""
    },
    {
        "No": "82",
        "New  Loan Number": "SCL202409292016",
        "Old Loan Number": "SCL202407221627",
        "Loan Product": "Government Payroll Loans",
        "Loan Customer": "Noel Filipo (8717030)",
        "Loan Date": "2024-07-22",
        "Loan Principal": " 350,000.00 ",
        "Loan Period": "24",
        "Period Type": "Monthly",
        "Loan Interest Rate": "4",
        "Loan Interest Amount": "218702.43",
        "Admin Fee Rate": "1",
        "Admin Fees Amount": "53217.27",
        "Loan Cover Rate": "1",
        "Loan Cover Amount": "53217.27",
        "Loan Amount Term": "28130.71",
        "Loan Amount Total": "675136.98",
        "Next Payment Id": "13",
        "Worthness File": "669e20db40b2f9107.PNG",
        "Narration": "",
        "Loan Added By": "Georgina Kadauma",
        "Loan Approved By": "Patrick Kalowekamo",
        "Approved Date": "2024-07-22 18:27:42",
        "Rejected By": "",
        "Rejected Date": "",
        "Loan Status": "CLOSED",
        "Disbursed": "Yes",
        "Disbursed By": "Patrick Kalowekamo",
        "Disbursed Date": "2024-08-01 15:32:58",
        "Written Off By": "",
        "Write Off Approved By": "",
        "Write Off Approval Date": "",
        "Written Off Date": "",
        "Loan Added Date": "2024-07-22 11:05:52",
        "": "",
        "status": ""
    },
    {
        "No": "83",
        "New  Loan Number": "SCL202409292017",
        "Old Loan Number": "SCL202407181626",
        "Loan Product": "Zitsamba",
        "Loan Customer": "Zitseko(964)",
        "Loan Date": "2024-07-17",
        "Loan Principal": " 1,900,000.00 ",
        "Loan Period": "12",
        "Period Type": "Weekly",
        "Loan Interest Rate": "0",
        "Loan Interest Amount": "0",
        "Admin Fee Rate": "0",
        "Admin Fees Amount": "0",
        "Loan Cover Rate": "0",
        "Loan Cover Amount": "0",
        "Loan Amount Term": "197916.67",
        "Loan Amount Total": "2375000",
        "Next Payment Id": "6",
        "Worthness File": "6698e36fc30887530.jpg",
        "Narration": "",
        "Loan Added By": "Grey Magombo",
        "Loan Approved By": "Patrick Kalowekamo",
        "Approved Date": "2024-07-22 18:27:31",
        "Rejected By": "",
        "Rejected Date": "",
        "Loan Status": "CLOSED",
        "Disbursed": "Yes",
        "Disbursed By": "Patrick Kalowekamo",
        "Disbursed Date": "2024-08-13 08:05:14",
        "Written Off By": "",
        "Write Off Approved By": "",
        "Write Off Approval Date": "",
        "Written Off Date": "",
        "Loan Added Date": "2024-07-18 11:42:30",
        "": "",
        "status": ""
    },
    {
        "No": "84",
        "New  Loan Number": "SCL202409292018",
        "Old Loan Number": "SCL202407181625",
        "Loan Product": "Zitsamba",
        "Loan Customer": "Thayelo(582)",
        "Loan Date": "2024-07-17",
        "Loan Principal": " 700,000.00 ",
        "Loan Period": "12",
        "Period Type": "Weekly",
        "Loan Interest Rate": "0",
        "Loan Interest Amount": "0",
        "Admin Fee Rate": "0",
        "Admin Fees Amount": "0",
        "Loan Cover Rate": "0",
        "Loan Cover Amount": "0",
        "Loan Amount Term": "72916.67",
        "Loan Amount Total": "875000",
        "Next Payment Id": "7",
        "Worthness File": "6698dede7af822919.jpg",
        "Narration": "",
        "Loan Added By": "Temwa Chawinga",
        "Loan Approved By": "Patrick Kalowekamo",
        "Approved Date": "2024-07-22 18:27:24",
        "Rejected By": "",
        "Rejected Date": "",
        "Loan Status": "CLOSED",
        "Disbursed": "Yes",
        "Disbursed By": "Patrick Kalowekamo",
        "Disbursed Date": "2024-08-13 08:04:58",
        "Written Off By": "",
        "Write Off Approved By": "",
        "Write Off Approval Date": "",
        "Written Off Date": "",
        "Loan Added Date": "2024-07-18 11:23:08",
        "": "",
        "status": ""
    },
    {
        "No": "85",
        "New  Loan Number": "SCL202409292019",
        "Old Loan Number": "SCL202407181624",
        "Loan Product": "Zitsamba",
        "Loan Customer": "Chimwaza(924)",
        "Loan Date": "2024-07-17",
        "Loan Principal": " 2,100,000.00 ",
        "Loan Period": "12",
        "Period Type": "Weekly",
        "Loan Interest Rate": "0",
        "Loan Interest Amount": "0",
        "Admin Fee Rate": "0",
        "Admin Fees Amount": "0",
        "Loan Cover Rate": "0",
        "Loan Cover Amount": "0",
        "Loan Amount Term": "218750",
        "Loan Amount Total": "2625000",
        "Next Payment Id": "7",
        "Worthness File": "6698c8b3b38813727.jpg",
        "Narration": "",
        "Loan Added By": "Temwa Chawinga",
        "Loan Approved By": "Patrick Kalowekamo",
        "Approved Date": "2024-07-22 18:26:57",
        "Rejected By": "",
        "Rejected Date": "",
        "Loan Status": "CLOSED",
        "Disbursed": "Yes",
        "Disbursed By": "Patrick Kalowekamo",
        "Disbursed Date": "2024-08-13 08:04:40",
        "Written Off By": "",
        "Write Off Approved By": "",
        "Write Off Approval Date": "",
        "Written Off Date": "",
        "Loan Added Date": "2024-07-18 09:48:35",
        "": "",
        "status": ""
    },
    {
        "No": "86",
        "New  Loan Number": "SCL202409292020",
        "Old Loan Number": "SCL202407181623",
        "Loan Product": "Zitsamba",
        "Loan Customer": "Kapha(368)",
        "Loan Date": "2024-07-17",
        "Loan Principal": " 900,000.00 ",
        "Loan Period": "12",
        "Period Type": "Weekly",
        "Loan Interest Rate": "0",
        "Loan Interest Amount": "0",
        "Admin Fee Rate": "0",
        "Admin Fees Amount": "0",
        "Loan Cover Rate": "0",
        "Loan Cover Amount": "0",
        "Loan Amount Term": "93750",
        "Loan Amount Total": "1125000",
        "Next Payment Id": "7",
        "Worthness File": "6698c34c8b2877636.jpg",
        "Narration": "",
        "Loan Added By": "Grey Magombo",
        "Loan Approved By": "Patrick Kalowekamo",
        "Approved Date": "2024-07-22 18:26:40",
        "Rejected By": "",
        "Rejected Date": "",
        "Loan Status": "CLOSED",
        "Disbursed": "Yes",
        "Disbursed By": "Patrick Kalowekamo",
        "Disbursed Date": "2024-08-13 08:04:17",
        "Written Off By": "",
        "Write Off Approved By": "",
        "Write Off Approval Date": "",
        "Written Off Date": "",
        "Loan Added Date": "2024-07-18 09:26:21",
        "": "",
        "status": ""
    },
    {
        "No": "87",
        "New  Loan Number": "SCL202409292021",
        "Old Loan Number": "SCL202406191528",
        "Loan Product": "Masamba",
        "Loan Customer": "Kennedy Mahapala (9315142)",
        "Loan Date": "2024-06-19",
        "Loan Principal": " 6,000,000.00 ",
        "Loan Period": "6",
        "Period Type": "Monthly",
        "Loan Interest Rate": "6",
        "Loan Interest Amount": "1382626.62",
        "Admin Fee Rate": "3.5",
        "Admin Fees Amount": "806532.19",
        "Loan Cover Rate": "3",
        "Loan Cover Amount": "691313.31",
        "Loan Amount Term": "1480078.69",
        "Loan Amount Total": "8880472.12",
        "Next Payment Id": "4",
        "Worthness File": "6672e99a357a48272.PNG",
        "Narration": "",
        "Loan Added By": "Kelvin Chaguza",
        "Loan Approved By": "RAPHAEL MSHALI",
        "Approved Date": "2024-06-26 17:45:01",
        "Rejected By": "",
        "Rejected Date": "",
        "Loan Status": "CLOSED",
        "Disbursed": "Yes",
        "Disbursed By": "Harold Mwala",
        "Disbursed Date": "2024-06-26 17:49:23",
        "Written Off By": "",
        "Write Off Approved By": "",
        "Write Off Approval Date": "",
        "Written Off Date": "",
        "Loan Added Date": "2024-06-19 16:22:22",
        "": "",
        "status": ""
    },
    {
        "No": "88",
        "New  Loan Number": "SCL202409271956",
        "Old Loan Number": "SCL202409191886",
        "Loan Product": "Masamba",
        "Loan Customer": "Bester Sikaloka",
        "Loan Date": "9/19/24",
        "Loan Principal": "2,700,000.00",
        "Loan Period": "6",
        "Period Type": "Monthly",
        "Loan Interest Rate": "",
        "Loan Interest Amount": "",
        "Admin Fee Rate": "",
        "Admin Fees Amount": "",
        "Loan Cover Rate": "",
        "Loan Cover Amount": "",
        "Loan Amount Term": "",
        "Loan Amount Total": "",
        "Next Payment Id": "",
        "Worthness File": "",
        "Narration": "",
        "Loan Added By": "ALEX KAMPUTA",
        "Loan Approved By": "",
        "Approved Date": "",
        "Rejected By": "",
        "Rejected Date": "",
        "Loan Status": "",
        "Disbursed": "",
        "Disbursed By": "",
        "Disbursed Date": "",
        "Written Off By": "",
        "Write Off Approved By": "",
        "Write Off Approval Date": "",
        "Written Off Date": "",
        "Loan Added Date": "",
        "": "",
        "status": ""
    },
    {
        "No": "89",
        "New  Loan Number": "SCL202409271974",
        "Old Loan Number": "SCL202409181877",
        "Loan Product": "Zitsamba",
        "Loan Customer": "MAKANDE GROUP(762)",
        "Loan Date": "2024--9-18",
        "Loan Principal": " 800,000.00 ",
        "Loan Period": "12",
        "Period Type": "Weekly",
        "Loan Interest Rate": "",
        "Loan Interest Amount": "",
        "Admin Fee Rate": "",
        "Admin Fees Amount": "",
        "Loan Cover Rate": "",
        "Loan Cover Amount": "",
        "Loan Amount Term": "",
        "Loan Amount Total": "",
        "Next Payment Id": "",
        "Worthness File": "",
        "Narration": "",
        "Loan Added By": "Temwa Chawinga",
        "Loan Approved By": "",
        "Approved Date": "",
        "Rejected By": "",
        "Rejected Date": "",
        "Loan Status": "",
        "Disbursed": "",
        "Disbursed By": "",
        "Disbursed Date": "",
        "Written Off By": "",
        "Write Off Approved By": "",
        "Write Off Approval Date": "",
        "Written Off Date": "",
        "Loan Added Date": "",
        "": "",
        "status": ""
    }
]';

// Decode JSON data into an associative array
        $base_day = (int)$date->format('d');
        $effective_offset = (int)$offset_months;

        if ($base_day > 15) {
            $effective_offset++;
        }

        $loans = json_decode($jsonData, true);

        if ($effective_offset !== 0) {
            $date->modify('+' . $effective_offset . ' months');
            $new_number = $loan['New  Loan Number'];
            $old_number = $loan['Old Loan Number'];




            $old_loan = $this->db->select("*")->from('loan')->where('loan_number',$old_number)->get()->row();
            $new_loan = $this->db->select("*")->from('loan')->where('loan_number',$new_number)->get()->row();
            $transactions = $this->db->select("*")->from('transaction')->where('account_number',$new_number)->get()->result();
    foreach ($transactions as $trans){
        $this->db->where('transaction_id',$trans->transaction_id)->delete('transaction');
    }
            $this->db->where('loan_id',$old_loan->loan_id)->delete('payement_schedules');
            $this->db->where('loan_number',$old_number)->delete($this->table);
            $this->db->where('account_number',$old_number)->delete('account');

            $this->db->where('loan_number',$new_number)->update($this->table, array('loan_number'=> $old_number));
            $this->db->where('account_number',$new_number)->update('account', array('account_number'=> $old_number));
            $this->db->where('account_number',$new_number)->update('transaction', array('account_number'=> $old_number));

        }
    }
	function get_all_revenue(){
		$this->db->select('*,ps.interest as pinterest, ps.principal as pprincipal, ps.amount as pamount');
		$this->db->from('payement_schedules  ps');
		$this->db->join('loan l', 'l.loan_id = ps.loan_id');
		$this->db->join('individual_customers ic', 'ic.id = ps.customer AND ps.customer_type = "individual"', 'LEFT');
		$this->db->join('groups g', 'g.group_id = ps.customer AND ps.customer_type = "group"', 'LEFT');
		$this->db->join('loan_products', 'loan_products.loan_product_id = l.loan_product', 'LEFT');
		$this->db->where('ps.status', 'PAID');

		$query = $this->db->get();

		$result = $query->result();

		return $result;
	}
	function get_all_balances($product, $officer, $loan, $from, $to){
		$this->load->helper('portfolio_formulae');
		$unpaid_principal = sql_unpaid_principal_expr('ps');
		$accrued_charges = sql_portfolio_accrued_charges_case('ps');
		$portfolio_outstanding = sql_portfolio_outstanding_balance_expr('ps');

		$this->db->select(
			'*, e.Firstname as efname, ic.Firstname as ifname, ic.Lastname as ilname, e.Lastname as elname, '
			. 'ps.interest as pinterest, ps.principal as pprincipal, ps.amount as pamount, '
			. $unpaid_principal . ' AS unpaid_principal, '
			. $accrued_charges . ' AS accrued_charges, '
			. $portfolio_outstanding . ' AS portfolio_outstanding',
			false
		);
		$this->db->from('payement_schedules ps');
		$this->db->join('loan l', 'l.loan_id = ps.loan_id');
		$this->db->join('individual_customers ic', 'ic.id = ps.customer AND ps.customer_type = "individual"', 'LEFT');
		$this->db->join('`groups` g', 'g.group_id = ps.customer AND ps.customer_type = "group"', 'LEFT');
		$this->db->join('loan_products', 'loan_products.loan_product_id = l.loan_product', 'LEFT');
		$this->db->join('employees e', 'l.loan_added_by = e.id', 'LEFT');
		$this->db->where('l.loan_status', 'ACTIVE');
		if (!empty($product)) {
			$this->db->where('l.loan_product', $product);
		}
		if (!empty($officer)) {
			$this->db->where('l.loan_added_by', $officer);
		}
		if (!empty($loan)) {
			$this->db->where('l.loan_id', $loan);
		}
		if ($from != '' && $to != '') {
			$this->db->where('payment_schedule BETWEEN "' . date('Y-m-d', strtotime($from)) . '" and "' . date('Y-m-d', strtotime($to)) . '"');
		}
		$this->db->order_by('l.loan_number', 'ASC');
		$this->db->order_by('ps.payment_number', 'ASC');
		$query = $this->db->get();

		return $query->result();
	}
	function get_all_balances_by_product($product){
		$this->db->select('*,ps.interest as pinterest, ps.principal as pprincipal, ps.amount as pamount');
		$this->db->from('payement_schedules  ps');
		$this->db->join('loan l', 'l.loan_id = ps.loan_id');
		$this->db->join('individual_customers ic', 'ic.id = ps.customer AND ps.customer_type = "individual"', 'LEFT');
		$this->db->join('groups g', 'g.group_id = ps.customer AND ps.customer_type = "group"', 'LEFT');
		$this->db->join('loan_products', 'loan_products.loan_product_id = l.loan_product', 'LEFT');
		$this->db->where('ps.status', 'NOT PAID');
		$this->db->where('l.loan_status', 'ACTIVE');
		$this->db->where('l.loan_product', $product);

		$query = $this->db->get();

		$result = $query->result();

		return $result;
	}
	function calculate5($amount, $months, $loan_id, $loan_date)
	{
		//get loan parameters
		$this->db->where('loan_product_id', $loan_id);
		$loan = $this->db->get('loan_products')->row();

		//divisor
		switch ($loan->frequency) {
			case 'Monthly':
				$divisor = 1;
				$days = 30;
				break;
			case 'Bi weekly':
				$divisor = 2;
				$days = 15;
				break;
			case 'Weekly':
				$divisor = 4;
				$days = 7;
				break;
		}



        $extra_interest = 0;
        $extra_days = 0;
        $total_extra_interest = 0;
        	$date = $loan_date;
		$day = date('d', strtotime($date));
				if($day >=15 && $loan->frequency=="Monthly") {

//                    if loan date is above 15 then the effective date should be 1st day of next month
                    $month_end_date= date("Y-m-t", strtotime($date));
//                    add 1 to last date of this month
                    $ll = strtotime('+1 day', strtotime($month_end_date));
                    $loan_date = date('Y-m-d',  $ll );

//				    calculate number of xtra days
                    $earlier = new DateTime($date);
                    $later = new DateTime($loan_date);
                   $date_diff =$later->diff($earlier);
                    $extra_days = $date_diff->d;


//lets calculate interest of these dayes
                    $total_extra_interest = abs((($extra_days)/30)*$amount*(($loan->interest+$loan->loan_cover)/100));
                    $extra_interest = $total_extra_interest/ $months;

				}elseif ($day < 15 && $loan->frequency=="Monthly"){
                    //                    if loan date is above 15 then the effective date should be 1st day of next month
                    $month_start_date= date("Y-m-01", strtotime($date));
//                    add 1 to last date of this month
                    $loan_date = date('Y-m-d', strtotime($month_start_date));

//				    calculate number of xtra days
                    $earlier = new DateTime($month_start_date);
                    $later = new DateTime($date);
                    $date_diff =$later->diff($earlier);
                    $extra_days = $date_diff->d;


//lets calculate interest of these dayes
                    $total_extra_interest = (($extra_days)/30)*$amount*(($loan->interest+$loan->loan_cover)/100);
                    $extra_interest = $total_extra_interest/ $months;
                }




	}
	function calculate($amount, $months, $loan_id, $loan_date)
	{
		//get loan parameters
		$this->db->where('loan_product_id', $loan_id);
		$loan = $this->db->get('loan_products')->row();

        if ($this->is_group_zitsamba_monthly_flat_product($loan)) {
            return $this->calculate_group_zitsamba_monthly_flat_table(
                $this->normalize_numeric_value($amount),
                (int)$this->normalize_numeric_value($months),
                $loan,
                $this->normalize_date_value($loan_date)
            );
        }

		// Set default values for missing properties - check both field names
		if(isset($loan->processing_fees)) {
            $processing_fee_percent = $this->normalize_numeric_value($loan->processing_fees);
		} elseif(isset($loan->processing_fee)) {
            $processing_fee_percent = $this->normalize_numeric_value($loan->processing_fee);
		} else {
			$processing_fee_percent = 0;
		}
		$loan->processing_fees = $processing_fee_percent;

		// Store original amount for display
		$original_amount = $amount;
		$processing_fee_amount = 0;

		// For Reducing Balance Weekly or Bi-weekly, add processing fee to principal (case-insensitive)
		if(strcasecmp(trim($loan->method), "Reducing Balance") == 0 && (strcasecmp(trim($loan->frequency), "Weekly") == 0 || strcasecmp(trim($loan->frequency), "Bi weekly") == 0)) {
			$processing_fee_amount = ($processing_fee_percent / 100) * $amount;
			$amount = $amount + $processing_fee_amount;
		}

		//divisor
		switch ($loan->frequency) {
			case 'Monthly':
				$divisor = 1;
				$days = 30;
				break;
			case 'Bi weekly':
				$divisor = 2;
				$days = 15;
				break;
			case 'Weekly':
				$divisor = 4;
				$days = 7;
				break;
		}



        $extra_interest = 0;
        $extra_days = 0;
        $total_extra_interest = 0;
        	$date = $loan_date;
		$day = date('d', strtotime($date));
				if($day >=15 && $loan->frequency=="Monthly" && $loan->schedule_plan=="cut off") {
;
//                    if loan date is above 15 then the effective date should be 1st day of next month
                    $month_end_date= date("Y-m-t", strtotime($date));
//                    add 1 to last date of this month
                    $ll = strtotime('+1 day', strtotime($month_end_date));
                    $loan_date = date('Y-m-d',  $ll );

//				    calculate number of xtra days
                    $earlier = new DateTime($date);
                    $later = new DateTime($loan_date);
                   $date_diff =$later->diff($earlier);
                    $extra_days = $date_diff->d;


//lets calculate interest of these dayes
                    $total_extra_interest = (($extra_days)/30)*$amount*(($loan->interest+$loan->loan_cover)/100);
                    $extra_interest = $total_extra_interest/ $months;

				}
				elseif ($day >= 1 && $day < 15 && $loan->frequency=="Monthly" && $loan->schedule_plan=="cut off"){
                    //                    if loan date is above 15 then the effective date should be 1st day of next month
                    $month_start_date= date("Y-m-01", strtotime($date));
//                    add 1 to last date of this month
                    $loan_date = date('Y-m-d', strtotime($month_start_date));

//				    calculate number of xtra days
                    $earlier = new DateTime($month_start_date);
                    $later = new DateTime($date);
                    $date_diff =$later->diff($earlier);
                    $extra_days = $date_diff->d;


//lets calculate interest of these dayes
                    $total_extra_interest = (($extra_days)/30)*$amount*(($loan->interest+$loan->loan_cover)/100);
                    $extra_interest = $total_extra_interest/ $months;
                }
				elseif ($loan->frequency=="Monthly" && $loan->schedule_plan=="no cutoff"){
					//                    if loan date is above 15 then the effective date should be 1st day of next month

					$loan_date = $date;

					$extra_interest = 0;
				}



        //interest

        $i = ($this->normalize_numeric_value($loan->interest) / 100) * 12;
		$af = ($loan->admin_fees / 100) * 12;
		$lc = ($loan->loan_cover / 100) * 12;
		$total_deduction = $i + $af + $lc;


		$monthly_payment = $amount * ($total_deduction / 12) * pow((1 + $total_deduction / 12), $months) / (pow((1 + $total_deduction / 12), $months) - 1);
		$monthly_payment1 = $amount * ($total_deduction / 12) * pow((1 + $total_deduction / 12), $months) / (pow((1 + $total_deduction / 12), $months) - 1);
		$monthly_payment_config = $amount * ($total_deduction / 12) * pow((1 + $total_deduction / 12), $months) / (pow((1 + $total_deduction / 12), $months) - 1);
		$current_balance = $amount;
		$current_balance1 = $amount;
		$payment_counter = 1;
		$total_interest = 0;
		$total_interest1 = 0;
		$total_admin_fees = 0;
		$total_admin_fees1 = 0;
		$total_loan_cover = 0;
		$total_loan_cover1 = 0;


		while ($current_balance1 > 0) {
			//create rows


			$towards_interest1 = ($i / 12) * $current_balance1;  //this calculates the portion of your monthly payment that goes towards interest
			$towards_fees = ($af / 12) * $current_balance1;  //this calculates the portion of your monthly payment that goes towards administration fees
			$towards_lc = ($lc / 12) * $current_balance1;  //this calculates the portion of your monthly payment that goes towards administration fees

			if ($monthly_payment1 > $current_balance1) {
				$monthly_payment1 = $current_balance1 + $towards_interest1 + $towards_fees + $towards_lc;

			}
			$towards_balance1 = $monthly_payment1 - ($towards_interest1 + $towards_fees + $towards_lc);
			$total_interest1 = $total_interest1 + $towards_interest1;
			$total_admin_fees = $total_admin_fees + $towards_fees;
			$total_loan_cover = $total_loan_cover + $towards_lc;
			$current_balance1 = $current_balance1 - $towards_balance1;
		}
        if($day >=15 && $loan->frequency=="Monthly" && $loan->schedule_plan=="cut off") {
            $m_config = $monthly_payment_config + $extra_interest;
            $pay_total = $total_interest1 + $amount + $total_admin_fees + $total_loan_cover+$total_extra_interest;
            $total_interest1 = $total_interest1 + $total_extra_interest;
        }
        elseif ($day >=1 && $day < 15 && $loan->frequency=="Monthly" && $loan->schedule_plan=="cut off"){
            $m_config = $monthly_payment_config - $extra_interest;
            $pay_total = $total_interest1 + $amount + $total_admin_fees + $total_loan_cover-$total_extra_interest;
            $total_interest1 = $total_interest1 - $total_extra_interest;
        }
        else{
            $pay_total = $total_interest1 + $amount + $total_admin_fees + $total_loan_cover;
            $m_config = $monthly_payment_config ;
            $total_interest1 = $total_interest1 + $total_extra_interest;
        }

			//Loan info
			$table = '<div id="calculator"><h3>Loan Info</h3>';
			$table = $table . '<table border="1" class="table">';
			$table = $table . '<tr><td>Loan Product:</td><td>' . $loan->product_name . '</td></tr>';
			$table = $table . '<tr><td>Loan Method:</td><td>' . $loan->method . '</td></tr>';
			$table = $table . '<tr><td>Interest:</td><td>' . $loan->interest . '%</td></tr>';
			$table = $table . '<tr><td>Admin Fee %:</td><td>' . $loan->admin_fees . '%</td></tr>';
			$table = $table . '<tr><td>Loan cover %:</td><td>' . $loan->loan_cover . '%</td></tr>';
			$table = $table . '<tr><td>Processing Fee %:</td><td>' . (isset($loan->processing_fees) ? $loan->processing_fees : 0) . '%</td></tr>';
			$table = $table . '<tr><td>Loan Term:</td><td>' . $months . '/'. $loan->frequency . '</td></tr>';
			$table = $table . '<tr><td>Loan start date:</td><td>' . $date . '</td></tr>';
			$table = $table . '<tr><td>Loan effective date:</td><td>' . $loan_date . '</td></tr>';
			$table = $table . '<tr><td>Extra days:</td><td>' . $extra_days . '</td></tr>';
			$table = $table . '<tr><td>Frequency:</td><td> ' . $loan->frequency . ' </td></tr>';
			$table = $table . '</table>';
			$table = $table . '<h3>Computation</h3>';
			$table = $table . '<table>';
			// For Reducing Balance Weekly/Bi-weekly, always show breakdown (case-insensitive)
			if(strcasecmp(trim($loan->method), "Reducing Balance") == 0 && (strcasecmp(trim($loan->frequency), "Weekly") == 0 || strcasecmp(trim($loan->frequency), "Bi weekly") == 0)) {
				$table = $table . '<tr><td>Original Loan Amount:</td><td> ' . $this->config->item('currency_symbol') . number_format($original_amount, 2, '.', ',') . '</td></tr>';
				$table = $table . '<tr><td>Processing Fee (' . $loan->processing_fees . '%):</td><td> ' . $this->config->item('currency_symbol') . number_format($processing_fee_amount, 2, '.', ',') . '</td></tr>';
				$table = $table . '<tr><td><strong>Loan Amount (Principal + Fee):</strong></td><td><strong> ' . $this->config->item('currency_symbol') . number_format($amount, 2, '.', ',') . '</strong></td></tr>';
			} else {
				$table = $table . '<tr><td>Loan Amount:</td><td> ' . $this->config->item('currency_symbol') . number_format($amount, 2, '.', ',') . '</td></tr>';
			}
//        $table = $table . '<tr><td>Interest per First Month:</td><td> '.$this->config->item('currency_symbol') . $amount*$i.'</td></tr>';
			$table = $table . '<tr><td>Total extra interest :</td><td> ' . $this->config->item('currency_symbol') . number_format(($total_extra_interest), 2) . '</td></tr>';
			$table = $table . '<tr><td>Total interest:</td><td> ' . $this->config->item('currency_symbol') . number_format(($total_interest1), 2) . '</td></tr>';
			$table = $table . '<tr><td>Total Admin fee:</td><td> ' . $this->config->item('currency_symbol') . number_format(($total_admin_fees), 2) . '</td></tr>';
			$table = $table . '<tr><td>Total Loan cover:</td><td> ' . $this->config->item('currency_symbol') . number_format(($total_loan_cover), 2) . '</td></tr>';
			$table = $table . '<tr><td>Amount Per Term:</td><td> ' . $this->config->item('currency_symbol') . number_format($m_config, 2) . '</td></tr>';

			$table = $table . '<tr><td>Total Payment:</td><td> ' . $this->config->item('currency_symbol') . number_format($pay_total, 2, '.', ',') . '</td></tr>';
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


			$table = $table . "<td width='70'>" . round($amount, 2) . "</td>";
			$table = $table . "</tr>";

        if($day >=15 && $loan->frequency=="Monthly" && $loan->schedule_plan=="cut off"){
			while ($current_balance > 0 && $payment_counter <= $months) {
                    //create rows


                    $towards_interest = (($i / 12) * $current_balance);  //this calculates the portion of your monthly payment that goes towards interest
                    $towards_fees1 = ($af / 12) * $current_balance;  //this calculates the portion of your monthly payment that goes towards interest
                    $towards_lc1 = ($lc / 12) * $current_balance;  //this calculates the portion of your monthly payment that goes towards interest

                    if ($monthly_payment > $current_balance) {
                        $monthly_payment = $current_balance + $towards_interest + $towards_fees1 + $towards_lc1;
                    }


                    $towards_balance = $monthly_payment - ($towards_interest + $towards_fees1 + $towards_lc1);
                    $total_interest = $total_interest + $towards_interest;
                    $total_admin_fees1 = $total_admin_fees1 + $towards_fees1;
                    $total_loan_cover1 = $total_loan_cover1 + $towards_lc1;
                    $current_balance = $current_balance - $towards_balance;


                    // display row

                    $table = $table . "<tr class='table_info'>";
                    $table = $table . "<td>" . $payment_counter . "</td>";
                    $table = $table . "<td>" . number_format(($monthly_payment+$extra_interest), 2) . "</td>";
                    $table = $table . "<td>" . number_format($towards_balance, 2) . "</td>";
                    $table = $table . "<td>" . number_format($towards_interest+$extra_interest, 2) . "</td>";

                    $table = $table . "<td>" . number_format($towards_fees1, 2) . "</td>";

                    $table = $table . "<td>" . number_format($towards_lc1, 2) . "</td>";
                    ;
                    $table = $table . "<td>" . number_format($current_balance, 2) . "</td>";
                    $table = $table . "</tr>";


                    $payment_counter++;


                }
            }
        elseif ($day >= 1 && $day < 15 && $loan->frequency=="Monthly" && $loan->schedule_plan=="cut off"){
                while ($current_balance > 0 && $payment_counter <= $months) {
                    //create rows


                    $towards_interest = (($i / 12) * $current_balance);  //this calculates the portion of your monthly payment that goes towards interest
                    $towards_fees1 = ($af / 12) * $current_balance;  //this calculates the portion of your monthly payment that goes towards interest
                    $towards_lc1 = ($lc / 12) * $current_balance;  //this calculates the portion of your monthly payment that goes towards interest

                    if ($monthly_payment > $current_balance) {
                        $monthly_payment = $current_balance + $towards_interest + $towards_fees1 + $towards_lc1;
                    }


                    $towards_balance = $monthly_payment - ($towards_interest + $towards_fees1 + $towards_lc1);
                    $total_interest = $total_interest + $towards_interest;
                    $total_admin_fees1 = $total_admin_fees1 + $towards_fees1;
                    $total_loan_cover1 = $total_loan_cover1 + $towards_lc1;
                    $current_balance = $current_balance - $towards_balance;


                    // display row

                    $table = $table . "<tr class='table_info'>";
                    $table = $table . "<td>" . $payment_counter . "</td>";
                    $table = $table . "<td>" . number_format(($monthly_payment-$extra_interest), 2) . "</td>";
                    $table = $table . "<td>" . number_format($towards_balance, 2) . "</td>";
                    $table = $table . "<td>" . number_format($towards_interest-$extra_interest, 2) . "</td>";

                    $table = $table . "<td>" . number_format($towards_fees1, 2) . "</td>";

                    $table = $table . "<td>" . number_format($towards_lc1, 2) . "</td>";

                    $table = $table . "<td>" . number_format($current_balance, 2) . "</td>";
                    $table = $table . "</tr>";


                    $payment_counter++;


                }
            }
        else{
			while ($current_balance > 0 && $payment_counter <= $months) {
                    //create rows


                    $towards_interest = (($i / 12) * $current_balance);  //this calculates the portion of your monthly payment that goes towards interest
                    $towards_fees1 = ($af / 12) * $current_balance;  //this calculates the portion of your monthly payment that goes towards interest
                    $towards_lc1 = ($lc / 12) * $current_balance;  //this calculates the portion of your monthly payment that goes towards interest

                    if ($monthly_payment > $current_balance) {
                        $monthly_payment = $current_balance + $towards_interest + $towards_fees1 + $towards_lc1;
                    }


                    $towards_balance = $monthly_payment - ($towards_interest + $towards_fees1 + $towards_lc1);
                    $total_interest = $total_interest + $towards_interest;
                    $total_admin_fees1 = $total_admin_fees1 + $towards_fees1;
                    $total_loan_cover1 = $total_loan_cover1 + $towards_lc1;
                    $current_balance = $current_balance - $towards_balance;


                    // display row

                    $table = $table . "<tr class='table_info'>";
                    $table = $table . "<td>" . $payment_counter . "</td>";
                    $table = $table . "<td>" . number_format(($monthly_payment+$extra_interest), 2) . "</td>";
                    $table = $table . "<td>" . number_format($towards_balance, 2) . "</td>";
                    $table = $table . "<td>" . number_format($towards_interest+$extra_interest, 2) . "</td>";

                    $table = $table . "<td>" . number_format($towards_fees1, 2) . "</td>";

                    $table = $table . "<td>" . number_format($towards_lc1, 2) . "</td>";
                    ;
                    $table = $table . "<td>" . number_format($current_balance, 2) . "</td>";
                    $table = $table . "</tr>";


                    $payment_counter++;


                }
            }
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
	function calculate2($amount, $months, $loan_id, $loan_date)
	{
		//get loan parameters
		$this->db->where('loan_product_id', $loan_id);
		$loan = $this->db->get('loan_products')->row();

		//divisor
		switch ($loan->frequency) {
			case 'Monthly':
				$divisor = 1;
				$days = 30;
				break;
			case 'Bi weekly':
				$divisor = 2;
				$days = 15;
				break;
			case 'Weekly':
				$divisor = 4;
				$days = 7;
				break;
		}
		$amount_interest = $amount *( ($loan->interest/100)*12);




        	$date = $loan_date;
		$day = date('d', strtotime($date));
				if($day >=15) {
//				    $months=$months+1;
				}

		//total payments applying interest
		$amount_total = $amount + $amount_interest * $months * $divisor;


		//payment per term
		$amount_term = number_format(round($amount / ($months * $divisor), 2) + $amount_interest, 2, '.', '');


		//$monthly_payment = $amount*($i/12)*pow((1+$i/12),$months)/(pow((1+$i/12),$months)-1);


		//interest

		$i = ($loan->interest / 100) * 12;
		$af = ($loan->admin_fees / 100) * 12;
		$lc = ($loan->loan_cover / 100) * 12;
		$total_deduction = $i + $af + $lc;


		$monthly_payment = $amount * ($total_deduction / 12) * pow((1 + $total_deduction / 12), $months) / (pow((1 + $total_deduction / 12), $months) - 1);
		$monthly_payment1 = $amount * ($total_deduction / 12) * pow((1 + $total_deduction / 12), $months) / (pow((1 + $total_deduction / 12), $months) - 1);
		$monthly_payment_config = $amount * ($total_deduction / 12) * pow((1 + $total_deduction / 12), $months) / (pow((1 + $total_deduction / 12), $months) - 1);
		$current_balance = $amount;
		$current_balance1 = $amount;
		$payment_counter = 1;
		$total_interest = 0;
		$total_interest1 = 0;
		$total_admin_fees = 0;
		$total_admin_fees1 = 0;
		$total_loan_cover = 0;
		$total_loan_cover1 = 0;


		while ($current_balance1 > 0) {
			//create rows


			$towards_interest1 = ($i / 12) * $current_balance1;  //this calculates the portion of your monthly payment that goes towards interest
			$towards_fees = ($af / 12) * $current_balance1;  //this calculates the portion of your monthly payment that goes towards administration fees
			$towards_lc = ($lc / 12) * $current_balance1;  //this calculates the portion of your monthly payment that goes towards administration fees

			if ($monthly_payment1 > $current_balance1) {
				$monthly_payment1 = $current_balance1 + $towards_interest1 + $towards_fees + $towards_lc;

			}
			$towards_balance1 = $monthly_payment1 - ($towards_interest1 + $towards_fees + $towards_lc);
			$total_interest1 = $total_interest1 + $towards_interest1;
			$total_admin_fees = $total_admin_fees + $towards_fees;
			$total_loan_cover = $total_loan_cover + $towards_lc;
			$current_balance1 = $current_balance1 - $towards_balance1;
		}

			//Loan info
			$table = '<div id="calculator"><h3>Loan Info</h3>';
			$table = $table . '<table border="1" class="table">';
			$table = $table . '<tr><td>Loan Name:</td><td>' . $loan->product_name . '</td></tr>';
			$table = $table . '<tr><td>Interest:</td><td>' . $loan->interest . '%</td></tr>';
			$table = $table . '<tr><td>Admin Fee %:</td><td>' . $loan->admin_fees . '%</td></tr>';
			$table = $table . '<tr><td>Loan cover %:</td><td>' . $loan->loan_cover . '%</td></tr>';
			$table = $table . '<tr><td>Terms:</td><td>' . $months . '</td></tr>';
			$table = $table . '<tr><td>Frequency:</td><td>Every ' . $loan->frequency . ' days</td></tr>';
			$table = $table . '</table>';
			$table = $table . '<h3>Computation</h3>';
			$table = $table . '<table>';
			$table = $table . '<tr><td>Loan Amount:</td><td> ' . $this->config->item('currency_symbol') . number_format($amount, 2, '.', ',') . '</td></tr>';
//        $table = $table . '<tr><td>Interest per First Month:</td><td> '.$this->config->item('currency_symbol') . $amount*$i.'</td></tr>';
			$table = $table . '<tr><td>Total interest:</td><td> ' . $this->config->item('currency_symbol') . number_format(($total_interest1), 2) . '</td></tr>';
			$table = $table . '<tr><td>Total Admin fee:</td><td> ' . $this->config->item('currency_symbol') . number_format(($total_admin_fees), 2) . '</td></tr>';
			$table = $table . '<tr><td>Total Loan cover:</td><td> ' . $this->config->item('currency_symbol') . number_format(($total_loan_cover), 2) . '</td></tr>';
			$table = $table . '<tr><td>Amount Per Term:</td><td> ' . $this->config->item('currency_symbol') . number_format($monthly_payment_config, 2) . '</td></tr>';
			$table = $table . '<tr><td>Total Payment:</td><td> ' . $this->config->item('currency_symbol') . number_format($total_interest1 + $amount + $total_admin_fees + $total_loan_cover, 2, '.', ',') . '</td></tr>';
			$table = $table . '</table>';

			//$monthly_payment = $amount*($i/12)*pow((1+$i/12),$months)/(pow((1+$i/12),$months)-1);


			$table = $table . '<table class="table" >

				<tr>
					<th width="30" align="center"><b>Pmt</b></th>
					<th width="60" align="center"><b>Payment</b></th>
					<th width="60" align="center"><b>Principal</b></th>
					<th width="60" align="center"><b>Interest</b></th>
					
					<th width="85" align="center"><b>Interest Paid</b></th>
					<th width="60" align="center"><b>Admin Fee</b></th>
					<th width="60" align="center"><b>Admin Fee Paid</b></th>
						<th width="60" align="center"><b>Loan cover</b></th>
					<th width="60" align="center"><b>Lona cover Paid</b></th>
					<th width="70" align="center"><b>Balance</b></th>
				</tr>	
			';


			$table = $table . "<tr>";
			$table = $table . "<td width='30'>0</td>";
			$table = $table . "<td width='60'>&nbsp;</td>";
			$table = $table . "<td width='60'>&nbsp;</td>";
			$table = $table . "<td width='60'>&nbsp;</td>";
			$table = $table . "<td width='85'>&nbsp;</td>";
			$table = $table . "<td width='85'>&nbsp;</td>";
			$table = $table . "<td width='85'>&nbsp;</td>";
			$table = $table . "<td width='85'>&nbsp;</td>";
			$table = $table . "<td width='85'>&nbsp;</td>";
			$table = $table . "<td width='70'>" . round($amount, 2) . "</td>";
			$table = $table . "</tr>";

			while ($current_balance > 0) {
				//create rows


				$towards_interest = ($i / 12) * $current_balance;  //this calculates the portion of your monthly payment that goes towards interest
				$towards_fees1 = ($af / 12) * $current_balance;  //this calculates the portion of your monthly payment that goes towards interest
				$towards_lc1 = ($lc / 12) * $current_balance;  //this calculates the portion of your monthly payment that goes towards interest

				if ($monthly_payment > $current_balance) {
					$monthly_payment = $current_balance + $towards_interest + $towards_fees1 + $towards_lc1;
				}


				$towards_balance = $monthly_payment - ($towards_interest + $towards_fees1 + $towards_lc1);
				$total_interest = $total_interest + $towards_interest;
				$total_admin_fees1 = $total_admin_fees1 + $towards_fees1;
				$total_loan_cover1 = $total_loan_cover1 + $towards_lc1;
				$current_balance = $current_balance - $towards_balance;


				// display row

				$table = $table . "<tr class='table_info'>";
				$table = $table . "<td>" . $payment_counter . "</td>";
				$table = $table . "<td>" . number_format($monthly_payment, 2) . "</td>";
				$table = $table . "<td>" . number_format($towards_balance, 2) . "</td>";
				$table = $table . "<td>" . number_format($towards_interest, 2) . "</td>";
				$table = $table . "<td>" . number_format($total_interest, 2) . "</td>";
				$table = $table . "<td>" . number_format($towards_fees1, 2) . "</td>";
				$table = $table . "<td>" . number_format($total_admin_fees1, 2) . "</td>";
				$table = $table . "<td>" . number_format($towards_lc1, 2) . "</td>";
				$table = $table . "<td>" . number_format($total_loan_cover1, 2) . "</td>";
				$table = $table . "<td>" . number_format($current_balance, 2) . "</td>";
				$table = $table . "</tr>";


				$payment_counter++;


			}
		$table = $table . "<tr style='color: white; background-color: #0e9970'>";
		$table = $table . "<td width='30'>-</td>";
		$table = $table . "<td width='60'>-</td>";
		$table = $table . "<td width='60'>-</td>";
		$table = $table . "<td width='60'>-</td>";
		$table = $table . "<td width='85'>".$total_interest."</td>";
		$table = $table . "<td width='85'>&nbsp;</td>";
		$table = $table . "<td width='85'>".$total_admin_fees1."</td>";
		$table = $table . "<td width='85'>&nbsp;</td>";
		$table = $table . "<td width='85'>".$total_loan_cover1."</td>";
		$table = $table . "<td width='70'>-</td>";
		$table = $table . "</tr>";
			$table = $table . '</table></div>';

			return $table;

	}
    function add_amortization_straight_weekly_edit($lidd,$principal,$loan_amount, $product_id, $loan_term, $start_date,$loan_customer, $customer_type, $worthness_file,$narration,$added_by, $frequency = 'Weekly') {
        $this->db->select('MAX(counter) as max_c');
        $lid = $this->db->get('loan');
        $result = $lid->row();
        $loanid='SCL'.date("Ymd").(100+$result->max_c);
        $fcounter=$result->max_c+1;




        // Calculate the total number of payments
        $loan = check_exist_in_table('loan_products','loan_product_id',$product_id);
        $num_payments = $loan_term;
        $interest_rate =  $loan->interest ;
        $frequency = $this->normalize_frequency_label($frequency);
        if ($frequency === 'Monthly') {
            $periods_per_year = 12;
            $period_interval_weeks = 0;
        } elseif ($frequency === 'Bi weekly') {
            $periods_per_year = 26;
            $period_interval_weeks = 2;
        } else {
            $periods_per_year = 52;
            $period_interval_weeks = 1;
        }

        // Calculate the periodic interest rate based on selected repayment frequency.
        $weekly_interest_rate = ($interest_rate / 100) / $periods_per_year;
        $processing_rate_weekely = ($loan->processing_fee / 100) / $periods_per_year;

        // Calculate the total processing fee amount
        $payment_amount_processing_fee = $loan_amount * $processing_rate_weekely;
//		calulate weely addend
        $weekly_addend = $payment_amount_processing_fee / $loan_term;
        $payment_amount_processing_fee = $loan_amount * $processing_rate_weekely;
        // Calculate the payment amount
        $payment_amount = $loan_amount / $num_payments;

        // Calculate the interest amount for each payment
        $interest_amount = ($loan_amount * $weekly_interest_rate);
        $total_interest_amount = ($loan_amount * $weekly_interest_rate)*$loan_term;

        // Calculate the principal amount for each payment
        $principal_amount = $payment_amount - $interest_amount;

        // Initialize the amortization schedule array
        $amortization_schedule = array();

        $use_zitsamba_biweekly = ($frequency === 'Bi weekly' && $this->is_zitsamba_biweekly_product($loan));

        // Initialize the payment date to the given start date
        $payment_date = new DateTime($start_date);
        if ($frequency === 'Monthly') {
            $payment_date = new DateTime($this->build_month_end_schedule_date($start_date, 0));
        } elseif (!$use_zitsamba_biweekly) {
            $payment_date->modify('+' . $period_interval_weeks . ' weeks');
        }

        // Loop through each payment period and calculate the payment details




        //$monthly_payment = $amount*($i/12)*pow((1+$i/12),$months)/(pow((1+$i/12),$months)-1);

        $data = array(

            'loan_product' => $product_id,
            'loan_customer' => $loan_customer,
            'customer_type' => $customer_type,
            'loan_date' => $start_date,
            'loan_principal' => $principal,
            'loan_period' => $loan_term,
            'worthness_file' => $worthness_file,
            'narration' => $narration,
            'period_type' => $frequency,
            'loan_amount_term' => $payment_amount+$weekly_addend,
            'loan_interest' => $loan->interest,
            'loan_interest_amount' => $total_interest_amount,
            'admin_fee' => $loan->admin_fees,
            'admin_fees_amount' => 0,
            'loan_cover' => $loan->loan_cover,
            'loan_cover_amount' => 0,
            'loan_amount_total' => $loan_amount+$total_interest_amount+$payment_amount_processing_fee,
            'loan_added_by' => $added_by,
            'counter' => $fcounter
        );
        $this->db->where('loan_id',$lidd);
        $this->db->update($this->table, $data);


        // borrower_loan_id
        $id = $lidd;

        $existing_schedule_state = $this->capture_schedule_payment_state($lidd);

        $this->db->where('loan_id',$lidd)->delete('payement_schedules');

        for ($i = 1; $i <= $num_payments; $i++) {
            if ($use_zitsamba_biweekly) {
                $schedule_date = $this->build_zitsamba_biweekly_schedule_date($start_date, $i);
            } else {
                if ($frequency !== 'Monthly' && $payment_date->format('N') >= 6) {
                    $payment_date->modify('next monday');
                }
                $schedule_date = $payment_date->format('Y-m-d');
            }

            // Calculate installment split using opening balance for this period.
            $opening_balance = $loan_amount - (($i - 1) * $payment_amount);
            $interest_payment = $opening_balance * $weekly_interest_rate;
            $principal_payment = $payment_amount - $interest_payment;
            $loan_balance = $opening_balance - $principal_payment;
            if ($loan_balance < 0) {
                $loan_balance = 0;
            }

            // Add the payment details to the amortization schedule array
            $amortization_schedule[] = array(
                'payment_number' => $i,
                'payment_date' => $schedule_date,
                'payment_amount' => $payment_amount+$weekly_addend,
                'interest_amount' => $interest_payment,
                'principal_amount' => $principal_payment,
                'loan_balance' => $loan_balance,
            );

            $carried_paid_amount = isset($existing_schedule_state[$i]) ? $existing_schedule_state[$i]['paid_amount'] : 0;
            if ($carried_paid_amount <= 0) {
                $schedule_status = 'NOT PAID';
                $schedule_partial_paid = 'NO';
            } elseif ($carried_paid_amount + 0.0001 >= $payment_amount) {
                $schedule_status = 'PAID';
                $schedule_partial_paid = 'NO';
            } else {
                $schedule_status = 'PARTIAL PAID';
                $schedule_partial_paid = 'YES';
            }

            $this->db->insert(
                'payement_schedules', array(

                    'customer' => $loan_customer,
                    'customer_type' => $customer_type,
                    'loan_id' => $id,
                    'payment_schedule' => $schedule_date,
                    'payment_number' => $i,
                    'amount' => $payment_amount,
                    'principal' => $principal_payment,
                    'interest' => $interest_payment,
                    'padmin_fee' => 0,
                    'ploan_cover' => 0,
                    'paid_amount' => $carried_paid_amount,
                    'loan_balance' => $loan_balance,
                    'loan_date' => $start_date,
                    'status' => $schedule_status,
                    'partial_paid' => $schedule_partial_paid,

                )
            );



            if ($use_zitsamba_biweekly) {
                // Dates are computed per installment.
            } elseif ($frequency === 'Monthly') {
                $payment_date->modify('first day of next month');
                $payment_date->modify('last day of this month');
            } else {
                $payment_date->modify('+' . $period_interval_weeks . ' weeks');
            }
        }

        $this->restore_schedule_payment_state($id, $existing_schedule_state);

        return $id;


    }
function add_amortization_biweekly($principal, $loan_amount, $product_id, $loan_term, $start_date, $loan_customer, $customer_type, $worthness_file, $narration, $added_by, $branch, $funds_source = null, $batch = null, $from_group = 'No', $group_id = null) {
  $loan_num_data = $this->generate_loan_number();
  $loanid   = $loan_num_data['loanid'];
  $fcounter = $loan_num_data['fcounter'];

  // Get loan product details
  $loan = check_exist_in_table('loan_products', 'loan_product_id', $product_id);

  // Calculate the total number of payments (bi-weekly instead of weekly)
  $num_payments = $loan_term;
  $interest_rate = $loan->interest;

  // Calculate the bi-weekly interest rate (26 periods per year instead of 52)
  $biweekly_interest_rate = ($interest_rate / 100) / 26;
  $processing_rate_biweekly = ($loan->processing_fees / 100) / 26;

  // Calculate the total processing fee amount
  $payment_amount_processing_fee = $loan_amount * $processing_rate_biweekly;

  // Calculate bi-weekly addend
  $biweekly_addend = $payment_amount_processing_fee / $loan_term;

  // Calculate the payment amount
  $payment_amount = $loan_amount / $num_payments;

  // Calculate the interest amount for each payment
  $interest_amount = ($loan_amount * $biweekly_interest_rate);
  $total_interest_amount = ($loan_amount * $biweekly_interest_rate) * $loan_term;

  // Initialize the amortization schedule array
  $amortization_schedule = array();

  $use_zitsamba_biweekly = $this->is_zitsamba_biweekly_product($loan);
  $payment_date = new DateTime($start_date);
  if (!$use_zitsamba_biweekly) {
      $payment_date->modify('+2 weeks');
  }

  // Prepare loan data for insertion
  $data = array(
   'loan_number' => $loanid,
   'loan_product' => $product_id,
   'loan_customer' => $loan_customer,
   'customer_type' => $customer_type,
   'loan_date' => $start_date,
   'loan_principal' => $principal,
   'loan_period' => $loan_term,
   'worthness_file' => $worthness_file,
   'narration' => $narration,
   'period_type' => 'Bi weekly', // Set period type to bi-weekly
   'loan_amount_term' => $payment_amount + $biweekly_addend,
   'loan_interest' => $loan->interest,
   'loan_interest_amount' => $total_interest_amount,
   'admin_fee' => $loan->admin_fees,
   'admin_fees_amount' => 0,
   'loan_cover' => $loan->loan_cover,
   'loan_cover_amount' => 0,
   'loan_amount_total' => $loan_amount + $total_interest_amount + $payment_amount_processing_fee,
   'next_payment_id' => 1,
   'loan_added_by' => $added_by,
   'counter' => $fcounter,
   'branch' => $branch,
   'funds_source' => $funds_source,
   'batch' => $batch,
   'from_group' => $from_group,
   'group_id' => $group_id
  );
  $this->db->insert($this->table, $data);

  // Get the loan ID from the insert
  $id = $this->db->insert_id();

  // Create payment schedules
  for ($i = 1; $i <= $num_payments; $i++) {
   if ($use_zitsamba_biweekly) {
    $schedule_date = $this->build_zitsamba_biweekly_schedule_date($start_date, $i);
   } else {
    if ($payment_date->format('N') >= 6) {
     $payment_date->modify('next monday');
    }
    $schedule_date = $payment_date->format('Y-m-d');
   }

   // Calculate the remaining loan balance
   $loan_balance = $loan_amount - ($i * $payment_amount);

   // Calculate the interest and principal amounts for this payment
   $interest_payment = ($i == 1) ? $interest_amount : $interest_amount + ($loan_balance * $biweekly_interest_rate);
   $principal_payment = $payment_amount - $interest_payment;

   // Add the payment details to the amortization schedule
   $amortization_schedule[] = array(
    'payment_number' => $i,
    'payment_date' => $schedule_date,
    'payment_amount' => $payment_amount + $biweekly_addend,
    'interest_amount' => $interest_payment,
    'principal_amount' => $principal_payment,
    'loan_balance' => $loan_balance,
   );

   // Insert payment schedule into database
   $this->db->insert(
    'payement_schedules', array(
     'customer' => $loan_customer,
     'customer_type' => $customer_type,
     'loan_id' => $id,
     'payment_schedule' => $schedule_date,
     'payment_number' => $i,
     'amount' => $payment_amount,
     'principal' => $principal_payment,
     'interest' => $interest_payment,
     'padmin_fee' => 0,
     'ploan_cover' => 0,
     'paid_amount' => 0,
     'loan_balance' => $loan_balance,
     'loan_date' => $start_date,
    )
   );

   if (!$use_zitsamba_biweekly) {
    $payment_date->modify('+2 weeks');
   }
  }

  // Create account record
  $data_account = array(
   'client_id' => $loan_customer,
   'account_number' => $loanid,
   'balance' => 0,
   'account_type' => 2,
   'account_type_product' => $product_id,
  );

  $this->db->insert('account', $data_account);
  return $id;
}
    function add_amortization_straight_weekly($principal,$loan_amount, $product_id, $loan_term, $start_date,$loan_customer, $customer_type, $worthness_file,$narration,$added_by, $branch, $funds_source = null, $batch = null, $from_group = 'No', $group_id = null) {
		$loan_num_data = $this->generate_loan_number();
		$loanid   = $loan_num_data['loanid'];
		$fcounter = $loan_num_data['fcounter'];




		// Calculate the total number of payments
		$loan = check_exist_in_table('loan_products','loan_product_id',$product_id);
		$num_payments = $loan_term;
		$interest_rate =  $loan->interest ;
		// Calculate the weekly interest rate
		$weekly_interest_rate = ($interest_rate / 100) / 52;
		$processing_rate_weekely = ($loan->processing_fee / 100) / 52;

		// Calculate the total processing fee amount
		$payment_amount_processing_fee = $loan_amount * $processing_rate_weekely;
//		calulate weely addend
		$weekly_addend = $payment_amount_processing_fee / $loan_term;
		$payment_amount_processing_fee = $loan_amount * $processing_rate_weekely;
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
		$payment_date->modify('+2 weeks');

		// Loop through each payment period and calculate the payment details




		//$monthly_payment = $amount*($i/12)*pow((1+$i/12),$months)/(pow((1+$i/12),$months)-1);

		$data = array(
			'loan_number' => $loanid,
			'loan_product' => $product_id,
			'loan_customer' => $loan_customer,
			'customer_type' => $customer_type,
			'loan_date' => $start_date,
			'loan_principal' => $principal,
			'loan_period' => $loan_term,
			'worthness_file' => $worthness_file,
			'narration' => $narration,
			'period_type' => $loan->frequency,
			'loan_amount_term' => $payment_amount+$weekly_addend,
			'loan_interest' => $loan->interest,
			'loan_interest_amount' => $total_interest_amount,
			'admin_fee' => $loan->admin_fees,
			'admin_fees_amount' => 0,
			'loan_cover' => $loan->loan_cover,
			'loan_cover_amount' => 0,
			'loan_amount_total' => $loan_amount+$total_interest_amount+$payment_amount_processing_fee,
			'next_payment_id' => 1,
			'loan_added_by' => $added_by,
			'counter' => $fcounter,
			'branch' => $branch,
			'funds_source' => $funds_source,
			'batch' => $batch,
			'from_group' => $from_group,
			'group_id' => $group_id
		);
		$this->db->insert($this->table, $data);


		//borrower_loan_id
		$id = $this->db->insert_id();




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
				'payment_amount' => $payment_amount+$weekly_addend,
				'interest_amount' => $interest_payment,
				'principal_amount' => $principal_payment,
				'loan_balance' => $loan_balance,
			);

			$this->db->insert(
				'payement_schedules', array(

					'customer' => $loan_customer,
					'customer_type' => $customer_type,
					'loan_id' => $id,
					'payment_schedule' => $payment_date->format('Y-m-d'),
					'payment_number' => $i,
					'amount' => $payment_amount,
					'principal' => $principal_payment,
					'interest' => $interest_payment,
					'padmin_fee' => 0,
					'ploan_cover' => 0,
					'paid_amount' => 0,
					'loan_balance' => $loan_balance,
					'loan_date' => $start_date,

				)
			);



			// Move the payment date to the next week
			$payment_date->modify('+1 week');
		}
		$data_account = array(
			'client_id' => $loan_customer,
			'account_number' => $loanid,
			'balance' => 0,
			'account_type' => 2,
			'account_type_product' => $product_id,


		);

		$this->db->insert('account', $data_account);
		return $id;


	}

	function add_reducing_balance_weekly($original_amount,$principal, $months, $product_id, $ldate, $loan_customer, $customer_type, $worthness_file, $narration, $added_by, $branch, $funds_source = null, $batch = null, $from_group = 'No', $group_id = null) {
        $original_amount = $this->normalize_numeric_value($original_amount);
        $principal = $this->normalize_numeric_value($principal);
        $months = (int)$this->normalize_numeric_value($months);
        $ldate = $this->normalize_date_value($ldate);
        if ($months <= 0) {
            throw new Exception('Loan period must be greater than zero.');
        }

		// Generate loan number (lock-safe)
		$loan_num_data = $this->generate_loan_number();
		$loanid   = $loan_num_data['loanid'];
		$fcounter = $loan_num_data['fcounter'];

		// Get loan product details
		$loan = $this->db->select("*")->from('loan_products')->where('loan_product_id', $product_id)->get()->row();

		// Set default values for missing properties
        $loan->loan_cover = isset($loan->loan_cover) ? $this->normalize_numeric_value($loan->loan_cover) : 0;
        $loan->admin_fees = isset($loan->admin_fees) ? $this->normalize_numeric_value($loan->admin_fees) : 0;

		// Check both field name variations for processing fee
		if(isset($loan->processing_fees)) {
            $processing_fee_percent = $this->normalize_numeric_value($loan->processing_fees);
		} elseif(isset($loan->processing_fee)) {
            $processing_fee_percent = $this->normalize_numeric_value($loan->processing_fee);
		} else {
			$processing_fee_percent = 0;
		}

		// Calculate processing fee and add to principal
		$processing_fee_amount = ($processing_fee_percent / 100) * $original_amount;
		$amount = $principal; // Principal already includes processing fee from caller

		// Set frequency parameters for Weekly
		$days = 7;
        $loan_date = $ldate;

		// Calculate interest rates
        $i = ($this->normalize_numeric_value($loan->interest) / 100) * 12;
		$af = ($loan->admin_fees / 100) * 12;
		$lc = ($loan->loan_cover / 100) * 12;
		$total_deduction = $i + $af + $lc;

		// Calculate monthly payment using amortization formula with new principal (including processing fee)
		$monthly_payment = ($amount * ($total_deduction / 12) * pow((1 + $total_deduction / 12), $months) / (pow((1 + $total_deduction / 12), $months) - 1));
		$monthly_payment_config = $monthly_payment;

		// Calculate totals for loan record
		$current_balance1 = $amount;
		$total_interest1 = 0;
		$total_admin_fees = 0;
		$total_loan_cover = 0;

		// Calculate total interest, admin fees, and loan cover
		while ($current_balance1 > 0) {
			$towards_interest1 = ($i / 12) * $current_balance1;
			$towards_fees = ($af / 12) * $current_balance1;
			$towards_lc = ($lc / 12) * $current_balance1;

			if ($monthly_payment > $current_balance1) {
				$monthly_payment_temp = $current_balance1 + $towards_interest1 + $towards_fees + $towards_lc;
			} else {
				$monthly_payment_temp = $monthly_payment;
			}

			$towards_balance1 = $monthly_payment_temp - ($towards_interest1 + $towards_fees + $towards_lc);
			$total_interest1 = $total_interest1 + $towards_interest1;
			$total_admin_fees = $total_admin_fees + $towards_fees;
			$total_loan_cover = $total_loan_cover + $towards_lc;
			$current_balance1 = $current_balance1 - $towards_balance1;
		}

		$pay_total = $total_interest1 + $amount + $total_admin_fees + $total_loan_cover;

		// Insert loan record
		$data = array(
			'loan_number' => $loanid,
			'loan_product' => $product_id,
			'loan_customer' => $loan_customer,
			'customer_type' => $customer_type,
            'loan_date' => $loan_date,
			'loan_principal' => $principal,
			'loan_period' => $months,
			'worthness_file' => $worthness_file,
			'narration' => $narration,
			'period_type' => $loan->frequency,
			'loan_amount_term' => $monthly_payment_config,
			'loan_interest' => $loan->interest,
			'loan_interest_amount' => $total_interest1,
			'admin_fee' => $loan->admin_fees,
			'admin_fees_amount' => $total_admin_fees,
			'loan_cover' => $loan->loan_cover,
			'loan_cover_amount' => $total_loan_cover,
			'loan_amount_total' => $pay_total,
			'next_payment_id' => 1,
			'loan_added_by' => $added_by,
			'counter' => $fcounter,
			'branch' => $branch,
			'funds_source' => $funds_source,
			'batch' => $batch,
			'from_group' => $from_group,
			'group_id' => $group_id,
		);
		$this->db->insert($this->table, $data);

		// Get the inserted loan ID
		$id = $this->db->insert_id();

		// Create payment schedules using reducing balance calculation
		$current_balance = $amount;
		$payment_counter = 1;
		$date = $loan_date;

		// Add 2 weeks grace period
		$date = date('Y-m-d', strtotime('+2 weeks', strtotime($date)));

		while ($current_balance > 0 && $payment_counter <= $months) {
			// Calculate portions for each payment
			$towards_interest = ($i / 12) * $current_balance;
			$towards_fees1 = ($af / 12) * $current_balance;
			$towards_lc1 = ($lc / 12) * $current_balance;

			// Adjust last payment if needed
			if ($monthly_payment > $current_balance) {
				$monthly_payment_actual = $current_balance + $towards_interest + $towards_fees1 + $towards_lc1;
			} else {
				$monthly_payment_actual = $monthly_payment;
			}

			$towards_balance = $monthly_payment_actual - ($towards_interest + $towards_fees1 + $towards_lc1);
			$current_balance = $current_balance - $towards_balance;

			// Calculate payment date (weekly frequency)
			$frequency = $days * $payment_counter;
			$newdate = strtotime('+' . $frequency . ' day', strtotime($date));
			$newdate = date('Y-m-d', $newdate);
			// Roll Sunday to Monday (7 = Sunday in date('N'))
			if (date('N', strtotime($newdate)) == 7) {
				$newdate = date('Y-m-d', strtotime('+1 day', strtotime($newdate)));
			}
			// Prevent duplicate schedule dates (Issue 2)
			$newdate = $this->_ensure_schedule_date_unique($id, $newdate);

			// Insert payment schedule
			$this->db->insert(
				'payement_schedules', array(
					'customer' => $loan_customer,
					'customer_type' => $customer_type,
					'loan_id' => $id,
					'payment_schedule' => $newdate,
					'payment_number' => $payment_counter,
					'amount' => $monthly_payment_actual,
					'principal' => $towards_balance,
					'interest' => $towards_interest,
					'padmin_fee' => $towards_fees1,
					'ploan_cover' => $towards_lc1,
					'paid_amount' => 0,
					'loan_balance' => $current_balance,
					'loan_date' => $loan_date,
				)
			);

			$payment_counter++;
		}

		// Create account record
		$data_account = array(
			'client_id' => $loan_customer,
			'account_number' => $loanid,
			'balance' => 0,
			'account_type' => 2,
			'account_type_product' => $product_id,
		);
		$this->db->insert('account', $data_account);

		return $id;
	}

	function add_reducing_balance_biweekly($original_amount, $months, $product_id, $ldate, $loan_customer, $customer_type, $worthness_file, $narration, $added_by, $branch, $funds_source = null, $batch = null, $from_group = 'No', $group_id = null) {
        $original_amount = $this->normalize_numeric_value($original_amount);
        $months = (int)$this->normalize_numeric_value($months);
        $ldate = $this->normalize_date_value($ldate);
        if ($months <= 0) {
            throw new Exception('Loan period must be greater than zero.');
        }

		// Generate loan number (lock-safe)
		$loan_num_data = $this->generate_loan_number();
		$loanid   = $loan_num_data['loanid'];
		$fcounter = $loan_num_data['fcounter'];

		// Get loan product details
		$loan = $this->db->select("*")->from('loan_products')->where('loan_product_id', $product_id)->get()->row();

		// Set default values for missing properties
        $loan->loan_cover = isset($loan->loan_cover) ? $this->normalize_numeric_value($loan->loan_cover) : 0;
        $loan->admin_fees = isset($loan->admin_fees) ? $this->normalize_numeric_value($loan->admin_fees) : 0;

		// Check both field name variations for processing fee
		if(isset($loan->processing_fees)) {
			$processing_fee_percent = $loan->processing_fees;
		} elseif(isset($loan->processing_fee)) {
			$processing_fee_percent = $loan->processing_fee;
		} else {
			$processing_fee_percent = 0;
		}

		// Calculate processing fee and add to principal
		$processing_fee_amount = ($processing_fee_percent / 100) * $original_amount;
		$amount = $original_amount + $processing_fee_amount; // New principal with processing fee included

		// Set frequency parameters for Bi-weekly
		$days = 15;
        $loan_date = $ldate;

		// Calculate interest rates
		$i = ($loan->interest / 100) * 12;
		$af = ($loan->admin_fees / 100) * 12;
		$lc = ($loan->loan_cover / 100) * 12;
		$total_deduction = $i + $af + $lc;

		// Calculate monthly payment using amortization formula with new principal (including processing fee)
		$monthly_payment = ($amount * ($total_deduction / 12) * pow((1 + $total_deduction / 12), $months) / (pow((1 + $total_deduction / 12), $months) - 1));
		$monthly_payment_config = $monthly_payment;

		// Calculate totals for loan record
		$current_balance1 = $amount;
		$total_interest1 = 0;
		$total_admin_fees = 0;
		$total_loan_cover = 0;

		// Calculate total interest, admin fees, and loan cover
		while ($current_balance1 > 0) {
			$towards_interest1 = ($i / 12) * $current_balance1;
			$towards_fees = ($af / 12) * $current_balance1;
			$towards_lc = ($lc / 12) * $current_balance1;

			if ($monthly_payment > $current_balance1) {
				$monthly_payment_temp = $current_balance1 + $towards_interest1 + $towards_fees + $towards_lc;
			} else {
				$monthly_payment_temp = $monthly_payment;
			}

			$towards_balance1 = $monthly_payment_temp - ($towards_interest1 + $towards_fees + $towards_lc);
			$total_interest1 = $total_interest1 + $towards_interest1;
			$total_admin_fees = $total_admin_fees + $towards_fees;
			$total_loan_cover = $total_loan_cover + $towards_lc;
			$current_balance1 = $current_balance1 - $towards_balance1;
		}

		$pay_total = $total_interest1 + $amount + $total_admin_fees + $total_loan_cover;

		// Insert loan record
		$data = array(
			'loan_number' => $loanid,
			'loan_product' => $product_id,
			'loan_customer' => $loan_customer,
			'customer_type' => $customer_type,
            'loan_date' => $loan_date,
			'loan_principal' => $amount,
			'loan_period' => $months,
			'worthness_file' => $worthness_file,
			'narration' => $narration,
			'period_type' => $loan->frequency,
			'loan_amount_term' => $monthly_payment_config,
			'loan_interest' => $loan->interest,
			'loan_interest_amount' => $total_interest1,
			'admin_fee' => $loan->admin_fees,
			'admin_fees_amount' => $total_admin_fees,
			'loan_cover' => $loan->loan_cover,
			'loan_cover_amount' => $total_loan_cover,
			'loan_amount_total' => $pay_total,
			'next_payment_id' => 1,
			'loan_added_by' => $added_by,
			'counter' => $fcounter,
			'branch' => $branch,
			'funds_source' => $funds_source,
			'batch' => $batch,
			'from_group' => $from_group,
			'group_id' => $group_id,
		);
		$this->db->insert($this->table, $data);

		// Get the inserted loan ID
		$id = $this->db->insert_id();

		// Create payment schedules using reducing balance calculation with bi-weekly frequency
		$current_balance = $amount;
		$payment_counter = 1;
		$use_zitsamba_biweekly = $this->is_zitsamba_biweekly_product($loan);
		$date = $loan_date;

		if (!$use_zitsamba_biweekly) {
			$loan_day = date('d', strtotime($date));
			$loan_month = date('m', strtotime($date));

			if ($loan_day >= 15) {
				$start_day = ($loan_month == '02') ? 28 : 30;
			} else {
				$start_day = 15;
			}

			$date = date('Y/m/' . $start_day, strtotime($date));

			if ($start_day == 15) {
				if (date('m', strtotime($date)) == '02') {
					$date = date('Y/02/28', strtotime($date));
				} else {
					$date = date('Y/m/30', strtotime($date));
				}
			} else {
				$date = date('Y/m/15', strtotime('+1 month', strtotime($date)));
			}
		}

		while ($current_balance > 0 && $payment_counter <= $months) {
			$towards_interest = ($i / 12) * $current_balance;
			$towards_fees1 = ($af / 12) * $current_balance;
			$towards_lc1 = ($lc / 12) * $current_balance;

			if ($monthly_payment > $current_balance) {
				$monthly_payment_actual = $current_balance + $towards_interest + $towards_fees1 + $towards_lc1;
			} else {
				$monthly_payment_actual = $monthly_payment;
			}

			$towards_balance = $monthly_payment_actual - ($towards_interest + $towards_fees1 + $towards_lc1);
			$current_balance = $current_balance - $towards_balance;

			if ($use_zitsamba_biweekly) {
				$schedule_date = $this->build_zitsamba_biweekly_schedule_date($loan_date, $payment_counter);
			} else {
				$schedule_date = $date;
				if (date('N', strtotime($date)) == 7) {
					$schedule_date = date('Y-m-d', strtotime('+1 day', strtotime($date)));
				}
			}
			$schedule_date = $this->_ensure_schedule_date_unique($id, $schedule_date);

			$this->db->insert(
				'payement_schedules', array(
					'customer' => $loan_customer,
					'customer_type' => $customer_type,
					'loan_id' => $id,
					'payment_schedule' => $schedule_date,
					'payment_number' => $payment_counter,
					'amount' => $monthly_payment_actual,
					'principal' => $towards_balance,
					'interest' => $towards_interest,
					'padmin_fee' => $towards_fees1,
					'ploan_cover' => $towards_lc1,
					'paid_amount' => 0,
					'loan_balance' => $current_balance,
					'loan_date' => $loan_date,
				)
			);

			if (!$use_zitsamba_biweekly) {
				$day = date('d', strtotime($date));
				if ($day == 15) {
					if (date('m', strtotime($date)) == '02') {
						$date = date('Y/02/28', strtotime($date));
					} else {
						$date = date('Y/m/30', strtotime($date));
					}
				} elseif ($day == 30 or $day > 15) {
					if (date('m', strtotime($date)) == '01') {
						$date = date('Y/02/15', strtotime('+1 month', strtotime($date)));
					} else {
						$date = date('Y/m/15', strtotime('+1 month', strtotime($date)));
					}
				}
			}

			$payment_counter++;
		}

		// Create account record
		$data_account = array(
			'client_id' => $loan_customer,
			'account_number' => $loanid,
			'balance' => 0,
			'account_type' => 2,
			'account_type_product' => $product_id,
		);
		$this->db->insert('account', $data_account);

		return $id;
	}

    function add_amortization_straight_weekly_rerun($principal,$loan_amount, $product_id, $loan_term, $start_date,$loan_customer, $customer_type, $loan_number, $loan_id,$fee, $frequency = 'Weekly') {
        $this->db->select('MAX(counter) as max_c');
        $lid = $this->db->get('loan');
        $result = $lid->row();
        $loanid= $loan_number;
        $fcounter=$result->max_c+1;




        // Calculate the total number of payments
        $loan = check_exist_in_table('loan_products','loan_product_id',$product_id);
        $num_payments = $loan_term;
        $interest_rate =  $loan->interest ;
        $frequency = $this->normalize_frequency_label($frequency);
        if ($frequency === 'Monthly') {
            $periods_per_year = 12;
            $period_interval_weeks = 0;
        } elseif ($frequency === 'Bi weekly') {
            $periods_per_year = 26;
            $period_interval_weeks = 2;
        } else {
            $periods_per_year = 52;
            $period_interval_weeks = 1;
        }

        // Calculate the periodic interest rate based on selected repayment frequency.
        $weekly_interest_rate = ($interest_rate / 100) / $periods_per_year;
        $processing_rate_weekely = ($fee / 100) / $periods_per_year;

        // Calculate the total processing fee amount
        $payment_amount_processing_fee = $loan_amount * $processing_rate_weekely;
//		calulate weely addend
        $weekly_addend = $payment_amount_processing_fee / $loan_term;
        $payment_amount_processing_fee = $loan_amount * $processing_rate_weekely;
        // Calculate the payment amount
        $payment_amount = $loan_amount / $num_payments;

        // Calculate the interest amount for each payment
        $interest_amount = ($loan_amount * $weekly_interest_rate);
        $total_interest_amount = ($loan_amount * $weekly_interest_rate)*$loan_term;

        // Calculate the principal amount for each payment
        $principal_amount = $payment_amount - $interest_amount;

        // Initialize the amortization schedule array
        $amortization_schedule = array();

        $use_zitsamba_biweekly = ($frequency === 'Bi weekly' && $this->is_zitsamba_biweekly_product($loan));

        // Initialize the payment date to the given start date
        $payment_date = new DateTime($start_date);
        if ($frequency === 'Monthly') {
            $payment_date->modify('last day of this month');
        } elseif (!$use_zitsamba_biweekly) {
            $payment_date->modify('+' . $period_interval_weeks . ' weeks');
        }

        // Loop through each payment period and calculate the payment details




        //$monthly_payment = $amount*($i/12)*pow((1+$i/12),$months)/(pow((1+$i/12),$months)-1);

        $data = array(
            'loan_number' => $loanid,
            'loan_product' => $product_id,
            'loan_customer' => $loan_customer,
            'customer_type' => $customer_type,
            'loan_date' => $start_date,
            'loan_principal' => $principal,
            'loan_period' => $loan_term,

            'period_type' => $frequency,
            'loan_amount_term' => $payment_amount+$weekly_addend,
            'loan_interest' => $loan->interest,
            'loan_interest_amount' => $total_interest_amount,
            'admin_fee' => $loan->admin_fees,
            'admin_fees_amount' => 0,
            'loan_cover' => $loan->loan_cover,
            'loan_cover_amount' => 0,
            'loan_amount_total' => $loan_amount+$total_interest_amount+$payment_amount_processing_fee,
            'next_payment_id' => 1,

            'counter' => $fcounter
        );
        $this->db->where('loan_id', $loan_id);
        $this->db->update($this->table, $data);

        for ($i = 1; $i <= $num_payments; $i++) {
            if ($use_zitsamba_biweekly) {
                $schedule_date = $this->build_zitsamba_biweekly_schedule_date($start_date, $i);
            } else {
                if ($frequency !== 'Monthly' && $payment_date->format('N') >= 6) {
                    $payment_date->modify('next monday');
                }
                $schedule_date = $payment_date->format('Y-m-d');
            }

            // Calculate installment split using opening balance for this period.
            $opening_balance = $loan_amount - (($i - 1) * $payment_amount);
            $interest_payment = $opening_balance * $weekly_interest_rate;
            $principal_payment = $payment_amount - $interest_payment;
            $loan_balance = $opening_balance - $principal_payment;
            if ($loan_balance < 0) {
                $loan_balance = 0;
            }

            // Add the payment details to the amortization schedule array
            $amortization_schedule[] = array(
                'payment_number' => $i,
                'payment_date' => $schedule_date,
                'payment_amount' => $payment_amount+$weekly_addend,
                'interest_amount' => $interest_payment,
                'principal_amount' => $principal_payment,
                'loan_balance' => $loan_balance,
            );
$this->db->where('loan_id',$loan_id);
$this->db->where('payment_number',$i);
            $this->db->update(
                'payement_schedules', array(

                    'customer' => $loan_customer,
                    'customer_type' => $customer_type,
                    'payment_schedule' => $schedule_date,

                    'payment_number' => $i,
                    'amount' => $payment_amount,
                    'principal' => $principal_payment,
                    'interest' => $interest_payment,
                    'padmin_fee' => 0,
                    'ploan_cover' => 0,
                    'paid_amount' => 0,
                    'loan_balance' => $loan_balance,
                    'status' => 'NOT PAID',
                    'partial_paid' => 'NO',
                    'paid_date' => null,

                )
            );

            if ($use_zitsamba_biweekly) {
                // Dates are computed per installment.
            } elseif ($frequency === 'Monthly') {
                $payment_date->modify('first day of next month');
                $payment_date->modify('last day of this month');
            } else {
                $payment_date->modify('+' . $period_interval_weeks . ' weeks');
            }
        }

        $this->db->where('loan_id', $loan_id)
            ->where('payment_number >', (int) $num_payments)
            ->delete('payement_schedules');

        return $loan_id;


    }

	function edits()
	{
		$this->db->select('*')->where('status','still');


		$r= $this->db->get('defect_loand')->result();
		$count = 0;

		foreach ($r as $rr){
			$mk = $this->db->select('*')->from($this->table)->where('loan_id',$rr->loan_id)->get()->row();
			$this->add_loan($mk->loan_principal, $mk->loan_period, $mk->loan_product,$mk->loan_date, $mk->loan_customer, $mk->customer_type, $mk->worthness_file, $mk->narration, $mk->loan_added_by);
			$this->db->where('loan_id', $rr->loan_id);
			$this->db->delete($this->table);

			$count ++;
		}
		echo $count;
	}

    function add_loan_edit($lidd,$lamount, $lmonths, $product_id, $ldate,$loan_customer, $customer_type,$worthness_file,$narration,$added_by, $period_type = null)
    {


        $this->db->select('MAX(counter) as max_c');
        $lid = $this->db->get('loan');
        $result = $lid->row();
        $loanid='SCL'.date("Ymd").(100+$result->max_c);
        $fcounter=$result->max_c+1;
        $amount = $this->normalize_numeric_value($lamount);
        $loan_date = $this->normalize_date_value($ldate);
        $original_loan_date = $loan_date;
        $months = (int)$this->normalize_numeric_value($lmonths);
        if ($months <= 0) {
            throw new Exception('Loan period must be at least 1.');
        }
        //get loan parameters
        $loan = $this->db->select("*")->from('loan_products')->where('loan_product_id',$product_id)->get()->row();
        $loan->frequency = $this->resolve_frequency_for_loan_edit($lidd, $product_id, $loan->frequency, $period_type);
        $existing_loan = $this->db->select('branch')->from('loan')->where('loan_id', $lidd)->get()->row();
        $existing_branch = $existing_loan ? $existing_loan->branch : null;
        $is_masamba_promotion = $this->is_masamba_promotion_for_lingwe_blantyre($loan, $existing_branch);
        if ($this->is_group_zitsamba_monthly_flat_product($loan)) {
            $existing_loan = $this->db->select('loan_number')->from($this->table)->where($this->id, $lidd)->get()->row();
            return $this->rerun_group_zitsamba_monthly_flat_loan(
                $amount,
                $months,
                $product_id,
                $loan_date,
                $loan_customer,
                $customer_type,
                $existing_loan ? $existing_loan->loan_number : $loanid,
                $lidd
            );
        }
//		echo $lamount;
//		print_r($loan);
//		exit();
        if (strcasecmp(trim($loan->method), 'Straight line') === 0 && ($loan->frequency == 'Weekly' || $loan->frequency == 'Bi weekly')) {
            $principal = (($loan->processing_fees/100)*$amount)+$amount;
            $p = $amount;
            $this->add_amortization_straight_weekly_edit($lidd,$p,$principal, $product_id, $months, $loan_date,$loan_customer,$customer_type,$worthness_file,$narration,$added_by, $loan->frequency);
        }else {
            //divisor
            switch ($loan->frequency) {
                case 'Monthly':
                    $divisor = 1;
                    $days = 30;
                    $days2 = 34;
                    break;
                case 'Bi weekly':
                    $divisor = 2;
                    $days = 15;
                    break;
                case 'Weekly':
                    $divisor = 4;
                    $days = 7;
                    break;
            }

            //interest
            $amount_interest = $amount * (($loan->interest / 100) * 12);
            $date = $ldate;
//			$day = date('d', strtotime($date));
            $extra_interest = 0;
            $extra_days = 0;

            $total_extra_interest = 0;
            $date = $loan_date;
            $day = date('d', strtotime($date));
            if ($day > 15 && $loan->frequency == "Monthly" && $loan->schedule_plan=="cut off") {
//				    find last day of the effective date of loan
                $last_date = date("t", strtotime($date));
//                    if loan date is above 15 then the effective date should be 1st day of next month
                $month_end_date = date("Y-m-t", strtotime($date));
//                    add 1 to last date of this month
                $ll = strtotime('+1 day', strtotime($month_end_date));
                $loan_date = date('Y-m-d', $ll);

//				    calculate number of xtra days
                $earlier = new DateTime($date);
                $later = new DateTime($loan_date);
                $date_diff = $later->diff($earlier);
                $extra_days = $date_diff->d;


//lets calculate interest of these dayes
                $total_extra_interest = (($extra_days) / 30) * $amount * (($loan->interest + $loan->loan_cover) / 100);
                $extra_interest = $total_extra_interest / $months;

            } elseif ($day >= 1 && $day < 15 && $loan->frequency == "Monthly" && $loan->schedule_plan=="cut off") {
                //                    if loan date is above 15 then the effective date should be 1st day of next month
                $month_start_date = date("Y-m-01", strtotime($date));
                $loan_date = date('Y-m-d', strtotime($month_start_date));

//				    calculate number of xtra days
                $earlier = new DateTime($month_start_date);
                $later = new DateTime($date);
                $date_diff = $later->diff($earlier);
                $extra_days = $date_diff->d;


//lets calculate interest of these dayes
                $total_extra_interest = (($extra_days) / 30) * $amount * (($loan->interest + $loan->loan_cover) / 100);
                $extra_interest = $total_extra_interest / $months;

            }elseif ( $loan->frequency == "Monthly" && $loan->schedule_plan=="no cutoff") {
                //                    if loan date is above 15 then the effective date should be 1st day of next month

                $loan_date = $date;

//				    calculate number of xtra days

                $extra_interest = 0;

            }

            // Keep the stored loan date exactly as selected by the user.
            $loan_date = $original_loan_date;


            $i = ($loan->interest / 100) * 12;
            $af = ($loan->admin_fees / 100) * 12;
            $lc = ($loan->loan_cover / 100) * 12;
            $total_deduction = $i + $af + $lc;

            $monthly_payment = $this->calculate_amortized_installment($amount, $months, $total_deduction);
            $monthly_payment1 = $monthly_payment;
            $monthly_payment_config = $monthly_payment;
            $current_balance = $amount;
            $current_balance1 = $amount;
            $payment_counter = 1;
            $total_interest = 0;
            $total_interest1 = 0;
            $total_admin_fees = 0;
            $total_admin_fees1 = 0;
            $total_loan_cover = 0;
            $total_loan_cover1 = 0;


            $ii = 1;


            while ($current_balance1 > 0) {
                //create rows


                $towards_interest1 = ($i / 12) * $current_balance1;  //this calculates the portion of your monthly payment that goes towards interest
                $towards_fees = ($af / 12) * $current_balance1;  //this calculates the portion of your monthly payment that goes towards administration fees
                $towards_lc = ($lc / 12) * $current_balance1;  //this calculates the portion of your monthly payment that goes towards administration fees

                if ($monthly_payment1 > $current_balance1) {
                    $monthly_payment1 = $current_balance1 + $towards_interest1 + $towards_fees + $towards_lc;

                }
                $towards_balance1 = $monthly_payment1 - ($towards_interest1 + $towards_fees + $towards_lc);
                $total_interest1 = $total_interest1 + $towards_interest1;
                $total_admin_fees = $total_admin_fees + $towards_fees;
                $total_loan_cover = $total_loan_cover + $towards_lc;
                $current_balance1 = $current_balance1 - $towards_balance1;

            }



            if($day > 15 && $loan->frequency=="Monthly" && $loan->schedule_plan=="cut off") {
                $m_config = $monthly_payment_config + $extra_interest;
                $pay_total = $total_interest1 + $amount + $total_admin_fees + $total_loan_cover+$total_extra_interest;
                $total_interest1 = $total_interest1 + $total_extra_interest;
            }elseif ($day >=1 && $day < 15 && $loan->frequency=="Monthly" && $loan->schedule_plan=="cut off"){
                $m_config = $monthly_payment_config - $extra_interest;
                $pay_total = $total_interest1 + $amount + $total_admin_fees + $total_loan_cover-$total_extra_interest;
                $total_interest1 = $total_interest1 - $total_extra_interest;
            }else{
                $pay_total = $total_interest1 + $amount + $total_admin_fees + $total_loan_cover;
                $m_config = $monthly_payment_config ;
                $total_interest1 = $total_interest1 + $total_extra_interest;
            }


            //additional info to be insert


            $data = array(

                'loan_product' => $product_id,
                'loan_customer' => $loan_customer,
                'customer_type' => $customer_type,
                'loan_date' => $loan_date,
                'loan_principal' => $amount,
                'loan_period' => $months,
                'worthness_file' => $worthness_file,
                'narration' => $narration,
                'period_type' => $loan->frequency,
                'loan_amount_term' => $m_config,
                'loan_interest' => $loan->interest,
                'loan_interest_amount' => $total_interest1,
                'admin_fee' => $loan->admin_fees,
                'admin_fees_amount' => $total_admin_fees,
                'loan_cover' => $loan->loan_cover,
                'loan_cover_amount' => $total_loan_cover,
                'loan_amount_total' => $pay_total,

                'loan_added_by' => $added_by,
                'counter' => $fcounter
            );
            $this->db->where('loan_id', $lidd);
            $this->db->update($this->table, $data);


            //borrower_loan_id
            $id = $lidd;
            $existing_schedule_state = $this->capture_schedule_payment_state($lidd);

            //insert each payment records to lend_payments
            if ($loan->frequency == 'Bi weekly') {
                $frequency = $months * 2;
                $this->db->where('loan_id', $lidd)->delete('payement_schedules');
                if ($this->is_zitsamba_biweekly_product($loan)) {
                    for ($i = 1; $i <= $frequency; $i++) {
                        $schedule_date = $this->build_zitsamba_biweekly_schedule_date($loan_date, $i);
                        $this->db->insert(
                            'payement_schedules', array(
                                'customer' => $loan_customer,
                                'loan_id' => $id,
                                'payment_schedule' => $schedule_date,
                                'payment_number' => $i,
                                'amount' => $monthly_payment1,
                                'principal' => $towards_balance1,
                                'interest' => $total_interest1,
                                'paid_amount' => 0,
                                'loan_balance' => $current_balance1,
                                'loan_date' => $loan_date,
                            )
                        );
                    }
                } else {
                    $date = $loan_date;
                    $loan_day = date('d', strtotime($date));
                    $loan_month = date('m', strtotime($date));
                    $start_day = ($loan_day >= 15) ? (($loan_month == '02') ? 28 : 30) : 15;
                    $date = date('Y/m/' . $start_day, strtotime($date));
                    for ($i = 1; $i <= $frequency; $i++) {
                        $this->db->insert(
                            'payement_schedules', array(
                                'customer' => $loan_customer,
                                'loan_id' => $id,
                                'payment_schedule' => $date,
                                'payment_number' => $i,
                                'amount' => $monthly_payment1,
                                'principal' => $towards_balance1,
                                'interest' => $total_interest1,
                                'paid_amount' => 0,
                                'loan_balance' => $current_balance1,
                                'loan_date' => $loan_date,
                            )
                        );

                        $day = date('d', strtotime($date));
                        if ($day == 15) {
                            if (date('m', strtotime($date)) == '02') {
                                $date = date('Y/02/28', strtotime($date));
                            } else {
                                $date = date('Y/m/30', strtotime($date));
                            }
                        } elseif ($day == 30 or $day > 15) {
                            if (date('m', strtotime($date)) == '01') {
                                $date = date('Y/02/15', strtotime('+1 month', strtotime($date)));
                            } else {
                                $date = date('Y/m/15', strtotime('+1 month', strtotime($date)));
                            }
                        }
                    }
                }
            }
            elseif ($loan->frequency == 'Weekly') {

                $this->db->where('loan_id',$lidd)->delete('payement_schedules');

                while ($current_balance > 0 && $ii <= $months) {


                    $towards_interest = ($i / 12) * $current_balance;  //this calculates the portion of your monthly payment that goes towards interest
                    $towards_fees1 = ($af / 12) * $current_balance;  //this calculates the portion of your monthly payment that goes towards interest
                    $towards_lc1 = ($lc / 12) * $current_balance;  //this calculates the portion of your monthly payment that goes towards interest

                    if ($monthly_payment > $current_balance) {
                        $monthly_payment = $current_balance + $towards_interest + $towards_fees1 + $towards_lc1;
                    }


                    $towards_balance = $monthly_payment - ($towards_interest + $towards_fees1 + $towards_lc1);
                    $total_interest = $total_interest + $towards_interest;
                    $total_admin_fees1 = $total_admin_fees1 + $towards_fees1;
                    $total_loan_cover1 = $total_loan_cover1 + $towards_lc1;
                    $current_balance = $current_balance - $towards_balance;


                    // display row


                    $frequency = $days * $ii;
                    $newdate = strtotime('+' . $frequency . ' day', strtotime($date));

                    //check if payment date landed on Sunday, push to Monday
                    if (date('D', $newdate) == 'Sun') {
                        $newdate = strtotime('+1 day', $newdate);
                    }

                    $newdate = date('Y-m-d', $newdate);
                    $this->db->insert(
                        'payement_schedules', array(

                            'customer' => $loan_customer,
                            'customer_type' => $customer_type,
                            'loan_id' => $id,
                            'payment_schedule' => $newdate,
                            'payment_number' => $ii,
                            'amount' => $monthly_payment + $extra_interest,
                            'principal' => $towards_balance,
                            'interest' => $towards_interest + $extra_interest,
                            'padmin_fee' => $towards_fees1,
                            'ploan_cover' => $total_loan_cover1,
                            'paid_amount' => 0,
                            'loan_balance' => $current_balance,
                            'loan_date' => $loan_date,

                        )
                    );


                    //$table = $table . '<tr><td>'.$i.'</td><td>'.$amount_term.'</td><td>'.$newdate.'</td></tr>';
                    $ii++;
                }
            }
            else {
                if ($day > 15 && $loan->frequency == "Monthly" && $loan->schedule_plan=="cut off") {
                    $this->db->where('loan_id',$lidd)->delete('payement_schedules');
                    while ($current_balance > 0 && $ii <= $months) {


                        $towards_interest = ($i / 12) * $current_balance;  //this calculates the portion of your monthly payment that goes towards interest
                        $towards_fees1 = ($af / 12) * $current_balance;  //this calculates the portion of your monthly payment that goes towards interest
                        $towards_lc1 = ($lc / 12) * $current_balance;  //this calculates the portion of your monthly payment that goes towards interest

                        if ($monthly_payment > $current_balance) {
                            $monthly_payment = $current_balance + $towards_interest + $towards_fees1 + $towards_lc1;
                        }


                        $towards_balance = $monthly_payment - ($towards_interest + $towards_fees1 + $towards_lc1);
                        $total_interest = $total_interest + $towards_interest;
                        $total_admin_fees1 = $total_admin_fees1 + $towards_fees1;
                        $total_loan_cover1 = $total_loan_cover1 + $towards_lc1;
                        $current_balance = $current_balance - $towards_balance;


                        // display row


                        $newdate = $this->build_month_end_schedule_date($loan_date, $ii - 1);

                        $this->db->insert(
                            'payement_schedules', array(

                                'customer' => $loan_customer,
                                'customer_type' => $customer_type,
                                'loan_id' => $id,
                                'payment_schedule' => $is_masamba_promotion
                                    ? $this->build_masamba_promotion_schedule_date($loan_date, $ii)
                                    : $newdate,
                                'payment_number' => $ii,
                                'amount' => $monthly_payment + $extra_interest,
                                'principal' => $towards_balance,
                                'interest' => $towards_interest + $extra_interest,
                                'padmin_fee' => $towards_fees1,
                                'ploan_cover' => $towards_lc1,
                                'paid_amount' => 0,
                                'loan_balance' => $current_balance,
                                'loan_date' => $loan_date,

                            )
                        );


                        //$table = $table . '<tr><td>'.$i.'</td><td>'.$amount_term.'</td><td>'.$newdate.'</td></tr>';
                        $ii++;
                    }
                }
                elseif ($day >= 1 && $day < 15 && $loan->frequency == "Monthly" && $loan->schedule_plan=="cut off") {
                    $this->db->where('loan_id',$lidd)->delete('payement_schedules');

                    while ($current_balance > 0 && $ii <= $months) {


                        $towards_interest = ($i / 12) * $current_balance;  //this calculates the portion of your monthly payment that goes towards interest
                        $towards_fees1 = ($af / 12) * $current_balance;  //this calculates the portion of your monthly payment that goes towards interest
                        $towards_lc1 = ($lc / 12) * $current_balance;  //this calculates the portion of your monthly payment that goes towards interest

                        if ($monthly_payment > $current_balance) {
                            $monthly_payment = $current_balance + $towards_interest + $towards_fees1 + $towards_lc1;
                        }


                        $towards_balance = $monthly_payment - ($towards_interest + $towards_fees1 + $towards_lc1);
                        $total_interest = $total_interest + $towards_interest;
                        $total_admin_fees1 = $total_admin_fees1 + $towards_fees1;
                        $total_loan_cover1 = $total_loan_cover1 + $towards_lc1;
                        $current_balance = $current_balance - $towards_balance;


                        // display row


                        $newdate = $this->build_month_end_schedule_date($loan_date, $ii - 1);
                        $this->db->insert(
                            'payement_schedules', array(

                                'customer' => $loan_customer,
                                'customer_type' => $customer_type,
                                'loan_id' => $id,
                                'payment_schedule' => $is_masamba_promotion
                                    ? $this->build_masamba_promotion_schedule_date($loan_date, $ii)
                                    : $newdate,
                                'payment_number' => $ii,
                                'amount' => $monthly_payment - $extra_interest,
                                'principal' => $towards_balance,
                                'interest' => $towards_interest - $extra_interest,
                                'padmin_fee' => $towards_fees1,
                                'ploan_cover' => $towards_lc1,
                                'paid_amount' => 0,
                                'loan_balance' => $current_balance,
                                'loan_date' => $loan_date,

                            )
                        );


                        //$table = $table . '<tr><td>'.$i.'</td><td>'.$amount_term.'</td><td>'.$newdate.'</td></tr>';
                        $ii++;
                    }
                }
                else {
                    $this->db->where('loan_id',$lidd)->delete('payement_schedules');
                    while ($current_balance > 0 && $ii <= $months){


                        $towards_interest = ($i / 12) * $current_balance;  //this calculates the portion of your monthly payment that goes towards interest
                        $towards_fees1 = ($af / 12) * $current_balance;  //this calculates the portion of your monthly payment that goes towards interest
                        $towards_lc1 = ($lc / 12) * $current_balance;  //this calculates the portion of your monthly payment that goes towards interest

                        if ($monthly_payment > $current_balance) {
                            $monthly_payment = $current_balance + $towards_interest + $towards_fees1 + $towards_lc1;
                        }


                        $towards_balance = $monthly_payment - ($towards_interest + $towards_fees1 + $towards_lc1);
                        $total_interest = $total_interest + $towards_interest;
                        $total_admin_fees1 = $total_admin_fees1 + $towards_fees1;
                        $total_loan_cover1 = $total_loan_cover1 + $towards_lc1;
                        $current_balance = $current_balance - $towards_balance;


                        // display row


                        $newdate = $this->build_month_end_schedule_date($loan_date, $ii - 1);

                        $this->db->insert(
                            'payement_schedules', array(

                                'customer' => $loan_customer,
                                'customer_type' => $customer_type,
                                'loan_id' => $id,
                                'payment_schedule' => $is_masamba_promotion
                                    ? $this->build_masamba_promotion_schedule_date($loan_date, $ii)
                                    : $newdate,
                                'payment_number' => $ii,
                                'amount' => $monthly_payment + $extra_interest,
                                'principal' => $towards_balance,
                                'interest' => $towards_interest + $extra_interest,
                                'padmin_fee' => $towards_fees1,
                                'ploan_cover' => $towards_lc1,
                                'paid_amount' => 0,
                                'loan_balance' => $current_balance,
                                'loan_date' => $loan_date,

                            )
                        );


                        //$table = $table . '<tr><td>'.$i.'</td><td>'.$amount_term.'</td><td>'.$newdate.'</td></tr>';
                        $ii++;
                    }
                }


            }

            $this->restore_schedule_payment_state($id, $existing_schedule_state);

            return $id;
        }
    }



	function add_loan($lamount, $lmonths, $product_id, $ldate,$loan_customer, $customer_type,$worthness_file,$narration,$added_by, $branch, $funds_source = null, $batch = null, $from_group = 'No', $group_id = null)
	{
		$pending_loan = $this->get_customer_open_loan_for_product(
			$loan_customer,
			$customer_type,
			$product_id,
			array('INITIATED', 'RECOMMENDED', 'APPROVED')
		);
		if ($pending_loan) {
			throw new Exception('Customer already has an open loan for this loan product.');
		}

		//set Time Zone
		//date_default_timezone_set('Africa/Blantyre');
		// Generate a collision-safe loan number before sub-functions are dispatched.
		// Sub-functions each call generate_loan_number() themselves, so this value
		// is only used by the else-path (reducing-balance monthly) below.
		$loan_num_data = $this->generate_loan_number();
		$loanid   = $loan_num_data['loanid'];
		$fcounter = $loan_num_data['fcounter'];
        $amount = $this->normalize_numeric_value($lamount);
        $loan_date = $this->normalize_date_value($ldate);
        $original_loan_date = $loan_date;
        $months = (int)$this->normalize_numeric_value($lmonths);
        if ($months <= 0) {
            throw new Exception('Loan period must be greater than zero.');
        }
		//get loan parameters
		$loan = $this->db->select("*")->from('loan_products')->where('loan_product_id',$product_id)->get()->row();
		
		// Check if loan product exists
		if (!$loan) {
			throw new Exception("Loan product with ID {$product_id} not found");
		}
        $is_masamba_promotion = $this->is_masamba_promotion_for_lingwe_blantyre($loan, $branch);
		
		// Set default values for missing properties
        $loan->loan_cover = isset($loan->loan_cover) ? $this->normalize_numeric_value($loan->loan_cover) : 0;
        $loan->admin_fees = isset($loan->admin_fees) ? $this->normalize_numeric_value($loan->admin_fees) : 0;
        $loan->processing_fees = isset($loan->processing_fees) ? $this->normalize_numeric_value($loan->processing_fees) : 0;
        $loan->interest = isset($loan->interest) ? $this->normalize_numeric_value($loan->interest) : 0;

        if ($this->is_group_zitsamba_monthly_flat_product($loan)) {
            return $this->add_group_zitsamba_monthly_flat_loan(
                $amount,
                $months,
                $product_id,
                $loan_date,
                $loan_customer,
                $customer_type,
                $worthness_file,
                $narration,
                $added_by,
                $branch,
                $funds_source,
                $batch,
                $from_group,
                $group_id,
                $loanid,
                $fcounter,
                $loan
            );
        }
		
//		echo $lamount;
//		print_r($loan);
//		exit();
		// DEBUG: Log loan method and frequency
		//var_dump("LOAN DEBUG - Method: '{$loan->method}', Frequency: '{$loan->frequency}', Product ID: {$product_id}");
		
		if($loan->method=="Straight line" && $loan->frequency=="Weekly"){
			$principal = (($loan->processing_fees/100)*$amount)+$amount;
			$p = $amount;
			return $this->add_amortization_straight_weekly($p,$principal, $product_id, $months, $loan_date,$loan_customer,$customer_type,$worthness_file,$narration,$added_by, $branch, $funds_source, $batch, $from_group, $group_id);
		}
        elseif($loan->method=="Straight line" && $loan->frequency=="Bi weekly")
        {
        $principal = (($loan->processing_fees/100)*$amount)+$amount;
        $p = $amount;
        return $this->add_amortization_biweekly($p,$principal, $product_id, $months, $loan_date,$loan_customer,$customer_type,$worthness_file,$narration,$added_by, $branch, $funds_source, $batch, $from_group, $group_id);
        }
        elseif($loan->method == "Reducing balance" && $loan->frequency == "Weekly")
        {
        // For Reducing Balance Weekly, processing fee is added to principal before calculation
        // var_dump("LOAN DEBUG - Taking Reducing Balance Weekly path");
      
      $principal = (($loan->processing_fees/100)*$amount)+$amount;
      $p = $amount;
         
      $r =   $this->add_reducing_balance_weekly($p,$principal, $months, $product_id, $loan_date, $loan_customer, $customer_type, $worthness_file, $narration, $added_by, $branch, $funds_source, $batch, $from_group, $group_id);
    //   var_dump($r);
    //   exit();
      return $r;
    }
        elseif($loan->method == "Reducing balance" && $loan->frequency == "Bi weekly"){
        // For Reducing Balance Bi-weekly, processing fee is added to principal before calculation
        return $this->add_reducing_balance_biweekly($amount, $months, $product_id, $loan_date, $loan_customer, $customer_type, $worthness_file, $narration, $added_by, $branch, $funds_source, $batch, $from_group, $group_id);
    }
        else {
			// For Reducing Balance + Weekly/Bi-weekly, add processing fee to principal FIRST

			$original_principal = $amount;
			if(strcasecmp(trim($loan->method), "Reducing Balance") == 0 && (strcasecmp(trim($loan->frequency), "Weekly") == 0 || strcasecmp(trim($loan->frequency), "Bi weekly") == 0)) {
				// Get processing fee
				if(isset($loan->processing_fees)) {
					$processing_fee_percent = $loan->processing_fees;
				} elseif(isset($loan->processing_fee)) {
					$processing_fee_percent = $loan->processing_fee;
				} else {
					$processing_fee_percent = 0;
				}

				// Calculate processing fee and add to principal
				$processing_fee_amount = ($processing_fee_percent / 100) * $amount;
				$amount = $amount + $processing_fee_amount; // NEW PRINCIPAL includes processing fee
			}

			//divisor
			switch ($loan->frequency) {
				case 'Monthly':
					$divisor = 1;
					$days = 30;
					$days2 = 34;
					break;
				case 'Bi weekly':
					$divisor = 2;
					$days = 15;
					break;
				case 'Weekly':
					$divisor = 4;
					$days = 7;
					break;
			}

			//interest
			$amount_interest = $amount * (($loan->interest / 100) * 12);
            $date = $loan_date;
//			$day = date('d', strtotime($date));
			$extra_interest = 0;
			$extra_days = 0;

			$total_extra_interest = 0;
			$date = $loan_date;
			$day = date('d', strtotime($date));
            if ($day > 15 && $loan->frequency == "Monthly" && $loan->schedule_plan=="cut off") {
//				    find last day of the effective date of loan
				$last_date = date("t", strtotime($date));
//                    if loan date is above 15 then the effective date should be 1st day of next month
				$month_end_date = date("Y-m-t", strtotime($date));
//                    add 1 to last date of this month
				$ll = strtotime('+1 day', strtotime($month_end_date));
				$loan_date = date('Y-m-d', $ll);

//				    calculate number of xtra days
				$earlier = new DateTime($date);
				$later = new DateTime($loan_date);
				$date_diff = $later->diff($earlier);
				$extra_days = $date_diff->d;


//lets calculate interest of these dayes
				$total_extra_interest = (($extra_days) / 30) * $amount * (($loan->interest + $loan->loan_cover) / 100);
				$extra_interest = $total_extra_interest / $months;

			} elseif ($day >= 1 && $day < 15 && $loan->frequency == "Monthly" && $loan->schedule_plan=="cut off") {
				//                    if loan date is above 15 then the effective date should be 1st day of next month
				$month_start_date = date("Y-m-01", strtotime($date));
//                    add 1 to last date of this month
                $loan_date = date('Y-m-d', strtotime($month_start_date));

//				    calculate number of xtra days
				$earlier = new DateTime($month_start_date);
				$later = new DateTime($date);
				$date_diff = $later->diff($earlier);
				$extra_days = $date_diff->d;


//lets calculate interest of these dayes
				$total_extra_interest = (($extra_days) / 30) * $amount * (($loan->interest + $loan->loan_cover) / 100);
				$extra_interest = $total_extra_interest / $months;

			}elseif ( $loan->frequency == "Monthly" && $loan->schedule_plan=="no cutoff") {
				//                    if loan date is above 15 then the effective date should be 1st day of next month

				$loan_date = $date;

//				    calculate number of xtra days

				$extra_interest = 0;

			}

			// Keep the stored loan date exactly as selected by the user.
			$loan_date = $original_loan_date;

			$i = ($loan->interest / 100) * 12;
			$af = ($loan->admin_fees / 100) * 12;
			$lc = ($loan->loan_cover / 100) * 12;
			$total_deduction = $i + $af + $lc;


			$monthly_payment = ($amount * ($total_deduction / 12) * pow((1 + $total_deduction / 12), $months) / (pow((1 + $total_deduction / 12), $months) - 1));
			$monthly_payment1 = ($amount * ($total_deduction / 12) * pow((1 + $total_deduction / 12), $months) / (pow((1 + $total_deduction / 12), $months) - 1));
			$monthly_payment_config = ($amount * ($total_deduction / 12) * pow((1 + $total_deduction / 12), $months) / (pow((1 + $total_deduction / 12), $months) - 1));
			$current_balance = $amount;
			$current_balance1 = $amount;
			$payment_counter = 1;
			$total_interest = 0;
			$total_interest1 = 0;
			$total_admin_fees = 0;
			$total_admin_fees1 = 0;
			$total_loan_cover = 0;
			$total_loan_cover1 = 0;

            // Guard against formatted numeric strings propagating into schedule math.
            $monthly_payment = $this->normalize_numeric_value($monthly_payment);
            $monthly_payment1 = $this->normalize_numeric_value($monthly_payment1);
            $monthly_payment_config = $this->normalize_numeric_value($monthly_payment_config);
            $extra_interest = $this->normalize_numeric_value($extra_interest);
            $i = $this->normalize_numeric_value($i);
            $af = $this->normalize_numeric_value($af);
            $lc = $this->normalize_numeric_value($lc);
            $current_balance = $this->normalize_numeric_value($current_balance);
            $current_balance1 = $this->normalize_numeric_value($current_balance1);


			$ii = 1;


			while ($current_balance1 > 0) {
				//create rows


				$towards_interest1 = ($i / 12) * $current_balance1;  //this calculates the portion of your monthly payment that goes towards interest
				$towards_fees = ($af / 12) * $current_balance1;  //this calculates the portion of your monthly payment that goes towards administration fees
				$towards_lc = ($lc / 12) * $current_balance1;  //this calculates the portion of your monthly payment that goes towards administration fees

				if ($monthly_payment1 > $current_balance1) {
					$monthly_payment1 = $current_balance1 + $towards_interest1 + $towards_fees + $towards_lc;

				}
				$towards_balance1 = $monthly_payment1 - ($towards_interest1 + $towards_fees + $towards_lc);
				$total_interest1 = $total_interest1 + $towards_interest1;
				$total_admin_fees = $total_admin_fees + $towards_fees;
				$total_loan_cover = $total_loan_cover + $towards_lc;
				$current_balance1 = $current_balance1 - $towards_balance1;

			}



            if($day > 15 && $loan->frequency=="Monthly" && $loan->schedule_plan=="cut off") {
				$m_config = $monthly_payment_config + $extra_interest;
				$pay_total = $total_interest1 + $amount + $total_admin_fees + $total_loan_cover+$total_extra_interest;
				$total_interest1 = $total_interest1 + $total_extra_interest;
			}elseif ($day >=1 && $day < 15 && $loan->frequency=="Monthly" && $loan->schedule_plan=="cut off"){
				$m_config = $monthly_payment_config - $extra_interest;
				$pay_total = $total_interest1 + $amount + $total_admin_fees + $total_loan_cover-$total_extra_interest;
				$total_interest1 = $total_interest1 - $total_extra_interest;
			}else{
				$pay_total = $total_interest1 + $amount + $total_admin_fees + $total_loan_cover;
				$m_config = $monthly_payment_config ;
				$total_interest1 = $total_interest1 + $total_extra_interest;
			}


			//additional info to be insert


			$data = array(
				'loan_number' => $loanid,
				'loan_product' => $product_id,
				'loan_customer' => $loan_customer,
				'customer_type' => $customer_type,
                'loan_date' => $loan_date,
                'loan_principal' => $amount,
                'loan_period' => $months,
				'worthness_file' => $worthness_file,
				'narration' => $narration,
				'period_type' => $loan->frequency,
				'loan_amount_term' => $m_config,
				'loan_interest' => $loan->interest,
				'loan_interest_amount' => $total_interest1,
				'admin_fee' => $loan->admin_fees,
				'admin_fees_amount' => $total_admin_fees,
				'loan_cover' => $loan->loan_cover,
				'loan_cover_amount' => $total_loan_cover,
				'loan_amount_total' => $pay_total,
				'next_payment_id' => 1,
				'loan_added_by' => $added_by,
				'counter' => $fcounter,
                'branch' => $branch,
                'funds_source' => $funds_source,
                'batch' => $batch,
                'from_group' => $from_group,
                'group_id' => $group_id,
			);
			$this->db->insert($this->table, $data);


			//borrower_loan_id
			$id = $this->db->insert_id();

			//insert each payment records to lend_payments
			if ($loan->frequency == 'Bi weekly') {
				$frequency = $months;
				if ($this->is_zitsamba_biweekly_product($loan)) {
					for ($i = 1; $i <= $frequency; $i++) {
						$schedule_date = $this->build_zitsamba_biweekly_schedule_date($loan_date, $i);
						$this->db->insert(
							'payement_schedules', array(
								'customer' => $loan_customer,
								'loan_id' => $id,
								'payment_schedule' => $schedule_date,
								'payment_number' => $i,
								'amount' => $monthly_payment1,
								'principal' => $towards_balance1,
								'interest' => $total_interest1,
								'paid_amount' => 0,
								'loan_balance' => $current_balance1,
								'loan_date' => $loan_date,
							)
						);
					}
				} else {
					$date = $loan_date;
					$start_day = 0;
					$loan_day = date('d', strtotime($date));
					$loan_month = date('m', strtotime($date));

					if ($loan_day >= 15) {
						$start_day = ($loan_month == '02') ? 28 : 30;
					} else {
						$start_day = 15;
					}

					$date = date('Y/m/' . $start_day, strtotime($date));
					for ($i = 1; $i <= $frequency; $i++) {
						$this->db->insert(
							'payement_schedules', array(
								'customer' => $loan_customer,
								'loan_id' => $id,
								'payment_schedule' => date('Y-m-d', strtotime($date)),
								'payment_number' => $i,
								'amount' => $monthly_payment1,
								'principal' => $towards_balance1,
								'interest' => $total_interest1,
								'paid_amount' => 0,
								'loan_balance' => $current_balance1,
								'loan_date' => $loan_date,
							)
						);

						$day = date('d', strtotime($date));
						if ($day == 15) {
							if (date('m', strtotime($date)) == '02') {
								$date = date('Y/02/28', strtotime($date));
							} else {
								$date = date('Y/m/30', strtotime($date));
							}
						} elseif ($day == 30 or $day > 15) {
							if (date('m', strtotime($date)) == '01') {
								$date = date('Y/02/15', strtotime('+1 month', strtotime($date)));
							} else {
								$date = date('Y/m/15', strtotime('+1 month', strtotime($date)));
							}
						}
					}
				}
			}
			elseif ($loan->frequency == 'Weekly') {

				while ($current_balance > 0 && $ii <= $months) {


					$towards_interest = ($i / 12) * $current_balance;  //this calculates the portion of your monthly payment that goes towards interest
					$towards_fees1 = ($af / 12) * $current_balance;  //this calculates the portion of your monthly payment that goes towards interest
					$towards_lc1 = ($lc / 12) * $current_balance;  //this calculates the portion of your monthly payment that goes towards interest

					if ($monthly_payment > $current_balance) {
						$monthly_payment = $current_balance + $towards_interest + $towards_fees1 + $towards_lc1;
					}


					$towards_balance = $monthly_payment - ($towards_interest + $towards_fees1 + $towards_lc1);
					$total_interest = $total_interest + $towards_interest;
					$total_admin_fees1 = $total_admin_fees1 + $towards_fees1;
					$total_loan_cover1 = $total_loan_cover1 + $towards_lc1;
					$current_balance = $current_balance - $towards_balance;


					// display row


					$frequency = $days * $ii;
					$newdate = strtotime('+' . $frequency . ' day', strtotime($date));

					//check if payment date landed on Sunday, push to Monday
					if (date('D', $newdate) == 'Sun') {
						$newdate = strtotime('+1 day', $newdate);
					}

					$newdate = date('Y-m-d', $newdate);

					$this->db->insert(
						'payement_schedules', array(

							'customer' => $loan_customer,
							'customer_type' => $customer_type,
							'loan_id' => $id,
							'payment_schedule' => $newdate,
							'payment_number' => $ii,
							'amount' => $monthly_payment + $extra_interest,
							'principal' => $towards_balance,
							'interest' => $towards_interest + $extra_interest,
							'padmin_fee' => $towards_fees1,
							'ploan_cover' => $total_loan_cover1,
							'paid_amount' => 0,
							'loan_balance' => $current_balance,
							'loan_date' => $loan_date,

						)
					);


					//$table = $table . '<tr><td>'.$i.'</td><td>'.$amount_term.'</td><td>'.$newdate.'</td></tr>';
					$ii++;
				}
			} else {
                if ($day > 15 && $loan->frequency == "Monthly" && $loan->schedule_plan=="cut off") {
					while ($current_balance > 0 && $ii <= $months) {


						$towards_interest = ($i / 12) * $current_balance;  //this calculates the portion of your monthly payment that goes towards interest
						$towards_fees1 = ($af / 12) * $current_balance;  //this calculates the portion of your monthly payment that goes towards interest
						$towards_lc1 = ($lc / 12) * $current_balance;  //this calculates the portion of your monthly payment that goes towards interest

						if ($monthly_payment > $current_balance) {
							$monthly_payment = $current_balance + $towards_interest + $towards_fees1 + $towards_lc1;
						}


						$towards_balance = $monthly_payment - ($towards_interest + $towards_fees1 + $towards_lc1);
						$total_interest = $total_interest + $towards_interest;
						$total_admin_fees1 = $total_admin_fees1 + $towards_fees1;
						$total_loan_cover1 = $total_loan_cover1 + $towards_lc1;
						$current_balance = $current_balance - $towards_balance;


						// display row


                        $newdate = $this->build_month_end_schedule_date($loan_date, $ii - 1);

						$this->db->insert(
							'payement_schedules', array(

								'customer' => $loan_customer,
								'customer_type' => $customer_type,
								'loan_id' => $id,
								'payment_schedule' => $is_masamba_promotion
                                    ? $this->build_masamba_promotion_schedule_date($loan_date, $ii)
                                    : $newdate,
								'payment_number' => $ii,
								'amount' => $monthly_payment + $extra_interest,
								'principal' => $towards_balance,
								'interest' => $towards_interest + $extra_interest,
								'padmin_fee' => $towards_fees1,
								'ploan_cover' => $towards_lc1,
								'paid_amount' => 0,
								'loan_balance' => $current_balance,
								'loan_date' => $loan_date,

							)
						);


						//$table = $table . '<tr><td>'.$i.'</td><td>'.$amount_term.'</td><td>'.$newdate.'</td></tr>';
						$ii++;
					}
				}
				elseif ($day >= 1 && $day < 15 && $loan->frequency == "Monthly" && $loan->schedule_plan=="cut off") {
					while ($current_balance > 0 && $ii <= $months) {


						$towards_interest = ($i / 12) * $current_balance;  //this calculates the portion of your monthly payment that goes towards interest
						$towards_fees1 = ($af / 12) * $current_balance;  //this calculates the portion of your monthly payment that goes towards interest
						$towards_lc1 = ($lc / 12) * $current_balance;  //this calculates the portion of your monthly payment that goes towards interest

						if ($monthly_payment > $current_balance) {
							$monthly_payment = $current_balance + $towards_interest + $towards_fees1 + $towards_lc1;
						}


						$towards_balance = $monthly_payment - ($towards_interest + $towards_fees1 + $towards_lc1);
						$total_interest = $total_interest + $towards_interest;
						$total_admin_fees1 = $total_admin_fees1 + $towards_fees1;
						$total_loan_cover1 = $total_loan_cover1 + $towards_lc1;
						$current_balance = $current_balance - $towards_balance;


						// display row


                        $newdate = $this->build_month_end_schedule_date($loan_date, $ii - 1);

						$this->db->insert(
							'payement_schedules', array(

								'customer' => $loan_customer,
								'customer_type' => $customer_type,
								'loan_id' => $id,
								'payment_schedule' => $is_masamba_promotion
                                    ? $this->build_masamba_promotion_schedule_date($loan_date, $ii)
                                    : $newdate,
								'payment_number' => $ii,
								'amount' => $monthly_payment - $extra_interest,
								'principal' => $towards_balance,
								'interest' => $towards_interest - $extra_interest,
								'padmin_fee' => $towards_fees1,
								'ploan_cover' => $towards_lc1,
								'paid_amount' => 0,
								'loan_balance' => $current_balance,
								'loan_date' => $loan_date,

							)
						);


						//$table = $table . '<tr><td>'.$i.'</td><td>'.$amount_term.'</td><td>'.$newdate.'</td></tr>';
						$ii++;
					}
				}
				else {
					while ($current_balance > 0 && $ii <= $months){


						$towards_interest = ($i / 12) * $current_balance;  //this calculates the portion of your monthly payment that goes towards interest
						$towards_fees1 = ($af / 12) * $current_balance;  //this calculates the portion of your monthly payment that goes towards interest
						$towards_lc1 = ($lc / 12) * $current_balance;  //this calculates the portion of your monthly payment that goes towards interest

						if ($monthly_payment > $current_balance) {
							$monthly_payment = $current_balance + $towards_interest + $towards_fees1 + $towards_lc1;
						}


						$towards_balance = $monthly_payment - ($towards_interest + $towards_fees1 + $towards_lc1);
						$total_interest = $total_interest + $towards_interest;
						$total_admin_fees1 = $total_admin_fees1 + $towards_fees1;
						$total_loan_cover1 = $total_loan_cover1 + $towards_lc1;
						$current_balance = $current_balance - $towards_balance;


						// display row


                        $frequency =  $ii;
                        if ($day > 15) {
                            $newdate = $this->build_month_end_schedule_date($date, $ii - 1);
                        } else {
                            $newdate = strtotime('+' . $frequency . ' month', strtotime($date));
                        }

                        $newdate_ts = is_numeric($newdate) ? (int)$newdate : strtotime((string)$newdate);
                        if ($newdate_ts === false) {
                            $newdate_ts = strtotime((string)$date);
                        }

						//check if payment date landed on Sunday, push to Monday
						if (date('D', $newdate_ts) == 'Sun') {
							$newdate_ts = strtotime('+1 day', $newdate_ts);
						}

						$newdate = date('Y-m-d', $newdate_ts);

						$this->db->insert(
							'payement_schedules', array(

								'customer' => $loan_customer,
								'customer_type' => $customer_type,
								'loan_id' => $id,
								'payment_schedule' => $is_masamba_promotion
                                    ? $this->build_masamba_promotion_schedule_date($loan_date, $ii)
                                    : $newdate,
								'payment_number' => $ii,
								'amount' => $monthly_payment + $extra_interest,
								'principal' => $towards_balance,
								'interest' => $towards_interest + $extra_interest,
								'padmin_fee' => $towards_fees1,
								'ploan_cover' => $towards_lc1,
								'paid_amount' => 0,
								'loan_balance' => $current_balance,
								'loan_date' => $loan_date,

							)
						);


						//$table = $table . '<tr><td>'.$i.'</td><td>'.$amount_term.'</td><td>'.$newdate.'</td></tr>';
						$ii++;
					}
				}


			}

			//get next payment id and insert to lend_borrower_loans.next_payment_id
//		$payment = $this->Loan_model->next_payment($id);
//		$this->db->update('lend_borrower_loans', array('next_payment_id' => $payment->id), array('id' => $id));
			$data_account = array(
				'client_id' => $loan_customer,
				'account_number' => $loanid,
				'balance' => 0,
				'account_type' => 2,
				'account_type_product' => $product_id,


			);

			$this->db->insert('account', $data_account);
			return $id;
		}
	}

    function add_loan_rerun($lamount, $lmonths, $product_id, $ldate, $loan_customer, $customer_type,$loan_number,$loan_id)
    {
        //set Time Zone
        //date_default_timezone_set('Africa/Blantyre');
        $this->db->select('MAX(counter) as max_c');
        $lid = $this->db->get('loan');
        $result = $lid->row();
        $loanid= $loan_number;
        $fcounter=$result->max_c+1;
        $amount = $this->normalize_numeric_value($lamount);
        $loan_date = $this->normalize_date_value($ldate);
        $original_loan_date = $loan_date;
        $months = (int)$this->normalize_numeric_value($lmonths);
        //get loan parameters
        $loan = $this->db->select("*")->from('loan_products')->where('loan_product_id',$product_id)->get()->row();
        $loan->frequency = $this->resolve_frequency_for_loan_edit($loan_id, $product_id, $loan->frequency, null);
        $existing_loan = $this->db->select('branch')->from('loan')->where('loan_id', $loan_id)->get()->row();
        $existing_branch = $existing_loan ? $existing_loan->branch : null;
        $is_masamba_promotion = $this->is_masamba_promotion_for_lingwe_blantyre($loan, $existing_branch);

        if ($this->is_group_zitsamba_monthly_flat_product($loan)) {
            return $this->rerun_group_zitsamba_monthly_flat_loan(
                $amount,
                $months,
                $product_id,
                $loan_date,
                $loan_customer,
                $customer_type,
                $loan_number,
                $loan_id
            );
        }
//		echo $lamount;
//		print_r($loan);
//		exit();
        if ($loan->method=="Straight line" && ($loan->frequency == 'Weekly' || $loan->frequency == 'Bi weekly')) {
            $principal = (($loan->processing_fees/100)*$amount)+$amount;
            $p = $amount;
            $this->add_amortization_straight_weekly_rerun($p,$principal, $product_id, $months, $loan_date,$loan_customer,$customer_type,$loan_number,$loan_id, $loan->processing_fees, $loan->frequency);
        }else {
            //divisor
            switch ($loan->frequency) {
                case 'Monthly':
                    $divisor = 1;
                    $days = 30;
                    $days2 = 34;
                    break;
                case 'Bi weekly':
                    $divisor = 2;
                    $days = 15;
                    break;
                case 'Weekly':
                    $divisor = 4;
                    $days = 7;
                    break;
            }

            //interest
            $amount_interest = $amount * (($loan->interest / 100) * 12);
            $date = $ldate;
//			$day = date('d', strtotime($date));
            $extra_interest = 0;
            $extra_days = 0;

            $total_extra_interest = 0;
            $date = $loan_date;
            $day = date('d', strtotime($date));
            if ($day > 15 && $loan->frequency == "Monthly" && $loan->schedule_plan=="cut off") {
//				    find last day of the effective date of loan
                $last_date = date("t", strtotime($date));
//                    if loan date is above 15 then the effective date should be 1st day of next month
                $month_end_date = date("Y-m-t", strtotime($date));
//                    add 1 to last date of this month
                $ll = strtotime('+1 day', strtotime($month_end_date));
                $loan_date = date('Y-m-d', $ll);

//				    calculate number of xtra days
                $earlier = new DateTime($date);
                $later = new DateTime($loan_date);
                $date_diff = $later->diff($earlier);
                $extra_days = $date_diff->d;


//lets calculate interest of these dayes
                $total_extra_interest = (($extra_days) / 30) * $amount * (($loan->interest + $loan->loan_cover) / 100);
                $extra_interest = $total_extra_interest / $months;

            } elseif ($day >= 1 && $day < 15 && $loan->frequency == "Monthly" && $loan->schedule_plan=="cut off") {
                //                    if loan date is above 15 then the effective date should be 1st day of next month
                $month_start_date = date("Y-m-01", strtotime($date));
//                    add 1 to last date of this month
                $loan_date = date('Y-m-d', strtotime($month_start_date));

//				    calculate number of xtra days
                $earlier = new DateTime($month_start_date);
                $later = new DateTime($date);
                $date_diff = $later->diff($earlier);
                $extra_days = $date_diff->d;


//lets calculate interest of these dayes
                $total_extra_interest = (($extra_days) / 30) * $amount * (($loan->interest + $loan->loan_cover) / 100);
                $extra_interest = $total_extra_interest / $months;

            }elseif ( $loan->frequency == "Monthly" && $loan->schedule_plan=="no cutoff") {
                //                    if loan date is above 15 then the effective date should be 1st day of next month

                $loan_date = $date;

//				    calculate number of xtra days

                $extra_interest = 0;

            }


            $i = ($loan->interest / 100) * 12;
            $af = ($loan->admin_fees / 100) * 12;
            $lc = ($loan->loan_cover / 100) * 12;
            $total_deduction = $i + $af + $lc;


            $monthly_payment = ($amount * ($total_deduction / 12) * pow((1 + $total_deduction / 12), $months) / (pow((1 + $total_deduction / 12), $months) - 1));
            $monthly_payment1 = ($amount * ($total_deduction / 12) * pow((1 + $total_deduction / 12), $months) / (pow((1 + $total_deduction / 12), $months) - 1));
            $monthly_payment_config = ($amount * ($total_deduction / 12) * pow((1 + $total_deduction / 12), $months) / (pow((1 + $total_deduction / 12), $months) - 1));
            $current_balance = $amount;
            $current_balance1 = $amount;
            $payment_counter = 1;
            $total_interest = 0;
            $total_interest1 = 0;
            $total_admin_fees = 0;
            $total_admin_fees1 = 0;
            $total_loan_cover = 0;
            $total_loan_cover1 = 0;


            $ii = 1;


            while ($current_balance1 > 0) {
                //create rows


                $towards_interest1 = ($i / 12) * $current_balance1;  //this calculates the portion of your monthly payment that goes towards interest
                $towards_fees = ($af / 12) * $current_balance1;  //this calculates the portion of your monthly payment that goes towards administration fees
                $towards_lc = ($lc / 12) * $current_balance1;  //this calculates the portion of your monthly payment that goes towards administration fees

                if ($monthly_payment1 > $current_balance1) {
                    $monthly_payment1 = $current_balance1 + $towards_interest1 + $towards_fees + $towards_lc;

                }
                $towards_balance1 = $monthly_payment1 - ($towards_interest1 + $towards_fees + $towards_lc);
                $total_interest1 = $total_interest1 + $towards_interest1;
                $total_admin_fees = $total_admin_fees + $towards_fees;
                $total_loan_cover = $total_loan_cover + $towards_lc;
                $current_balance1 = $current_balance1 - $towards_balance1;

            }



            if($day > 15 && $loan->frequency=="Monthly" && $loan->schedule_plan=="cut off") {
                $m_config = $monthly_payment_config + $extra_interest;
                $pay_total = $total_interest1 + $amount + $total_admin_fees + $total_loan_cover+$total_extra_interest;
                $total_interest1 = $total_interest1 + $total_extra_interest;
            }elseif ($day >=1 && $day < 15 && $loan->frequency=="Monthly" && $loan->schedule_plan=="cut off"){
                $m_config = $monthly_payment_config - $extra_interest;
                $pay_total = $total_interest1 + $amount + $total_admin_fees + $total_loan_cover-$total_extra_interest;
                $total_interest1 = $total_interest1 - $total_extra_interest;
            }else{
                $pay_total = $total_interest1 + $amount + $total_admin_fees + $total_loan_cover;
                $m_config = $monthly_payment_config ;
                $total_interest1 = $total_interest1 + $total_extra_interest;
            }


            //additional info to be insert

            $loan_date = $original_loan_date;


            $data = array(
                'loan_number' => $loanid,
                'loan_product' => $product_id,
                'loan_customer' => $loan_customer,
                'customer_type' => $customer_type,
                'loan_date' => $loan_date,
                'loan_principal' => $amount,
                'loan_period' => $months,
                'period_type' => $loan->frequency,
                'loan_amount_term' => $m_config,
                'loan_interest' => $loan->interest,
                'loan_interest_amount' => $total_interest1,
                'admin_fee' => $loan->admin_fees,
                'admin_fees_amount' => $total_admin_fees,
                'loan_cover' => $loan->loan_cover,
                'loan_cover_amount' => $total_loan_cover,
                'loan_amount_total' => $pay_total,
                'next_payment_id' => 1,

            );
            $this->db->where($this->id, $loan_id);
            $this->db->update($this->table, $data);


            //borrower_loan_id
            $id = $loan_id;

            //insert each payment records to lend_payments
            if ($loan->frequency == 'Bi weekly') {
                $frequency = $months * 2;
                if ($this->is_zitsamba_biweekly_product($loan)) {
                    for ($i = 1; $i <= $frequency; $i++) {
                        $schedule_date = $this->build_zitsamba_biweekly_schedule_date($loan_date, $i);
                        $this->db->where('loan_id', $id);
                        $this->db->where('payment_number', $i);
                        $this->db->update(
                            'payement_schedules', array(
                                'customer' => $loan_customer,
                                'loan_id' => $id,
                                'payment_schedule' => $schedule_date,
                                'amount' => $monthly_payment1,
                                'principal' => $towards_balance1,
                                'interest' => $total_interest1,
                                'loan_balance' => $current_balance1,
                            )
                        );
                    }
                } else {
                    $date = $loan_date;
                    $loan_day = date('d', strtotime($date));
                    $loan_month = date('m', strtotime($date));
                    $start_day = ($loan_day >= 15) ? (($loan_month == '02') ? 28 : 30) : 15;
                    $date = date('Y/m/' . $start_day, strtotime($date));
                    for ($i = 1; $i <= $frequency; $i++) {
                        $this->db->where('loan_id', $id);
                        $this->db->where('payment_number', $i);
                        $this->db->update(
                            'payement_schedules', array(
                                'customer' => $loan_customer,
                                'loan_id' => $id,
                                'payment_schedule' => $date,
                                'amount' => $monthly_payment1,
                                'principal' => $towards_balance1,
                                'interest' => $total_interest1,
                                'loan_balance' => $current_balance1,
                            )
                        );

                        $day = date('d', strtotime($date));
                        if ($day == 15) {
                            if (date('m', strtotime($date)) == '02') {
                                $date = date('Y/02/28', strtotime($date));
                            } else {
                                $date = date('Y/m/30', strtotime($date));
                            }
                        } elseif ($day == 30 or $day > 15) {
                            if (date('m', strtotime($date)) == '01') {
                                $date = date('Y/02/15', strtotime('+1 month', strtotime($date)));
                            } else {
                                $date = date('Y/m/15', strtotime('+1 month', strtotime($date)));
                            }
                        }
                    }
                }
            }
            elseif ($loan->frequency == 'Weekly') {

                while ($current_balance > 0 && $ii <= $months) {


                    $towards_interest = ($i / 12) * $current_balance;  //this calculates the portion of your monthly payment that goes towards interest
                    $towards_fees1 = ($af / 12) * $current_balance;  //this calculates the portion of your monthly payment that goes towards interest
                    $towards_lc1 = ($lc / 12) * $current_balance;  //this calculates the portion of your monthly payment that goes towards interest

                    if ($monthly_payment > $current_balance) {
                        $monthly_payment = $current_balance + $towards_interest + $towards_fees1 + $towards_lc1;
                    }


                    $towards_balance = $monthly_payment - ($towards_interest + $towards_fees1 + $towards_lc1);
                    $total_interest = $total_interest + $towards_interest;
                    $total_admin_fees1 = $total_admin_fees1 + $towards_fees1;
                    $total_loan_cover1 = $total_loan_cover1 + $towards_lc1;
                    $current_balance = $current_balance - $towards_balance;


                    // display row


                    $frequency = $days * $ii;
                    $newdate = strtotime('+' . $frequency . ' day', strtotime($date));

                    //check if payment date landed on Sunday, push to Monday
                    if (date('D', $newdate) == 'Sun') {
                        $newdate = strtotime('+1 day', $newdate);
                    }

                    $newdate = date('Y-m-d', $newdate);
                    $this->db->where('loan_id',$id);
                    $this->db->where('payment_number',$ii);

                    $this->db->update(
                        'payement_schedules', array(

                            'customer' => $loan_customer,
                            'customer_type' => $customer_type,

                            'payment_schedule' => $newdate,
                            'amount' => $monthly_payment + $extra_interest,
                            'principal' => $towards_balance,
                            'interest' => $towards_interest + $extra_interest,
                            'padmin_fee' => $towards_fees1,
                            'ploan_cover' => $total_loan_cover1,
                            'loan_balance' => $current_balance,


                        )
                    );


                    //$table = $table . '<tr><td>'.$i.'</td><td>'.$amount_term.'</td><td>'.$newdate.'</td></tr>';
                    $ii++;
                }
            } else {
                if ($day > 15 && $loan->frequency == "Monthly" && $loan->schedule_plan=="cut off") {
                    while ($current_balance > 0 && $ii <= $months) {


                        $towards_interest = ($i / 12) * $current_balance;  //this calculates the portion of your monthly payment that goes towards interest
                        $towards_fees1 = ($af / 12) * $current_balance;  //this calculates the portion of your monthly payment that goes towards interest
                        $towards_lc1 = ($lc / 12) * $current_balance;  //this calculates the portion of your monthly payment that goes towards interest

                        if ($monthly_payment > $current_balance) {
                            $monthly_payment = $current_balance + $towards_interest + $towards_fees1 + $towards_lc1;
                        }


                        $towards_balance = $monthly_payment - ($towards_interest + $towards_fees1 + $towards_lc1);
                        $total_interest = $total_interest + $towards_interest;
                        $total_admin_fees1 = $total_admin_fees1 + $towards_fees1;
                        $total_loan_cover1 = $total_loan_cover1 + $towards_lc1;
                        $current_balance = $current_balance - $towards_balance;


                        // display row


                        $newdate = $this->build_month_end_schedule_date($loan_date, $ii - 1);
                        $schedule_date = $is_masamba_promotion
                            ? $this->build_masamba_promotion_schedule_date($loan_date, $ii)
                            : $newdate;
                        $this->db->where('loan_id',$id);
                        $this->db->where('payment_number',$ii);
                        $this->db->update(
                            'payement_schedules', array(

                                'customer' => $loan_customer,
                                'customer_type' => $customer_type,

                                'payment_schedule' => $schedule_date,

                                'amount' => $monthly_payment + $extra_interest,
                                'principal' => $towards_balance,
                                'interest' => $towards_interest + $extra_interest,
                                'padmin_fee' => $towards_fees1,
                                'ploan_cover' => $towards_lc1,

                                'loan_balance' => $current_balance,


                            )
                        );


                        //$table = $table . '<tr><td>'.$i.'</td><td>'.$amount_term.'</td><td>'.$newdate.'</td></tr>';
                        $ii++;
                    }
                }
                elseif ($day >= 1 && $day < 15 && $loan->frequency == "Monthly" && $loan->schedule_plan=="cut off") {
                    while ($current_balance > 0 && $ii <= $months) {


                        $towards_interest = ($i / 12) * $current_balance;  //this calculates the portion of your monthly payment that goes towards interest
                        $towards_fees1 = ($af / 12) * $current_balance;  //this calculates the portion of your monthly payment that goes towards interest
                        $towards_lc1 = ($lc / 12) * $current_balance;  //this calculates the portion of your monthly payment that goes towards interest

                        if ($monthly_payment > $current_balance) {
                            $monthly_payment = $current_balance + $towards_interest + $towards_fees1 + $towards_lc1;
                        }


                        $towards_balance = $monthly_payment - ($towards_interest + $towards_fees1 + $towards_lc1);
                        $total_interest = $total_interest + $towards_interest;
                        $total_admin_fees1 = $total_admin_fees1 + $towards_fees1;
                        $total_loan_cover1 = $total_loan_cover1 + $towards_lc1;
                        $current_balance = $current_balance - $towards_balance;


                        // display row


                        $newdate = $this->build_month_end_schedule_date($loan_date, $ii - 1);
                        $schedule_date = $is_masamba_promotion
                            ? $this->build_masamba_promotion_schedule_date($loan_date, $ii)
                            : $newdate;
                        $this->db->where('loan_id',$id);
                        $this->db->where('payment_number',$ii);
                        $this->db->update(
                            'payement_schedules', array(

                                'customer' => $loan_customer,
                                'customer_type' => $customer_type,

                                'payment_schedule' => $schedule_date,

                                'amount' => $monthly_payment - $extra_interest,
                                'principal' => $towards_balance,
                                'interest' => $towards_interest - $extra_interest,
                                'padmin_fee' => $towards_fees1,
                                'ploan_cover' => $towards_lc1,
                                'loan_balance' => $current_balance,


                            )
                        );


                        //$table = $table . '<tr><td>'.$i.'</td><td>'.$amount_term.'</td><td>'.$newdate.'</td></tr>';
                        $ii++;
                    }
                }
                else {
                    while ($current_balance > 0 && $ii <= $months){


                        $towards_interest = ($i / 12) * $current_balance;  //this calculates the portion of your monthly payment that goes towards interest
                        $towards_fees1 = ($af / 12) * $current_balance;  //this calculates the portion of your monthly payment that goes towards interest
                        $towards_lc1 = ($lc / 12) * $current_balance;  //this calculates the portion of your monthly payment that goes towards interest

                        if ($monthly_payment > $current_balance) {
                            $monthly_payment = $current_balance + $towards_interest + $towards_fees1 + $towards_lc1;
                        }


                        $towards_balance = $monthly_payment - ($towards_interest + $towards_fees1 + $towards_lc1);
                        $total_interest = $total_interest + $towards_interest;
                        $total_admin_fees1 = $total_admin_fees1 + $towards_fees1;
                        $total_loan_cover1 = $total_loan_cover1 + $towards_lc1;
                        $current_balance = $current_balance - $towards_balance;


                        // display row


                        $frequency =  $ii;
                        if ($day > 15) {
                            $newdate = $this->build_month_end_schedule_date($date, $ii - 1);
                        } else {
                            $newdate = strtotime('+' . $frequency . ' month', strtotime($date));
                        }

                        $newdate_ts = is_numeric($newdate) ? (int)$newdate : strtotime((string)$newdate);
                        if ($newdate_ts === false) {
                            $newdate_ts = strtotime((string)$date);
                        }

                        //check if payment date landed on Sunday, push to Monday
                        if (date('D', $newdate_ts) == 'Sun') {
                            $newdate_ts = strtotime('+1 day', $newdate_ts);
                        }

                        $newdate = date('Y-m-d', $newdate_ts);
                        $schedule_date = $is_masamba_promotion
                            ? $this->build_masamba_promotion_schedule_date($loan_date, $ii)
                            : $newdate;
                        $this->db->where('loan_id',$id);
                        $this->db->where('payment_number',$ii);
                        $this->db->update(
                            'payement_schedules', array(

                                'customer' => $loan_customer,
                                'customer_type' => $customer_type,

                                'payment_schedule' => $schedule_date,

                                'amount' => $monthly_payment + $extra_interest,
                                'principal' => $towards_balance,
                                'interest' => $towards_interest + $extra_interest,
                                'padmin_fee' => $towards_fees1,
                                'ploan_cover' => $towards_lc1,

                                'loan_balance' => $current_balance,


                            )
                        );


                        //$table = $table . '<tr><td>'.$i.'</td><td>'.$amount_term.'</td><td>'.$newdate.'</td></tr>';
                        $ii++;
                    }
                }


            }

            return $id;
        }
    }
	function add_loan2($lamount, $lmonths, $product_id, $ldate,$loan_customer, $customer_type,$worthness_file,$narration,$added_by)
	{
		//set Time Zone
		//date_default_timezone_set('Africa/Blantyre');
		$this->db->select('MAX(counter) as max_c');
		$lid = $this->db->get('loan');
		$result = $lid->row();
		$loanid='SCL'.date("Ymd").(100+$result->max_c);
		$fcounter=$result->max_c+1;
        $amount = $this->normalize_numeric_value($lamount);
        $loan_date = $this->normalize_date_value($ldate);
        $months = (int)$this->normalize_numeric_value($lmonths);
		//get loan parameters
		$loan = $this->db->select("*")->from('loan_products')->where('loan_product_id',$product_id)->get()->row();

		//divisor
		switch ($loan->frequency) {
			case 'Monthly':
				$divisor = 1;
				$days = 30;
				break;
			case 'Bi weekly':
				$divisor = 2;
				$days = 15;
				break;
			case 'Weekly':
				$divisor = 4;
				$days = 7;
				break;
		}

		//interest
		$amount_interest = $amount *( ($loan->interest/100)*12);
	$date = $ldate;
		$day = date('d', strtotime($date));
        $extra_interest = 0;
        $extra_days = 0;
        $total_extra_interest = 0;
        $date = $loan_date;
        $day = date('d', strtotime($date));
        if($day >=15 && $loan->frequency=="Monthly") {
//				    find last day of the effective date of loan
            $last_date = date("t", strtotime($date));
//                    if loan date is above 15 then the effective date should be 1st day of next month
            $month_end_date= date("Y-m-t", strtotime($date));
//                    add 1 to last date of this month
            $ll = strtotime('+1 day', strtotime($month_end_date));
            $loan_date = date('Y-m-d',  $ll );

//				    calculate number of xtra days
            $earlier = new DateTime($date);
            $later = new DateTime($loan_date);
            $date_diff =$later->diff($earlier);
            $extra_days = $date_diff->d;


//lets calculate interest of these dayes
            $total_extra_interest = (($extra_days)/30)*$amount*(($loan->interest+$loan->loan_cover)/100);
            $extra_interest = $total_extra_interest/ $months;

        }elseif ($day>1 && $day < 15 && $loan->frequency=="Monthly"){
            //                    if loan date is above 15 then the effective date should be 1st day of next month
            $month_start_date= date("Y-m-01", strtotime($date));
//                    add 1 to last date of this month
            $loan_date = date('Y-m-d', strtotime($month_start_date));

//				    calculate number of xtra days
            $earlier = new DateTime($month_start_date);
            $later = new DateTime($date);
            $date_diff =$later->diff($earlier);
            $extra_days = $date_diff->d;


//lets calculate interest of these dayes
            $total_extra_interest = (($extra_days)/30)*$amount*(($loan->interest+$loan->loan_cover)/100);
            $extra_interest = $total_extra_interest/ $months;
        }



		$i = ($loan->interest / 100) * 12;
		$af = ($loan->admin_fees / 100) * 12;
		$lc = ($loan->loan_cover / 100) * 12;
		$total_deduction = $i + $af + $lc;


		$monthly_payment = ($amount * ($total_deduction / 12) * pow((1 + $total_deduction / 12), $months) / (pow((1 + $total_deduction / 12), $months) - 1))+$extra_interest;
		$monthly_payment1 = ($amount * ($total_deduction / 12) * pow((1 + $total_deduction / 12), $months) / (pow((1 + $total_deduction / 12), $months) - 1))+$extra_interest;
		$current_balance = $amount;
		$current_balance1 = $amount;
		$payment_counter = 1;
		$total_interest = 0;
		$total_interest1 = 0;
		$total_admin_fees = 0;
		$total_admin_fees1 = 0;
		$total_loan_cover = 0;
		$total_loan_cover1 = 0;



		$ii=1;





		while($current_balance1 > 0) {
			//create rows



			$towards_interest1 = ($i / 12) * $current_balance1;  //this calculates the portion of your monthly payment that goes towards interest
			$towards_fees = ($af / 12) * $current_balance1;  //this calculates the portion of your monthly payment that goes towards administration fees
			$towards_lc = ($lc / 12) * $current_balance1;  //this calculates the portion of your monthly payment that goes towards administration fees

			if ($monthly_payment1 > $current_balance1) {
				$monthly_payment1 = $current_balance1 + $towards_interest1 + $towards_fees + $towards_lc + $extra_interest;

			}
			$towards_balance1 = $monthly_payment1 - ($towards_interest1 + $towards_fees + $towards_lc);
			$total_interest1 = $total_interest1 + $towards_interest1;
			$total_admin_fees = $total_admin_fees + $towards_fees;
			$total_loan_cover = $total_loan_cover + $towards_lc;
			$current_balance1 = $current_balance1 - $towards_balance1;

		}



		//additional info to be insert


		$data = array(
			'loan_number'=>$loanid,
			'loan_product'=>$product_id,
			'loan_customer'=>$loan_customer,
			'customer_type'=>$customer_type,
			'loan_date'=>$loan_date,
            'loan_principal'=>$amount,
            'loan_period'=>$months,
			'worthness_file'=>$worthness_file,
			'narration'=>$narration,
			'period_type'=> $loan->frequency,
			'loan_amount_term' => $monthly_payment,
			'loan_interest'=> $loan->interest,
			'loan_interest_amount'=> $total_interest1+$total_extra_interest,
			'admin_fee'=> $loan->admin_fees,
			'admin_fees_amount'=> $total_admin_fees,
			'loan_cover'=> $loan->loan_cover,
			'loan_cover_amount'=> $total_loan_cover,
			'loan_amount_total'=> $total_interest1+$amount+$total_loan_cover+$total_admin_fees+$total_extra_interest,
			'next_payment_id'=>1,
			'loan_added_by'=>$added_by,
			'counter'=>$fcounter
		);
		$this->db->insert($this->table,$data);


		//borrower_loan_id
		$id = $this->db->insert_id();

		//insert each payment records to lend_payments
		if($loan->frequency == 'Bi weekly') {
			$frequency = $months * 2;
			if ($this->is_zitsamba_biweekly_product($loan)) {
				for ($i = 1; $i <= $frequency; $i++) {
					$schedule_date = $this->build_zitsamba_biweekly_schedule_date($loan_date, $i);
					$this->db->insert(
						'payement_schedules', array(
							'customer' => $loan_customer,
							'loan_id' => $id,
							'payment_schedule' => $schedule_date,
							'payment_number' => $i,
							'amount' => $monthly_payment1,
							'principal' => $towards_balance1,
							'interest' => $total_interest1,
							'paid_amount' => 0,
							'loan_balance' => $current_balance1,
							'loan_date' => $loan_date,
						)
					);
				}
			} else {
				$date = $loan_date;
				$loan_day = date('d', strtotime($date));
				$loan_month = date('m', strtotime($date));
				$start_day = ($loan_day >= 15) ? (($loan_month == '02') ? 28 : 30) : 15;
				$date = date('Y/m/' . $start_day, strtotime($date));
				for ($i = 1; $i <= $frequency; $i++) {
					$this->db->insert(
						'payement_schedules', array(
							'customer' => $loan_customer,
							'loan_id' => $id,
							'payment_schedule' => $date,
							'payment_number' => $i,
							'amount' => $monthly_payment1,
							'principal' => $towards_balance1,
							'interest' => $total_interest1,
							'paid_amount' => 0,
							'loan_balance' => $current_balance1,
							'loan_date' => $loan_date,
						)
					);

					$day = date('d', strtotime($date));
					if ($day == 15) {
						if (date('m', strtotime($date)) == '02') {
							$date = date('Y/02/28', strtotime($date));
						} else {
							$date = date('Y/m/30', strtotime($date));
						}
					} elseif ($day == 30 or $day > 15) {
						if (date('m', strtotime($date)) == '01') {
							$date = date('Y/02/15', strtotime('+1 month', strtotime($date)));
						} else {
							$date = date('Y/m/15', strtotime('+1 month', strtotime($date)));
						}
					}
				}
			}
		}
		elseif($loan->frequency == 'Weekly') {

            while ($current_balance > 0)
            {




                $towards_interest = ($i / 12) * $current_balance;  //this calculates the portion of your monthly payment that goes towards interest
                $towards_fees1 = ($af / 12) * $current_balance;  //this calculates the portion of your monthly payment that goes towards interest
                $towards_lc1 = ($lc / 12) * $current_balance;  //this calculates the portion of your monthly payment that goes towards interest

                if ($monthly_payment > $current_balance) {
                    $monthly_payment = $current_balance + $towards_interest + $towards_fees1 + $towards_lc1+$extra_interest;
                }


                $towards_balance = $monthly_payment - ($towards_interest + $towards_fees1 + $towards_lc1);
                $total_interest = $total_interest + $towards_interest;
                $total_admin_fees1 = $total_admin_fees1 + $towards_fees1;
                $total_loan_cover1 = $total_loan_cover1 + $towards_lc1;
                $current_balance = $current_balance - $towards_balance;


                // display row



                $frequency = $days * $ii;
                $newdate = strtotime ('+'.$frequency.' day', strtotime ($date)) ;

                //check if payment date landed on weekend
                //if Sunday, make it Monday. If Saturday, make it Friday
                if(date ('D', $newdate) == 'Sun') {
                    $newdate = strtotime('+1 day', $newdate) ;
                } elseif(date('D', $newdate) == 'Sat') {
                    $newdate = strtotime('-1 day', $newdate) ;
                }

                $newdate = date('Y-m-d', $newdate );

                $this->db->insert(
                    'payement_schedules', array(

                        'customer' => $loan_customer,
                        'customer_type' => $customer_type,
                        'loan_id' => $id,
                        'payment_schedule' => $newdate,
                        'payment_number' => $ii,
                        'amount' => $monthly_payment,
                        'principal' => $towards_balance,
                        'interest' => $towards_interest+$extra_interest,
                        'padmin_fee' => $towards_fees1,
                        'ploan_cover' => $total_loan_cover1,
                        'paid_amount' => 0,
                        'loan_balance' => $current_balance,
                        'loan_date' => $loan_date,

                    )
                );


                //$table = $table . '<tr><td>'.$i.'</td><td>'.$amount_term.'</td><td>'.$newdate.'</td></tr>';
                $ii ++;
            }
		}
		else {
			while ($current_balance > 0)
			{




				$towards_interest = ($i / 12) * $current_balance;  //this calculates the portion of your monthly payment that goes towards interest
				$towards_fees1 = ($af / 12) * $current_balance;  //this calculates the portion of your monthly payment that goes towards interest
				$towards_lc1 = ($lc / 12) * $current_balance;  //this calculates the portion of your monthly payment that goes towards interest

				if ($monthly_payment > $current_balance) {
					$monthly_payment = $current_balance + $towards_interest + $towards_fees1 + $towards_lc1+$extra_interest;
				}


				$towards_balance = $monthly_payment - ($towards_interest + $towards_fees1 + $towards_lc1);
				$total_interest = $total_interest + $towards_interest;
				$total_admin_fees1 = $total_admin_fees1 + $towards_fees1;
				$total_loan_cover1 = $total_loan_cover1 + $towards_lc1;
				$current_balance = $current_balance - $towards_balance;


				// display row



				$frequency = $days * $ii;
				$newdate = strtotime ('+'.$frequency.' day', strtotime ($date)) ;

				//check if payment date landed on weekend
				//if Sunday, make it Monday. If Saturday, make it Friday
				if(date ('D', $newdate) == 'Sun') {
					$newdate = strtotime('+1 day', $newdate) ;
				} elseif(date('D', $newdate) == 'Sat') {
					$newdate = strtotime('-1 day', $newdate) ;
				}

				$newdate = date('Y-m-d', $newdate );

				$this->db->insert(
					'payement_schedules', array(

						'customer' => $loan_customer,
						'customer_type' => $customer_type,
						'loan_id' => $id,
						'payment_schedule' => $newdate,
						'payment_number' => $ii,
						'amount' => $monthly_payment,
						'principal' => $towards_balance,
						'interest' => $towards_interest+$extra_interest,
						'padmin_fee' => $towards_fees1,
						'ploan_cover' => $total_loan_cover1,
						'paid_amount' => 0,
						'loan_balance' => $current_balance,
						'loan_date' => $loan_date,

					)
				);


				//$table = $table . '<tr><td>'.$i.'</td><td>'.$amount_term.'</td><td>'.$newdate.'</td></tr>';
				$ii ++;
			}

		}

		//get next payment id and insert to lend_borrower_loans.next_payment_id
//		$payment = $this->Loan_model->next_payment($id);
//		$this->db->update('lend_borrower_loans', array('next_payment_id' => $payment->id), array('id' => $id));
		$data_account = array(
			'client_id' => $loan_customer,
			'account_number' => $loanid,
			'balance' => 0,
			'account_type' => 2,
			'account_type_product' => $product_id,


		);

		$this->db->insert('account',$data_account);
		return $id;
	}
	function add_loans($amount, $months, $loan_id, $loan_date,$loan_customer)
	{


		//get loan parameters
		$this->db->where('loan_product_id',$loan_id);
		$loan = $this->db->get('loan_products')->row();

		//divisor
		switch ($loan->frequency) {
			case 'Monthly':
				$divisor = 1;
				$days = 30;
				break;
			case 'Bi weekly':
				$divisor = 2;
				$days = 15;
				break;
			case 'Weekly':
				$divisor = 4;
				$days = 7;
				break;
		}

		//interest
		$amount_interest = $amount * ($loan->interest/100)/$divisor;

		//total payments applying interest
		$amount_total = $amount + $amount_interest * $months * $divisor;

		//payment per term
		$amount_term = number_format(round($amount / ($months * $divisor), 2) + $amount_interest, 2, '.', ',');


		$date = $loan_date;
		$i=($loan->interest/100)*12;


		$monthly_payment = $amount*($i/12)*pow((1+$i/12),$months)/(pow((1+$i/12),$months)-1);
		$monthly_payment1 = $amount*($i/12)*pow((1+$i/12),$months)/(pow((1+$i/12),$months)-1);
		$current_balance = $amount;
		$current_balance1 = $amount;
		$payment_counter = 1;
		$total_interest = 0;
		$total_interest1=0;




		while($current_balance1 > 0) {
			//create rows


			$towards_interest1 = ($i/12)*$current_balance1;  //this calculates the portion of your monthly payment that goes towards interest

			if ($monthly_payment1 > $current_balance1){
				$monthly_payment1 = $current_balance1 + $towards_interest1;
			}


			$towards_balance1 = $monthly_payment1 - $towards_interest1;
			$total_interest1 = $total_interest1 + $towards_interest1;
			$current_balance1 = $current_balance1 - $towards_balance1;

		}

		//Loan info
		$table = '<div id="calculator"><h3>Loan Info</h3>';
		$table = $table . '<table border="1" class="table">';
		$table = $table . '<tr><td>Loan Name:</td><td>'.$loan->product_name.'</td></tr>';
		$table = $table . '<tr><td>Interest:</td><td>'.$loan->interest.'%</td></tr>';
		$table = $table . '<tr><td>Terms:</td><td>'.$months.'</td></tr>';
		$table = $table . '<tr><td>Frequency:</td><td>Every '.$loan->frequency.' days</td></tr>';
		$table = $table . '</table>';
		$table = $table . '<h3>Computation</h3>';
		$table = $table . '<table>';
		$table = $table . '<tr><td>Loan Amount:</td><td> '.$this->config->item('currency_symbol') . number_format($amount, 2, '.', ',').'</td></tr>';
//        $table = $table . '<tr><td>Interest per First Month:</td><td> '.$this->config->item('currency_symbol') . $amount*$i.'</td></tr>';
//		$table = $table . '<tr><td>Interest per Term:</td><td> '.$this->config->item('currency_symbol') . $amount_interest.'</td></tr>';
		$table = $table . '<tr><td>Amount Per Term:</td><td> '.$this->config->item('currency_symbol') . round($monthly_payment,2).'</td></tr>';
		$table = $table . '<tr><td>Total Payment:</td><td> '.$this->config->item('currency_symbol') . number_format($total_interest1+$amount, 2, '.', ',').'</td></tr>';
		$table = $table . '</table>';

		//$monthly_payment = $amount*($i/12)*pow((1+$i/12),$months)/(pow((1+$i/12),$months)-1);


		$table = $table . '<table class="table" cellpadding="15" >
				<tr>
					<td width="30" align="center"><b>Pmt</b></td>
					<td width="60" align="center"><b>Payment</b></td>
					<td width="60" align="center"><b>Principal</b></td>
					<td width="60" align="center"><b>Interest</b></td>
					<td width="85" align="center"><b>Interest Paid</b></td>
					<td width="70" align="center"><b>Balance</b></td>
				</tr>	
			</table>';

		$table = $table ."<table  class='table' cellpadding='15' ";

		$table = $table ."<tr>";
		$table = $table . "<td width='30'>0</td>";
		$table = $table . "<td width='60'>&nbsp;</td>";
		$table = $table . "<td width='60'>&nbsp;</td>";
		$table = $table . "<td width='60'>&nbsp;</td>";
		$table = $table . "<td width='85'>&nbsp;</td>";
		$table = $table . "<td width='70'>".round($amount,2)."</td>";
		$table = $table . "</tr>";
		$data = array(
			'loan_number'=>rand(100,9999),
			'loan_product'=>$loan_id,
			'loan_customer'=>$loan_customer,
			'loan_date'=>$loan_date,
			'loan_principal'=>$amount,
			'loan_period'=>$months,
			'period_type'=> $loan->frequency,
			'loan_interest'=> $loan->interest,
			'loan_amount_total'=> $total_interest1+$amount,
			'next_payment_id'=>1,
			'loan_added_by'=>$this->session->userdata('user_id')
		);
		$this->db->insert($this->table,$data);
		$lid= $this->db->insert_id();
		while($current_balance > 0) {
			//create rows


			$towards_interest = ($i/12)*$current_balance;  //this calculates the portion of your monthly payment that goes towards interest

			if ($monthly_payment > $current_balance){
				$monthly_payment = $current_balance + $towards_interest;
			}


			$towards_balance = $monthly_payment - $towards_interest;
			$total_interest = $total_interest + $towards_interest;
			$current_balance = $current_balance - $towards_balance;


			// display row

			$table = $table . "<tr class='table_info'>";
			$table = $table . "<td>".$payment_counter."</td>";
			$table = $table ."<td>".round($monthly_payment,2)."</td>";
			$table = $table . "<td>".round($towards_balance,2)."</td>";
			$table = $table . "<td>".round($towards_interest,2)."</td>";
			$table = $table ."<td>".round($total_interest,2)."</td>";
			$table = $table ."<td>".round($current_balance,2)."</td>";
			$table = $table . "</tr>";

			$schedules = array(

				'customer' => $loan_customer,
				'loan_id' => $lid,
				'payment_schedule' => $this->input->post('payement_schedules',TRUE),
				'payment_number' => $payment_counter,
				'amount' => $monthly_payment,
				'principal' => $towards_balance,
				'interest' => $total_interest,
				'paid_amount' => 0,
				'loan_balance' => $current_balance,
				'loan_date' => $loan_date,

			);
			$payment_counter++;


		}

		$table = $table . '</table></div>';


		return true;
	}


	public function add_zitsamba_group($loan_amount, $product_id, $loan_term, $start_date) {
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
	
	// get all
	function get_all($status, $batch = null)
	{
        $this->db->order_by('loan.loan_added_date', 'DESC');
        // Pre-join borrower + branch so list views avoid N+1 queries per row (track, restructure, repayment, etc.)
        $this->db->select("loan.*, loan_products.product_name, loan_products.product_code,
            employees.Firstname AS efname, employees.Lastname AS elname, loan.branch AS loan_branch,
            " . $this->sql_loan_branch_display_name_expr() . ",
            CASE
                WHEN LOWER(TRIM(COALESCE(loan.customer_type, ''))) IN ('group', 'groups') THEN COALESCE(
                    NULLIF(CONCAT(COALESCE(borrower_grp.group_name, ''), '(', COALESCE(borrower_grp.group_code, ''), ')'), '()'),
                    (
                        SELECT CONCAT(COALESCE(g2.group_name, ''), '(', COALESCE(g2.group_code, ''), ')')
                        FROM `groups` g2
                        WHERE g2.group_code = loan.loan_customer
                        LIMIT 1
                    ),
                    CONCAT('Group #', loan.loan_customer)
                )
                WHEN LOWER(TRIM(COALESCE(loan.customer_type, ''))) IN ('individual', 'member') THEN COALESCE(
                    NULLIF(
                        CONCAT(
                            TRIM(CONCAT(COALESCE(borrower_ic.Firstname, ''), ' ', COALESCE(borrower_ic.Lastname, ''))),
                            IF(COALESCE(borrower_ic.ClientId, '') <> '', CONCAT(' (', borrower_ic.ClientId, ')'), '')
                        ),
                        ''
                    ),
                    (
                        SELECT CONCAT(
                            TRIM(CONCAT(COALESCE(ic2.Firstname, ''), ' ', COALESCE(ic2.Lastname, ''))),
                            IF(COALESCE(ic2.ClientId, '') <> '', CONCAT(' (', ic2.ClientId, ')'), '')
                        )
                        FROM individual_customers ic2
                        WHERE ic2.ClientId = loan.loan_customer
                        LIMIT 1
                    ),
                    CONCAT('Member #', loan.loan_customer)
                )
                WHEN borrower_grp.group_id IS NOT NULL THEN CONCAT(COALESCE(borrower_grp.group_name, ''), '(', COALESCE(borrower_grp.group_code, ''), ')')
                WHEN borrower_ic.id IS NOT NULL THEN CONCAT(
                    TRIM(CONCAT(COALESCE(borrower_ic.Firstname, ''), ' ', COALESCE(borrower_ic.Lastname, ''))),
                    IF(COALESCE(borrower_ic.ClientId, '') <> '', CONCAT(' (', borrower_ic.ClientId, ')'), '')
                )
                ELSE CONCAT('Customer #', COALESCE(NULLIF(loan.loan_customer, ''), 'N/A'))
            END AS customer_display_name,
            COALESCE(funds_source.source_name, 'N/A') AS funds_source_name,
            COALESCE(CONCAT(linked_member_grp.group_name, ' (', linked_member_grp.group_code, ')'), 'N/A') AS customer_group_name,
            COALESCE(loan.batch, 'N/A') AS batch_number,
            " . sql_loan_rbm_classification_expr() . " AS rbm_classification", false)
			->from($this->table)
			->join('loan_products', 'loan_products.loan_product_id = loan.loan_product')
            ->join('employees', 'employees.id = loan.loan_added_by')
            ->join('funds_source', 'funds_source.funds_source = loan.funds_source', 'left')
            ->join('groups linked_member_grp', 'linked_member_grp.group_id = loan.group_id AND loan.from_group = "Yes"', 'left')
            ->join('groups borrower_grp', "borrower_grp.group_id = loan.loan_customer AND LOWER(TRIM(COALESCE(loan.customer_type, ''))) IN ('group', 'groups')", 'left')
            ->join('individual_customers borrower_ic', "borrower_ic.id = loan.loan_customer AND LOWER(TRIM(COALESCE(loan.customer_type, ''))) IN ('individual', 'member')", 'left');
        $this->db->where("loan_status !=", "DELETED");

		if(!empty($status)){
			$this->db->where('loan_status',$status);
		}
		if (!empty($batch)) {
			$this->db->where('loan.batch', $batch);
		}

		return $this->db->get()->result();
	}
    function get_all_list()
    {
        $this->db->order_by('loan.loan_added_date', 'DESC');
        $this->db->select("*")
            ->from($this->table);


//$this->db->where('loan_number','SCL20231222645');

        return $this->db->get()->result();
    }
	function  get_all2(){
		$this->db->select("*")
			->from($this->table);
		$this->db->where('loan_status',"CLOSED");
		$this->db->order_by('loan.loan_id', 'ASC');
		return $this->db->get()->result();
	}
	function  get_all_by_product(){
		$this->db->select("*")
			->from($this->table);
		$this->db->where('loan_product',6);
		$this->db->order_by('loan.loan_id', 'ASC');
		return $this->db->get()->result();
	}
	function get_all_mod($status)
	{

		$this->db->select("*")
			->from($this->table)
			->join('loan_products','loan_products.loan_product_id =loan.loan_product')
			->join('individual_customers','individual_customers.id = loan.loan_customer');
		if($status !=""){
			$this->db->where('loan_status',$status);
//			$this->db->where('written_off_by !=', NULL);
			$this->db->where('written_off_by is NOT NULL', NULL, FALSE);
		}
		$this->db->order_by('loan.loan_id', 'DESC');
		return $this->db->get()->result();
	}
	function get_disbursed()
	{

		$this->db->select("*")
			->from($this->table)
			->join('loan_products','loan_products.loan_product_id =loan.loan_product')
			->join('individual_customers','individual_customers.id = loan.loan_customer');

		$this->db->where('disbursed','Yes');

		$this->db->order_by('loan.loan_id', 'DESC');
		return $this->db->get()->result();
	}

	function track_individual($user)
	{
		$this->db->select("loan.*, loan_products.product_name, loan_products.product_code,
			loan.branch AS loan_branch,
			" . $this->sql_loan_branch_display_name_expr() . ",
			employees.Firstname AS efname, employees.Lastname AS elname,
			CONCAT(TRIM(CONCAT(COALESCE(individual_customers.Firstname, ''), ' ', COALESCE(individual_customers.Lastname, ''))), IF(COALESCE(individual_customers.ClientId, '') <> '', CONCAT(' (', individual_customers.ClientId, ')'), '')) AS customer_display_name,
			COALESCE(funds_source.source_name, 'N/A') AS funds_source_name,
			COALESCE(CONCAT(member_grp.group_name, ' (', member_grp.group_code, ')'), 'N/A') AS customer_group_name,
			COALESCE(loan.batch, 'N/A') AS batch_number", false)
			->from($this->table)
			->join('loan_products', 'loan_products.loan_product_id = loan.loan_product')
			->join('individual_customers', 'individual_customers.id = loan.loan_customer')
			->join('employees', 'employees.id = loan.loan_added_by', 'left')
			->join('funds_source', 'funds_source.funds_source = loan.funds_source', 'left')
			->join('groups member_grp', 'member_grp.group_id = loan.group_id AND loan.from_group = "Yes"', 'left');
		if($user !=""){
			$this->db->where('loan_added_by',$user);
		}
		$this->db->order_by('loan.loan_id', 'DESC');
		return $this->db->get()->result();
	}
	function loan_user($id)
	{
		$this->db->select("*")
			->from($this->table)

			->join('individual_customers','individual_customers.id = loan.loan_customer');

		$this->db->where('loan_id',$id);

		return $this->db->get()->row();
	}
	public function sum_loans($from ,$to){
		$this->db->select('SUM(loan_principal) as total');
		$this->db->from('loan');
//	$this->db->join('payement_schedules','payement_schedules.loan_id=loan.loan_id');
		// $this->db->join('lend_payments','lend_payments.borrower_loan_id=lend_borrower_loans.id');
		$this->db->where('disbursed','Yes');
		$this->db->where('loan_status','ACTIVE');
		if($from !="" && $to !=""){
			$this->db->where('loan_added_date BETWEEN "'. date('Y-m-d', strtotime($from)). '" and "'. date('Y-m-d', strtotime($to)).'"');

		}
		return $this->db->get()->row();
	}
	public  function update_defaulters(){
		$get_days = check_exist_in_table('settings','settings_id ',1);
		$this->db->select("*")
			->from($this->table);
		$r = $this->db->get()->result();
		foreach ($r as $m){
			$this->db->select_max('payment_schedule')
				->from('payement_schedules')
				->where('loan_id',$m->loan_id);
			$result = $this->db->get()->row();
			$date=	date('Y-m-d', strtotime($result->payment_schedule. ' + '.$get_days->defaulter_durations.' days'));
//		echo $result->payement_schedules.' '.$date;
//		echo "<br>";
			if($date < date('Y-m-d')){
//				echo $result->payement_schedules.' '.$date;

				$this->db->where('loan_id',$m->loan_id)
					->update('loan',array('loan_status'=>'DEFAULTED'));
			}
		}


	}
	public function count_disbursed_loans($from,$to){
		$this->db->select('*');
		$this->db->from('loan');
		$this->db->where('disbursed','Yes');
		if($from !="" && $to !=""){
			$this->db->where('loan_added_date BETWEEN "'. date('Y-m-d', strtotime($from)). '" and "'. date('Y-m-d', strtotime($to)).'"');

		}
		return $this->db->count_all_results();
	}
	public function sum_total($from,$to){
		$this->db->select('*,loan.loan_principal as lm');
		$this->db->from('loan');
		$this->db->join('payement_schedules','payement_schedules.loan_id=loan.loan_id');
		// $this->db->join('lend_payments','lend_payments.borrower_loan_id=lend_borrower_loans.id');
		$this->db->where('disbursed','Yes');
		if($from !="" && $to !=""){
			$this->db->where('loan_added_date BETWEEN "'. date('Y-m-d', strtotime($from)). '" and "'. date('Y-m-d', strtotime($to)).'"');

		}

		return $this->db->get()->result();
	}
	public function sum_total_par()
{
	$this->db->select('*,loan.loan_principal as lm');
	$this->db->from('loan');
	$this->db->join('payement_schedules','payement_schedules.loan_id=loan.loan_id');
	// $this->db->join('lend_payments','lend_payments.borrower_loan_id=lend_borrower_loans.id');
	$this->db->where('disbursed','Yes');


	return $this->db->get()->result();
}
	public function sum_total2($q){
		if(!empty($q)){
			$this->db->select('*,loan.loan_principal as lm');
			$this->db->from('loan');
			$this->db->join('payement_schedules','payement_schedules.loan_id=loan.loan_id');
			$this->db->where('loan_status','ACTIVE');
		}else{
			$this->db->select('*,loan.loan_principal as lm');
			$this->db->from('loan');
			$this->db->join('payement_schedules','payement_schedules.loan_id=loan.loan_id');
			$this->db->where('loan_status','ACTIVE');

		}


		return $this->db->get()->result();
	}
	function get_summaryu($user,$loanproduct)
	{

        if($user == "" && $loanproduct==""){
            $q="";

            $q= "AND AA.loan_status != deleted";
            $qly = "(SELECT MIN(payment_schedule) FROM payement_schedules WHERE customer = AA.loan_customer AND status = 'NOT PAID' AND payment_schedule <= DATE(NOW()) ) AS max_d";

            $q= "SELECT AA.loan_id AS loan_id, AA.loan_added_by AS loan_added_by, AA.loan_customer AS loan_customer, AA.customer_type AS customer_type, AA.loan_number AS lnumber, AA.loan_principal AS lm, EP.Firstname, EP.Lastname, SUM(PS.amount) AS total_amount, $qly  FROM payement_schedules PS JOIN loan AA ON PS.loan_id = AA.loan_id JOIN employees EP ON AA.loan_added_by = EP.id WHERE PS.status = 'NOT PAID' AND PS.payment_schedule <= DATE(NOW()) AND AA.loan_status != 'deleted' GROUP BY AA.loan_id";

        }elseif($user && $user!='All' ){

            $q= "AND AA.loan_added_by = $user";
            $qly = "(SELECT MIN(payment_schedule) FROM payement_schedules WHERE customer = AA.loan_customer AND status = 'NOT PAID' AND payment_schedule <= DATE(NOW()) ) AS max_d";

            $q= "SELECT AA.loan_id AS loan_id, AA.loan_added_by AS loan_added_by, AA.loan_customer AS loan_customer, AA.customer_type AS customer_type, AA.loan_number AS lnumber, AA.loan_principal AS lm, EP.Firstname, EP.Lastname, SUM(PS.amount) AS total_amount, $qly  FROM payement_schedules PS JOIN loan AA ON PS.loan_id = AA.loan_id JOIN employees EP ON AA.loan_added_by = EP.id WHERE PS.status = 'NOT PAID' AND PS.payment_schedule <= DATE(NOW()) AND AA.loan_status != 'deleted' ".$q." GROUP BY AA.loan_id";
           }
        else{
            $q= "AND AA.loan_product = $loanproduct";
            $qly = "(SELECT MIN(payment_schedule) FROM payement_schedules WHERE customer = AA.loan_customer AND status = 'NOT PAID' AND payment_schedule <= DATE(NOW()) ) AS max_d";

            $q= "SELECT AA.loan_id AS loan_id, AA.loan_added_by AS loan_added_by, AA.loan_customer AS loan_customer, AA.customer_type AS customer_type, AA.loan_number AS lnumber, AA.loan_principal AS lm, EP.Firstname, EP.Lastname, SUM(PS.amount) AS total_amount, $qly FROM payement_schedules PS JOIN loan AA ON PS.loan_id = AA.loan_id JOIN employees EP ON AA.loan_added_by = EP.id WHERE PS.status = 'NOT PAID' AND PS.payment_schedule <= DATE(NOW()) AND AA.loan_status != 'deleted'  ".$q."  GROUP BY AA.loan_id";

        }



		$query = $this->db->query($q);



		if ($query->num_rows() > 0)
		{
			return $query->result();
		}

		return FALSE;
	}
    function get_filter($user, $branch, $branchgp, $product, $status, $from, $to, $loan_number = '')
	{
        $deletestatus='';
		$this->db->select("*, 
           loan.branch as loan_branch,
		   CASE
        WHEN groups.group_id IS NOT NULL THEN CONCAT(groups.group_name,' ',groups.group_code)
        WHEN individual_customers.id IS NOT NULL THEN CONCAT(individual_customers.Firstname, ' ', individual_customers.Lastname,'(',individual_customers.ClientId,')')
        ELSE NULL
    END AS customer_nam,
		
		e.Firstname as efname, e.Lastname as elname, loan.loan_customer as cid,
		approver.Firstname as approverfname, approver.Lastname as approverlname,
		rejecter.Firstname as rejecterfname, rejecter.Lastname as rejecterlname,
		rejecter.Firstname as rejecterfname, rejecter.Lastname as rejecterlname,
		disburser.Firstname as disburserfname, disburser.Lastname as disburserlname,
		roff.Firstname as rofffname, roff.Lastname as rofflname,
		COALESCE(funds_source.source_name, 'N/A') as funds_source_name,
		COALESCE(CONCAT(member_groups.group_name, ' (', member_groups.group_code, ')'), 'N/A') as customer_group_name,
		COALESCE(loan.batch, 'N/A') as batch_number,
		" . $this->sql_loan_branch_display_name_expr() . ",
		" . sql_loan_rbm_classification_expr() . " AS rbm_classification,
		CASE
			WHEN loan.customer_type = 'group' THEN CONCAT(COALESCE(borrower_grp.group_name, ''), '(', COALESCE(borrower_grp.group_code, ''), ')')
			WHEN loan.customer_type = 'individual' THEN CONCAT(TRIM(CONCAT(COALESCE(borrower_ic.Firstname, ''), ' ', COALESCE(borrower_ic.Lastname, ''))), IF(COALESCE(borrower_ic.ClientId, '') <> '', CONCAT(' (', borrower_ic.ClientId, ')'), ''))
			ELSE NULL
		END AS customer_display_name
		
		", false)
			->from($this->table)
			->join('loan_products','loan_products.loan_product_id =loan.loan_product')
			->join('employees e','e.id = loan.loan_added_by','left')
			->join('employees approver','approver.id = loan.loan_approved_by','left')
			->join('employees disburser','disburser.id = loan.disbursed_by','left')
			->join('employees roff','roff.id = loan.written_off_by','left')
			->join('employees rejecter','rejecter.id = loan.rejected_by','left');
		$this->db->join('individual_customers', 'loan.loan_customer = individual_customers.id', 'left');
		$this->db->join('groups', 'loan.loan_customer = groups.group_id', 'left');
		$this->db->join('funds_source', 'funds_source.funds_source = loan.funds_source', 'left');
		$this->db->join('groups member_groups', 'member_groups.group_id = loan.group_id AND loan.from_group = "Yes"', 'left');
		$this->db->join('groups borrower_grp', "borrower_grp.group_id = loan.loan_customer AND loan.customer_type = 'group'", 'left');
		$this->db->join('individual_customers borrower_ic', "borrower_ic.id = loan.loan_customer AND loan.customer_type = 'individual'", 'left');
        if (!empty($branch) && $branch != "All") {
            $this->apply_loan_branch_value_filter($branch);
        }
		if($status !="All"){
			$this->db->where('loan_status',$status);
		}
        $this->db->where("loan_status !=", "DELETED");
		if($user !="All"){
			$this->db->where('loan_added_by',$user);
		}
		if($product !="All"){
			$this->db->where('loan_product',$product);
		}
        if($from !=""){
            $this->db->where('loan_added_date >=', date('Y-m-d', strtotime($from)));
        }
        if($to !=""){
            $this->db->where('loan_added_date <=', date('Y-m-d', strtotime($to)));
        }
        if ($loan_number !== '') {
            $this->db->like('loan.loan_number', $loan_number);
        }
		$this->db->order_by('loan.loan_id', 'DESC');
		return $this->db->get()->result();
	}

	/**
	 * Branch display name when loan.branch stores branches.id, Code, or BranchCode.
	 */
	private function sql_loan_branch_display_name_expr($loan_alias = 'loan')
	{
		return "(SELECT b.BranchName FROM branches b
			WHERE b.id = {$loan_alias}.branch
			   OR b.Code = {$loan_alias}.branch
			   OR b.BranchCode = {$loan_alias}.branch
			LIMIT 1) AS branch_display_name";
	}

	/**
	 * Filter loans by branch (dropdown uses Code; loan.branch may store id, Code, or BranchCode).
	 */
	private function apply_loan_branch_value_filter($branch_value, $loan_alias = 'loan')
	{
		if ($branch_value === '' || $branch_value === null || $branch_value === 'All') {
			return;
		}
		$escaped = $this->db->escape($branch_value);
		$this->db->where("(
			{$loan_alias}.branch = {$escaped}
			OR {$loan_alias}.branch IN (SELECT Code FROM branches WHERE Code = {$escaped} OR BranchCode = {$escaped} OR CAST(id AS CHAR) = {$escaped})
			OR {$loan_alias}.branch IN (SELECT BranchCode FROM branches WHERE Code = {$escaped} OR BranchCode = {$escaped} OR CAST(id AS CHAR) = {$escaped})
			OR {$loan_alias}.branch IN (SELECT CAST(id AS CHAR) FROM branches WHERE Code = {$escaped} OR BranchCode = {$escaped} OR CAST(id AS CHAR) = {$escaped})
		)", null, false);
	}

	/**
	 * Shared loan list query (track, recommend, approve, etc.) with filters + pagination.
	 */
	private function apply_loan_list_joins($lightweight = false)
	{
		$this->db->from($this->table)
			->join('loan_products', 'loan_products.loan_product_id = loan.loan_product')
			->join('employees', 'employees.id = loan.loan_added_by', 'left')
			->join('funds_source', 'funds_source.funds_source = loan.funds_source', 'left')
			->join('groups linked_member_grp', 'linked_member_grp.group_id = loan.group_id AND loan.from_group = "Yes"', 'left')
			->join('groups borrower_grp', "borrower_grp.group_id = loan.loan_customer AND LOWER(TRIM(COALESCE(loan.customer_type, ''))) IN ('group', 'groups')", 'left')
			->join('individual_customers borrower_ic', "borrower_ic.id = loan.loan_customer AND LOWER(TRIM(COALESCE(loan.customer_type, ''))) IN ('individual', 'member')", 'left');

		if (!$lightweight) {
			$this->db->join('individual_customers', 'loan.loan_customer = individual_customers.id', 'left')
				->join('groups', 'loan.loan_customer = groups.group_id', 'left');
		}
	}

	private function sql_loan_list_customer_display_expr()
	{
		return "CASE
                WHEN LOWER(TRIM(COALESCE(loan.customer_type, ''))) IN ('group', 'groups') THEN COALESCE(
                    NULLIF(CONCAT(COALESCE(borrower_grp.group_name, ''), '(', COALESCE(borrower_grp.group_code, ''), ')'), '()'),
                    CONCAT('Group #', loan.loan_customer)
                )
                WHEN LOWER(TRIM(COALESCE(loan.customer_type, ''))) IN ('individual', 'member') THEN COALESCE(
                    NULLIF(
                        CONCAT(
                            TRIM(CONCAT(COALESCE(borrower_ic.Firstname, ''), ' ', COALESCE(borrower_ic.Lastname, ''))),
                            IF(COALESCE(borrower_ic.ClientId, '') <> '', CONCAT(' (', borrower_ic.ClientId, ')'), '')
                        ),
                        ''
                    ),
                    CONCAT('Member #', loan.loan_customer)
                )
                WHEN borrower_grp.group_id IS NOT NULL THEN CONCAT(COALESCE(borrower_grp.group_name, ''), '(', COALESCE(borrower_grp.group_code, ''), ')')
                WHEN borrower_ic.id IS NOT NULL THEN CONCAT(
                    TRIM(CONCAT(COALESCE(borrower_ic.Firstname, ''), ' ', COALESCE(borrower_ic.Lastname, ''))),
                    IF(COALESCE(borrower_ic.ClientId, '') <> '', CONCAT(' (', borrower_ic.ClientId, ')'), '')
                )
                ELSE CONCAT('Customer #', COALESCE(NULLIF(loan.loan_customer, ''), 'N/A'))
            END AS customer_display_name";
	}

	private function apply_loan_list_select($lightweight = false)
	{
		$rbm_sql = $lightweight ? "'Standard' AS rbm_classification" : (sql_loan_rbm_classification_expr() . " AS rbm_classification");
		$customer_nam_sql = $lightweight ? '' : ",
            CASE
                WHEN groups.group_id IS NOT NULL THEN CONCAT(groups.group_name, ' ', groups.group_code)
                WHEN individual_customers.id IS NOT NULL THEN CONCAT(individual_customers.Firstname, ' ', individual_customers.Lastname, '(', individual_customers.ClientId, ')')
                ELSE NULL
            END AS customer_nam";

		$this->db->select("loan.*, loan_products.product_name, loan_products.product_code,
            employees.Firstname AS efname, employees.Lastname AS elname, loan.branch AS loan_branch,
            " . $this->sql_loan_branch_display_name_expr() . ",
            " . $rbm_sql . ",
            " . $this->sql_loan_list_customer_display_expr() . $customer_nam_sql . ",
            COALESCE(funds_source.source_name, 'N/A') AS funds_source_name,
            COALESCE(CONCAT(linked_member_grp.group_name, ' (', linked_member_grp.group_code, ')'), 'N/A') AS customer_group_name,
            COALESCE(loan.batch, 'N/A') AS batch_number", false);
	}

	private function apply_loan_list_filters(array $filters)
	{
		if (!empty($filters['exclude_deleted'])) {
			$status_filter = isset($filters['status']) ? $filters['status'] : '';
			if ($status_filter !== 'DELETED') {
				$this->db->where('loan.loan_status !=', 'DELETED');
			}
		}

		if (!empty($filters['status_in']) && is_array($filters['status_in'])) {
			$this->db->where_in('loan.loan_status', $filters['status_in']);
		} elseif (isset($filters['status']) && $filters['status'] !== '' && $filters['status'] !== 'All') {
			$this->db->where('loan.loan_status', $filters['status']);
		}

		if (!empty($filters['batch'])) {
			$this->db->where('loan.batch', $filters['batch']);
		}

		if (!empty($filters['disbursed'])) {
			$this->db->where('loan.disbursed', $filters['disbursed']);
		}

		if (!empty($filters['written_off_pending'])) {
			$this->db->where('loan.written_off_by IS NOT NULL', null, false);
		}

		if (!empty($filters['supervisor_officer_ids']) && is_array($filters['supervisor_officer_ids'])) {
			if (empty($filters['supervisor_officer_ids'])) {
				$this->db->where('1 = 0', null, false);
			} else {
				$this->db->where_in('loan.loan_added_by', $filters['supervisor_officer_ids']);
			}
		} elseif (!empty($filters['user']) && $filters['user'] !== 'All') {
			$this->db->where('loan.loan_added_by', $filters['user']);
		}

		if (!empty($filters['branch']) && $filters['branch'] !== 'All') {
			$this->apply_loan_branch_value_filter($filters['branch']);
		}

		if (!empty($filters['product']) && $filters['product'] !== 'All') {
			$this->db->where('loan.loan_product', $filters['product']);
		}

		if (!empty($filters['from'])) {
			$this->db->where('DATE(loan.loan_added_date) >=', date('Y-m-d', strtotime($filters['from'])));
		}

		if (!empty($filters['to'])) {
			$this->db->where('DATE(loan.loan_added_date) <=', date('Y-m-d', strtotime($filters['to'])));
		}

		if (!empty($filters['loan_number'])) {
			$this->db->like('loan.loan_number', $filters['loan_number']);
		}

		if (!empty($filters['customer_name'])) {
			$this->apply_loan_list_customer_name_filter($filters['customer_name']);
		}
	}

	private function apply_loan_list_customer_name_filter($customer_name)
	{
		$term = trim((string) $customer_name);
		if ($term === '') {
			return;
		}

		$like = '%' . $this->db->escape_like_str($term) . '%';
		$esc_like = $this->db->escape($like);

		$this->db->group_start();
		$this->db->like('borrower_ic.Firstname', $term);
		$this->db->or_like('borrower_ic.Lastname', $term);
		$this->db->or_like('borrower_ic.ClientId', $term);
		$this->db->or_like('borrower_grp.group_name', $term);
		$this->db->or_like('borrower_grp.group_code', $term);
		$this->db->or_where(
			"CONCAT(TRIM(COALESCE(borrower_ic.Firstname,'')), ' ', TRIM(COALESCE(borrower_ic.Lastname,''))) LIKE {$esc_like}",
			null,
			false
		);
		$this->db->group_end();
	}

	/**
	 * Repayment list: all matching loans without per-row RBM subqueries (fast full load).
	 */
	public function get_loan_repayment_list(array $filters = array())
	{
		$this->db->reset_query();
		$this->db->order_by('loan.loan_added_date', 'DESC');
		$this->db->select("loan.*, loan_products.product_name, loan_products.product_code,
            employees.Firstname AS efname, employees.Lastname AS elname, loan.branch AS loan_branch,
            " . $this->sql_loan_branch_display_name_expr() . ",
            " . $this->sql_loan_list_customer_display_expr() . ",
            COALESCE(funds_source.source_name, 'N/A') AS funds_source_name,
            COALESCE(CONCAT(linked_member_grp.group_name, ' (', linked_member_grp.group_code, ')'), 'N/A') AS customer_group_name,
            COALESCE(loan.batch, 'N/A') AS batch_number", false)
			->from($this->table)
			->join('loan_products', 'loan_products.loan_product_id = loan.loan_product')
			->join('employees', 'employees.id = loan.loan_added_by', 'left')
			->join('funds_source', 'funds_source.funds_source = loan.funds_source', 'left')
			->join('groups linked_member_grp', 'linked_member_grp.group_id = loan.group_id AND loan.from_group = "Yes"', 'left')
			->join('groups borrower_grp', "borrower_grp.group_id = loan.loan_customer AND LOWER(TRIM(COALESCE(loan.customer_type, ''))) IN ('group', 'groups')", 'left')
			->join('individual_customers borrower_ic', "borrower_ic.id = loan.loan_customer AND LOWER(TRIM(COALESCE(loan.customer_type, ''))) IN ('individual', 'member')", 'left');
		$this->apply_loan_list_filters($filters);
		return $this->db->get()->result();
	}

	public function count_loan_list(array $filters = array())
	{
		$this->apply_loan_list_joins();
		$this->apply_loan_list_filters($filters);
		$this->db->select('COUNT(DISTINCT loan.loan_id) AS cnt', false);
		$row = $this->db->get()->row();
		return $row ? (int) $row->cnt : 0;
	}

	public function get_loan_list_paginated(array $filters = array(), $limit = 10, $offset = 0)
	{
		$this->apply_loan_list_select();
		$this->apply_loan_list_joins();
		$this->apply_loan_list_filters($filters);
		$this->db->order_by('loan.loan_added_date', 'DESC');
		$this->db->limit((int) $limit, (int) $offset);
		return $this->db->get()->result();
	}

	public function get_loan_list_all(array $filters = array(), $lightweight = false)
	{
		if (!empty($filters['customer_name'])) {
			$lightweight = true;
		}
		$this->db->reset_query();
		$this->apply_loan_list_select($lightweight);
		$this->apply_loan_list_joins($lightweight);
		$this->apply_loan_list_filters($filters);
		$this->db->order_by('loan.loan_added_date', 'DESC');
		return $this->db->get()->result();
	}

	/**
	 * Full loan list for Excel export (filters applied; includes approver/disburser names).
	 */
	public function get_loan_list_for_export(array $filters = array())
	{
		$this->db->reset_query();
		$this->apply_loan_list_select(true);
		$this->db->select(
			'approver.Firstname AS approverfname, approver.Lastname AS approverlname,
			rejecter.Firstname AS rejecterfname, rejecter.Lastname AS rejecterlname,
			disburser.Firstname AS disburserfname, disburser.Lastname AS disburserlname',
			false
		);
		$this->apply_loan_list_joins(true);
		$this->db->join('employees approver', 'approver.id = loan.loan_approved_by', 'left')
			->join('employees disburser', 'disburser.id = loan.disbursed_by', 'left')
			->join('employees rejecter', 'rejecter.id = loan.rejected_by', 'left');
		$this->apply_loan_list_filters($filters);
		$this->db->order_by('loan.loan_added_date', 'DESC');
		return $this->db->get()->result();
	}

	function get_user_loan($id)
	{
		$this->db->order_by($this->id, $this->order);
		$this->db->select("*")
			->from($this->table)
			->join('loan_products','loan_products.loan_product_id =loan.loan_product');
//			->join('individual_customers','individual_customers.id = loan.loan_customer');

		$this->db->where('loan_customer',$id);
		//$this->db->where('customer_type','individual');


		return $this->db->get()->result();
	}
	function get_group_loan($id)
	{
		$this->db->order_by($this->id, $this->order);
		$this->db->select("*")
			->from($this->table)
			->join('loan_products','loan_products.loan_product_id =loan.loan_product');
//			->join('individual_customers','individual_customers.id = loan.loan_customer');

		$this->db->where('loan_customer',$id);
		$this->db->where('customer_type','group');

		return $this->db->get()->result();
	}

	// get data by id
	function get_by_id($id)
	{
		$this->db->select("*")
			->from($this->table)
			->join('loan_products','loan_products.loan_product_id =loan.loan_product')
//			->join('individual_customers','individual_customers.id = loan.loan_customer');
			->join('employees','employees.id = loan.loan_added_by');
		$this->db->where($this->id, $id);
		return $this->db->get()->row();
	}
    function get_one($number)
    {

        $this->db->select("*, 
		   CASE
        WHEN groups.group_id IS NOT NULL THEN CONCAT(groups.group_name,' ',groups.group_code)
        WHEN individual_customers.id IS NOT NULL THEN CONCAT(individual_customers.Firstname, ' ', individual_customers.Lastname,'(',individual_customers.ClientId,')')
        ELSE NULL
    END AS customer_name,

		
		
		")
            ->from($this->table);


        $this->db->join('individual_customers', 'loan.loan_customer = individual_customers.id', 'left');
        $this->db->join('groups', 'loan.loan_customer = groups.group_id', 'left');


            $this->db->where('loan_number',$number);




        return $this->db->get()->row();
    }
    function get_next($number)
    {

        $this->db->select("*")
            ->from($this->table);

            $this->db->where('loan_number',$number);

        $re = $this->db->get()->row();
        if(!empty($re)){

            $this->db->select("*")
                ->from('payement_schedules');

            $this->db->where('loan_id',$re->loan_id);
            $this->db->where('payment_number',$re->next_payment_id );

            $res = $this->db->get()->row();
            return $res;
        }else{
            return  array();

        }
    }
    function get_one_loan($number)
    {

        $this->db->select("*, 
		   CASE
        WHEN groups.group_id IS NOT NULL THEN CONCAT(groups.group_name,' ',groups.group_code)
        WHEN individual_customers.id IS NOT NULL THEN CONCAT(individual_customers.Firstname, ' ', individual_customers.Lastname,'(',individual_customers.ClientId,')')
        ELSE NULL
    END AS customer_name,

		
		
		")
            ->from($this->table);


        $this->db->join('individual_customers', 'loan.loan_customer = individual_customers.id', 'left');
        $this->db->join('groups', 'loan.loan_customer = groups.group_id', 'left');


            $this->db->where('loan_id',$number);




        return $this->db->get()->row();
    }
	function get_by_id_r($id)
	{
		$this->db->select("*,individual_customers.Firstname as fname, individual_customers.Lastname as lname,employees.Firstname as efname, employees.Lastname as elname, ")
			->from($this->table)
			->join('loan_products','loan_products.loan_product_id =loan.loan_product')
			->join('individual_customers','individual_customers.id = loan.loan_customer')
			->join('employees','employees.id = loan.loan_added_by');
		$this->db->where($this->id, $id);
		return $this->db->get()->row();
	}
	// get total rows
	function total_rows($q = NULL) {
		$this->db->like('loan_id', $q);
		$this->db->or_like('loan_number', $q);
		$this->db->or_like('loan_product', $q);
		$this->db->or_like('loan_customer', $q);
		$this->db->or_like('loan_date', $q);
		$this->db->or_like('loan_principal', $q);
		$this->db->or_like('loan_period', $q);
		$this->db->or_like('period_type', $q);
		$this->db->or_like('loan_interest', $q);
		$this->db->or_like('loan_amount_total', $q);
		$this->db->or_like('next_payment_id', $q);
		$this->db->or_like('loan_added_by', $q);
		$this->db->or_like('loan_approved_by', $q);
		$this->db->or_like('loan_status', $q);
		$this->db->or_like('loan_added_date', $q);
		$this->db->from($this->table);
		return $this->db->count_all_results();
	}

	// get data with limit and search
	function get_limit_data($limit, $start = 0, $q = NULL) {
		$this->db->order_by($this->id, $this->order);
		$this->db->like('loan_id', $q);
		$this->db->or_like('loan_number', $q);
		$this->db->or_like('loan_product', $q);
		$this->db->or_like('loan_customer', $q);
		$this->db->or_like('loan_date', $q);
		$this->db->or_like('loan_principal', $q);
		$this->db->or_like('loan_period', $q);
		$this->db->or_like('period_type', $q);
		$this->db->or_like('loan_interest', $q);
		$this->db->or_like('loan_amount_total', $q);
		$this->db->or_like('next_payment_id', $q);
		$this->db->or_like('loan_added_by', $q);
		$this->db->or_like('loan_approved_by', $q);
		$this->db->or_like('loan_status', $q);
		$this->db->or_like('loan_added_date', $q);
		$this->db->limit($limit, $start);
		return $this->db->get($this->table)->result();
	}

	// insert data
	function insert($data)
	{
		$this->db->insert($this->table, $data);
	}

	// update data
	function update($id, $data)
	{
		$this->db->where($this->id, $id);
		$this->db->update($this->table, $data);
	}
	function update1($id, $data)
	{
		$this->db->where('loan_customer', $id);
		$this->db->update($this->table, $data);
	}

	// delete data
	function delete($id)
	{
		$this->db->where($this->id, $id);
		$this->db->delete($this->table);
	}

	// delete data
	function delete_data($id)
	{
		$this->db->where($this->id, $id);
		$this->db->delete($this->table_d);
	}
    /**
     * Get loan with RBM classification and additional risk information
     *
     * @param int $loan_id The ID of the loan
     * @return object Loan object with additional information
     */
    /**
     * Get loan with RBM classification and additional risk information
     *
     * @param int $loan_id The ID of the loan
     * @return object Loan object with additional information
     */
    public function get_loan_with_classification($loan_id) {
        // Get basic loan details
        $this->db->select('loan.*, lp.product_name, 
                    (SELECT MAX(DATEDIFF(CURRENT_DATE(), payment_schedule)) 
                     FROM payement_schedules 
                     WHERE loan_id = loan.loan_id 
                     AND status = "NOT PAID" 
                     AND payment_schedule < CURRENT_DATE()) as days_overdue,
                    (SELECT SUM(paid_amount) FROM payement_schedules WHERE loan_id = loan.loan_id) as amount_paid,
                    (SELECT MAX(payment_schedule) FROM payement_schedules WHERE loan_id = loan.loan_id AND paid_amount > 0) as last_payment_date');
        $this->db->from('loan');
        $this->db->join('loan_products lp', 'lp.loan_product_id = loan.loan_product');
        $this->db->where('loan.loan_id', $loan_id);

        $query = $this->db->get();
        return $query->row();
    }

    /**
     * Get days in arrears for a loan
     *
     * @param int $loan_id The ID of the loan
     * @return int Number of days in arrears (0 if no arrears)
     */
    public function get_days_in_arrears($loan_id) {
        $this->db->select('MAX(DATEDIFF(CURRENT_DATE(), payment_schedule)) as days_in_arrears');
        $this->db->from('payement_schedules');
        $this->db->where('loan_id', $loan_id);
        $this->db->where('status', 'NOT PAID');
        $this->db->where('payment_schedule < CURRENT_DATE()');

        $query = $this->db->get();
        $result = $query->row();

        return $result && $result->days_in_arrears ? $result->days_in_arrears : 0;
    }

    /**
     * Update risk information for a loan
     *
     * @param int $loan_id The ID of the loan
     * @param array $data Risk information data
     * @return bool Success status
     */
    public function update_risk_info($loan_id, $data) {
        $this->db->where('loan_id', $loan_id);
        return $this->db->update('loan', $data);
    }

    /**
     * Get all loans with risk information
     *
     * @return array List of loans with risk information
     */
    public function get_all_loans_with_risk_info() {
        $this->db->select('loan.*, lp.product_name, 
                    e.Firstname as officer_firstname, e.Lastname as officer_lastname,
                    re.Firstname as risk_officer_firstname, re.Lastname as risk_officer_lastname,
                    DATEDIFF(CURRENT_DATE(), 
                        (SELECT MIN(payment_schedule) FROM payement_schedules 
                         WHERE loan_id = loan.loan_id AND status = "NOT PAID" 
                         AND payment_schedule < CURRENT_DATE())) as days_in_arrears');
        $this->db->from('loan');
        $this->db->join('loan_products lp', 'lp.loan_product_id = loan.loan_product', 'left');
        $this->db->join('employees e', 'e.id = loan.loan_added_by', 'left');
        $this->db->join('employees re', 're.id = loan.risk_officer_id');
        $this->db->where('loan.loan_status', 'ACTIVE');
        $this->db->where('loan.disbursed', 'Yes');

        $query = $this->db->get();
        return $query->result();
    }
    /**
     * Get loans for risk recovery report
     *
     * @param string $risk_category Filter by RBM risk category
     * @param int $officer Filter by officer
     * @param int $branch Filter by branch
     * @param int $writeoff Filter by write-off recommendation (1 = yes, 0 = no)
     * @return array Loans with risk information
     */
    public function get_risk_recovery_loans($risk_category = null, $officer = null, $branch = null, $writeoff = null) {
        // Main query
        $this->db->select('loan.*, 
                    lp.product_name,
                    e.Firstname as officer_firstname, e.Lastname as officer_lastname,
                    re.Firstname as risk_officer_firstname, re.Lastname as risk_officer_lastname,
                    ' . $this->sql_loan_branch_display_name_expr() . ',
                    ' . sql_loan_rbm_classification_expr() . ' AS rbm_classification,
                    IFNULL((SELECT MAX(DATEDIFF(CURRENT_DATE(), payment_schedule)) 
                     FROM payement_schedules 
                     WHERE loan_id = loan.loan_id 
                     AND status IN ("NOT PAID", "PARTIAL PAID") 
                     AND payment_schedule < CURRENT_DATE()), 0) as days_in_arrears,
                    IFNULL((SELECT SUM(
                        GREATEST((amount - IFNULL(paid_amount, 0)), 0) *
                        (CASE WHEN amount > 0 THEN (principal / amount) ELSE 0 END)
                    )
                     FROM payement_schedules 
                     WHERE loan_id = loan.loan_id
                     AND status IN ("NOT PAID", "PARTIAL PAID")), 0) as principal_balance,
                    IFNULL((SELECT SUM(
                        GREATEST((amount - IFNULL(paid_amount, 0)), 0) *
                        (CASE WHEN amount > 0 THEN (interest / amount) ELSE 0 END)
                    )
                     FROM payement_schedules 
                     WHERE loan_id = loan.loan_id
                     AND status IN ("NOT PAID", "PARTIAL PAID")), 0) as interest_balance,
                    (SELECT MAX(paid_date)
                     FROM payement_schedules 
                     WHERE loan_id = loan.loan_id AND IFNULL(paid_amount, 0) > 0) as last_payment_date,
                    IFNULL((SELECT SUM(IFNULL(paid_amount, 0)) 
                     FROM payement_schedules 
                     WHERE loan_id = loan.loan_id), 0) as amount_paid,
                    (SELECT COUNT(*) 
                     FROM collateral 
                     WHERE loan_id = loan.loan_id) as collateral_count');

        $this->db->from('loan');
        $this->db->join('loan_products lp', 'lp.loan_product_id = loan.loan_product', 'left');
        $this->db->join('employees e', 'e.id = loan.loan_added_by', 'left');
        $this->db->join('employees re', 're.id = loan.risk_officer_id', 'left');

        // Base condition - only active loans
        $this->db->where('loan.loan_status', 'ACTIVE');
        $this->db->where('loan.disbursed', 'Yes');


//        // Apply filters
        if ($risk_category) {
            // Convert risk category to days range
            switch ($risk_category) {
                case 'Standard':
                    $this->db->having('days_in_arrears < 30');
                    break;
                case 'Special_Mention':
                    $this->db->having('days_in_arrears >= 30 AND days_in_arrears < 60');
                    break;
                case 'Substandard':
                    $this->db->having('days_in_arrears >= 60 AND days_in_arrears < 90');
                    break;
                case 'Doubtful':
                    $this->db->having('days_in_arrears >= 90 AND days_in_arrears < 180');
                    break;
                case 'Loss':
                    $this->db->having('days_in_arrears >= 180');
                    break;
            }
        }

        if ($officer) {
            $this->db->where('loan.risk_officer_id', $officer);
        }

        if ($branch) {
            $this->apply_loan_branch_value_filter($branch);
        }

        if ($writeoff !== null) {
            $this->db->where('loan.write_off_recommendation', $writeoff);
        }

        // Execute query
        $loans = $this->db->get()->result();

        // Process loans to add customer names and collateral info
        foreach ($loans as $key => $loan) {
            // Add customer name
            if ($loan->customer_type == 'individual') {
                $customer = $this->db->get_where('individual_customers', ['id' => $loan->loan_customer])->row();
                $loans[$key]->customer_name = $customer ? $customer->Firstname . ' ' . $customer->Lastname : 'Unknown';
            } else {
                $group = $this->db->get_where('groups', ['group_id' => $loan->loan_customer])->row();
                $loans[$key]->customer_name = $group ? $group->group_name . ' (' . $group->group_code . ')' : 'Unknown Group';
            }

            // Get collateral details
            $collaterals = $this->db->get_where('collateral', ['loan_id' => $loan->loan_id])->result();
            $loans[$key]->collaterals = $collaterals;

            // Calculate collateral total value
            $loans[$key]->collateral_total_value = array_sum(array_column($collaterals, 'value'));

            $loans[$key]->rbm_classification = determine_rbm_classification($loan->days_in_arrears);
        }

        return $loans;
    }

}
