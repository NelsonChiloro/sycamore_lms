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
        if ($this->db->table_exists('loan_charge_state') && $this->db->where('loan_id', $loan_number)->count_all_results('loan_charge_state') > 0) {
            // Component balances are authoritative. Restructure replays receipts
            // in installment order; do not redistribute contract cash separately
            // from the penalties paid by those same receipts.
            $next = $this->db->where('loan_id', $loan_number)
                ->group_start()->where('status !=', 'PAID')->or_where('total_late_charge >', 0)->group_end()
                ->order_by('payment_number')->get($this->table)->row();
            $last = end($schedules);
            $this->db->where('loan_id',$loan_number)->update('loan',array('next_payment_id'=>$next ? $next->payment_number : $last->payment_number+1));
            $this->recalculate_loan_balances($loan_number);
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
        $this->recalculate_loan_balances($loan_number);
    }

    public function normalize_after_reversal($loan_number)
    {
        $this->normalize_schedule_payment_allocation($loan_number);
    }

    /**
     * Rebuild a loan's schedule from valid repayment allocation transactions.
     *
     * Repayments linked to an account transaction marked as reversed are
     * deliberately excluded. This also repairs reversals completed before the
     * schedule-balance reversal fix was deployed.
     */
    public function repair_loan_payment_state($loan_id)
    {
        $this->db->trans_begin();
        try {
            $charges=$this->charge_model(); $charges->assert_installed();
            $loan=$this->db->query('SELECT * FROM loan WHERE loan_id = ? FOR UPDATE',array((int)$loan_id))->row();
            if (!$loan) throw new RuntimeException('Loan was not found.');
            $rows=$this->db->where('loan_id',(int)$loan_id)->order_by('payment_number')->get($this->table)->result();
            if (!$rows) throw new RuntimeException('No payment schedules were found.');
            $states=$charges->states($loan_id,$rows);
            $valid=$this->db->query("SELECT t.* FROM transactions t WHERE t.loan_id=? AND t.transaction_type=3 AND t.amount>0
                AND NOT EXISTS(SELECT 1 FROM transaction a WHERE a.transaction_id=t.ref AND a.reversed='Yes')
                ORDER BY t.date_stamp,t.transaction_id",array((int)$loan_id))->result();
            $ledger_count=$this->db->where('loan_id',(int)$loan_id)->where('transaction_type',3)->count_all_results('transactions');
            $existing=0; foreach($rows as $row) $existing+=(float)$row->paid_amount;
            if (!$ledger_count && $existing>.01) throw new RuntimeException('Schedule payments have no repayment history. Reconcile the source records first.');
            $legacy=0; $total=0; $new=array(); $last_date=null;
            foreach($valid as $payment) {
                $total+=(float)$payment->amount; $last_date=$payment->date_stamp;
                if ($payment->description!=='charge_policy_v1') $legacy+=(float)$payment->amount;
                else {
                    $key=$payment->ref.'|'.$payment->date_stamp;
                    if (!isset($new[$key])) $new[$key]=array('amount'=>0,'date'=>$payment->date_stamp);
                    $new[$key]['amount']+=(float)$payment->amount;
                }
            }
            $left=$legacy; $capacity=0;
            foreach($rows as $row) {
                $n=$row->payment_number; $capacity+=(float)$row->amount;
                $copy=clone $row; $copy->paid_amount=min(max(0,$left),(float)$row->amount);
                $left-=$copy->paid_amount;
                $states[$n]=array_merge($states[$n],Loan_charge_policy::seed($copy));
                $paid_penalty=$this->db->query("SELECT COALESCE(SUM(c.amount),0) total FROM loan_payment_components c
                    WHERE c.loan_id=? AND c.payment_number=? AND c.component='penalty'
                    AND NOT EXISTS(SELECT 1 FROM transaction a WHERE a.transaction_id=c.ref AND a.reversed='Yes')",array((int)$loan_id,$n))->row();
                $states[$n]['penalty_paid']=(float)$paid_penalty->total;
            }
            foreach($new as $payment) {
                $allocation=Loan_charge_policy::allocate($rows,$states,$payment['amount'],$payment['date'],false);
                $states=$allocation['states'];
            }
            $applied=0;
            foreach($states as $state) foreach(Loan_charge_policy::$components as $component=>$field) $applied+=(float)$state['paid_'.$component];
            if (abs($applied-min($capacity,$total))>.02) throw new RuntimeException('Repayment totals did not reconcile.');
            $charges->save_states($loan_id,$rows,$states,$last_date);
            $this->normalize_schedule_payment_allocation($loan_id);
            if (in_array($loan->loan_status,array('ACTIVE','CLOSED'),true)) {
                $this->db->where('loan_id',(int)$loan_id)->update('loan',array('loan_status'=>$this->is_loan_fully_paid($loan_id)?'CLOSED':'ACTIVE'));
            }
            if (!$this->db->trans_status()) throw new RuntimeException('Schedule reconciliation failed.');
            $this->db->trans_commit();
            return array('loan_id'=>(int)$loan_id,'total_txn_paid'=>round($total,2),'total_schedule_amount'=>round($capacity,2),'applied'=>round($applied,2),'unapplied'=>round(max(0,$total-$applied),2));
        } catch(Throwable $e) { $this->db->trans_rollback(); return array('error'=>$e->getMessage()); }
    }
    /**
     * Pay loan with late charges allocation priority
     * Payment sequence: Late charges -> Loan cover -> Admin fees -> Interest -> Principal
     */
    private function charge_model()
    {
        $this->load->model('Late_charges_model');
        return $this->Late_charges_model;
    }

    public function pay_loan_with_late_charges($loan_number, $pay_number, $amount, $date, $tid)
    {
        return $this->allocate_framework_payment($loan_number, $amount, $date, $tid);
    }

    private function process_standard_payment($loan_number, $pay_number, $amount, $date, $tid)
    {
        $result = $this->allocate_framework_payment($loan_number, $amount, $date, $tid);
        return !empty($result['success']);
    }

    private function allocate_framework_payment($loan_id, $amount, $date, $tid)
    {
        if ((int)$loan_id <= 0 || !is_numeric($amount) || (float)$amount <= 0 || !is_finite((float)$amount)) return array('success'=>false);
        $this->db->trans_begin();
        try {
            $charges=$this->charge_model();
            $charges->assert_installed();
            $loan=$this->db->query('SELECT * FROM loan WHERE loan_id = ? FOR UPDATE',array((int)$loan_id))->row();
            if (!$loan || $loan->loan_status!=='ACTIVE') throw new RuntimeException('Only active loans accept repayments.');
            if ($this->db->where('loan_id',(int)$loan_id)->where('ref',$tid)->count_all_results('loan_payment_components')) throw new RuntimeException('This repayment reference has already been allocated.');
            $today=(new DateTimeImmutable('now',new DateTimeZone('Africa/Blantyre')))->format('Y-m-d');
            if (substr($date,0,10)>$today) throw new RuntimeException('Future-dated repayments are not supported.');
            $current=$charges->refresh_loan($loan_id,$date);
            $allocated=Loan_charge_policy::allocate($current['rows'],$current['states'],round((float)$amount,2),$date);
            if ($allocated['remaining']>0.005) throw new RuntimeException('Payment exceeds the total outstanding balance.');
            $contracts=array(); $log=array();
            foreach($allocated['allocations'] as $allocation) {
                $component=$allocation['component']; $n=$allocation['payment_number'];
                $this->db->insert('loan_payment_components',array_merge($allocation,array('loan_id'=>(int)$loan_id,'ref'=>$tid,'paid_at'=>$date)));
                $log[$component]=($log[$component]??0)+$allocation['amount'];
                if ($component!=='penalty') $contracts[$n]=($contracts[$n]??0)+$allocation['amount'];
            }
            // Type 3 is contract repayment only. Penalties have their own ledger
            // and must never inflate schedule paid_amount or principal.
            foreach($contracts as $number=>$paid) $this->insert_transaction_compat(array(
                'ref'=>$tid,'loan_id'=>(int)$loan_id,'amount'=>round($paid,2),'payment_number'=>$number,
                'transaction_type'=>3,'description'=>'charge_policy_v1','payment_proof'=>'null',
                'added_by'=>$this->session->userdata('user_id'),'date_stamp'=>$date));
            foreach($allocated['allocations'] as $allocation) {
                if ($allocation['component'] !== 'penalty') continue;
                $this->insert_transaction_compat(array('ref'=>$tid,'loan_id'=>(int)$loan_id,
                    'amount'=>$allocation['amount'],'payment_number'=>$allocation['payment_number'],
                    'transaction_type'=>2,'description'=>'charge_policy_v1','payment_proof'=>'null',
                    'added_by'=>$this->session->userdata('user_id'),'date_stamp'=>$date));
            }
            $charges->save_states($loan_id,$current['rows'],$allocated['states'],$date);
            $this->normalize_schedule_payment_allocation($loan_id);
            $this->close_loan_if_fully_paid($loan_id);
            if (!$this->db->trans_status()) throw new RuntimeException('Repayment allocation failed.');
            $this->db->trans_commit();
            $messages=array();
            foreach($log as $component=>$paid) $messages[]=ucfirst($component).': MWK '.number_format($paid,2);
            return array('success'=>true,'amount_allocated'=>round((float)$amount,2),'remaining_amount'=>0,'allocation_log'=>$messages);
        } catch (Throwable $e) {
            $this->db->trans_rollback();
            log_message('error','Loan payment allocation: '.$e->getMessage());
            return array('success'=>false,'error'=>$e->getMessage());
        }
    }
    // get all
    function get_all()
    {
        $this->db->order_by($this->id, $this->order);
        return $this->db->get($this->table)->result();
    }
	function get_all_by_id($id)
	{
        $this->charge_model()->refresh_loan($id);
        $this->normalize_schedule_payment_allocation($id);
		$this->db->select('*');
		$this->db->order_by($this->id, $this->order);
		$this->db->join('loan','loan.loan_id = payement_schedules.loan_id');
		$this->db->where('payement_schedules.loan_id',$id);
		return $this->db->get($this->table)->result();
	}

    public function new_pay_new($loan_number,$pay_number,$amount,$date,$tid) {
        return $this->process_standard_payment($loan_number,$pay_number,$amount,$date,$tid);
    }

    public function pay_off($loan_number,$pay_number,$amount,$date,$tid) {
        return $this->process_standard_payment($loan_number,$pay_number,$amount,$date,$tid);
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
    /** Display contract balances without changing stored principal balances. */
    public static function with_contract_balances($payments)
    {
        $total=0;$paid=0;$result=array();
        $payments=array_values((array)$payments);
        usort($payments,function($a,$b){return (int)($a->payment_number??0)<=>(int)($b->payment_number??0);});
        foreach((array)$payments as $row) $total+=(int)round((float)$row->amount*100);
        foreach((array)$payments as $row) {
            $copy=clone $row;
            $paid+=(int)round((float)$row->paid_amount*100);
            $copy->contract_balance=max(0,$total-$paid)/100;
            $result[]=$copy;
        }
        return $result;
    }

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

    /**
     * Calculate an early loan payoff from the actual repayment schedules.
     *
     * The first half (including the middle installment) is payable in full when
     * it is still outstanding. After that point only the current installment is
     * payable in full; later installments contribute principal only.
     *
     * @param array $schedules Ordered or unordered repayment schedule rows
     * @return object|null
     */
    public function calculate_payoff(array $schedules)
    {
        if (empty($schedules)) {
            return null;
        }

        usort($schedules, function ($a, $b) {
            return (int) $a->payment_number <=> (int) $b->payment_number;
        });

        $current_index = null;
        foreach ($schedules as $index => $schedule) {
            $remaining = max(0.0,
                (float) ($schedule->amount ?? 0)
                + (float) ($schedule->total_late_charge ?? 0)
                - (float) ($schedule->paid_amount ?? 0)
            );
            if ($remaining > 0.005) {
                $current_index = $index;
                break;
            }
        }

        if ($current_index === null) {
            return null;
        }

        $schedule_count = count($schedules);
        $halfway_number = (int) ceil($schedule_count / 2);
        $current_number = (int) $schedules[$current_index]->payment_number;
        $full_through_number = $current_number <= $halfway_number
            ? $halfway_number
            : $current_number;

        $full_amount = 0.0;
        $principal_amount = 0.0;
        foreach ($schedules as $index => $schedule) {
            if ($index < $current_index) {
                continue;
            }

            $payment_number = (int) $schedule->payment_number;
            $paid_amount = max(0.0, (float) ($schedule->paid_amount ?? 0));
            $scheduled_amount = max(0.0, (float) ($schedule->amount ?? 0));

            if ($payment_number <= $full_through_number) {
                $full_amount += max(0.0,
                    $scheduled_amount
                    + max(0.0, (float) ($schedule->total_late_charge ?? 0))
                    - $paid_amount
                );
                continue;
            }

            // Payments are allocated proportionally on partially-paid schedules
            // elsewhere in this model, so use the same rule for principal owing.
            $principal = max(0.0, (float) ($schedule->principal ?? 0));
            $component_state = isset($schedule->loan_id) ? $this->db->where('loan_id',(int)$schedule->loan_id)
                ->where('payment_number',$payment_number)->get('loan_charge_state')->row() : null;
            $principal_paid = $component_state ? (float)$component_state->paid_principal : ($scheduled_amount > 0
                ? min($principal, $principal * min(1, $paid_amount / $scheduled_amount)) : 0.0);
            $principal_amount += max(0.0, $principal - $principal_paid);
            $full_amount += max(0, (float)($schedule->total_late_charge ?? 0));
        }

        $full_amount = round($full_amount, 2);
        $principal_amount = round($principal_amount, 2);

        return (object) array(
            'schedule_count' => $schedule_count,
            'halfway_payment' => $halfway_number,
            'current_payment' => $current_number,
            'full_through_payment' => $full_through_number,
            'full_amount' => $full_amount,
            'principal_amount' => $principal_amount,
            'payoff_amount' => round($full_amount + $principal_amount, 2),
        );
    }

    /** Apply a previously calculated payoff to its schedules. */
    public function apply_payoff($loan_id, $payoff, $date, $transaction_ref)
    {
        $this->db->trans_begin();
        try {
            $charges=$this->charge_model();
            $current=$charges->refresh_loan($loan_id,$date);
            $rows=$this->db->where('loan_id',(int)$loan_id)->order_by('payment_number')->get($this->table)->result();
            $fresh=$this->calculate_payoff($rows);
            if (!$fresh || abs($fresh->payoff_amount-$payoff->payoff_amount)>.01) throw new RuntimeException('Payoff quote changed; refresh the quote.');
            foreach($rows as $row) {
                if ($row->payment_number <= $fresh->full_through_payment) continue;
                $state=$current['states'][$row->payment_number];
                // Waive only unpaid future fees/interest; retain paid components
                // and the original principal so the principal ledger stays valid.
                $update=array('ploan_cover'=>$state['paid_cover'],'padmin_fee'=>$state['paid_admin'],
                    'interest'=>$state['paid_interest']);
                $update['amount']=round($row->principal+array_sum($update),2);
                $this->db->where('id',$row->id)->update($this->table,$update);
            }
            $result=$this->allocate_framework_payment($loan_id,$fresh->payoff_amount,$date,$transaction_ref);
            if (empty($result['success'])) throw new RuntimeException($result['error']??'Payoff allocation failed.');
            $total=$this->db->select_sum('amount')->where('loan_id',(int)$loan_id)->get($this->table)->row();
            $this->db->where('loan_id',(int)$loan_id)->update('loan',array('paid_off'=>'YES','loan_status'=>'CLOSED','loan_amount_total'=>$total->amount));
            if (!$this->db->trans_status()) throw new RuntimeException('Payoff update failed.');
            $this->db->trans_commit(); return true;
        } catch(Throwable $e) { $this->db->trans_rollback(); log_message('error',$e->getMessage()); return false; }
    }	function edits()
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
        $this->charge_model()->refresh_loan($id);
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
        $this->charge_model()->refresh_loan($id);
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

    public function out_pay($loan_number,$pay_number,$amount,$date) {
        $tid='PAY-'.uniqid('',true);
        return $this->process_standard_payment($loan_number,$pay_number,$amount,$date,$tid) ? $tid : false;
    }

    public function new_pay($loan_number,$pay_number,$amount,$date) {
        return $this->process_standard_payment($loan_number,$pay_number,$amount,$date,'PAY-'.uniqid('',true));
    }
    
    
    //

    public function topnew_pay($loan_number,$pay_number,$amount,$realaccountbalance) {
        // Repayments no longer reprice schedules; use the approved restructure flow for term changes.
        return $this->process_standard_payment($loan_number,$pay_number,$amount,date('Y-m-d H:i:s'),'PAY-'.uniqid('',true));
    }
    
    
    //

    public function finish_pay($loan_number,$pay_number,$amount) {
        return $this->process_standard_payment($loan_number, 1, $amount, date('Y-m-d H:i:s'), 'PAY-'.uniqid('',true));
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
        $component_states = array();
        if ($this->db->table_exists('loan_charge_state')) {
            foreach ($this->db->where('loan_id',(int)$loan_id)->get('loan_charge_state')->result() as $state) $component_states[$state->payment_number]=$state;
        }

        foreach ($schedules as $s) {
            $principal = (float) ($s->principal ?? 0);
            $amount = (float) ($s->amount ?? 0);
            $paid = max(0.0, (float) ($s->paid_amount ?? 0));

            if (isset($component_states[$s->payment_number])) {
                $principal_paid = min($principal, max(0,(float)$component_states[$s->payment_number]->paid_principal));
            } elseif ($paid > 0 && $amount > 0) {
                $principal_paid = min($principal, $principal * ($paid / $amount));
            } else {
                $principal_paid = 0.0;
            }

            $running_balance -= $principal_paid;
            $running_balance = max(0.0, $running_balance);
            $balance = round($running_balance, 2);
            if (abs((float)$s->loan_balance - $balance) > 0.0001) {
                $this->db->where('id', $s->id)->update($this->table, array('loan_balance' => $balance));
            }
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

        $rows = $this->db->select('amount, paid_amount, status, total_late_charge')
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

            if ($paid + 0.0001 < $amount || (float)$row->total_late_charge > 0.005) {
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

    public function pay($loan_number,$pay_number,$amount) {
        return $this->process_standard_payment($loan_number, 1, $amount, date('Y-m-d H:i:s'), 'PAY-'.uniqid('',true));
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

    public function pay_advance($loan_number,$amount,$arr) {
        return $this->process_standard_payment($loan_number, 1, (float)$amount * count((array)$arr), date('Y-m-d H:i:s'), 'PAY-'.uniqid('',true));
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
    private function collection_customer_groups_sql()
    {
        // Memberships are one-to-many: aggregate before joining so each
        // scheduled installment contributes only once to the report and total.
        return "(SELECT customer_groups.customer,
                    GROUP_CONCAT(DISTINCT CONCAT(member_group.group_name, ' (', member_group.group_code, ')')
                        ORDER BY member_group.group_name, member_group.group_code SEPARATOR ', ') AS customer_group_name
                 FROM customer_groups
                 INNER JOIN `groups` member_group ON member_group.group_id = customer_groups.group_id
                 GROUP BY customer_groups.customer) member_groups";
    }

function  payment_today(){
	date_default_timezone_set("Africa/Blantyre");
	$curr_date = date('Y-m-d');

	$this->db->select("*,employees.Firstname as efname,individual_customers.Firstname as ifname,individual_customers.Lastname as ilname,employees.Lastname as elname,
		COALESCE(member_groups.customer_group_name, 'N/A') as customer_group_name")->from($this->table)
		->join('loan','loan.loan_id = payement_schedules.loan_id')
		->join('loan_products', 'loan_products.loan_product_id = loan.loan_product', 'LEFT')
		->join('individual_customers','individual_customers.id = payement_schedules.customer','LEFT')
		->join('employees','employees.id = loan.loan_added_by','LEFT')
		->join($this->collection_customer_groups_sql(), 'member_groups.customer = loan.loan_customer AND loan.customer_type = "individual"', 'LEFT', false)
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
		COALESCE(member_groups.customer_group_name, 'N/A') as customer_group_name", false)->from($this->table)
		->join('loan','loan.loan_id = payement_schedules.loan_id','LEFT')
		->join('loan_products', 'loan_products.loan_product_id = loan.loan_product', 'LEFT')
		->join('individual_customers','individual_customers.id = payement_schedules.customer','LEFT')
		->join('employees','employees.id = loan.loan_added_by','LEFT')
		->join($this->collection_customer_groups_sql(), 'member_groups.customer = loan.loan_customer AND loan.customer_type = "individual"', 'LEFT', false)
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
		COALESCE(member_groups.customer_group_name, 'N/A') as customer_group_name", false)->from($this->table)
		->join('loan','loan.loan_id = payement_schedules.loan_id','LEFT')
		->join('loan_products', 'loan_products.loan_product_id = loan.loan_product', 'LEFT')
		->join('individual_customers','individual_customers.id = payement_schedules.customer','LEFT')
		->join('employees','employees.id = loan.loan_added_by','LEFT')
		->join('employees rel_supervisor','rel_supervisor.id = employees.Supervisor','LEFT')
		->join($this->collection_customer_groups_sql(), 'member_groups.customer = loan.loan_customer AND loan.customer_type = "individual"', 'LEFT', false)
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
		COALESCE(member_groups.customer_group_name, 'N/A') as customer_group_name", false)->from($this->table)
		->join('loan','loan.loan_id = payement_schedules.loan_id')
		->join('loan_products', 'loan_products.loan_product_id = loan.loan_product', 'LEFT')
		->join('individual_customers','individual_customers.id = payement_schedules.customer','LEFT')
		->join('employees','employees.id = loan.loan_added_by','LEFT')
		->join($this->collection_customer_groups_sql(), 'member_groups.customer = loan.loan_customer AND loan.customer_type = "individual"', 'LEFT', false);
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



