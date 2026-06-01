<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Payement_schedules_model extends CI_Model
{

    public $table = 'payement_schedules';
    public $id = '';
    public $order = 'DESC';
    private $transaction_fields = null;

    function __construct()
    {
        parent::__construct();
    }

    private function insert_transaction_compat($transaction)
    {
        if ($this->transaction_fields === null) {
            $this->transaction_fields = $this->db->list_fields('transactions');
        }

        $fallback_ref = '';
        if (!empty($transaction['payment_reference'])) {
            $fallback_ref = (string)$transaction['payment_reference'];
        } elseif (!empty($transaction['reference'])) {
            $fallback_ref = (string)$transaction['reference'];
        } elseif (!empty($transaction['ref'])) {
            $fallback_ref = (string)$transaction['ref'];
        } else {
            $fallback_ref = 'SYS-' . uniqid('', true);
        }

        if (!isset($transaction['reference']) && in_array('reference', $this->transaction_fields)) {
            $transaction['reference'] = $fallback_ref;
        }

        if (!isset($transaction['method']) && in_array('method', $this->transaction_fields)) {
            $transaction['method'] = 0;
        }

        if (in_array('payment_reference', $this->transaction_fields)) {
            if (!isset($transaction['payment_reference']) || trim((string)$transaction['payment_reference']) === '') {
                $transaction['payment_reference'] = $fallback_ref;
            }
        }

        if (!isset($transaction['payment_type']) && in_array('payment_type', $this->transaction_fields)) {
            $transaction['payment_type'] = 'cash';
        }

        $filtered = [];
        foreach ($transaction as $key => $value) {
            if (in_array($key, $this->transaction_fields)) {
                $filtered[$key] = $value;
            }
        }

        $this->db->insert('transactions', $filtered);
    }

    /**
     * Ensure installment allocation is strictly sequential so only one schedule can be PARTIAL PAID.
     */
    private function normalize_schedule_payment_allocation($loan_number)
    {
        $loan_number = (int)$loan_number;
        if ($loan_number <= 0) {
            return;
        }

        $schedules = $this->db->select('id, payment_number, amount, paid_amount, status, partial_paid, paid_date')
            ->from($this->table)
            ->where('loan_id', $loan_number)
            ->order_by('payment_number', 'ASC')
            ->get()
            ->result();

        if (empty($schedules)) {
            return;
        }

        $total_paid = 0.0;
        $last_payment_number = 0;
        foreach ($schedules as $schedule) {
            $total_paid += max(0.0, (float)$schedule->paid_amount);
            $last_payment_number = (int)$schedule->payment_number;
        }

        $remaining_paid = $total_paid;
        $first_incomplete = null;

        foreach ($schedules as $schedule) {
            $schedule_amount = (float)$schedule->amount;
            // Zero-amount rows must never be treated as "fully paid" (0.0001 >= 0 was true),
            // which corrupted statuses and contributed to premature loan closure checks.
            if ($schedule_amount <= 0.0001) {
                if (abs((float)$schedule->paid_amount) > 0.0001
                    || (string)$schedule->status !== 'NOT PAID'
                    || (string)$schedule->partial_paid !== 'NO') {
                    $this->db->where('id', (int)$schedule->id)->update($this->table, array(
                        'paid_amount' => 0,
                        'status' => 'NOT PAID',
                        'partial_paid' => 'NO',
                        'paid_date' => null,
                    ));
                }
                continue;
            }

            $allocated = min($remaining_paid, $schedule_amount);
            $remaining_paid -= $allocated;

            $is_fully_paid = ($allocated + 0.0001) >= $schedule_amount;
            $is_partial = ($allocated > 0.0) && !$is_fully_paid;

            $new_status = $is_fully_paid ? 'PAID' : ($is_partial ? 'PARTIAL PAID' : 'NOT PAID');
            $new_partial_paid = $is_partial ? 'YES' : 'NO';
            $new_paid_date = $allocated > 0.0 ? $schedule->paid_date : null;

            if (
                abs(((float)$schedule->paid_amount) - $allocated) > 0.0001 ||
                (string)$schedule->status !== $new_status ||
                (string)$schedule->partial_paid !== $new_partial_paid ||
                (string)$schedule->paid_date !== (string)$new_paid_date
            ) {
                $this->db->where('id', (int)$schedule->id)->update($this->table, array(
                    'paid_amount' => $allocated,
                    'status' => $new_status,
                    'partial_paid' => $new_partial_paid,
                    'paid_date' => $new_paid_date
                ));
            }

            if ($first_incomplete === null && !$is_fully_paid) {
                $first_incomplete = (int)$schedule->payment_number;
            }
        }

        $next_payment_id = ($first_incomplete !== null) ? $first_incomplete : ($last_payment_number + 1);
        $this->db->where('loan_id', $loan_number)->update('loan', array('next_payment_id' => $next_payment_id));
    }

    /**
     * Lightweight repair: if repayments exist in transactions but schedules are stale,
     * apply only the missing paid delta sequentially across open schedules.
     */
    /**
     * Explicit repair: resets all schedule paid state to zero then re-applies
     * actual payments from the transactions table sequentially.
     *
     * Uses one payment amount per unique transaction ref (to avoid legacy
     * cumulative duplicates). Total is hard-capped to total_schedule_amount.
     * Safe to call from a controller; never called automatically on reads.
     *
     * Returns an array with keys: total_txn_paid, total_schedule_amount, applied.
     */
    public function repair_loan_payment_state($loan_number)
    {
        $loan_number = (int)$loan_number;
        if ($loan_number <= 0) {
            return array('error' => 'Invalid loan id');
        }

        $schedules = $this->db->select('id, amount, payment_number')
            ->from($this->table)
            ->where('loan_id', $loan_number)
            ->order_by('payment_number', 'ASC')
            ->get()
            ->result();

        if (empty($schedules)) {
            return array('error' => 'No schedules found');
        }

        $total_schedule_amount = 0.0;
        foreach ($schedules as $s) {
            $total_schedule_amount += (float)$s->amount;
        }

        // Sum ONE amount per unique ref (each ref = one real payment event).
        // MAX(amount) per ref guards against cumulative-write legacy rows.
        $txn_row = $this->db->query("
            SELECT COALESCE(SUM(max_per_ref), 0) AS total_paid,
                   MAX(date_stamp)               AS last_date
            FROM (
                SELECT ref, MAX(amount) AS max_per_ref, MAX(date_stamp) AS date_stamp
                FROM transactions
                WHERE loan_id         = ?
                  AND transaction_type = 3
                  AND amount           > 0
                GROUP BY ref
            ) AS unique_refs
        ", array($loan_number))->row();

        $raw_total = $txn_row ? (float)$txn_row->total_paid : 0.0;

        // Hard cap: never apply more than the total owed.
        $to_apply = min($raw_total, $total_schedule_amount);

        $fallback_date = ($txn_row && !empty($txn_row->last_date)
            && $txn_row->last_date !== '0000-00-00'
            && $txn_row->last_date !== '0000-00-00 00:00:00')
            ? date('Y-m-d', strtotime($txn_row->last_date))
            : date('Y-m-d');

        // Step 1: reset every schedule row to unpaid.
        $this->db->where('loan_id', $loan_number)->update($this->table, array(
            'paid_amount'  => 0,
            'paid_date'    => null,
            'status'       => 'NOT PAID',
            'partial_paid' => 'NO',
        ));

        // Step 2: apply sequentially from earliest installment.
        $remaining = $to_apply;
        foreach ($schedules as $schedule) {
            if ($remaining <= 0.001) {
                break;
            }

            $schedule_amount = (float)$schedule->amount;
            if ($schedule_amount <= 0) {
                continue;
            }

            $apply_now     = min($schedule_amount, $remaining);
            $is_fully_paid = ($apply_now + 0.0001) >= $schedule_amount;

            $this->db->where('id', (int)$schedule->id)->update($this->table, array(
                'paid_amount'  => $apply_now,
                'paid_date'    => $fallback_date,
                'status'       => $is_fully_paid ? 'PAID' : 'PARTIAL PAID',
                'partial_paid' => $is_fully_paid ? 'NO' : 'YES',
            ));

            $remaining -= $apply_now;
        }

        // Step 3: re-normalise to fix next_payment_id and any status edge cases.
        $this->normalize_schedule_payment_allocation($loan_number);

        return array(
            'total_txn_paid'       => $raw_total,
            'total_schedule_amount'=> $total_schedule_amount,
            'applied'              => $to_apply,
        );
    }

    /**
     * Pay loan with late charges allocation priority
     * Payment sequence: Late charges -> Loan cover -> Admin fees -> Interest -> Principal
     */
    public function pay_loan_with_late_charges($loan_number, $pay_number, $amount, $date, $tid) {
        // Keep allocations sequential before applying payment.
        $this->normalize_schedule_payment_allocation($loan_number);

        // Get payment schedule details
        $this->db->select("*")->from($this->table);
        $this->db->where('loan_id', $loan_number);
        $this->db->where('payment_number', $pay_number);
        $schedule = $this->db->get()->row();

        if (!$schedule) {
            return false;
        }

        $remaining_amount = $amount;
        $allocation_log = [];

        // 1. Pay Late Charges first
        $late_charges = $schedule->total_late_charge ?? 0;
        if ($late_charges > 0 && $remaining_amount > 0) {
            $late_charge_payment = min($remaining_amount, $late_charges);
            $remaining_amount -= $late_charge_payment;

            // Update late charges
            $new_late_charges = $late_charges - $late_charge_payment;
            $this->db->where('loan_id', $loan_number);
            $this->db->where('payment_number', $pay_number);
            $this->db->update($this->table, ['total_late_charge' => $new_late_charges]);

            $allocation_log[] = "Late Charges: MWK " . number_format($late_charge_payment, 2);
        }

        // Calculate remaining dues for each component
        $total_schedule_amount = ($schedule->amount ?? 0);
        $already_paid = ($schedule->paid_amount ?? 0);
        $schedule_balance = $total_schedule_amount - $already_paid;

        // If there's still remaining amount after late charges, allocate to schedule components
        if ($remaining_amount > 0 && $schedule_balance > 0) {
            $schedule_payment = min($remaining_amount, $schedule_balance);
            $remaining_amount -= $schedule_payment;

            // Update paid amount
            $new_paid_amount = $already_paid + $schedule_payment;

            $update_data = [
                'paid_amount' => $new_paid_amount,
                'paid_date' => $date
            ];

            // Check if fully paid (including late charges)
            if ($new_paid_amount >= $total_schedule_amount && ($schedule->total_late_charge ?? 0) == 0) {
                $update_data['status'] = 'PAID';
                $update_data['partial_paid'] = 'NO';

                // Update next payment ID
                $this->db->where('loan_id', $loan_number)
                         ->update('loan', ['next_payment_id' => $pay_number + 1]);

                $this->_should_close_loan($loan_number, $pay_number);
            } else {
                $update_data['partial_paid'] = 'YES';
            }

            $this->db->where('loan_id', $loan_number);
            $this->db->where('payment_number', $pay_number);
            $this->db->update($this->table, $update_data);

            $allocation_log[] = "Schedule Payment: MWK " . number_format($schedule_payment, 2);
        }

        // Record transaction
        $total_payment = $amount - $remaining_amount;
        $transaction = array(
            'ref' => $tid,
            'loan_id' => $loan_number,
            'amount' => $total_payment,
            'payment_number' => $pay_number,
            'transaction_type' => 3,
            'payment_proof' => 'null',
            'added_by' => $this->session->userdata('user_id'),
            'date_stamp' => $date
        );
        $this->insert_transaction_compat($transaction);

        $this->normalize_schedule_payment_allocation($loan_number);

        return [
            'success' => true,
            'amount_allocated' => $total_payment,
            'remaining_amount' => $remaining_amount,
            'allocation_log' => $allocation_log
        ];
    }

    /**
     * Apply a standard repayment starting from a given schedule number.
     * Allocates sequentially, updates statuses consistently, and logs each allocation.
     */
    private function process_standard_payment($loan_number, $pay_number, $amount, $date, $tid)
    {
        $loan_number = (int)$loan_number;
        $pay_number = (int)$pay_number;
        $remaining = (float)$amount;

        if ($loan_number <= 0 || $pay_number <= 0 || $remaining <= 0) {
            return false;
        }

        // Repair any historical out-of-order partial allocations first.
        $this->normalize_schedule_payment_allocation($loan_number);

        // Always start from the earliest incomplete schedule (NOT PAID or PARTIAL PAID).
        // If there is a PARTIAL PAID schedule with a lower number than $pay_number, start there.
        $earliest_row = $this->db->select('MIN(payment_number) as min_pn')
            ->from($this->table)
            ->where('loan_id', $loan_number)
            ->where_in('status', array('NOT PAID', 'PARTIAL PAID'))
            ->get()->row();
        $effective_start = !empty($earliest_row) && $earliest_row->min_pn !== null
            ? min((int)$pay_number, (int)$earliest_row->min_pn)
            : $pay_number;

        $schedules = $this->db->select('*')
            ->from($this->table)
            ->where('loan_id', $loan_number)
            ->where('payment_number >=', $effective_start)
            ->where_in('status', array('NOT PAID', 'PARTIAL PAID'))
            ->order_by('payment_number', 'ASC')
            ->get()
            ->result();

        if (empty($schedules)) {
            return false;
        }

        $applied = 0.0;
        foreach ($schedules as $schedule) {
            if ($remaining <= 0) {
                break;
            }

            $already_paid = (float)$schedule->paid_amount;
            $schedule_amount = (float)$schedule->amount;
            $due = $schedule_amount - $already_paid;

            if ($due <= 0) {
                continue;
            }

            $pay_now = min($remaining, $due);
            $new_paid = $already_paid + $pay_now;
            $is_fully_paid = ($new_paid + 0.0001) >= $schedule_amount;

            $update_data = array(
                'paid_amount' => $new_paid,
                'paid_date' => $date,
                'partial_paid' => $is_fully_paid ? 'NO' : 'YES',
                'status' => $is_fully_paid ? 'PAID' : 'PARTIAL PAID',
            );

            $this->db->where('loan_id', $loan_number)
                ->where('payment_number', (int)$schedule->payment_number)
                ->update($this->table, $update_data);

            if ($is_fully_paid) {
                $this->db->where('loan_id', $loan_number)
                    ->update('loan', array('next_payment_id' => ((int)$schedule->payment_number) + 1));

                $this->_should_close_loan($loan_number, (int)$schedule->payment_number);
            }

            $transaction = array(
                'ref' => $tid,
                'loan_id' => $loan_number,
                'amount' => $pay_now,
                'payment_number' => (int)$schedule->payment_number,
                'transaction_type' => 3,
                'payment_proof' => 'null',
                'added_by' => $this->session->userdata('user_id'),
                'date_stamp' => $date,
            );
            $this->insert_transaction_compat($transaction);

            $remaining -= $pay_now;
            $applied += $pay_now;
        }

        if ($applied > 0) {
            $this->normalize_schedule_payment_allocation($loan_number);
            return true;
        }

        return false;
    }

    // get all
    function get_all()
    {
        $this->db->order_by($this->id, $this->order);
        return $this->db->get($this->table)->result();
    }
	function get_all_by_id($id)
	{
        $this->normalize_schedule_payment_allocation($id);
        $this->recalculate_loan_balances($id);
        $this->correct_premature_loan_closure($id);
		$this->db->select('*');
		$this->db->order_by($this->id, $this->order);
		$this->db->join('loan','loan.loan_id = payement_schedules.loan_id');
		$this->db->where('payement_schedules.loan_id',$id);
		return $this->db->get($this->table)->result();
	}
    public function new_pay_new($loan_number,$pay_number,$amount, $date, $tid){
        return $this->process_standard_payment($loan_number, $pay_number, $amount, $date, $tid);

        $this->db->select("*")->from($this->table);
        $this->db->where('loan_id', $loan_number);
        $this->db->where('payment_number', $pay_number);
        $get_real_amount = $this->db->get()->row();
        $to_pay = $get_real_amount->amount - $get_real_amount->paid_amount ;


        if(intval($to_pay) > intval($amount) ){


            $final_paid = $amount + $get_real_amount->paid_amount ;
            $data = array(
                'partial_paid'=>'YES',

                'paid_amount'=>$final_paid,
                'paid_date'=> $date
            );
            $this->db->where('loan_id', $loan_number);
            $this->db->where('payment_number', $pay_number);
            $this->db->update($this->table,$data);

            $transaction = array(
                'ref' =>$tid,
                'loan_id' => $loan_number,
                'amount' => $final_paid,
                'payment_number' => $pay_number,
                'transaction_type' => 3,
                'payment_proof' => 'null',
                'added_by' => $this->session->userdata('user_id'),
                'date_stamp'=> $date

            );
            $this->insert_transaction_compat($transaction);
            return true;

        }
        elseif(intval($amount) > intval($to_pay)) {

            $our_amount = $amount;
//get all loans
            $this->db->select("*")
                ->from($this->table)

                ->where('loan_id', $loan_number)
                ->where('status', 'NOT PAID');
//                ->or_where('partial_paid', 'NO');
            $this->db->order_by('payment_number', 'ASC');

            $result = $this->db->get()->result();

            foreach ($result as $lr){




                if($our_amount < ($lr->amount-$lr->paid_amount) ){
                    $data = array(
                        'partial_paid'=>'YES',
                        'paid_amount'=>$our_amount,
                        'paid_date'=> $date
                    );
                    $this->db->where('loan_id', $loan_number);
                    $this->db->where('payment_number',  $lr->payment_number);
                    $this->db->update($this->table,$data);

                    $transaction = array(
                        'ref' => $tid,
                        'loan_id' => $loan_number,
                        'amount' => $our_amount ,
                        'payment_number' =>  $lr->payment_number,
                        'transaction_type' => 3,
                        'payment_proof' => 'null',
                        'added_by' => $this->session->userdata('user_id'),
                        'date_stamp'=> $date

                    );
                    $this->insert_transaction_compat($transaction);
                    return true;
                }
                elseif($our_amount==($lr->amount-$lr->paid_amount)){
                    $data = array(
                        'partial_paid'=>'NO',
                        'status'=>'PAID',
                        'paid_amount'=>$our_amount,
                        'paid_date'=> $date
                    );
                    $this->db->where('loan_id', $loan_number);
                    $this->db->where('payment_number', $lr->payment_number);
                    $this->db->update($this->table,$data);
                    $this->db->where('loan_id',$loan_number)->update('loan',array('next_payment_id'=>$lr->payment_number+1));
                    $this->_should_close_loan($loan_number, $lr->payment_number);
                    $transaction = array(
                        'ref' => $tid,
                        'loan_id' => $loan_number,
                        'amount' => $our_amount,
                        'payment_number' => $lr->payment_number,
                        'transaction_type' => 3,
                        'payment_proof' => 'null',
                        'added_by' => $this->session->userdata('user_id'),
                        'date_stamp'=> $date

                    );
                    $this->insert_transaction_compat($transaction);
                    return true;
                }
                else{
                    $our_amount = $our_amount - ($lr->amount-$lr->paid_amount);
                    $data = array(
                        'partial_paid'=>'NO',
                        'status'=>'PAID',
                        'paid_amount'=>$lr->amount,
                        'paid_date'=> $date
                    );
                    $this->db->where('loan_id', $loan_number);
                    $this->db->where('payment_number', $lr->payment_number);
                    $this->db->update($this->table,$data);
                    $this->db->where('loan_id',$loan_number)->update('loan',array('next_payment_id'=>$lr->payment_number+1));
                    $this->_should_close_loan($loan_number, $lr->payment_number);
                    $transaction = array(
                        'ref' => $tid,
                        'loan_id' => $loan_number,
                        'amount' => $lr->amount,
                        'payment_number' => $lr->payment_number,
                        'transaction_type' => 3,
                        'payment_proof' => 'null',
                        'added_by' => $this->session->userdata('user_id'),
                        'date_stamp'=> $date

                    );
                    $this->insert_transaction_compat($transaction);
                }

            }
            return true;

        }

        elseif(intval($to_pay) === intval($amount)){

            $new_to_pay = $amount;
            $final_paid = $new_to_pay + $get_real_amount->paid_amount ;

            $data = array(
                'partial_paid'=>'NO',
                'status'=>'PAID',
                'paid_amount'=>$final_paid,
                'paid_date'=> $date
            );
            $this->db->where('loan_id', $loan_number);
            $this->db->where('payment_number', $pay_number);
            $this->db->update($this->table,$data);
            $this->db->where('loan_id',$loan_number)->update('loan',array('next_payment_id'=>$pay_number+1));
            $this->_should_close_loan($loan_number, $pay_number);
            $transaction = array(
                'ref' => $tid,
                'loan_id' => $loan_number,
                'amount' => $final_paid,
                'payment_number' => $pay_number,
                'transaction_type' => 3,
                'payment_proof' => 'null',
                'added_by' => $this->session->userdata('user_id'),
                'date_stamp'=> $date

            );
            $this->insert_transaction_compat($transaction);
            return true;

        }
        else{

        }
    }
    public function pay_off($loan_number,$pay_number,$amount, $date, $tid){
        return $this->process_standard_payment($loan_number, $pay_number, $amount, $date, $tid);

        $this->db->select("*")->from($this->table);
        $this->db->where('loan_id', $loan_number);
        $this->db->where('payment_number', $pay_number);
        $get_real_amount = $this->db->get()->row();
        $to_pay = $get_real_amount->amount - $get_real_amount->paid_amount ;


        if(intval($to_pay) > intval($amount) ){


            $final_paid = $amount + $get_real_amount->paid_amount ;
            $data = array(
                'partial_paid'=>'YES',

                'paid_amount'=>$final_paid,
                'paid_date'=> $date
            );
            $this->db->where('loan_id', $loan_number);
            $this->db->where('payment_number', $pay_number);
            $this->db->update($this->table,$data);

            $transaction = array(
                'ref' =>$tid,
                'loan_id' => $loan_number,
                'amount' => $final_paid,
                'payment_number' => $pay_number,
                'transaction_type' => 3,
                'payment_proof' => 'null',
                'added_by' => $this->session->userdata('user_id'),
                'date_stamp'=> $date

            );
            $this->insert_transaction_compat($transaction);
            return true;

        }
        elseif(intval($amount) > intval($to_pay)) {

            $our_amount = $amount;
//get all loans
            $this->db->select("*")
                ->from($this->table)

                ->where('loan_id', $loan_number)
                ->where('status', 'NOT PAID');
//                ->or_where('partial_paid', 'NO');
            $this->db->order_by('payment_number', 'ASC');

            $result = $this->db->get()->result();

            foreach ($result as $lr){




                if($our_amount < ($lr->amount-$lr->paid_amount) ){
                    $data = array(
                        'partial_paid'=>'YES',
                        'paid_amount'=>$our_amount,
                        'paid_date'=> $date
                    );
                    $this->db->where('loan_id', $loan_number);
                    $this->db->where('payment_number',  $lr->payment_number);
                    $this->db->update($this->table,$data);

                    $transaction = array(
                        'ref' => $tid,
                        'loan_id' => $loan_number,
                        'amount' => $our_amount ,
                        'payment_number' =>  $lr->payment_number,
                        'transaction_type' => 3,
                        'payment_proof' => 'null',
                        'added_by' => $this->session->userdata('user_id'),
                        'date_stamp'=> $date

                    );
                    $this->insert_transaction_compat($transaction);
                    return true;
                }
                elseif($our_amount==($lr->amount-$lr->paid_amount)){
                    $data = array(
                        'partial_paid'=>'NO',
                        'status'=>'PAID',
                        'paid_amount'=>$our_amount,
                        'paid_date'=> $date
                    );
                    $this->db->where('loan_id', $loan_number);
                    $this->db->where('payment_number', $lr->payment_number);
                    $this->db->update($this->table,$data);
                    $this->db->where('loan_id',$loan_number)->update('loan',array('next_payment_id'=>$lr->payment_number+1));
                    $this->_should_close_loan($loan_number, $lr->payment_number);
                    $transaction = array(
                        'ref' => $tid,
                        'loan_id' => $loan_number,
                        'amount' => $our_amount,
                        'payment_number' => $lr->payment_number,
                        'transaction_type' => 3,
                        'payment_proof' => 'null',
                        'added_by' => $this->session->userdata('user_id'),
                        'date_stamp'=> $date

                    );
                    $this->insert_transaction_compat($transaction);
                    return true;
                }
                else{
                    $our_amount = $our_amount - ($lr->amount-$lr->paid_amount);
                    $data = array(
                        'partial_paid'=>'NO',
                        'status'=>'PAID',
                        'paid_amount'=>$lr->amount,
                        'paid_date'=> $date
                    );
                    $this->db->where('loan_id', $loan_number);
                    $this->db->where('payment_number', $lr->payment_number);
                    $this->db->update($this->table,$data);
                    $this->db->where('loan_id',$loan_number)->update('loan',array('next_payment_id'=>$lr->payment_number+1));
                    $this->_should_close_loan($loan_number, $lr->payment_number);
                    $transaction = array(
                        'ref' => $tid,
                        'loan_id' => $loan_number,
                        'amount' => $lr->amount,
                        'payment_number' => $lr->payment_number,
                        'transaction_type' => 3,
                        'payment_proof' => 'null',
                        'added_by' => $this->session->userdata('user_id'),
                        'date_stamp'=> $date

                    );
                    $this->insert_transaction_compat($transaction);
                }

            }
            return true;

        }

        elseif(intval($to_pay) === intval($amount)){

            $new_to_pay = $amount;
            $final_paid = $new_to_pay + $get_real_amount->paid_amount ;

            $data = array(
                'partial_paid'=>'NO',
                'status'=>'PAID',
                'paid_amount'=>$final_paid,
                'paid_date'=> $date
            );
            $this->db->where('loan_id', $loan_number);
            $this->db->where('payment_number', $pay_number);
            $this->db->update($this->table,$data);
            $this->db->where('loan_id',$loan_number)->update('loan',array('next_payment_id'=>$pay_number+1));
            $this->_should_close_loan($loan_number, $pay_number);
            $transaction = array(
                'ref' => $tid,
                'loan_id' => $loan_number,
                'amount' => $final_paid,
                'payment_number' => $pay_number,
                'transaction_type' => 3,
                'payment_proof' => 'null',
                'added_by' => $this->session->userdata('user_id'),
                'date_stamp'=> $date

            );
            $this->insert_transaction_compat($transaction);
            return true;

        }
        else{

        }
    }
	function get_total($id)
	{
		$this->db->select('SUM(amount) as total_payment, SUM(paid_amount) as paid_amount');

		$this->db->where('payement_schedules.loan_id',$id);
		return $this->db->get($this->table)->row();
	}

	/**
	 * Build consistent loan balance figures from repayment schedules.
	 *
	 * @param array $payments Schedule rows (optionally with total_pay_amount / total_late_charge)
	 * @param float|null $loan_amount_total Stored contract total on the loan row
	 * @return object
	 */
	public function summarize_loan_balances($payments, $loan_amount_total = null)
	{
		$total_scheduled = 0.0;
		$total_paid = 0.0;
		$total_late_charges = 0.0;
		$total_due_now = 0.0;

		foreach ((array) $payments as $row) {
			$amount = (float) ($row->amount ?? 0);
			$late_charge = (float) ($row->total_late_charge ?? 0);
			$paid = (float) ($row->paid_amount ?? 0);

			$total_scheduled += $amount;
			$total_paid += $paid;
			$total_late_charges += $late_charge;
			$total_due_now += max(0, $amount + $late_charge - $paid);
		}

		$total_scheduled = round($total_scheduled, 2);
		$total_paid = round($total_paid, 2);
		$total_late_charges = round($total_late_charges, 2);
		$contract_remaining = round(max(0, $total_scheduled - $total_paid), 2);
		$total_due_now = round($total_due_now, 2);
		$stored_total = $loan_amount_total !== null ? round((float) $loan_amount_total, 2) : 0.0;

		// Contract total only (installments). Late charges are penalties, not part of loan principal/contract.
		$display_total = $stored_total > 0 ? $stored_total : $total_scheduled;
		if ($stored_total > 0 && $total_scheduled > 0 && abs($stored_total - $total_scheduled) < 0.02) {
			$display_total = $total_scheduled;
		}

		return (object) array(
			'total_loan_amount' => $display_total,
			'total_scheduled' => $total_scheduled,
			'total_paid' => $total_paid,
			'remaining_balance' => $contract_remaining,
			'total_late_charges' => $total_late_charges,
			'total_due_now' => $total_due_now,
			'stored_loan_amount_total' => $stored_total,
			'totals_mismatch' => ($stored_total > 0 && $total_scheduled > 0 && abs($stored_total - $total_scheduled) >= 0.02),
		);
	}
	function edits()
	{
		$this->db->select('*');


		$r= $this->db->get('defect_loand')->result();
		$count = 0;
		foreach ($r as $rr){
			$this->db->where('loan_id', $rr->loan_id);
			$this->db->delete($this->table);
			$count ++;
		}
		echo $count;
	}
	  function get_all_by_idPayNumber($id,$paymentnumber)
	{
        $this->normalize_schedule_payment_allocation($id);
		$this->db->select('*');
		$this->db->order_by($this->id, $this->order);
		$this->db->join('loan','loan.loan_id = payement_schedules.loan_id');
		$this->db->where('payement_schedules.loan_id',$id);
        $this->db->where('payement_schedules.payment_number',$paymentnumber);
		return $this->db->get($this->table)->result();
	}
	
	 // update data
     function updateTopup($id,$paymentnumber, $data)
     {
         $this->db->where('loan_id', $id);
         $this->db->where('payment_number', $paymentnumber);
         $this->db->update($this->table, $data);
     }
     
   function get_next($pay_number,$id)
    {
		$this->normalize_schedule_payment_allocation($id);

        $this->db->where('loan_id',$id);
        $this->db->where('payment_number',$pay_number);
        return $this->db->get($this->table)->row();
    }

    // get data by id
    function get_by_id($id)
    {
        $this->db->where($this->id, $id);
        return $this->db->get($this->table)->row();
    }
    function out_pay($loan_number, $pay_number, $amount, $date)
    {
$tid = "ST." . date('Y') . date('m') . date('d') . '.' . rand(100, 999);
        $this->process_standard_payment($loan_number, $pay_number, $amount, $date, $tid);
        return $tid;

        // Get all loans
        $this->db->select("*")
            ->from($this->table)
            ->where('loan_id', $loan_number)

            ->where('status', 'NOT PAID');

        $result = $this->db->get()->result();
        $balance = $amount;
        foreach ($result as $lr) {

            $amount_to_pay = ($lr->amount - $lr->paid_amount);

            // If the balance is already 0, break out of the loop
            if ($balance <= 0 ) {
                break;
            }

            if ($amount_to_pay == $balance) {
                $data = array(
                    'partial_paid' => 'NO',
                    'status' => 'PAID',
                    'paid_amount' => $balance,
                    'paid_date' => $date
                );
                $balance = 0;
            } elseif ($amount_to_pay > $balance) {
                $data = array(
                    'partial_paid' => 'YES',
                    'paid_amount' => $balance + $lr->paid_amount,
                    'paid_date' => $date
                );
                $balance = 0;
            } else {
                $data = array(
                    'partial_paid' => 'NO',
                    'status' => 'PAID',
                    'paid_amount' => $lr->amount,
                    'paid_date' => $date
                );
                $balance -= $lr->amount;
            }

            $this->db->where('loan_id', $loan_number);
            $this->db->where('payment_number', $lr->payment_number);
            $this->db->update($this->table, $data);
            $this->db->where('loan_id', $loan_number)->update('loan', array('next_payment_id' => $pay_number));
            $this->_should_close_loan($loan_number, $lr->payment_number);

            $transaction = array(
                'ref' => $tid,
                'loan_id' => $loan_number,
                'amount' => 0,
                'payment_number' => $pay_number,
                'transaction_type' => 3,
                'payment_proof' => 'null',
                'added_by' => $this->session->userdata('user_id'),
                'date_stamp' => $date

            );
            $this->insert_transaction_compat($transaction);
            $pay_number++;
        }
        return $tid;
    }


    public function new_pay($loan_number,$pay_number,$amount, $date){
        $tid = "GF.".date('Y').date('m').date('d').'.'.rand(100,999);
        return $this->process_standard_payment($loan_number, $pay_number, $amount, $date, $tid);

        $this->db->select("*")->from($this->table);
        $this->db->where('loan_id', $loan_number);
        $this->db->where('payment_number', $pay_number);
        $get_real_amount = $this->db->get()->row();
        $to_pay = $get_real_amount->amount - $get_real_amount->paid_amount ;


        if(intval($to_pay) > intval($amount) ){


            $final_paid = $amount + $get_real_amount->paid_amount ;
            $data = array(
                'partial_paid'=>'YES',

                'paid_amount'=>$final_paid,
                'paid_date'=> $date
            );
            $this->db->where('loan_id', $loan_number);
            $this->db->where('payment_number', $pay_number);
            $this->db->update($this->table,$data);

            $transaction = array(
                'ref' => "GF.".date('Y').date('m').date('d').'.'.rand(100,999),
                'loan_id' => $loan_number,
                'amount' => $final_paid,
                'payment_number' => $pay_number,
                'transaction_type' => 3,
                'payment_proof' => 'null',
                'added_by' => $this->session->userdata('user_id'),
                'date_stamp'=> $date

            );
            $this->insert_transaction_compat($transaction);
            return true;

        }
        elseif(intval($amount) > intval($to_pay)) {

            $our_amount = $amount;
//get all loans
            $this->db->select("*")
                ->from($this->table)

                ->where('loan_id', $loan_number)
                ->where('status', 'NOT PAID');
//                ->or_where('partial_paid', 'NO');
            $this->db->order_by('payment_number', 'ASC');

            $result = $this->db->get()->result();

            foreach ($result as $lr){




                if($our_amount < ($lr->amount-$lr->paid_amount) ){
                    $data = array(
                        'partial_paid'=>'YES',
                        'paid_amount'=>$our_amount,
                        'paid_date'=> $date
                    );
                    $this->db->where('loan_id', $loan_number);
                    $this->db->where('payment_number',  $lr->payment_number);
                    $this->db->update($this->table,$data);

                    $transaction = array(
                        'ref' => "GF.".date('Y').date('m').date('d').'.'.rand(100,999),
                        'loan_id' => $loan_number,
                        'amount' => $our_amount ,
                        'payment_number' =>  $lr->payment_number,
                        'transaction_type' => 3,
                        'payment_proof' => 'null',
                        'added_by' => $this->session->userdata('user_id'),
                        'date_stamp'=> $date

                    );
                    $this->insert_transaction_compat($transaction);
                    return true;
                }
                elseif($our_amount==($lr->amount-$lr->paid_amount)){
                    $data = array(
                        'partial_paid'=>'NO',
                        'status'=>'PAID',
                        'paid_amount'=>$our_amount,
                        'paid_date'=> $date
                    );
                    $this->db->where('loan_id', $loan_number);
                    $this->db->where('payment_number', $lr->payment_number);
                    $this->db->update($this->table,$data);
                    $this->db->where('loan_id',$loan_number)->update('loan',array('next_payment_id'=>$lr->payment_number+1));
                    $this->_should_close_loan($loan_number, $lr->payment_number);
                    $transaction = array(
                        'ref' => "GF.".date('Y').date('m').date('d').'.'.rand(100,999),
                        'loan_id' => $loan_number,
                        'amount' => $our_amount,
                        'payment_number' => $lr->payment_number,
                        'transaction_type' => 3,
                        'payment_proof' => 'null',
                        'added_by' => $this->session->userdata('user_id'),
                        'date_stamp'=> $date

                    );
                    $this->insert_transaction_compat($transaction);
                    return true;
                }
                else{
                    $our_amount = $our_amount - ($lr->amount-$lr->paid_amount);
                    $data = array(
                        'partial_paid'=>'NO',
                        'status'=>'PAID',
                        'paid_amount'=>$lr->amount,
                        'paid_date'=> $date
                    );
                    $this->db->where('loan_id', $loan_number);
                    $this->db->where('payment_number', $lr->payment_number);
                    $this->db->update($this->table,$data);
                    $this->db->where('loan_id',$loan_number)->update('loan',array('next_payment_id'=>$lr->payment_number+1));
                    $this->_should_close_loan($loan_number, $lr->payment_number);
                    $transaction = array(
                        'ref' => "GF.".date('Y').date('m').date('d').'.'.rand(100,999),
                        'loan_id' => $loan_number,
                        'amount' => $lr->amount,
                        'payment_number' => $lr->payment_number,
                        'transaction_type' => 3,
                        'payment_proof' => 'null',
                        'added_by' => $this->session->userdata('user_id'),
                        'date_stamp'=> $date

                    );
                    $this->insert_transaction_compat($transaction);
                }

            }
            return true;

        }

        elseif(intval($to_pay) === intval($amount)){

            $new_to_pay = $amount;
            $final_paid = $new_to_pay + $get_real_amount->paid_amount ;

            $data = array(
                'partial_paid'=>'NO',
                'status'=>'PAID',
                'paid_amount'=>$final_paid,
                'paid_date'=> $date
            );
            $this->db->where('loan_id', $loan_number);
            $this->db->where('payment_number', $pay_number);
            $this->db->update($this->table,$data);
            $this->db->where('loan_id',$loan_number)->update('loan',array('next_payment_id'=>$pay_number+1));
            $this->_should_close_loan($loan_number, $pay_number);
            $transaction = array(
                'ref' => "GF.".date('Y').date('m').date('d').'.'.rand(100,999),
                'loan_id' => $loan_number,
                'amount' => $final_paid,
                'payment_number' => $pay_number,
                'transaction_type' => 3,
                'payment_proof' => 'null',
                'added_by' => $this->session->userdata('user_id'),
                'date_stamp'=> $date

            );
            $this->insert_transaction_compat($transaction);
            return true;

        }
        else{

        }
    }
    
    
    //
    
    
                     public function topnew_pay($loan_number,$pay_number,$amount,$realaccountbalance){
                    
                     
                         
                         $amountComing=$amount;
                         
                            $datapaymentschedule = get_by_id2($this->table,'loan_id ='.$loan_number.'  AND payment_number ='.$pay_number);
                            //get loan products id
                             $dataloanproductsid = get_by_id2('loan','loan_id ='.$loan_number);
                                   //get loan products details
                            
                             $dataloanproducts = get_by_id2('loan_products','loan_product_id ='.$dataloanproductsid->loan_product);
                              $this->db->select("*")->from($this->table);
        $this->db->where('loan_id', $loan_number);
        $this->db->where('payment_number', $pay_number);
        $get_real_amount = $this->db->get()->row();
        $to_pay = $get_real_amount->amount - $get_real_amount->paid_amount ;
		                 

                           
                            if($datapaymentschedule ->loan_balance>$realaccountbalance){
                             $newbalance=($datapaymentschedule ->loan_balance- $realaccountbalance);
                            }
                            else {
                                 $newbalance=$realaccountbalance-$to_pay;
                            }
                           
                             //interest
   
                         	$amount_interest = $amount *( ($dataloanproducts ->interest/100)*12);


		


		//total payments applying interest
		$amount_total =$newbalance + $amount_interest * $dataloanproductsid ->loan_period * 1;

		//payment per term
		$amount_term = number_format(round($newbalance / ($dataloanproductsid ->loan_period * 1), 2) + $amount_interest, 2, '.', '');

	
		//$monthly_payment = $amount*($i/12)*pow((1+$i/12),$months)/(pow((1+$i/12),$months)-1);

		$i = ($dataloanproducts->interest / 100) * 12;
		$af = ($dataloanproducts->admin_fees / 100) * 12;
		$lc = ($dataloanproducts->loan_cover / 100) * 12;
		$total_deduction = $i + $af + $lc;

       $months=  $dataloanproductsid->loan_period;
		$monthly_payment = $amount * ($total_deduction / 12) * pow((1 + $total_deduction / 12), $months) / (pow((1 + $total_deduction / 12), $months) - 1);
		$monthly_payment1 = $amount * ($total_deduction / 12) * pow((1 + $total_deduction / 12), $months) / (pow((1 + $total_deduction / 12), $months) - 1);
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
				$monthly_payment1 = $current_balance1 + $towards_interest1 + $towards_fees + $towards_lc;

			}
			$towards_balance1 = $monthly_payment1 - ($towards_interest1 + $towards_fees + $towards_lc);
			$total_interest1 = $total_interest1 + $towards_interest1;
			$total_admin_fees = $total_admin_fees + $towards_fees;
			$total_loan_cover = $total_loan_cover + $towards_lc;
			$current_balance1 = $current_balance1 - $towards_balance1;

		}



		
		
		
		               
        if(intval($to_pay) > intval($newbalance) ){


            $final_paid = $amount + $get_real_amount->paid_amount ;
            $data = array(
                'partial_paid'=>'YES',
                'amount' => $monthly_payment,
                'principal' =>$realaccountbalance,
                 'interest' => 	$amount_interest ,
                  'loan_balance' => $newbalance,
                 
                'paid_amount'=>$final_paid
            );
            $this->db->where('loan_id', $loan_number);
            $this->db->where('payment_number', $pay_number);
            $this->db->update($this->table,$data);

            $transaction = array(
                'ref' => "GF.".date('Y').date('m').date('d').'.'.rand(100,999),
                'loan_id' => $loan_number,
                'amount' => $final_paid,
                'payment_number' => $pay_number,
                'transaction_type' => 3,
                'payment_proof' => 'null',
                'added_by' => $this->session->userdata('user_id')

            );
            $this->insert_transaction_compat($transaction);
            return true;

        }elseif(intval($to_pay) === intval($newbalance)){


            $new_to_pay = $newbalance;
            $final_paid = $new_to_pay + $get_real_amount->paid_amount ;

            $data = array(
                'partial_paid'=>'NO',
                'status'=>'PAID',
                'paid_amount'=>$final_paid
            );
            $this->db->where('loan_id', $loan_number);
            $this->db->where('payment_number', $pay_number);
            $this->db->update($this->table,$data);
            $this->db->where('loan_id',$loan_number)->update('loan',array('next_payment_id'=>$pay_number+1));
                $this->_should_close_loan($loan_number, $pay_number);
            $transaction = array(
                'ref' => "GF.".date('Y').date('m').date('d').'.'.rand(100,999),
                'loan_id' => $loan_number,
                'amount' => $final_paid,
                'payment_number' => $pay_number,
                'transaction_type' => 3,
                'payment_proof' => 'null',
                'added_by' => $this->session->userdata('user_id')

            );
            $this->insert_transaction_compat($transaction);
            return true;

        }else{

        }
    }
    
    
    //
    public function finish_pay($loan_number,$pay_number,$amount){
        $this->db->select("*")->from($this->table);
        $this->db->where('loan_id', $loan_number);
        $this->db->where('payment_number', $pay_number);


            $data = array(
                'partial_paid'=>'NO',
                'status'=>'PAID',
                'paid_amount'=>$amount
            );
            $this->db->where('loan_id', $loan_number);
            $this->db->where('payment_number', $pay_number);
            $this->db->update($this->table,$data);
            $this->db->where('loan_id',$loan_number)->update('loan',array('next_payment_id'=>$pay_number+1));
            $this->_should_close_loan($loan_number, $pay_number);
            $transaction = array(
                'ref' => "GF.".date('Y').date('m').date('d').'.'.rand(100,999),
                'loan_id' => $loan_number,
                'amount' => $amount,
                'payment_number' => $pay_number,
                'transaction_type' => 3,
                'payment_proof' => 'null',
                'added_by' => $this->session->userdata('user_id')

            );
            $this->insert_transaction_compat($transaction);
            return true;
            
    }
    function count_payments($loan_number){
        $this->db->select("*")->from($this->table);
        $this->db->where('loan_id', $loan_number);
        return $this->db->count_all_results();
    }

    /**
     * Shift schedule dates to start from disbursed_date instead of loan_date
     */
    public function shift_schedules_to_disbursed_date($loan_id, $loan_date, $disbursed_date) {
        $loan_ts = strtotime((string)$loan_date);
        $disb_ts = strtotime((string)$disbursed_date);
        if ($loan_ts === false || $disb_ts === false) {
            return;
        }

        $loan_dt = new DateTime(date('Y-m-d', $loan_ts));
        $disb_dt = new DateTime(date('Y-m-d', $disb_ts));
        $days_diff = (int)$loan_dt->diff($disb_dt)->format('%r%a');
        if ($days_diff === 0) {
            return;
        }

        $schedules = $this->db
            ->where('loan_id', $loan_id)
            ->order_by('payment_number', 'ASC')
            ->get($this->table)
            ->result();

        if (empty($schedules)) {
            return;
        }

        $shifted = array();
        $earliest_shifted = null;
        foreach ($schedules as $s) {
            $schedule_ts = strtotime((string)$s->payment_schedule);
            if ($schedule_ts === false) {
                continue;
            }

            $candidate = new DateTime(date('Y-m-d', $schedule_ts));
            $candidate->modify(($days_diff >= 0 ? '+' : '') . $days_diff . ' days');
            $candidate_str = $candidate->format('Y-m-d');

            if ($earliest_shifted === null || $candidate_str < $earliest_shifted) {
                $earliest_shifted = $candidate_str;
            }

            $shifted[] = array(
                'id' => (int)$s->id,
                'date' => $candidate_str,
            );
        }

        if (empty($shifted)) {
            return;
        }

        $min_allowed = $disb_dt->format('Y-m-d');
        $extra_shift_days = 0;
        if ($earliest_shifted !== null && $earliest_shifted < $min_allowed) {
            $earliest_dt = new DateTime($earliest_shifted);
            $extra_shift_days = (int)$earliest_dt->diff($disb_dt)->format('%a');
        }

        $used_dates = array();
        foreach ($shifted as $row) {
            $candidate = new DateTime($row['date']);

            if ($extra_shift_days > 0) {
                $candidate->modify('+' . $extra_shift_days . ' days');
            }

            if ($candidate->format('Y-m-d') < $min_allowed) {
                $candidate = new DateTime($min_allowed);
            }

            if ((int)$candidate->format('N') === 7) {
                $candidate->modify('+1 day');
            }

            $candidate_str = $candidate->format('Y-m-d');
            while (isset($used_dates[$candidate_str])) {
                $candidate->modify('+1 day');
                if ((int)$candidate->format('N') === 7) {
                    $candidate->modify('+1 day');
                }
                $candidate_str = $candidate->format('Y-m-d');
            }

            $used_dates[$candidate_str] = true;

            $this->db->where('id', $row['id'])->update($this->table, array('payment_schedule' => $candidate_str));
        }
    }

    /**
     * Recalculate loan_balance for all schedules based on principal actually paid to date.
     */
    public function recalculate_loan_balances($loan_id) {
        $loan = $this->db->where('loan_id', $loan_id)->get('loan')->row();
        if (!$loan) {
            return;
        }

        $schedules = $this->db->where('loan_id', $loan_id)->order_by('payment_number', 'ASC')->get($this->table)->result();
        $running_balance = (float) $loan->loan_principal;

        foreach ($schedules as $s) {
            $principal = (float) ($s->principal ?? 0);
            $amount = (float) ($s->amount ?? 0);
            $paid = max(0.0, (float) ($s->paid_amount ?? 0));
            $status = strtoupper(trim((string) ($s->status ?? '')));

            if ($status === 'PAID') {
                $principal_paid = $principal;
            } elseif ($paid > 0 && $amount > 0) {
                $principal_paid = min($principal, $principal * ($paid / $amount));
            } else {
                $principal_paid = 0.0;
            }

            $running_balance -= $principal_paid;
            $running_balance = max(0.0, $running_balance);
            $this->db->where('id', $s->id)->update($this->table, array('loan_balance' => round($running_balance, 2)));
        }

        $schedule_total = $this->db->select('SUM(amount) AS total_amount')
            ->from($this->table)
            ->where('loan_id', $loan_id)
            ->get()
            ->row();

        if ($schedule_total && isset($schedule_total->total_amount)) {
            $stored_total = (float) $loan->loan_amount_total;
            $computed_total = round((float) $schedule_total->total_amount, 2);
            if ($computed_total > 0 && abs($stored_total - $computed_total) < 1) {
                $this->db->where('loan_id', $loan_id)->update('loan', array('loan_amount_total' => $computed_total));
            }
        }
    }

    /**
     * True when every real installment (amount > 0) is fully paid.
     */
    public function is_loan_fully_paid($loan_number)
    {
        $loan_number = (int) $loan_number;
        if ($loan_number <= 0) {
            return false;
        }

        $rows = $this->db->select('amount, paid_amount, status')
            ->from($this->table)
            ->where('loan_id', $loan_number)
            ->where('amount >', 0)
            ->order_by('payment_number', 'ASC')
            ->get()
            ->result();

        if (empty($rows)) {
            return false;
        }

        $total_due = 0.0;
        $total_paid = 0.0;
        foreach ($rows as $row) {
            $amount = (float) $row->amount;
            $paid = max(0.0, (float) $row->paid_amount);
            $total_due += $amount;
            $total_paid += $paid;

            if ($paid + 0.0001 < $amount) {
                return false;
            }

        }

        return $total_due > 0.0001 && $total_paid >= $total_due - 0.01;
    }

    /**
     * Re-open loans that were marked CLOSED without being fully repaid (not intentional pay-off).
     */
    public function correct_premature_loan_closure($loan_number)
    {
        $loan_number = (int) $loan_number;
        if ($loan_number <= 0) {
            return false;
        }

        $loan = $this->db->select('loan_status, paid_off')
            ->from('loan')
            ->where('loan_id', $loan_number)
            ->get()
            ->row();

        if (!$loan || strtoupper(trim((string) $loan->loan_status)) !== 'CLOSED') {
            return false;
        }

        if (strtoupper(trim((string) $loan->paid_off)) === 'YES') {
            return false;
        }

        if ($this->is_loan_fully_paid($loan_number)) {
            return false;
        }

        $this->db->where('loan_id', $loan_number)->update('loan', array(
            'loan_status' => 'ACTIVE',
        ));

        return true;
    }

    /**
     * Close loan only when fully repaid.
     */
    private function close_loan_if_fully_paid($loan_number, $pay_number = null)
    {
        $loan_number = (int) $loan_number;
        $pay_number = $pay_number !== null ? (int) $pay_number : null;

        if ($loan_number <= 0 || !$this->is_loan_fully_paid($loan_number)) {
            return false;
        }

        if ($pay_number !== null && $pay_number > 0) {
            $last_row = $this->db->select('payment_number')
                ->from($this->table)
                ->where('loan_id', $loan_number)
                ->where('amount >', 0)
                ->order_by('payment_number', 'DESC')
                ->limit(1)
                ->get()
                ->row();

            if ($last_row && (int) $last_row->payment_number !== $pay_number) {
                return false;
            }
        }

        $loan = $this->db->select('loan_status')
            ->from('loan')
            ->where('loan_id', $loan_number)
            ->get()
            ->row();

        if (!$loan || strtoupper(trim((string) $loan->loan_status)) !== 'ACTIVE') {
            return false;
        }

        $this->db->where('loan_id', $loan_number)->update('loan', array(
            'loan_status' => 'CLOSED',
        ));

        return true;
    }

    /**
     * Check if loan should be closed after a payment on the given installment.
     */
    private function _should_close_loan($loan_number, $pay_number)
    {
        $loan_number = (int) $loan_number;
        $pay_number = (int) $pay_number;
        if ($loan_number <= 0 || $pay_number <= 0) {
            return false;
        }

        $loan = $this->db->select('loan_status')
            ->from('loan')
            ->where('loan_id', $loan_number)
            ->get()
            ->row();
        if (!$loan || strtoupper(trim((string) $loan->loan_status)) !== 'ACTIVE') {
            return false;
        }

        return $this->close_loan_if_fully_paid($loan_number, $pay_number);
    }
    function pay($loan_number,$pay_number,$amount)
    {
//get payment id
        $this->db->select("*")->from($this->table);
            $this->db->where('loan_id', $loan_number);
        $this->db->where('payment_number', $pay_number);
        $get_real_amount = $this->db->get()->row();
            if($get_real_amount->partial_paid == 'Yes'){
                $new_paid_bal = $get_real_amount->amount - $get_real_amount->paid_amount;
                $data = array(
                    'partial_paid'=>'Yes',
                    'paid_amount'=>$amount
                );
                $this->db->where('loan_id', $loan_number);
                $this->db->where('payment_number', $pay_number);
                $this->db->update($this->table,$data);
//            $this->db->where('loan_id',$loan_number)->update('loan',array('next_payment_id'=>$pay_number+1));
                $transaction = array(
                    'ref' => "GF.".date('Y').date('m').date('d').'.'.rand(100,999),
                    'loan_id' => $loan_number,
                    'amount' => $amount,
                    'payment_number' => $pay_number,
                    'transaction_type' => 3,
                    'payment_proof' => 'null',
                    'added_by' => $this->session->userdata('user_id')

                );
                $this->insert_transaction_compat($transaction);

            }else{

            }
        if($get_real_amount->amount == $amount){
            $data = array(
                'status'=>'PAID',
                'paid_amount'=>$amount
            );
            $this->db->where('loan_id', $loan_number);
            $this->db->where('payment_number', $pay_number);
            $this->db->update($this->table,$data);
            $this->db->where('loan_id',$loan_number)->update('loan',array('next_payment_id'=>$pay_number+1));
            $transaction = array(
                'ref' => "GF.".date('Y').date('m').date('d').'.'.rand(100,999),
                'loan_id' => $loan_number,
                'amount' => $amount,
                'payment_number' => $pay_number,
                'transaction_type' => 3,
                'payment_proof' => 'null',
                'added_by' => $this->session->userdata('user_id')

            );
            $this->insert_transaction_compat($transaction);
        }
        else{
            $data = array(
                'partial_paid'=>'Yes',
                'paid_amount'=>$amount
            );
            $this->db->where('loan_id', $loan_number);
            $this->db->where('payment_number', $pay_number);
            $this->db->update($this->table,$data);
//            $this->db->where('loan_id',$loan_number)->update('loan',array('next_payment_id'=>$pay_number+1));
            $transaction = array(
                'ref' => "GF.".date('Y').date('m').date('d').'.'.rand(100,999),
                'loan_id' => $loan_number,
                'amount' => $amount,
                'payment_number' => $pay_number,
                'transaction_type' => 3,
                'payment_proof' => 'null',
                'added_by' => $this->session->userdata('user_id')

            );
            $this->insert_transaction_compat($transaction);
        }



		return true;
    }
    public function sum_interests($from,$to){
	$this->db->select('SUM(interest) as interest');
	$this->db->from('payement_schedules')->where('status','PAID');

	// $this->db->join('lend_payments','lend_payments.borrower_loan_id=lend_borrower_loans.id');

	if($from !="" && $to !=""){
		$this->db->where('payment_schedule BETWEEN "'. date('Y-m-d', strtotime($from)). '" and "'. date('Y-m-d', strtotime($to)).'"');

	}

	return $this->db->get()->row();
}
public function sum_admin($from,$to){
	$this->db->select('SUM(padmin_fee) as admin_fee');
	$this->db->from('payement_schedules')->where('status','PAID');

	// $this->db->join('lend_payments','lend_payments.borrower_loan_id=lend_borrower_loans.id');

	if($from !="" && $to !=""){
		$this->db->where('payment_schedule BETWEEN "'. date('Y-m-d', strtotime($from)). '" and "'. date('Y-m-d', strtotime($to)).'"');

	}

	return $this->db->get()->row();
}
public function sum_cover($from,$to){
	$this->db->select('SUM(ploan_cover) as loan_cover');
	$this->db->from('payement_schedules')->where('status','PAID');

	// $this->db->join('lend_payments','lend_payments.borrower_loan_id=lend_borrower_loans.id');

	if($from !="" && $to !=""){
		$this->db->where('payment_schedule BETWEEN "'. date('Y-m-d', strtotime($from)). '" and "'. date('Y-m-d', strtotime($to)).'"');

	}

	return $this->db->get()->row();
}
public function bad_debits($from,$to){
	$this->db->select('SUM(principal) as principal')->join('loan','loan.loan_id=payement_schedules.loan_id');
	$this->db->from('payement_schedules')->where('loan_status','DEFAULTED');

	// $this->db->join('lend_payments','lend_payments.borrower_loan_id=lend_borrower_loans.id');

	if($from !="" && $to !=""){
		$this->db->where('payment_schedule BETWEEN "'. date('Y-m-d', strtotime($from)). '" and "'. date('Y-m-d', strtotime($to)).'"');

	}

	return $this->db->get()->row();
}
	function get_last_payment($loan_number)
	{
		//get last payment info
		$this->db->from($this->table);
		$this->db->where('loan_id', $loan_number);
		$this->db->order_by('payment_schedule', 'DESC');
		$this->db->limit(1);
		$result = $this->db->get();

		if ($result->num_rows() > 0) {

			return $result->row();
		}

		return FALSE;
	}
	function get_first_payment($loan_number)
	{
		//get last payment info
		$this->db->from($this->table);
		$this->db->where('loan_id', $loan_number);
		$this->db->order_by('payment_schedule', 'ASC');
		$this->db->limit(1);
		$result = $this->db->get();

		if ($result->num_rows() > 0) {
			return $result->row();
		}

		return FALSE;
	}
    function pay_advance($loan_number,$amount,$arr)
    {
		for($i=0;$i <count($arr);$i++){

			$data = array(
				'status'=>'PAID',
				'paid_amount'=>$amount
			);
			$this->db->where('loan_id', $loan_number);
			$this->db->where('payment_number', $arr[$i]);
			$this->db->update($this->table,$data);
			$this->db->where('loan_id',$loan_number)->update('loan',array('next_payment_id'=>$arr[$i]+1));

			$transaction = array(
				'ref' => "GF.".date('Y').date('m').date('d').'.'.rand(100,999),
				'loan_id' => $loan_number,
				'amount' => $amount,
				
				'payment_number' => $arr[$i],
				'transaction_type' => 3,
				'added_by' => $this->session->userdata('user_id')

			);
			$this->insert_transaction_compat($transaction);


		}


		return true;
    }
    
    // get total rows
    function total_rows($q = NULL) {
        $this->db->like('', $q);
	$this->db->or_like('id', $q);
	$this->db->or_like('customer', $q);
	$this->db->or_like('loan_id', $q);
	$this->db->or_like('payment_schedule', $q);
	$this->db->or_like('payment_number', $q);
	$this->db->or_like('amount', $q);
	$this->db->or_like('principal', $q);
	$this->db->or_like('interest', $q);
	$this->db->or_like('paid_amount', $q);
	$this->db->or_like('loan_balance', $q);
	$this->db->or_like('status', $q);
	$this->db->or_like('loan_date', $q);
	$this->db->or_like('paid_date', $q);
	$this->db->or_like('marked_due', $q);
	$this->db->or_like('marked_due_date', $q);
	$this->db->from($this->table);
        return $this->db->count_all_results();
    }

    // get data with limit and search
    function get_limit_data($limit, $start = 0, $q = NULL) {
        $this->db->order_by($this->id, $this->order);
        $this->db->like('', $q);
	$this->db->or_like('id', $q);
	$this->db->or_like('customer', $q);
	$this->db->or_like('loan_id', $q);
	$this->db->or_like('payment_schedule', $q);
	$this->db->or_like('payment_number', $q);
	$this->db->or_like('amount', $q);
	$this->db->or_like('principal', $q);
	$this->db->or_like('interest', $q);
	$this->db->or_like('paid_amount', $q);
	$this->db->or_like('loan_balance', $q);
	$this->db->or_like('status', $q);
	$this->db->or_like('loan_date', $q);
	$this->db->or_like('paid_date', $q);
	$this->db->or_like('marked_due', $q);
	$this->db->or_like('marked_due_date', $q);
	$this->db->limit($limit, $start);
        return $this->db->get($this->table)->result();
    }

    // insert data
    function insert($data)
    {
        $this->db->insert($this->table, $data);
    }

    // update data
    function update1($id, $data)
    {
        $this->db->where('loan_id', $id);
        $this->db->update($this->table, $data);
    }
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
    /**
     * Shared arrears rules used by dashboard summary and arrears reports.
     */
    private function apply_institutional_arrears_filters($by_date = null, $from = null, $to = null)
    {
        date_default_timezone_set('Africa/Blantyre');

        $this->db->where('payement_schedules.payment_schedule <', date('Y-m-d'));
        $this->db->where_in('payement_schedules.status', array('NOT PAID', 'PARTIAL PAID'));
        $this->db->where_in('loan.loan_status', array('APPROVED', 'ACTIVE'));
        $this->db->where('loan.disbursed', 'Yes');
        $this->db->where('loan.loan_status <>', 'DELETED');

        if ($from !== '' && $from !== null && $to !== '' && $to !== null) {
            $this->db->where('payement_schedules.payment_schedule >=', date('Y-m-d', strtotime($from)));
            $this->db->where('payement_schedules.payment_schedule <=', date('Y-m-d', strtotime($to)));
        } elseif ($by_date === 'one_day') {
            $this->db->where('payement_schedules.payment_schedule', date('Y-m-d', strtotime('-1 day')));
        } elseif ($by_date === 'three_days') {
            $this->db->where('payement_schedules.payment_schedule >=', date('Y-m-d', strtotime('-3 day')));
            $this->db->where('payement_schedules.payment_schedule <=', date('Y-m-d', strtotime('-1 day')));
        } elseif ($by_date === 'week') {
            $this->db->where('payement_schedules.payment_schedule >=', date('Y-m-d', strtotime('-7 day')));
            $this->db->where('payement_schedules.payment_schedule <=', date('Y-m-d', strtotime('-1 day')));
        } elseif ($by_date === 'month') {
            $this->db->where('payement_schedules.payment_schedule >=', date('Y-m-d', strtotime('-30 day')));
            $this->db->where('payement_schedules.payment_schedule <=', date('Y-m-d', strtotime('-1 day')));
        } elseif ($by_date === '2month') {
            $this->db->where('payement_schedules.payment_schedule >=', date('Y-m-d', strtotime('-60 day')));
            $this->db->where('payement_schedules.payment_schedule <=', date('Y-m-d', strtotime('-1 day')));
        } elseif ($by_date === '3month') {
            $this->db->where('payement_schedules.payment_schedule >=', date('Y-m-d', strtotime('-90 day')));
            $this->db->where('payement_schedules.payment_schedule <=', date('Y-m-d', strtotime('-1 day')));
        }
    }

    /**
     * Sum overdue installment balances using the same rules as the dashboard.
     */
    public function sum_institutional_arrears($loan = 'All', $from = null, $to = null, $by_date = null, $idofficer = 'All', $supervisor_id = null)
    {
        $this->db->select('SUM(COALESCE(payement_schedules.amount, 0) - COALESCE(payement_schedules.paid_amount, 0)) AS total_arrears', false)
            ->from($this->table)
            ->join('loan', 'loan.loan_id = payement_schedules.loan_id');

        $this->apply_institutional_arrears_filters($by_date, $from, $to);

        if ($loan !== 'All' && $loan !== '' && $loan !== null) {
            $this->db->where('payement_schedules.loan_id', $loan);
        }
        if (!$supervisor_id) {
            $supervisor_id = report_supervisor_input_value('get');
        }
        if ($supervisor_id) {
            report_apply_supervisor_loan_filter($supervisor_id);
        } elseif ($idofficer !== 'All' && $idofficer !== '' && $idofficer !== null) {
            $this->db->where('loan.loan_added_by', $idofficer);
        }

        $row = $this->db->get()->row();
        return $row ? (float) $row->total_arrears : 0.0;
    }

    function arrears($loan, $from, $to, $by_date, $idofficer, $supervisor_id = null)
    {
        $this->db->select(
            'payement_schedules.*, loan.*, loan_products.product_name, employees.Firstname as efname, employees.Lastname as elname, '
            . 'individual_customers.Firstname as ifname, individual_customers.Lastname as ilname, '
            . report_supervisor_select_sql('rel_supervisor') . ', '
            . report_sql_loan_branch_display_expr('loan') . ', '
            . '(COALESCE(payement_schedules.amount, 0) - COALESCE(payement_schedules.paid_amount, 0)) AS amount_due',
            false
        )->from($this->table)
            ->join('loan', 'loan.loan_id = payement_schedules.loan_id')
            ->join('loan_products', 'loan_products.loan_product_id = loan.loan_product', 'LEFT')
            ->join('individual_customers', 'individual_customers.id = payement_schedules.customer', 'LEFT')
            ->join('employees', 'employees.id = loan.loan_added_by', 'LEFT');
        report_join_relationship_supervisor('employees', 'rel_supervisor');

        $this->apply_institutional_arrears_filters($by_date, $from, $to);

        if ($loan !== 'All' && $loan !== '' && $loan !== null) {
            $this->db->where('payement_schedules.loan_id', $loan);
        }
        if (!$supervisor_id) {
            $supervisor_id = report_supervisor_input_value('get');
        }
        if ($supervisor_id) {
            report_apply_supervisor_loan_filter($supervisor_id);
        } elseif ($idofficer !== 'All' && $idofficer !== '' && $idofficer !== null) {
            $this->db->where('loan.loan_added_by', $idofficer);
        }

        return $this->db->get()->result();
    }
function  payment_today(){
	date_default_timezone_set("Africa/Blantyre");
	$curr_date = date('Y-m-d');

	$this->db->select("*,employees.Firstname as efname,individual_customers.Firstname as ifname,individual_customers.Lastname as ilname,employees.Lastname as elname,
		COALESCE(CONCAT(member_groups.group_name, ' (', member_groups.group_code, ')'), 'N/A') as customer_group_name")->from($this->table)
		->join('loan','loan.loan_id = payement_schedules.loan_id')
		->join('loan_products', 'loan_products.loan_product_id = loan.loan_product', 'LEFT')
		->join('individual_customers','individual_customers.id = payement_schedules.customer','LEFT')
		->join('employees','employees.id = loan.loan_added_by','LEFT')
		->join('customer_groups', 'customer_groups.customer = loan.loan_customer AND loan.customer_type = "individual"', 'LEFT')
		->join('groups member_groups', 'member_groups.group_id = customer_groups.group_id', 'LEFT')
			->where('DATE(payement_schedules.payment_schedule)', $curr_date)
			->where_in('payement_schedules.status', array('NOT PAID', 'PARTIAL PAID'))
			->where('loan.loan_status','ACTIVE');

    return	$this->db->get()->result();
}
function  payment_month(){
	date_default_timezone_set("Africa/Blantyre");
	$month_start = date('Y-m-01');
	$month_end = date('Y-m-t');

	$this->db->select("*,employees.Firstname as efname,individual_customers.Firstname as ifname,individual_customers.Lastname as ilname,employees.Lastname as elname,
		".report_sql_loan_branch_display_expr('loan').",
		COALESCE(CONCAT(member_groups.group_name, ' (', member_groups.group_code, ')'), 'N/A') as customer_group_name", false)->from($this->table)
		->join('loan','loan.loan_id = payement_schedules.loan_id','LEFT')
		->join('loan_products', 'loan_products.loan_product_id = loan.loan_product', 'LEFT')
		->join('individual_customers','individual_customers.id = payement_schedules.customer','LEFT')
		->join('employees','employees.id = loan.loan_added_by','LEFT')
		->join('customer_groups', 'customer_groups.customer = loan.loan_customer AND loan.customer_type = "individual"', 'LEFT')
		->join('groups member_groups', 'member_groups.group_id = customer_groups.group_id', 'LEFT')
			->where('DATE(payement_schedules.payment_schedule) >=', $month_start)
			->where('DATE(payement_schedules.payment_schedule) <=', $month_end)
			->where_in('payement_schedules.status', array('NOT PAID', 'PARTIAL PAID'))
			->where('loan.loan_status','ACTIVE');

    return	$this->db->get()->result();
}
function  payment_date($from,$to,$user,$product, $branch, $supervisor_id = null){
	date_default_timezone_set("Africa/Blantyre");
	$this->db->select("*,employees.Firstname as efname,individual_customers.Firstname as ifname,individual_customers.Lastname as ilname,employees.Lastname as elname,
		".report_supervisor_select_sql('rel_supervisor').",
		".report_sql_loan_branch_display_expr('loan').",
		COALESCE(CONCAT(member_groups.group_name, ' (', member_groups.group_code, ')'), 'N/A') as customer_group_name", false)->from($this->table)
		->join('loan','loan.loan_id = payement_schedules.loan_id','LEFT')
		->join('loan_products', 'loan_products.loan_product_id = loan.loan_product', 'LEFT')
		->join('individual_customers','individual_customers.id = payement_schedules.customer','LEFT')
		->join('employees','employees.id = loan.loan_added_by','LEFT')
		->join('employees rel_supervisor','rel_supervisor.id = employees.Supervisor','LEFT')
		->join('customer_groups', 'customer_groups.customer = loan.loan_customer AND loan.customer_type = "individual"', 'LEFT')
		->join('groups member_groups', 'member_groups.group_id = customer_groups.group_id', 'LEFT')
		->where_in('payement_schedules.status', array('NOT PAID', 'PARTIAL PAID'))
			->where('loan.loan_status','ACTIVE');
	if($from !="" && $to !=""){
		$from_date = date('Y-m-d', strtotime($from));
		$to_date = date('Y-m-d', strtotime($to));
		$this->db->where('DATE(payement_schedules.payment_schedule) >=', $from_date);
		$this->db->where('DATE(payement_schedules.payment_schedule) <=', $to_date);
	} elseif ($from != "") {
		$from_date = date('Y-m-d', strtotime($from));
		$this->db->where('DATE(payement_schedules.payment_schedule) >=', $from_date);
	} elseif ($to != "") {
		$to_date = date('Y-m-d', strtotime($to));
		$this->db->where('DATE(payement_schedules.payment_schedule) <=', $to_date);
	}
	if (!$supervisor_id) {
        $supervisor_id = report_supervisor_input_value('get');
    }
    if ($supervisor_id) {
        report_apply_supervisor_loan_filter($supervisor_id);
    } elseif ($user !=""){
        $this->db->where('loan.loan_added_by',$user);
    }
	if($product !=""){
        $this->db->where('loan.loan_product',$product);
    }
    if($branch !=""){
        report_apply_loan_branch_value_filter($branch, 'loan');
    }
    return	$this->db->get()->result();
}
function  payment_week(){
	date_default_timezone_set("Africa/Blantyre");
	$week_start = date('Y-m-d', strtotime('monday this week'));
	$week_end = date('Y-m-d', strtotime('sunday this week'));


	$this->db->select("*,employees.Firstname as efname,individual_customers.Firstname as ifname,individual_customers.Lastname as ilname,employees.Lastname as elname,
		".report_sql_loan_branch_display_expr('loan').",
		COALESCE(CONCAT(member_groups.group_name, ' (', member_groups.group_code, ')'), 'N/A') as customer_group_name", false)->from($this->table)
		->join('loan','loan.loan_id = payement_schedules.loan_id')
		->join('loan_products', 'loan_products.loan_product_id = loan.loan_product', 'LEFT')
		->join('individual_customers','individual_customers.id = payement_schedules.customer','LEFT')
		->join('employees','employees.id = loan.loan_added_by','LEFT')
		->join('customer_groups', 'customer_groups.customer = loan.loan_customer AND loan.customer_type = "individual"', 'LEFT')
		->join('groups member_groups', 'member_groups.group_id = customer_groups.group_id', 'LEFT');
			$this->db->where('DATE(payement_schedules.payment_schedule) >=', $week_start);
			$this->db->where('DATE(payement_schedules.payment_schedule) <=', $week_end);
			$this->db->where_in('payement_schedules.status', array('NOT PAID', 'PARTIAL PAID'))
				->where('loan.loan_status','ACTIVE');

    return	$this->db->get()->result();
}


	function get_filter_projection($from,$to)
	{

		$this->db->select_sum("paid_amount")
			->join('loan','loan.loan_id = payement_schedules.loan_id')
			->where('loan.loan_status','ACTIVE')
			->where('status','PAID');

		$this->db->where('payment_schedule BETWEEN "'. date('Y-m-d', strtotime($from)). '" and "'. date('Y-m-d', strtotime($to)).'"');

		$this->db->order_by('payement_schedules.loan_id', 'DESC');
		$result = $this->db-> get($this->table)->row();
//		$result = $this->db-> get()->row();
		return array(

			'paid_amount'=>$result->paid_amount
		);
	}
    function next_payment($user, $product, $branch) {
        date_default_timezone_set("Africa/Blantyre");

        $this->db->select("*,employees.Firstname as efname,individual_customers.Firstname as ifname,individual_customers.Lastname as ilname,employees.Lastname as elname,"
            . report_sql_loan_branch_display_expr('loan') . ",", false)
            ->from($this->table)
            ->join('loan', 'loan.loan_id = payement_schedules.loan_id')
            ->join('loan_products', 'loan_products.loan_product_id = loan.loan_product', 'LEFT')
            ->join('individual_customers','individual_customers.id = payement_schedules.customer','LEFT')
            ->join('employees','employees.id = loan.loan_added_by','LEFT')
            ->where('loan.loan_status','ACTIVE')
            ->where('payement_schedules.payment_number = loan.next_payment_id');

        if($user !=""){
            $this->db->where('loan.loan_added_by', $user);
        }
        if($product !=""){
            $this->db->where('loan.loan_product', $product);
        }
        if($branch !=""){
            report_apply_loan_branch_value_filter($branch, 'loan');
        }
        return $this->db->get()->result();
    }
	function get_filter_projections($from,$to)
	{

		$this->db->select_sum('amount')
			->join('loan','loan.loan_id = payement_schedules.loan_id')
			->where('loan.loan_status','ACTIVE');


		$this->db->where('payment_schedule BETWEEN "'. date('Y-m-d', strtotime($from)). '" and "'. date('Y-m-d', strtotime($to)).'"');

		$this->db->order_by('payement_schedules.loan_id', 'DESC');
		$result = $this->db-> get($this->table)->row();
		return array(
			'amount'=>$result->amount,

		);
	}

	function get_filter_projection_principal($from,$to)
	{
		$this->db->select_sum('principal')
			->join('loan','loan.loan_id = payement_schedules.loan_id')
			->where('loan.loan_status','ACTIVE');

		$this->db->where('payment_schedule BETWEEN "'. date('Y-m-d', strtotime($from)). '" and "'. date('Y-m-d', strtotime($to)).'"');

		$this->db->order_by('payement_schedules.loan_id', 'DESC');
		$result = $this->db-> get($this->table)->row();
		return array(
			'principal'=>$result->principal,

		);
	}

	function get_filter_projection_interest($from,$to)
	{
		$this->db->select_sum('interest')
			->join('loan','loan.loan_id = payement_schedules.loan_id')
			->where('loan.loan_status','ACTIVE');


		$this->db->where('payment_schedule BETWEEN "'. date('Y-m-d', strtotime($from)). '" and "'. date('Y-m-d', strtotime($to)).'"');

		$this->db->order_by('payement_schedules.loan_id', 'DESC');
		$result = $this->db-> get($this->table)->row();
		return array(
			'interest'=>$result->interest,

		);
	}
}





