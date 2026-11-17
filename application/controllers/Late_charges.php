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

    /** Super-admin preview with an explicit POST to apply the reconstruction. */
    public function reconstruct_history($loan_number = '') {
        if ($this->session->userdata('RoleName') !== 'SUPER ADMIN') {
            show_error('You are not authorised to reconstruct historical penalties.',403);
            return;
        }
        $loan=$this->db->where('loan_number',(string)$loan_number)->get('loan')->row();
        if (!$loan && ctype_digit((string)$loan_number)) $loan=$this->db->where('loan_id',(int)$loan_number)->get('loan')->row();
        if (!$loan) {show_error('Loan was not found.',404);return;}
        $apply=$this->input->method(true)==='POST' && $this->input->post('apply')==='yes';
        $escape=function($value){return htmlspecialchars((string)$value,ENT_QUOTES,'UTF-8');};
        $this->output->set_content_type('text/html');
        try {
            $report=$this->Late_charges_model->reconstruct_history($loan->loan_id,$apply);
            echo '<!doctype html><html><head><meta charset="utf-8"><title>Historical penalties</title></head><body>';
            echo '<h1>Historical penalties: '.$escape($loan->loan_number).'</h1>';
            echo '<p>Calculated through '.$escape($report['cutoff_on']).'. Reversed payment references are excluded.</p>';
            echo '<p>This replaces the pre-deployment opening penalty balance. Contract payments and principal remain unchanged.</p>';
            if (!empty($report['resolved_payment_dates'])) {
                echo '<h2>Payment dates recovered from the account ledger</h2><ul>';
                foreach($report['resolved_payment_dates'] as $resolved) echo '<li>'.$escape($resolved['reference']).' (transaction '.$escape($resolved['transaction_id']).'): '.$escape($resolved['effective_date']).'</li>';
                echo '</ul><p>Original repayment records were not edited. These recorded account dates are included in the reconstruction audit.</p>';
            }
            echo '<table border="1" cellpadding="8"><tr><th>Installment</th><th>Due date</th><th>Historical charges</th><th>Historical penalties paid</th><th>Opening penalty</th></tr>';
            foreach($report['installments'] as $row) echo '<tr><td>'.$row['payment_number'].'</td><td>'.$escape($row['due_date']).'</td><td>'.number_format($row['historical_accrued'],2).'</td><td>'.number_format($row['historical_paid'],2).'</td><td>'.number_format($row['opening_penalty'],2).'</td></tr>';
            echo '</table><p>Historical opening penalties: <strong>MWK '.number_format($report['opening_penalty'],2).'</strong>.</p>';
            if ($apply) echo '<p>Reconstruction saved. The previous values were retained in the audit table.</p>';
            else {
                echo '<form method="post" action="'.$escape(current_url()).'">';
                if ($this->config->item('csrf_protection')) echo '<input type="hidden" name="'.$escape($this->security->get_csrf_token_name()).'" value="'.$escape($this->security->get_csrf_hash()).'">';
                echo '<input type="hidden" name="apply" value="yes"><button type="submit">Apply historical reconstruction</button></form>';
            }
            echo '<p><a href="'.$escape(site_url('loan/view/'.$loan->loan_id)).'">Return to loan schedule</a></p></body></html>';
        } catch(Throwable $e) {
            echo '<h1>Historical reconstruction needs review</h1><p>'.$escape($e->getMessage()).'</p><p>No historical penalty changes were saved.</p>';
        }
    }

    public function bulk_history() {
        if ($this->session->userdata('RoleName') !== 'SUPER ADMIN') {
            show_error('Only SUPER ADMIN can repair historical penalties.',403);return;
        }
        $this->load->view('late_charges/bulk_history');
    }

    /** Process a short batch so large portfolios do not time out in the browser. */
    public function bulk_history_batch() {
        if ($this->session->userdata('RoleName') !== 'SUPER ADMIN') {
            show_error('Only SUPER ADMIN can repair historical penalties.',403);return;
        }
        if ($this->input->method(true) !== 'POST') {show_error('Use the bulk historical repair page.',405);return;}
        $mode=$this->input->post('mode');$source=$this->input->post('date_source');
        if (!in_array($mode,array('preview','apply'),true) || !in_array($source,array('account-only','reference-date'),true)) {
            show_error('Invalid repair options.',400);return;
        }
        $buffer_level=ob_get_level();ob_start();
        try {
            $this->run_history_repair($mode,$source,max(0,(int)$this->input->post('after_id')),5,true);
            $lines=explode("\n",trim(ob_get_clean()));$items=array();$result=array();
            foreach($lines as $line) {
                $entry=json_decode($line,true);
                if (isset($entry['error']) && !isset($entry['loan_id'])) throw new RuntimeException($entry['error']);
                if (isset($entry['summary'])) $result=$entry;else $items[]=$entry;
            }
            if (!isset($result['summary'])) throw new RuntimeException('The batch did not return a summary.');
            $result['items']=$items;$result['success']=true;
        } catch(Throwable $e) {
            while(ob_get_level()>$buffer_level) ob_end_clean();
            $result=array('success'=>false,'error'=>$e->getMessage());
        }
        if ($this->config->item('csrf_protection')) $result['csrf']=array('name'=>$this->security->get_csrf_token_name(),'value'=>$this->security->get_csrf_hash());
        $this->output->set_content_type('application/json')->set_output(json_encode($result));
    }

    /** Batch reconstruction is CLI-only; default is a read-only preview. */
    public function repair_all_history($mode = 'preview', $date_source = 'account-only', $after_id = 0, $batch_size = 100) {
        if (!is_cli()) {show_error('Run bulk historical repair from the server command line.',403);return;}
        $this->run_history_repair($mode,$date_source,$after_id,$batch_size);
    }

    private function run_history_repair($mode, $date_source, $after_id, $batch_size, $single_batch = false) {
        if (!in_array($mode,array('preview','apply'),true) || !in_array($date_source,array('account-only','reference-date'),true)) {
            echo "Usage: php index.php Late_charges repair_all_history preview|apply account-only|reference-date [after_id] [batch_size]\n";return;
        }
        try {
            $this->Late_charges_model->assert_installed();
            foreach(array('loan_history_date_repairs','loan_penalty_reconstructions') as $table) {
                if (!$this->db->table_exists($table)) throw new RuntimeException('Install the historical reconstruction and bulk date-repair SQL migrations first.');
            }
        } catch(Throwable $e) {echo json_encode(array('error'=>$e->getMessage())).PHP_EOL;return;}
        $batch_size=max(1,min(500,(int)$batch_size));$cursor=max(0,(int)$after_id);
        $summary=array('mode'=>$mode,'date_source'=>$date_source,'processed'=>0,'date_repairs'=>0,'reference_dates'=>0,'reconstructed'=>0,'needs_review'=>0,'last_loan_id'=>$cursor);
        do {
            $loans=$this->db->select('loan_id, loan_number')->where('loan_status','ACTIVE')->where('loan_id >',$cursor)->order_by('loan_id')->limit($batch_size)->get('loan')->result();
            foreach($loans as $loan) {
                $cursor=(int)$loan->loan_id;$summary['last_loan_id']=$cursor;$summary['processed']++;
                $item=array('loan_id'=>$cursor,'loan_number'=>$loan->loan_number,'mode'=>$mode);
                $this->db->trans_begin();
                try {
                    // Preview simulates date updates and reconstruction inside a
                    // transaction, then rolls back all updates and audit entries.
                    $dates=$this->Late_charges_model->repair_history_dates($cursor,true,$date_source==='reference-date');
                    $item['date_repairs']=$dates['repairs'];$item['unresolved_dates']=$dates['unresolved'];
                    $summary['date_repairs']+=count($dates['repairs']);
                    foreach($dates['repairs'] as $repair) if ($repair['source']==='reference_posting_date_inferred') $summary['reference_dates']++;
                    try {
                        $reconstruction=$this->Late_charges_model->reconstruct_history($cursor,$mode==='apply');
                        $item['opening_penalty']=$reconstruction['opening_penalty'];$item['status']=$mode==='apply'?'reconstructed':'ready';
                        $summary['reconstructed']++;
                    } catch(Throwable $e) {
                        // Keep valid date repairs when another source-data issue
                        // prevents penalties. Never alter contract payments here.
                        $item['status']='needs_review';$item['error']=$e->getMessage();
                    }
                    if (!$this->db->trans_status()) throw new RuntimeException('Database update failed.');
                    if ($mode==='apply') $this->db->trans_commit();else $this->db->trans_rollback();
                } catch(Throwable $e) {
                    $this->db->trans_rollback();$item['status']='failed';$item['error']=$e->getMessage();
                }
                if (in_array($item['status'],array('needs_review','failed'),true)) $summary['needs_review']++;
                echo json_encode($item).PHP_EOL;
            }
        } while(!$single_batch && count($loans)===$batch_size);
        echo json_encode(array('summary'=>$summary,'has_more'=>count($loans)===$batch_size)).PHP_EOL;
    }

    /** Batch reconstruction is CLI-only; default is a read-only preview. */
    public function reconstruct_all_history($mode = 'preview', $after_id = 0, $limit = 100) {
        if (!is_cli()) {show_error('Run historical batches from the server command line.',403);return;}
        if (!in_array($mode,array('preview','apply'),true)) {echo "Mode must be preview or apply.\n";return;}
        $limit=max(1,min(500,(int)$limit));
        $loans=$this->db->select('loan_id, loan_number')->where('loan_status','ACTIVE')->where('loan_id >',(int)$after_id)
            ->order_by('loan_id')->limit($limit)->get('loan')->result();
        $summary=array('mode'=>$mode,'processed'=>0,'applied'=>0,'needs_review'=>0,'next_after_id'=>(int)$after_id,'loans'=>array());
        foreach($loans as $loan) {
            $summary['processed']++;$summary['next_after_id']=(int)$loan->loan_id;
            try {
                $report=$this->Late_charges_model->reconstruct_history($loan->loan_id,$mode==='apply');
                if ($report['applied']) $summary['applied']++;
                unset($report['installments']);$summary['loans'][]=$report;
            } catch(Throwable $e) {
                $summary['needs_review']++;
                $summary['loans'][]=array('loan_number'=>$loan->loan_number,'error'=>$e->getMessage());
            }
        }
        echo json_encode($summary,JSON_PRETTY_PRINT).PHP_EOL;
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
        $secret = $this->config->item('cron_secret_token');
        if (!is_string($secret) || $secret === '' || !is_string($token) || !hash_equals($secret, $token)) {
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
