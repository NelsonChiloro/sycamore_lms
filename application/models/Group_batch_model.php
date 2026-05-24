<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Group batch loan operations — financial summaries, repayments, and batch edits.
 */
class Group_batch_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Payement_schedules_model');
        $this->load->model('Loan_model');
        $this->load->model('Groups_model');
        $this->load->model('Individual_customers_model');
    }

    /**
     * @param array $loans Loan rows for one batch (with member_name when set)
     * @return object
     */
    public function build_financial_summary($loans)
    {
        $summary = (object) array(
            'total_principal' => 0.0,
            'total_loan_amount' => 0.0,
            'total_paid' => 0.0,
            'outstanding_balance' => 0.0,
            'total_interest' => 0.0,
            'total_penalties' => 0.0,
            'next_installment_due' => null,
            'next_installment_amount' => 0.0,
            'loan_status_label' => 'Mixed',
            'active_count' => 0,
            'closed_count' => 0,
            'member_count' => 0,
        );

        if (empty($loans)) {
            return $summary;
        }

        $statuses = array();
        $earliest_due_ts = null;

        foreach ($loans as $loan) {
            if (strtoupper(trim((string) $loan->loan_status)) === 'DELETED') {
                continue;
            }

            $summary->member_count++;
            $summary->total_principal += (float) $loan->loan_principal;
            $summary->total_interest += (float) ($loan->loan_interest_amount ?? 0);

            $payments = $this->Payement_schedules_model->get_all_by_id($loan->loan_id);
            $balance = $this->Payement_schedules_model->summarize_loan_balances(
                $payments,
                isset($loan->loan_amount_total) ? $loan->loan_amount_total : null
            );

            $summary->total_loan_amount += (float) $balance->total_loan_amount;
            $summary->total_paid += (float) $balance->total_paid;
            $summary->outstanding_balance += (float) $balance->remaining_balance;
            $summary->total_penalties += (float) ($balance->total_late_charges ?? 0);

            $status = strtoupper(trim((string) $loan->loan_status));
            $statuses[$status] = true;
            if ($status === 'ACTIVE') {
                $summary->active_count++;
            }
            if ($status === 'CLOSED') {
                $summary->closed_count++;
            }

            $this->db->select('payment_schedule, amount, paid_amount, status, total_late_charge');
            $this->db->from('payement_schedules');
            $this->db->where('loan_id', (int) $loan->loan_id);
            $this->db->where_in('status', array('NOT PAID', 'PARTIAL PAID'));
            $this->db->order_by('payment_schedule', 'ASC');
            $this->db->limit(1);
            $next = $this->db->get()->row();

            if ($next && !empty($next->payment_schedule)) {
                $due_ts = strtotime($next->payment_schedule);
                $due_amount = max(0, (float) $next->amount - (float) $next->paid_amount)
                    + (float) ($next->total_late_charge ?? 0);
                if ($earliest_due_ts === null || $due_ts < $earliest_due_ts) {
                    $earliest_due_ts = $due_ts;
                    $summary->next_installment_due = $next->payment_schedule;
                    $summary->next_installment_amount = round($due_amount, 2);
                }
            }
        }

        $summary->total_principal = round($summary->total_principal, 2);
        $summary->total_loan_amount = round($summary->total_loan_amount, 2);
        $summary->total_paid = round($summary->total_paid, 2);
        $summary->outstanding_balance = round($summary->outstanding_balance, 2);
        $summary->total_interest = round($summary->total_interest, 2);
        $summary->total_penalties = round($summary->total_penalties, 2);

        if (count($statuses) === 1) {
            $summary->loan_status_label = key($statuses);
        } elseif ($summary->active_count > 0) {
            $summary->loan_status_label = $summary->active_count . ' ACTIVE / ' . $summary->member_count . ' members';
        }

        return $summary;
    }

    /**
     * Repayment allocation rows for ACTIVE loans in the batch.
     *
     * @param array $loans
     * @return array
     */
    public function build_repayment_member_rows($loans)
    {
        $rows = array();

        foreach ($loans as $loan) {
            if (strtoupper(trim((string) $loan->loan_status)) !== 'ACTIVE') {
                continue;
            }

            $member_name = !empty($loan->member_name)
                ? (string) $loan->member_name
                : trim((string) $loan->Firstname . ' ' . (string) $loan->Lastname);

            $this->db->select('SUM(amount - paid_amount) as outstanding');
            $this->db->from('payement_schedules');
            $this->db->where('loan_id', (int) $loan->loan_id);
            $this->db->where_in('status', array('NOT PAID', 'PARTIAL PAID'));
            $outstanding_row = $this->db->get()->row();
            // duplicate query block kept for clarity — outstanding uses same filter as installment
            $outstanding = round((float) ($outstanding_row && $outstanding_row->outstanding ? $outstanding_row->outstanding : 0), 2);

            $this->db->select('payment_schedule, amount, paid_amount, total_late_charge, payment_number');
            $this->db->from('payement_schedules');
            $this->db->where('loan_id', (int) $loan->loan_id);
            $this->db->where_in('status', array('NOT PAID', 'PARTIAL PAID'));
            $this->db->order_by('payment_number', 'ASC');
            $this->db->limit(1);
            $next = $this->db->get()->row();

            $installment_due = 0.0;
            $installment_date = '';
            if ($next) {
                $installment_due = round(
                    max(0, (float) $next->amount - (float) $next->paid_amount) + (float) ($next->total_late_charge ?? 0),
                    2
                );
                $installment_date = $next->payment_schedule ?? '';
            }

            $rows[] = (object) array(
                'loan_id' => (int) $loan->loan_id,
                'loan_number' => $loan->loan_number,
                'member_name' => $member_name !== '' ? $member_name : ('Member #' . $loan->loan_customer),
                'outstanding' => $outstanding,
                'installment_due' => $installment_due,
                'installment_date' => $installment_date,
            );
        }

        return $rows;
    }

    /**
     * Shared edit context from first loan in batch + per-member principals.
     *
     * @param array $loans
     * @param string $batch
     * @return object|null
     */
    public function build_edit_context($loans, $batch)
    {
        if (empty($loans)) {
            return null;
        }

        $editable = array();
        foreach ($loans as $loan) {
            if (strtoupper(trim((string) $loan->loan_status)) === 'DELETED') {
                continue;
            }
            $editable[] = $loan;
        }

        if (empty($editable)) {
            return null;
        }

        $first = $editable[0];
        $members = array();

        foreach ($editable as $loan) {
            $name = !empty($loan->member_name)
                ? (string) $loan->member_name
                : trim((string) $loan->Firstname . ' ' . (string) $loan->Lastname);
            $members[] = (object) array(
                'loan_id' => (int) $loan->loan_id,
                'loan_number' => $loan->loan_number,
                'member_name' => $name !== '' ? $name : ('Member #' . $loan->loan_customer),
                'loan_principal' => (float) $loan->loan_principal,
                'loan_customer' => (int) $loan->loan_customer,
                'customer_type' => $loan->customer_type,
            );
        }

        return (object) array(
            'batch' => $batch,
            'loan_product_id' => (int) $first->loan_product,
            'loan_period' => (int) $first->loan_period,
            'period_type' => $first->period_type,
            'loan_date' => $first->loan_date,
            'loan_interest' => $first->loan_interest,
            'loan_added_by' => (int) $first->loan_added_by,
            'narration' => $first->narration,
            'worthness_file' => $first->worthness_file,
            'members' => $members,
        );
    }

    /**
     * Build old/new snapshot pair for one member (mirrors Loan::edit_action).
     */
    public function build_member_edit_snapshots($loan_row, $product_row, $posted)
    {
        $customer_name = '';
        $preview_url = 'Individual_customers/view/';

        if ($loan_row->customer_type === 'group') {
            $group = $this->Groups_model->get_by_id($loan_row->loan_customer);
            if ($group) {
                $customer_name = $group->group_name . '(' . $group->group_code . ')';
                $preview_url = 'Customer_groups/members/';
            }
        } else {
            $indi = $this->Individual_customers_model->get_by_id($loan_row->loan_customer);
            if ($indi) {
                $customer_name = $indi->Firstname . ' ' . $indi->Lastname;
            }
        }

        $added_by_old = get_by_id('employees', 'id', $loan_row->loan_added_by);
        $added_by_new = get_by_id('employees', 'id', $posted['user']);

        $old = array(
            'loan_id' => $loan_row->loan_id,
            'loan_number' => $loan_row->loan_number,
            'loan_product' => $loan_row->product_name ?? '',
            'loan_customer' => $customer_name,
            'customer_type' => $loan_row->customer_type,
            'preview_url' => $preview_url,
            'customer_id' => $loan_row->loan_customer,
            'loan_date' => $loan_row->loan_date,
            'loan_principal' => $loan_row->loan_principal,
            'loan_period' => $loan_row->loan_period,
            'period_type' => $loan_row->period_type,
            'loan_worthness_file' => $loan_row->worthness_file,
            'narration' => $loan_row->narration,
            'loan_added_by' => $added_by_old ? ($added_by_old->Firstname . ' ' . $added_by_old->Lastname) : '',
        );

        $new = array(
            'loan_id' => $loan_row->loan_id,
            'loan_number' => $posted['loan_number'],
            'sy_loan_product' => $posted['loan_type'],
            'loan_product' => $product_row ? $product_row->product_name : '',
            'period_type' => $posted['period_type'] ?: $loan_row->period_type,
            'sy_loan_customer' => $loan_row->loan_customer,
            'loan_customer' => $customer_name,
            'customer_type' => $loan_row->customer_type,
            'preview_url' => $preview_url,
            'customer_id' => $loan_row->loan_customer,
            'loan_date' => $posted['loan_date'],
            'loan_principal' => $posted['principal'],
            'loan_period' => $posted['months'],
            'loan_worthness_file' => $posted['worthness_file'],
            'narration' => $posted['narration'],
            'sy_added_by' => $posted['user'],
            'added_by' => $added_by_new ? ($added_by_new->Firstname . ' ' . $added_by_new->Lastname) : '',
        );

        return array('old' => $old, 'new' => $new);
    }

    /**
     * Group batch edits are stored as type "Loan edit" with batch/members in JSON.
     */
    public function is_group_batch_approval($approval_row)
    {
        if (!$approval_row || empty($approval_row->new_info)) {
            return false;
        }

        $payload = json_decode($approval_row->new_info);
        return is_object($payload)
            && !empty($payload->batch)
            && isset($payload->members)
            && is_array($payload->members)
            && count($payload->members) > 0;
    }

    /**
     * Batch number from a group batch approval row, or null.
     *
     * @param object $approval_row
     * @return string|null
     */
    public function get_batch_from_approval($approval_row)
    {
        if (!$this->is_group_batch_approval($approval_row)) {
            return null;
        }

        $payload = json_decode($approval_row->new_info);
        if (!$payload || empty($payload->batch)) {
            return null;
        }

        $batch = trim((string) $payload->batch);
        return $batch !== '' ? $batch : null;
    }

    /**
     * Pending group batch edit for this batch (Initiated or recommended).
     *
     * @param string $batch
     * @return object|null
     */
    public function get_pending_batch_edit($batch)
    {
        $batch = trim((string) $batch);
        if ($batch === '') {
            return null;
        }

        $this->db->where('type', 'Loan edit');
        $this->db->where_in('state', array('Initiated', 'recommended'));
        $this->db->order_by('approval_edits_id', 'DESC');
        $rows = $this->db->get('approval_edits')->result();

        foreach ($rows as $row) {
            if (!$this->is_group_batch_approval($row)) {
                continue;
            }
            $payload = json_decode($row->new_info);
            if ($payload && isset($payload->batch) && (string) $payload->batch === $batch) {
                return $row;
            }
        }

        return null;
    }

    /**
     * Apply approved group batch edit using add_loan_edit per member.
     *
     * @param object $approval_row approval_edits row
     * @return array ['success'=>bool, 'message'=>string, 'batch'=>string|null]
     */
    public function apply_approved_batch_edit($approval_row)
    {
        $payload = json_decode($approval_row->new_info);
        if (!$payload || empty($payload->members) || !is_array($payload->members)) {
            return array('success' => false, 'message' => 'Invalid group batch edit payload.');
        }

        $shared = isset($payload->shared) ? $payload->shared : null;
        $batch = isset($payload->batch) ? $payload->batch : '';

        $this->db->trans_begin();

        try {
            foreach ($payload->members as $member) {
                $loan_id = (int) ($member->loan_id ?? 0);
                if ($loan_id <= 0) {
                    throw new Exception('Invalid loan id in batch edit payload.');
                }

                $period_type = isset($member->period_type) ? $member->period_type : null;
                if ($shared && isset($shared->period_type) && !$period_type) {
                    $period_type = $shared->period_type;
                }

                $this->Loan_model->add_loan_edit(
                    $loan_id,
                    $member->loan_principal,
                    $member->loan_period,
                    $member->sy_loan_product,
                    $member->loan_date,
                    $member->sy_loan_customer,
                    $member->customer_type,
                    isset($member->loan_worthness_file) ? $member->loan_worthness_file : null,
                    isset($member->narration) ? $member->narration : null,
                    $member->sy_added_by,
                    $period_type
                );
            }

            if ($this->db->trans_status() === false) {
                throw new Exception('Database error while applying batch edit.');
            }

            $this->db->trans_commit();
            return array('success' => true, 'message' => 'Group batch edit applied successfully.', 'batch' => $batch);
        } catch (Exception $e) {
            $this->db->trans_rollback();
            return array('success' => false, 'message' => $e->getMessage(), 'batch' => $batch);
        }
    }
}
