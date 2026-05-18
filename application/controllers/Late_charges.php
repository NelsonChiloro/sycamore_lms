<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Late_charges extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('Late_charges_model');
        $this->load->model('Loan_model');
        $this->load->model('Activity_logger_model');
        $this->load->helper('date');
    }

    /**
     * Calculate and update late charges for all eligible payment schedules
     * This endpoint should be called by cron job or manually
     */
    public function calculate_late_charges() {
        try {
            $result = $this->Late_charges_model->calculate_and_update_late_charges();

            if ($result['success']) {
                $response = [
                    'status' => 'success',
                    'message' => 'Late charges calculated successfully',
                    'processed_loans' => $result['processed_loans'],
                    'updated_schedules' => $result['updated_schedules'],
                    'total_late_charges_added' => $result['total_late_charges_added']
                ];
            } else {
                $response = [
                    'status' => 'error',
                    'message' => $result['message']
                ];
            }

            // Log the activity
        
                 $logger = array(

                        'user_id' => $this->session->userdata('user_id')?? 'System',
                       'activity' => 'Late charges calculation: ' . $response['message'] .
                             (isset($result['processed_loans']) ? ' | Processed: ' . $result['processed_loans'] . ' loans' : '')
                        //'activity_cate' => 'Late charges calculations'

                    );
                    log_activity($logger);
            echo json_encode($response);

        } catch (Exception $e) {
            $response = [
                'status' => 'error',
                'message' => 'Error calculating late charges: ' . $e->getMessage()
            ];
            echo json_encode($response);
        }
    }

    /**
     * Get late charges for a specific loan
     */
    public function get_loan_late_charges($loan_id) {
        if (!$loan_id) {
            echo json_encode(['status' => 'error', 'message' => 'Loan ID required']);
            return;
        }

        $late_charges = $this->Late_charges_model->get_loan_late_charges($loan_id);
        echo json_encode([
            'status' => 'success',
            'late_charges' => $late_charges
        ]);
    }

    /**
     * Manual trigger for calculating late charges (for testing)
     */
    public function manual_calculate() {
        // Only allow if logged in as admin
        // if (!$this->session->userdata('logged_in') || $this->session->userdata('role') !== 'Admin') {
        //     redirect('Auth');
        //     return;
        // }

        $result = $this->calculate_late_charges();
        $this->session->set_flashdata('message', 'Late charges calculation completed');
        redirect('Admin');
    }

    /**
     * Cron-friendly endpoint for calculating late charges
     * This can be called without authentication using a secret token
     *
     * Usage: http://localhost/newsycamore/Late_charges/cron_calculate?token=YOUR_SECRET_TOKEN
     */
    public function cron_calculate() {
        // Get the token from query parameter
        $token = $this->input->get('token');

        // Verify the token matches the configured cron secret
        if ($token !== $this->config->item('cron_secret_token')) {
            header('HTTP/1.1 403 Forbidden');
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid or missing authentication token'
            ]);
            return;
        }

        // Token is valid, proceed with calculation
        $this->calculate_late_charges();
    }
}