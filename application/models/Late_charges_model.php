<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Late_charges_model extends CI_Model {

    const GRACE_PERIOD_DAYS = 2;
    const MAX_CHARGING_MONTHS = 12;
    const DAYS_PER_MONTH = 30;

    public function __construct() {
        parent::__construct();
    }

    /**
     * Calculate and update late charges for all eligible payment schedules
     */
    public function calculate_and_update_late_charges() {
        try {
            $processed_loans = 0;
            $updated_schedules = 0;
            $total_late_charges_added = 0;

            // Get all active loans with their payment schedules that are overdue
            $overdue_schedules = $this->get_overdue_payment_schedules();

            foreach ($overdue_schedules as $schedule) {
                $loan_id = $schedule->loan_id;
                $schedule_id = $schedule->id;

                // Get loan product penalty percentage
                $penalty_percentage = $this->get_loan_penalty_percentage($loan_id);

                if ($penalty_percentage === null) {
                    continue; // Skip if no penalty configured
                }

                // Calculate the new late charges
                $late_charge_info = $this->calculate_late_charge_for_schedule($schedule, $penalty_percentage);

                if ($late_charge_info['should_update']) {
                    // Update the payment schedule with new late charges
                    $update_data = [
                        'total_late_charge' => $late_charge_info['total_late_charge']
                    ];

                    $this->db->where('id', $schedule_id);
                    $this->db->update('payement_schedules', $update_data);

                    $updated_schedules++;
                    $total_late_charges_added += $late_charge_info['charges_added'];
                }

                $processed_loans++;
            }

            return [
                'success' => true,
                'processed_loans' => $processed_loans,
                'updated_schedules' => $updated_schedules,
                'total_late_charges_added' => $total_late_charges_added
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get overdue payment schedules for active loans
     */
    private function get_overdue_payment_schedules() {
        $grace_date = date('Y-m-d', strtotime('-' . self::GRACE_PERIOD_DAYS . ' days'));

        $this->db->select('ps.*, l.loan_product, l.loan_status');
        $this->db->from('payement_schedules ps');
        $this->db->join('loan l', 'l.loan_id = ps.loan_id');
        $this->db->where('l.loan_status', 'ACTIVE');
        $this->db->where('ps.payment_schedule <=', $grace_date);
        $this->db->where('ps.status', 'NOT PAID');
        $this->db->order_by('ps.loan_id, ps.payment_number');

        return $this->db->get()->result();
    }

    /**
     * Get penalty percentage for a loan from loan_products table
     */
    private function get_loan_penalty_percentage($loan_id) {
        $this->db->select('lp.penalty');
        $this->db->from('loan l');
        $this->db->join('loan_products lp', 'lp.loan_product_id = l.loan_product');
        $this->db->where('l.loan_id', $loan_id);

        $result = $this->db->get()->row();

        return $result ? (float)$result->penalty : null;
    }

    /**
     * Calculate late charge for a specific payment schedule
     */
    private function calculate_late_charge_for_schedule($schedule, $penalty_percentage) {
        $current_date = date('Y-m-d');
        $due_date = $schedule->payment_schedule;
        $grace_end_date = date('Y-m-d', strtotime($due_date . ' +' . self::GRACE_PERIOD_DAYS . ' days'));

        // Calculate days overdue (after grace period)
        // Charging starts the day after grace period ends
        $charging_start_date = date('Y-m-d', strtotime($grace_end_date . ' +1 day'));
        $days_overdue = max(0, floor((strtotime($current_date) - strtotime($charging_start_date)) / (60 * 60 * 24)));

        // Check maximum charging period (12 months = 360 days)
        $max_charging_days = self::MAX_CHARGING_MONTHS * self::DAYS_PER_MONTH;
        $days_to_charge = min($days_overdue, $max_charging_days);

        if ($days_to_charge <= 0) {
            return [
                'total_late_charge' => $schedule->total_late_charge ?? 0,
                'charges_added' => 0,
                'should_update' => false
            ];
        }

        // Calculate daily penalty rate
        $monthly_penalty_rate = $penalty_percentage / 100;
        $daily_penalty_rate = $monthly_penalty_rate / self::DAYS_PER_MONTH;

        // Calculate total late charges based on days elapsed
        $schedule_amount = (float)$schedule->amount;
        $calculated_total_late_charge = $schedule_amount * $daily_penalty_rate * $days_to_charge;

        // Get current stored late charges
        $current_late_charge = (float)($schedule->total_late_charge ?? 0);

        // Intelligent handling: Check if we need to update
        // This handles missed cron runs by calculating the correct total based on days elapsed
        $should_update = false;
        $charges_added = 0;

        if (abs($calculated_total_late_charge - $current_late_charge) > 0.01) {
            // There's a difference, update needed
            $should_update = true;
            $charges_added = $calculated_total_late_charge - $current_late_charge;
        }

        return [
            'total_late_charge' => round($calculated_total_late_charge, 2),
            'charges_added' => round($charges_added, 2),
            'should_update' => $should_update,
            'days_charged' => $days_to_charge,
            'daily_rate' => $daily_penalty_rate
        ];
    }

    /**
     * Get late charges for a specific loan
     */
    public function get_loan_late_charges($loan_id) {
        $this->db->select('
            ps.*,
            ps.total_late_charge,
            CASE
                WHEN ps.status = "NOT PAID" AND ps.payment_schedule <= DATE_SUB(CURDATE(), INTERVAL ' . self::GRACE_PERIOD_DAYS . ' DAY)
                THEN DATEDIFF(CURDATE(), DATE_ADD(ps.payment_schedule, INTERVAL ' . self::GRACE_PERIOD_DAYS . ' DAY))
                ELSE 0
            END as days_overdue
        ');
        $this->db->from('payement_schedules ps');
        $this->db->where('ps.loan_id', $loan_id);
        $this->db->order_by('ps.payment_number');

        return $this->db->get()->result();
    }

    /**
     * Get total late charges for a loan
     */
    public function get_total_loan_late_charges($loan_id) {
        $this->db->select('SUM(total_late_charge) as total_late_charges');
        $this->db->from('payement_schedules');
        $this->db->where('loan_id', $loan_id);

        $result = $this->db->get()->row();
        return $result ? (float)$result->total_late_charges : 0;
    }

    /**
     * Clear late charges for a specific payment schedule (when payment is made)
     */
    public function clear_schedule_late_charges($schedule_id) {
        $update_data = ['total_late_charge' => 0];
        $this->db->where('id', $schedule_id);
        return $this->db->update('payement_schedules', $update_data);
    }
}