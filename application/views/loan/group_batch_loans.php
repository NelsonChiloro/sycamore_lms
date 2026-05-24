<div class="main-content">
    <div class="page-header">
        <h2 class="header-title">Group Batch Loans - <?php echo $batch; ?></h2>
        <div class="header-sub-title">
            <nav class="breadcrumb breadcrumb-dash">
                <a href="<?php echo base_url('Admin')?>" class="breadcrumb-item"><i class="anticon anticon-home m-r-5"></i>Home</a>
                <a href="<?php echo base_url('loan/group_file')?>" class="breadcrumb-item">Group File</a>
                <span class="breadcrumb-item active">Batch: <?php echo $batch; ?></span>
            </nav>
        </div>
    </div>

    <?php if (!empty($pending_batch_edit)): ?>
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <strong>Group loan edit pending (<?php echo htmlspecialchars($pending_batch_edit->state); ?>).</strong>
        Changes are not applied until recommendation and approval are complete.
        <?php if ($pending_batch_edit->state === 'Initiated' && !empty($permissions['can_recommend'])): ?>
            <a class="alert-link" href="<?php echo base_url('Approval_general/auth_data/') . (int)$pending_batch_edit->approval_edits_id . '/edit_recommend/edit_approve'; ?>">Recommend this edit</a>
            or open <a class="alert-link" href="<?php echo base_url('loan/edit_recommend'); ?>">Recommend loan edit</a>.
        <?php elseif ($pending_batch_edit->state === 'recommended' && !empty($permissions['can_approve'])): ?>
            <a class="alert-link" href="<?php echo base_url('Approval_general/auth_data/') . (int)$pending_batch_edit->approval_edits_id . '/edit_recommend/edit_approve'; ?>">Approve this edit</a>
            or open <a class="alert-link" href="<?php echo base_url('loan/edit_approve'); ?>">Approve loan edit</a>.
        <?php else: ?>
            Awaiting an officer with the correct permission on <a class="alert-link" href="<?php echo base_url('loan/edit_recommend'); ?>">Recommend loan edit</a> / <a class="alert-link" href="<?php echo base_url('loan/edit_approve'); ?>">Approve loan edit</a>.
        <?php endif; ?>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
    </div>
    <?php endif; ?>
    
    <!-- Batch Summary -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h4 class="mb-0"><i class="fas fa-info-circle mr-2"></i>Batch Summary</h4>
                </div>
                <div class="col-md-4 text-right">
                    <?php 
                    // Check loan statuses in this batch
                    $has_recommendable = false; // loans that can be recommended (not already recommended/approved/active)
                    $has_recommended = false;   // loans with RECOMMENDED status
                    $has_approved = false;      // loans with APPROVED status
                    $has_payable = false;       // loans with ACTIVE status for batch payment
                    
                    foreach($loans as $check_loan) {
                        if(!in_array($check_loan->loan_status, ['RECOMMENDED', 'APPROVED', 'ACTIVE', 'CLOSED', 'DELETED'])) {
                            $has_recommendable = true;
                        }
                        if($check_loan->loan_status == 'RECOMMENDED') {
                            $has_recommended = true;
                        }
                        if($check_loan->loan_status == 'APPROVED') {
                            $has_approved = true;
                        }
                        if(strtoupper(trim($check_loan->loan_status)) == 'ACTIVE') {
                            $has_payable = true;
                        }
                    }
                    
                    if($has_recommendable && $permissions['can_recommend']): ?>
                    <button class="btn btn-warning mr-2" onclick="recommendBatch('<?php echo $batch; ?>')">
                        <i class="fas fa-thumbs-up mr-2"></i>Recommend Batch
                    </button>
                    <?php endif;
                    if($has_recommended && $permissions['can_approve']): ?>
                    <button class="btn btn-primary mr-2" onclick="approveBatch('<?php echo $batch; ?>')">
                        <i class="fas fa-check mr-2"></i>Approve Batch
                    </button>
                    <?php endif; ?>
                    <?php if($has_approved && $permissions['can_disburse']): ?>
                    <button class="btn btn-danger mr-2" onclick="openBatchDisburseModal('<?php echo $batch; ?>')">
                        <i class="fas fa-money-bill-wave mr-2"></i>Disburse Batch
                    </button>
                    <?php endif; ?>
                    <?php if(!empty($edit_context) && !empty($permissions['can_edit'])): ?>
                    <button class="btn btn-info mr-2" onclick="openGroupEditModal()">
                        <i class="fas fa-edit mr-2"></i>Edit Group Loan
                    </button>
                    <?php endif; ?>
                    <?php if($has_payable && $permissions['can_pay']): ?>
                    <button class="btn btn-success mr-2" onclick="openBatchPaymentModal()">
                        <i class="fas fa-hand-holding-usd mr-2"></i>Group Loan Repayment
                    </button>
                    <?php endif; ?>
                    <a href="<?php echo base_url('loan/batch_statement/').rawurlencode($batch).'?run=1'; ?>" class="btn btn-outline-light mr-2" title="View and export batch loan account transactions">
                        <i class="fas fa-file-invoice mr-2"></i>Batch Statement
                    </a>
                    <a href="<?php echo base_url('loan/batch_report/').rawurlencode($batch); ?>" class="btn btn-outline-light mr-2" target="_blank" title="Combined batch summary report">
                        <i class="fas fa-file-alt mr-2"></i>Batch Summary Report
                    </a>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <div class="stat-card">
                        <h5>Total Loans</h5>
                        <h3 class="text-primary"><?php echo count($loans); ?></h3>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <h5>Total Amount</h5>
                        <h3 class="text-success">MK<?php echo number_format(array_sum(array_column($loans, 'loan_principal')), 2); ?></h3>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <h5>Group</h5>
                        <h3 class="text-info"><?php echo !empty($loans) ? $loans[0]->group_name . ' (' . $loans[0]->group_code . ')' : 'N/A'; ?></h3>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <h5>Batch Number</h5>
                        <h3 class="text-warning"><?php echo $batch; ?></h3>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if (!empty($batch_summary)): ?>
    <div class="card mb-4 group-finance-dashboard">
        <div class="card-header bg-dark text-white">
            <h4 class="mb-0"><i class="fas fa-chart-line mr-2"></i>Group Loan Financial Summary</h4>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4 col-lg-3 mb-3">
                    <div class="finance-card finance-card-primary">
                        <div class="finance-label">Total Group Loan Amount</div>
                        <div class="finance-value">MK <?php echo number_format($batch_summary->total_loan_amount, 2); ?></div>
                        <small class="text-muted">Principal: MK <?php echo number_format($batch_summary->total_principal, 2); ?></small>
                    </div>
                </div>
                <div class="col-md-4 col-lg-3 mb-3">
                    <div class="finance-card finance-card-success">
                        <div class="finance-label">Total Amount Paid</div>
                        <div class="finance-value">MK <?php echo number_format($batch_summary->total_paid, 2); ?></div>
                    </div>
                </div>
                <div class="col-md-4 col-lg-3 mb-3">
                    <div class="finance-card finance-card-danger finance-card-highlight">
                        <div class="finance-label">Outstanding Balance</div>
                        <div class="finance-value">MK <?php echo number_format($batch_summary->outstanding_balance, 2); ?></div>
                    </div>
                </div>
                <div class="col-md-4 col-lg-3 mb-3">
                    <div class="finance-card finance-card-info">
                        <div class="finance-label">Total Interest</div>
                        <div class="finance-value">MK <?php echo number_format($batch_summary->total_interest, 2); ?></div>
                    </div>
                </div>
                <div class="col-md-4 col-lg-3 mb-3">
                    <div class="finance-card finance-card-warning">
                        <div class="finance-label">Total Penalties</div>
                        <div class="finance-value">MK <?php echo number_format($batch_summary->total_penalties, 2); ?></div>
                    </div>
                </div>
                <div class="col-md-4 col-lg-3 mb-3">
                    <div class="finance-card finance-card-secondary">
                        <div class="finance-label">Next Installment Due</div>
                        <div class="finance-value finance-value-sm">
                            <?php if (!empty($batch_summary->next_installment_due)): ?>
                                <?php echo date('M d, Y', strtotime($batch_summary->next_installment_due)); ?>
                            <?php else: ?>
                                N/A
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($batch_summary->next_installment_amount)): ?>
                        <small>MK <?php echo number_format($batch_summary->next_installment_amount, 2); ?></small>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-4 col-lg-3 mb-3">
                    <div class="finance-card finance-card-muted">
                        <div class="finance-label">Loan Status</div>
                        <div class="finance-value finance-value-sm"><?php echo htmlspecialchars($batch_summary->loan_status_label); ?></div>
                        <small><?php echo (int)$batch_summary->member_count; ?> member loan(s)</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Individual Loan Details -->
    <?php 
    $loan_counter = 1;
    foreach ($loans as $loan):
        $payment_balance = isset($loan->payment_balance) ? $loan->payment_balance : null;
        $payment_summary = (object) array(
            'total_payments' => isset($loan->schedule_count) ? (int) $loan->schedule_count : 0,
            'total_paid' => $payment_balance ? (float) $payment_balance->total_paid : 0.0,
            'total_outstanding' => $payment_balance ? (float) $payment_balance->remaining_balance : 0.0,
        );
    ?>
    
    <!-- Loan Section LOAN <?php echo $loan->loan_number; ?> -->
    <div class="card loan-section mb-4">
        <div class="card-header loan-header">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h4 class="mb-0">
                        <i class="fas fa-file-invoice-dollar mr-2"></i>
                        LOAN <?php echo $loan->loan_number; ?>: <?php echo $loan->Firstname . ' ' . $loan->Lastname; ?>
                    </h4>
                    <small class="text-muted">Customer: <?php echo $loan->Firstname . ' ' . $loan->Lastname; ?></small>
                </div>
                <div class="col-md-4 text-right">
                    <span class="badge badge-<?php echo $loan->loan_status == 'ACTIVE' ? 'success' : ($loan->loan_status == 'CLOSED' ? 'secondary' : 'warning'); ?> badge-lg">
                        <?php echo $loan->loan_status; ?>
                    </span>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                <!-- Loan Info -->
                <div class="col-lg-4 border-right">
                    <h5 class="section-title">Loan Information</h5>
                    <table class="loan-info-table">
                        <tr>
                            <td>Loan Number:</td>
                            <td><strong><?php echo $loan->loan_number; ?></strong></td>
                        </tr>
                        <tr>
                            <td>Product:</td>
                            <td><?php echo $loan->product_name; ?></td>
                        </tr>
                        <tr>
                            <td>Customer:</td>
                            <td>
                                <a href="<?php echo base_url('individual_customers/view/').$loan->loan_customer; ?>">
                                    <?php echo $loan->Firstname . ' ' . $loan->Lastname; ?>
                                    <small>(<?php echo $loan->ClientId; ?>)</small>
                                </a>
                            </td>
                        </tr>
                        <tr>
                            <td>Customer Type:</td>
                            <td><?php echo ucfirst($loan->customer_type); ?></td>
                        </tr>
                        <tr>
                            <td>Loan Status:</td>
                            <td><span class="badge badge-<?php echo $loan->loan_status == 'ACTIVE' ? 'success' : 'secondary'; ?>"><?php echo $loan->loan_status; ?></span></td>
                        </tr>
                        <tr>
                            <td>Loan Date:</td>
                            <td><?php echo date('M d, Y', strtotime($loan->loan_date)); ?></td>
                        </tr>
                    </table>
                </div>
                
                <!-- Financial Info -->
                <div class="col-lg-4 border-right">
                    <h5 class="section-title">Financial Details</h5>
                    <table class="loan-info-table">
                        <tr>
                            <td>Principal:</td>
                            <td><strong>MK<?php echo number_format($loan->loan_principal, 2); ?></strong></td>
                        </tr>
                        <tr>
                            <td>Interest Rate:</td>
                            <td><?php echo $loan->loan_interest; ?>%</td>
                        </tr>
                        <tr>
                            <td>Interest Amount:</td>
                            <td>MK<?php echo number_format($loan->loan_interest_amount, 2); ?></td>
                        </tr>
                        <tr>
                            <td>Total Amount:</td>
                            <td><strong>MK<?php echo number_format($loan->loan_amount_total, 2); ?></strong></td>
                        </tr>
                        <tr>
                            <td>Loan Period:</td>
                            <td><?php echo $loan->loan_period; ?> <?php echo $loan->period_type; ?></td>
                        </tr>
                        <tr>
                            <td>Payment Term:</td>
                            <td>MK<?php echo number_format($loan->loan_amount_term, 2); ?></td>
                        </tr>
                    </table>
                </div>
                
                <!-- Payment Status -->
                <div class="col-lg-4">
                    <h5 class="section-title">Payment Status</h5>
                    <table class="loan-info-table">
                        <tr>
                            <td>Total Payments:</td>
                            <td><?php echo $payment_summary ? $payment_summary->total_payments : 0; ?></td>
                        </tr>
                        <tr>
                            <td>Amount Paid:</td>
                            <td class="text-success"><strong>MK<?php echo number_format($payment_summary ? $payment_summary->total_paid : 0, 2); ?></strong></td>
                        </tr>
                        <tr>
                            <td>Outstanding:</td>
                            <td class="text-danger"><strong>MK<?php echo number_format($payment_summary ? $payment_summary->total_outstanding : 0, 2); ?></strong></td>
                        </tr>
                        <tr>
                            <td>Progress:</td>
                            <td>
                                <?php 
                                $progress = $payment_summary && $loan->loan_amount_total > 0 ? 
                                    ($payment_summary->total_paid / $loan->loan_amount_total) * 100 : 0;
                                ?>
                                <div class="progress">
                                    <div class="progress-bar bg-success" style="width: <?php echo $progress; ?>%"></div>
                                </div>
                                <small><?php echo number_format($progress, 1); ?>% Complete</small>
                            </td>
                        </tr>
                    </table>
                    
                    <!-- Action Buttons -->
                    <div class="mt-3">
                        <a href="<?php echo base_url('loan/view/').$loan->loan_id; ?>" class="btn btn-primary btn-sm">
                            <i class="fas fa-eye mr-1"></i>View Details
                        </a>
                        <button class="btn btn-warning btn-sm" onclick="toggleAmortization(<?php echo $loan->loan_id; ?>)">
                            <i class="fas fa-table mr-1"></i>View Amortization
                        </button>
                        <?php if (strtoupper(trim($loan->loan_status)) == 'ACTIVE'): ?>
                        <?php if ($permissions['can_pay']): ?>
                        <button class="btn btn-success btn-sm" onclick="payLoanBatch(<?php echo $loan->loan_id; ?>, '<?php echo $loan->loan_number; ?>')">
                            <i class="fas fa-money-bill-wave mr-1"></i>Pay Now
                        </button>
                        <?php endif; ?>
                        <?php if ($permissions['can_pay_off']): ?>
                        <button class="btn btn-warning btn-sm" onclick="payOffLoanBatch(<?php echo $loan->loan_id; ?>, '<?php echo $loan->loan_number; ?>')">
                            <i class="fas fa-hand-holding-usd mr-1"></i>Pay Off
                        </button>
                        <?php endif; ?>
                        <?php endif; ?>
                        <?php
                        $last_receipt = get_last($loan->loan_number);
                        if(!empty($last_receipt)){
                        ?>
                        <a href="<?php echo base_url('account/print_loan_receipt/').$last_receipt->id; ?>" target="_blank" class="btn btn-secondary btn-sm">
                            <i class="fas fa-print mr-1"></i>Latest Receipt
                        </a>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Amortization Schedule (Initially Hidden) -->
        <div id="amortization-<?php echo $loan->loan_id; ?>" class="amortization-section" style="display: none;">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0"><i class="fas fa-table mr-2"></i>Amortization Schedule - LOAN <?php echo $loan->loan_number; ?></h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-bordered amortization-table">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center">Payment #</th>
                                <th class="text-center">Payment Date</th>
                                <th class="text-center">Principal</th>
                                <th class="text-center">Interest</th>
                                <th class="text-center">Admin Fee</th>
                                <th class="text-center">Loan Cover</th>
                                <th class="text-center">Total Payment</th>
                                <th class="text-center">Amount Paid</th>
                                <th class="text-center">Balance</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            // Get payment schedule for this loan
                            $this->db->where('loan_id', $loan->loan_id);
                            $this->db->order_by('payment_number', 'ASC');
                            $payment_schedules = $this->db->get('payement_schedules')->result();
                            
                            foreach ($payment_schedules as $payment): 
                                $status_class = '';
                                $status_text = $payment->status;
                                
                                if($payment->payment_schedule < date('Y-m-d') && $payment->status == 'NOT PAID') {
                                    $status_class = 'table-danger';
                                    $status_text = 'OVERDUE';
                                } elseif($payment->payment_schedule == date('Y-m-d') && $payment->status == 'NOT PAID') {
                                    $status_class = 'table-warning';
                                    $status_text = 'DUE TODAY';
                                } elseif($payment->status == 'PAID') {
                                    $status_class = 'table-success';
                                }
                                
                                if($payment->partial_paid == "YES") {
                                    $status_text .= ' (Partial)';
                                }
                            ?>
                            <tr class="<?php echo $status_class; ?>">
                                <td><?php echo $payment->payment_number; ?></td>
                                <td><?php echo date('M d, Y', strtotime($payment->payment_schedule)); ?></td>
                                <td>MK<?php echo number_format($payment->principal, 2); ?></td>
                                <td>MK<?php echo number_format($payment->interest, 2); ?></td>
                                <td>MK<?php echo number_format($payment->padmin_fee, 2); ?></td>
                                <td>MK<?php echo number_format($payment->ploan_cover, 2); ?></td>
                                <td><strong>MK<?php echo number_format($payment->amount, 2); ?></strong></td>
                                <td class="text-success">MK<?php echo number_format($payment->paid_amount, 2); ?></td>
                                <td>MK<?php echo number_format($payment->loan_balance, 2); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $payment->status == 'PAID' ? 'success' : ($status_text == 'OVERDUE' ? 'danger' : ($status_text == 'DUE TODAY' ? 'warning' : 'secondary')); ?>">
                                        <?php echo $status_text; ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Loan Section Separator -->
        <div class="loan-separator">
            <div class="separator-line"></div>
            <div class="separator-text">End of LOAN <?php echo $loan->loan_number; ?></div>
            <div class="separator-line"></div>
        </div>
    </div>
    
    <?php 
    $loan_counter++;
    endforeach; 
    ?>
    
    <!-- Back Button -->
    <div class="text-center mt-4">
        <a href="<?php echo base_url('loan/group_file'); ?>" class="btn btn-secondary btn-lg">
            <i class="fas fa-arrow-left mr-2"></i>Back to Group File
        </a>
    </div>
</div>

<!-- Payment Modal -->
<div aria-hidden="true" class="onboarding-modal modal fade" id="batch_payment_modal" role="dialog" tabindex="-1">
    <div class="modal-dialog modal-lg modal-centered" role="document">
        <div class="modal-content text-center">
            <span></span><button style="float: right;" aria-label="Close" class="close" data-dismiss="modal" type="button"><span class="close-label">Close</span><span class="anticon anticon-close"></span></button>
            <div class="onboarding-content" style="padding: 1em;">
                <h4 class="onboarding-title">Loan Payment</h4>
                <p style="color: red;">Enter deposit amount</p>
                <table id="payment-loan-info">
                    <tr>
                        <td style="text-align: right;padding-right: 10px;" width="150">Loan #</td>
                        <td id="payment-loan-number"></td>
                    </tr>
                    <tr>
                        <td style="text-align: right;padding-right: 10px;" width="150">Customer</td>
                        <td id="payment-customer-name"></td>
                    </tr>
                    <tr>
                        <td style="text-align: right;padding-right: 10px;" width="150">Next Payment #</td>
                        <td id="payment-payment-number"></td>
                    </tr>
                    <tr>
                        <td style="text-align: right;padding-right: 10px;">Payment Amount</td>
                        <td id="payment-amount">MK 0.00</td>
                    </tr>
                </table>
                <form action="<?php echo base_url('loan/pay_loan')?>" class="form-row" method="POST" id="batch_payment_form">
                    <div class="form-group col-lg-12" style="padding: 2em;">
                        <label for="amount">Enter deposit amount</label>
                        <input type="hidden" name="loan_id" id="payment-loan-id">
                        <input type="hidden" name="payment_number" id="payment-payment-num">
                        <input style="border: thin red solid;" type="text" class="form-control" name="amount" onkeyup="formatNumber(this)" value="0" placeholder="Enter pay amount" required />
                        <input type="hidden" name="topay_amount" id="payment-topay-amount">
                        <input type="hidden" name="payment_method" value="0">
                        <div class="form-group col-12 mt-3">
                            <label for="batch_payment_type"><strong>Payment Type <span style="color:red;">*</span></strong></label>
                            <select class="form-control" name="payment_type" id="batch_payment_type" required onchange="updateRefLabel('batch_payment_type','batch_ref_label','batch_payment_reference')">
                                <option value="">-- Select Payment Type --</option>
                                <option value="bank">Bank Transfer</option>
                                <option value="cash">Cash</option>
                            </select>
                        </div>
                        <div class="form-group col-12 mt-2">
                            <label id="batch_ref_label" for="batch_payment_reference"><strong>Reference Number <span style="color:red;">*</span></strong></label>
                            <div class="input-group">
                                <input type="text" class="form-control" name="payment_reference" id="batch_payment_reference"
                                       placeholder="Enter Transaction ID or Receipt Number" required
                                        oninput="checkPaymentRef(this,'batch_ref_feedback')" autocomplete="off" disabled />
                                <div class="input-group-append">
                                    <span class="input-group-text" id="batch_ref_feedback"></span>
                                </div>
                            </div>
                            <small class="form-text text-muted">Bank payment: enter the Transaction ID from the bank statement. Cash: enter the Receipt Number.</small>
                        </div>
                        <div class="form-group col-12 mt-3">
                            <label for="pdate">Payment date</label>
                            <input type="datetime-local" class="form-control" name="pdate" id="pdate" />
                        </div>
                    </div>
                    <button class="btn btn-sm btn-block btn-danger" type="submit" id="submit_batch_payment">Submit Payment</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Pay Off Modal -->
<div aria-hidden="true" class="onboarding-modal modal fade" id="batch_payoff_modal" role="dialog" tabindex="-1">
    <div class="modal-dialog modal-lg modal-centered" role="document">
        <div class="modal-content text-center">
            <span></span><button style="float: right;" aria-label="Close" class="close" data-dismiss="modal" type="button"><span class="close-label">Close</span><span class="anticon anticon-close"></span></button>
            <div class="onboarding-content" style="padding: 1em;">
                <h4 class="onboarding-title">Loan Pay Off</h4>
                <p style="color: orange;">Are you sure you want to finish loan payments? Please check below calculations first.</p>
                <p style="color: red;">This kind of transaction cannot be reversed - do it with caution!</p>
                <table id="payoff-loan-info">
                    <tr>
                        <td style="text-align: right;padding-right: 10px;" width="150">Loan #</td>
                        <td id="payoff-loan-number"></td>
                    </tr>
                    <tr>
                        <td style="text-align: right;padding-right: 10px;" width="150">Customer</td>
                        <td id="payoff-customer-name"></td>
                    </tr>
                    <tr>
                        <td style="text-align: right;padding-right: 10px;" width="150">Payment #</td>
                        <td id="payoff-payment-number"></td>
                    </tr>
                    <tr>
                        <td style="text-align: right;padding-right: 10px;">Pay Off Amount</td>
                        <td id="payoff-amount">MK 0.00</td>
                    </tr>
                </table>
                <form action="<?php echo base_url('loan/finish_loan')?>" class="form-row" method="POST" id="batch_payoff_form">
                    <div class="form-group col-lg-12" style="padding: 2em;">
                        <label for="amount">Pay Off Amount</label>
                        <input type="hidden" name="loan_id" id="payoff-loan-id">
                        <input type="hidden" name="payment_number" id="payoff-payment-num">
                        <input type="hidden" name="repay_amounts" id="payoff-repay-amounts">
                        <input type="hidden" name="totalbalance" id="payoff-total-balance">
                        <input type="hidden" name="middlepayment" id="payoff-middle-payment">
                        <input style="border: thin red solid;" type="text" class="form-control" name="amount" id="payoff-amount-input" readonly required />
                        <input type="hidden" name="payment_method" value="0">
                        <input type="hidden" name="pay_proof" value="Null">
                        <div class="form-group col-12 mt-3">
                            <label for="batch_payoff_type"><strong>Payment Type <span style="color:red;">*</span></strong></label>
                            <select class="form-control" name="payment_type" id="batch_payoff_type" required onchange="updateRefLabel('batch_payoff_type','batch_payoff_ref_label','batch_payoff_reference')">
                                <option value="">-- Select Payment Type --</option>
                                <option value="bank">Bank Transfer</option>
                                <option value="cash">Cash</option>
                            </select>
                        </div>
                        <div class="form-group col-12 mt-2">
                            <label id="batch_payoff_ref_label" for="batch_payoff_reference"><strong>Reference Number <span style="color:red;">*</span></strong></label>
                            <div class="input-group">
                                <input type="text" class="form-control" name="payment_reference" id="batch_payoff_reference"
                                       placeholder="Enter Transaction ID or Receipt Number" required
                                        oninput="checkPaymentRef(this,'batch_payoff_ref_feedback')" autocomplete="off" disabled />
                                <div class="input-group-append">
                                    <span class="input-group-text" id="batch_payoff_ref_feedback"></span>
                                </div>
                            </div>
                            <small class="form-text text-muted">Bank payment: enter the Transaction ID from the bank statement. Cash: enter the Receipt Number.</small>
                        </div>
                        <div class="form-group col-12 mt-3">
                            <label for="pdate">Payment date</label>
                            <input type="date" class="form-control" name="pdate" id="payoff-pdate" />
                        </div>
                    </div>
                    <button class="btn btn-sm btn-block btn-danger" type="submit" id="submit_batch_payoff">Pay Off Loan</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Batch Disburse Modal -->
<div aria-hidden="true" class="onboarding-modal modal fade" id="batch_disburse_modal" role="dialog" tabindex="-1">
    <div class="modal-dialog modal-centered" role="document">
        <div class="modal-content text-center">
            <button style="float: right;" aria-label="Close" class="close" data-dismiss="modal" type="button"><span class="close-label">Close</span><span class="anticon anticon-close"></span></button>
            <div class="onboarding-content" style="padding: 1em;">
                <h4 class="onboarding-title">Disburse batch</h4>
                <p>Batch: <strong id="batch_disburse_label"></strong></p>
                <p style="color: #555;">This will disburse all APPROVED loans in the batch.</p>
                <div class="form-group text-left">
                    <label for="batch_disbursement_date"><strong>Disbursement date</strong></label>
                    <input type="date" class="form-control" id="batch_disbursement_date" name="disbursement_date">
                    <small class="form-text text-muted">Leave blank to use today's date.</small>
                </div>
                <input type="hidden" id="batch_disburse_number" value="">
                <button type="button" class="btn btn-danger btn-block mt-3" id="confirm_batch_disburse_btn" onclick="confirmBatchDisburse()">Disburse batch</button>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($edit_context)): ?>
<div aria-hidden="true" class="onboarding-modal modal fade" id="group_edit_modal" role="dialog" tabindex="-1">
    <div class="modal-dialog modal-xl modal-centered" role="document">
        <div class="modal-content text-left">
            <button style="float: right;" aria-label="Close" class="close" data-dismiss="modal" type="button"><span class="close-label">Close</span><span class="anticon anticon-close"></span></button>
            <div class="onboarding-content" style="padding: 1em;">
                <h4 class="onboarding-title">Edit Group Loan â€” <?php echo htmlspecialchars($batch); ?></h4>
                <p class="text-muted">Uses the same edit engine as individual loans. All members update after approval; repayments are preserved.</p>
                <form method="post" action="<?php echo base_url('loan/batch_edit_action'); ?>">
                    <input type="hidden" name="batch" value="<?php echo htmlspecialchars($batch); ?>">
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label><strong>Loan product</strong></label>
                            <select name="loan_type" class="form-control" required>
                                <?php foreach ($loan_types as $lt): ?>
                                    <option value="<?php echo (int)$lt->loan_product_id; ?>" <?php echo ((int)$edit_context->loan_product_id === (int)$lt->loan_product_id) ? 'selected' : ''; ?>><?php echo htmlspecialchars($lt->product_name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group col-md-3">
                            <label><strong>Repayment period</strong></label>
                            <input type="number" name="months" class="form-control" min="1" value="<?php echo (int)$edit_context->loan_period; ?>" required>
                        </div>
                        <div class="form-group col-md-3">
                            <label><strong>Frequency</strong></label>
                            <input type="text" name="period_type" class="form-control" value="<?php echo htmlspecialchars($edit_context->period_type); ?>">
                        </div>
                        <div class="form-group col-md-4">
                            <label><strong>Loan / disbursement date</strong></label>
                            <input type="date" name="loan_date" class="form-control" value="<?php echo htmlspecialchars(date('Y-m-d', strtotime($edit_context->loan_date))); ?>" required>
                        </div>
                        <div class="form-group col-md-4">
                            <label><strong>Loan officer</strong></label>
                            <select name="user" class="form-control" required>
                                <?php foreach ($officers as $officer): ?>
                                    <option value="<?php echo (int)$officer->id; ?>" <?php echo ((int)$edit_context->loan_added_by === (int)$officer->id) ? 'selected' : ''; ?>><?php echo htmlspecialchars($officer->Firstname . ' ' . $officer->Lastname); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group col-md-12">
                            <label><strong>Notes / narration</strong></label>
                            <textarea name="narration" class="form-control" rows="2"><?php echo htmlspecialchars((string)$edit_context->narration); ?></textarea>
                        </div>
                    </div>
                    <table class="table table-bordered table-sm mt-2">
                        <thead class="thead-light"><tr><th>Member</th><th>Loan #</th><th>Principal (MWK)</th></tr></thead>
                        <tbody>
                            <?php foreach ($edit_context->members as $member): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($member->member_name); ?></td>
                                <td><?php echo htmlspecialchars($member->loan_number); ?></td>
                                <td><input type="text" class="form-control" name="member_amounts[<?php echo (int)$member->loan_id; ?>]" value="<?php echo number_format($member->loan_principal, 2, '.', ''); ?>" required></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <button type="submit" class="btn btn-primary btn-block mt-3" onclick="return confirm('Submit group loan edit for approval?');">Submit group edit for approval</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Group Batch Payment Modal -->
<div aria-hidden="true" class="onboarding-modal modal fade" id="group_batch_payment_modal" role="dialog" tabindex="-1">
    <div class="modal-dialog modal-xl modal-centered" role="document">
        <div class="modal-content text-left">
            <span></span><button style="float: right;" aria-label="Close" class="close" data-dismiss="modal" type="button"><span class="close-label">Close</span><span class="anticon anticon-close"></span></button>
            <div class="onboarding-content" style="padding: 1em;">
                <h4 class="onboarding-title">Group Loan Repayment — <?php echo $batch; ?></h4>
                <p style="color: #555;">Enter payment details once, then allocate the total across members. Allocation must equal the deposited amount.</p>
                <form class="form-row" id="group_batch_payment_form" method="POST" action="<?php echo base_url('loan/batch_pay'); ?>" onsubmit="doSubmitBatchPayment(); return false;">
                    <input type="hidden" name="batch" value="<?php echo $batch; ?>">
                    <div class="form-group col-md-4">
                        <label for="batch-total-amount"><strong>Total Deposited Amount (MWK) <span style="color:red;">*</span></strong></label>
                        <input type="text" class="form-control" id="batch-total-amount" name="total_amount" placeholder="e.g. 150,000.00" oninput="updateBatchAllocationTotals()" onchange="updateBatchAllocationTotals()" onkeyup="updateBatchAllocationTotals()" required>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="group_batch_payment_type"><strong>Payment Type <span style="color:red;">*</span></strong></label>
                        <select class="form-control" name="payment_type" id="group_batch_payment_type" required onchange="updateRefLabel('group_batch_payment_type','group_batch_ref_label','group_batch_payment_reference')">
                            <option value="">-- Select Payment Type --</option>
                            <option value="bank">Bank Transfer</option>
                            <option value="cash">Cash</option>
                        </select>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="group_batch_pdate"><strong>Payment Date</strong></label>
                        <input type="date" class="form-control" name="pdate" id="group_batch_pdate" />
                    </div>
                    <div class="form-group col-md-12 mt-2">
                        <label id="group_batch_ref_label" for="group_batch_payment_reference"><strong>Transaction / Receipt Number <span style="color:red;">*</span></strong></label>
                        <div class="input-group">
                            <input type="text" class="form-control" name="payment_reference" id="group_batch_payment_reference"
                                   placeholder="Enter Transaction ID or Receipt Number" required
                                oninput="checkPaymentRef(this,'group_batch_ref_feedback')" autocomplete="off" disabled />
                            <div class="input-group-append">
                                <span class="input-group-text" id="group_batch_ref_feedback"></span>
                            </div>
                        </div>
                        <small class="form-text text-muted">Use the same bank Transaction ID or cash Receipt Number for the whole group batch payment.</small>
                    </div>

                    <div class="col-12 mt-2">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped" id="group-batch-allocation-table">
                                <thead class="table-light">
                                    <tr>
                                        <th>Member Name</th>
                                        <th>Loan Number</th>
                                        <th>Outstanding Balance</th>
                                        <th>Installment Due</th>
                                        <th>Amount to Repay (MWK)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $repayment_rows = isset($repayment_members) ? $repayment_members : array();
                                    foreach ($repayment_rows as $member_row):
                                    ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($member_row->member_name); ?></td>
                                                <td><strong><?php echo htmlspecialchars($member_row->loan_number); ?></strong></td>
                                                <td class="text-danger"><strong>MK <?php echo number_format($member_row->outstanding, 2); ?></strong></td>
                                                <td>
                                                    MK <?php echo number_format($member_row->installment_due, 2); ?>
                                                    <?php if (!empty($member_row->installment_date)): ?>
                                                        <br><small class="text-muted"><?php echo date('M d, Y', strtotime($member_row->installment_date)); ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <input
                                                        type="number"
                                                        class="form-control batch-member-amount"
                                                        name="allocations[<?php echo (int)$member_row->loan_id; ?>]"
                                                        step="0.01"
                                                        min="0"
                                                        max="<?php echo number_format($member_row->outstanding, 2, '.', ''); ?>"
                                                        data-loan-number="<?php echo htmlspecialchars($member_row->loan_number); ?>"
                                                        data-outstanding="<?php echo number_format($member_row->outstanding, 2, '.', ''); ?>"
                                                        oninput="updateBatchAllocationTotals()"
                                                        onchange="updateBatchAllocationTotals()"
                                                        placeholder="0.00"
                                                    >
                                                </td>
                                            </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="col-12 mt-2">
                        <div id="batch-sum-alert" class="alert alert-danger" style="display:none;"></div>
                        <div id="batch-debug-alert" class="alert alert-warning" style="display:none;"></div>
                        <div id="batch-status-alert" class="alert alert-info" style="display:none;"></div>
                        <div class="alert alert-info mb-2">
                            <strong>Total Allocated:</strong> <span id="batch-allocated-total">MK 0.00</span>
                            | <strong>Deposited:</strong> <span id="batch-total-deposited-display">MK 0.00</span>
                            | <strong>Difference:</strong> <span id="batch-allocation-difference">MK 0.00</span>
                        </div>
                        <div class="alert alert-secondary mb-0">
                            Allocated repayment amount must equal total deposited amount (difference must be MK 0.00).
                        </div>
                    </div>

                    <div class="col-12 mt-3">
                        <button class="btn btn-danger btn-block" type="button" id="submit_group_batch_payment" onclick="doSubmitBatchPayment()" style="display:none;">Submit Group Repayment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
/* Custom styles for Group Batch Loans */
.loan-section {
    border: 2px solid #e3e6f0;
    border-radius: 10px;
    transition: all 0.3s ease;
}

.loan-section:hover {
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    transform: translateY(-2px);
}

.loan-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 8px 8px 0 0;
}

.section-title {
    color: #5a5c69;
    font-weight: 600;
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid #e3e6f0;
}

.loan-info-table {
    width: 100%;
    margin-bottom: 1rem;
}

.loan-info-table td {
    padding: 0.4rem 0;
    vertical-align: top;
}

.loan-info-table td:first-child {
    font-weight: 500;
    color: #5a5c69;
    width: 40%;
    padding-right: 1rem;
}

.stat-card {
    text-align: center;
    padding: 1rem;
    background: #f8f9fc;
    border-radius: 8px;
    border-left: 4px solid #4e73df;
}

.stat-card h5 {
    margin-bottom: 0.5rem;
    color: #5a5c69;
    font-size: 0.9rem;
    font-weight: 500;
}

.stat-card h3 {
    margin: 0;
    font-weight: 700;
}

.group-finance-dashboard .finance-card {
    border-radius: 10px;
    padding: 1rem 1.1rem;
    height: 100%;
    border: 1px solid #e3e6f0;
    background: #fff;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
}

.finance-label {
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: #6c757d;
    margin-bottom: 0.35rem;
}

.finance-value {
    font-size: 1.25rem;
    font-weight: 700;
    color: #2e384d;
}

.finance-value-sm {
    font-size: 1rem;
}

.finance-card-highlight {
    border: 2px solid #e74a3b;
    background: linear-gradient(135deg, #fff5f5 0%, #ffffff 100%);
}

.finance-card-highlight .finance-value {
    color: #c0392b;
}

.finance-card-primary { border-left: 4px solid #4e73df; }
.finance-card-success { border-left: 4px solid #1cc88a; }
.finance-card-danger { border-left: 4px solid #e74a3b; }
.finance-card-info { border-left: 4px solid #36b9cc; }
.finance-card-warning { border-left: 4px solid #f6c23e; }
.finance-card-secondary { border-left: 4px solid #858796; }
.finance-card-muted { border-left: 4px solid #d1d3e2; }

.allocation-summary-box {
    background: #f8f9fc;
    border: 1px solid #e3e6f0;
    border-radius: 8px;
    padding: 0.75rem 1rem;
    text-align: center;
}

.allocation-summary-label {
    font-size: 0.75rem;
    text-transform: uppercase;
    color: #6c757d;
}

.allocation-summary-value {
    font-size: 1.1rem;
    font-weight: 700;
}

.allocation-summary-diff .allocation-summary-value {
    color: #e74a3b;
}

.badge-lg {
    padding: 0.5rem 1rem;
    font-size: 0.9rem;
}

.progress {
    height: 10px;
    border-radius: 5px;
    overflow: hidden;
    background-color: #e9ecef;
}

.progress-bar {
    transition: width 0.6s ease;
}

.loan-separator {
    display: flex;
    align-items: center;
    margin: 2rem 0 0 0;
    padding: 1rem;
    background: #f8f9fc;
}

.separator-line {
    flex: 1;
    height: 2px;
    background: linear-gradient(to right, transparent, #e3e6f0, transparent);
}

.separator-text {
    padding: 0 2rem;
    font-weight: 600;
    color: #858796;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.border-right {
    border-right: 1px solid #e3e6f0 !important;
}

@media (max-width: 768px) {
    .border-right {
        border-right: none !important;
        border-bottom: 1px solid #e3e6f0;
        margin-bottom: 1rem;
        padding-bottom: 1rem;
    }
}

.btn-sm {
    margin: 0.2rem;
}

.card-header {
    font-weight: 500;
}

/* Keep member names readable regardless of theme table stripe overrides */
#group-batch-allocation-table tbody td {
    color: #212529 !important;
    background-color: #ffffff;
}

#group-batch-allocation-table.table-striped tbody tr:nth-of-type(odd) td {
    background-color: #f8f9fc;
    color: #212529 !important;
}

#group-batch-allocation-table.table-striped tbody tr:nth-of-type(even) td {
    background-color: #ffffff;
    color: #212529 !important;
}

/* Amortization Section Styles */
.amortization-section {
    border-top: 1px solid #e3e6f0;
    animation: slideDown 0.3s ease-in-out;
}

.amortization-table {
    font-size: 0.9rem;
    margin-bottom: 0;
}

.amortization-table th {
    background-color: #f8f9fc;
    color: #5a5c69;
    font-weight: 600;
    padding: 0.75rem 0.5rem;
    border-color: #e3e6f0;
    font-size: 0.85rem;
}

.amortization-table td {
    text-align: center;
    padding: 0.6rem 0.4rem;
    vertical-align: middle;
    border-color: #e3e6f0;
    font-size: 0.85rem;
}

.amortization-table .table-success {
    background-color: rgba(40, 167, 69, 0.1);
}

.amortization-table .table-danger {
    background-color: rgba(220, 53, 69, 0.1);
}

.amortization-table .table-warning {
    background-color: rgba(255, 193, 7, 0.1);
}

@keyframes slideDown {
    from {
        opacity: 0;
        max-height: 0;
    }
    to {
        opacity: 1;
        max-height: 1000px;
    }
}

.table-responsive {
    max-height: 400px;
    overflow-y: auto;
}
</style>

<script>
function toggleAmortization(loanId) {
    var amortizationSection = document.getElementById('amortization-' + loanId);
    var button = event.target;
    
    if (amortizationSection.style.display === 'none' || amortizationSection.style.display === '') {
        amortizationSection.style.display = 'block';
        button.innerHTML = '<i class="fas fa-eye-slash mr-1"></i>Hide Amortization';
        button.classList.remove('btn-warning');
        button.classList.add('btn-secondary');
    } else {
        amortizationSection.style.display = 'none';
        button.innerHTML = '<i class="fas fa-table mr-1"></i>View Amortization';
        button.classList.remove('btn-secondary');
        button.classList.add('btn-warning');
    }
}

function payLoanBatch(loanId, loanNumber) {
    // Get loan information via AJAX to populate the modal
    $.ajax({
        url: '<?php echo base_url("loan/get_next_payment_info"); ?>',
        type: 'POST',
        data: {
            loan_id: loanId
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // Populate modal with loan information
                $('#payment-loan-number').text(loanNumber);
                $('#payment-customer-name').text(response.customer_name);
                $('#payment-payment-number').text(response.payment_number);
                $('#payment-amount').text('MK ' + response.amount_formatted);
                
                // Set form values
                $('#payment-loan-id').val(loanId);
                $('#payment-payment-num').val(response.payment_number);
                $('#payment-topay-amount').val(response.amount);
                
                // Show the modal
                $("#batch_payment_modal").modal('show');
            } else {
                alert('Error: ' + response.message);
            }
        },
        error: function() {
            alert('Error fetching payment information');
        }
    });
}

var refCheckTimerBatch = null;
function checkPaymentRef(input, feedbackId) {
    clearTimeout(refCheckTimerBatch);
    var val = input.value.trim();
    var feedback = document.getElementById(feedbackId);
    var submitBtn = input.closest('form').querySelector('[type=submit]');
    if (val.length === 0) {
        feedback.innerHTML = '';
        feedback.style.color = '';
        if (submitBtn) submitBtn.disabled = false;
        return;
    }
    refCheckTimerBatch = setTimeout(function() {
        feedback.innerHTML = '&#8230;';
        feedback.style.color = '#aaa';
        var xhr = new XMLHttpRequest();
        xhr.open('POST', '<?php echo base_url('loan/check_payment_reference'); ?>', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status === 200) {
                try {
                    var res = JSON.parse(xhr.responseText);
                    if (res.duplicate) {
                        feedback.innerHTML = '&#10008; Duplicate reference';
                        feedback.style.color = 'red';
                        input.style.borderColor = 'red';
                        if (submitBtn) submitBtn.disabled = true;
                    } else {
                        feedback.innerHTML = '&#10004; OK';
                        feedback.style.color = 'green';
                        input.style.borderColor = 'green';
                        if (submitBtn) submitBtn.disabled = false;
                    }
                } catch(e) {}
            }
        };
        xhr.send('payment_reference=' + encodeURIComponent(val));
    }, 500);
}

function updateRefLabel(selectId, labelId, inputId) {
    var sel = document.getElementById(selectId);
    var lbl = document.getElementById(labelId);
    var inp = document.getElementById(inputId);
    if (!sel || !lbl || !inp) return;

    var isTypeSelected = (sel.value === 'bank' || sel.value === 'cash');
    inp.disabled = !isTypeSelected;

    if (sel.value === 'bank') {
        lbl.textContent = 'Bank Transaction ID';
        inp.placeholder = 'Enter bank transaction ID from statement';
    } else if (sel.value === 'cash') {
        lbl.textContent = 'Cash Receipt Number';
        inp.placeholder = 'Enter receipt number issued at point of payment';
    } else {
        lbl.textContent = 'Payment Reference';
        inp.placeholder = 'Select payment type first';
        inp.value = '';
        inp.style.borderColor = '';

        var feedback = inp.parentElement ? inp.parentElement.querySelector('.input-group-text') : null;
        if (feedback) {
            feedback.innerHTML = '';
            feedback.style.color = '';
        }
    }
}

function formatNumber(input) {
    // Remove any non-digit characters except for decimal point
    let value = input.value.replace(/[^\d.]/g, '');
    
    // Format the number with commas
    if (value) {
        let parts = value.split('.');
        parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        input.value = parts.join('.');
    }
}

function recommendBatch(batchNumber) {
    if (!confirm('Are you sure you want to recommend ALL loans in batch ' + batchNumber + '? This action will change the status of all loans in this batch to RECOMMENDED.')) {
        return;
    }
    
    // Show loading state
    const button = event.target;
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Processing...';
    button.disabled = true;
    
    $.ajax({
        url: '<?php echo base_url("loan/batch_recommend"); ?>',
        type: 'POST',
        data: {
            batch: batchNumber
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                alert('Success: ' + response.message);
                // Refresh the page to show updated statuses
                location.reload();
            } else {
                alert('Error: ' + response.message);
            }
        },
        error: function(xhr, status, error) {
            alert('Error: Failed to process batch recommendation. Please try again.');
            console.error('AJAX Error:', error);
        },
        complete: function() {
            // Restore button state
            button.innerHTML = originalText;
            button.disabled = false;
        }
    });
}

function approveBatch(batchNumber) {
    if (!confirm('Are you sure you want to approve ALL RECOMMENDED loans in batch ' + batchNumber + '? This action will change the status of all RECOMMENDED loans in this batch to APPROVED.')) {
        return;
    }
    
    // Show loading state
    const button = event.target;
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Processing...';
    button.disabled = true;
    
    $.ajax({
        url: '<?php echo base_url("loan/batch_approve"); ?>',
        type: 'POST',
        data: {
            batch: batchNumber
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                alert('Success: ' + response.message);
                // Refresh the page to show updated statuses
                location.reload();
            } else {
                alert('Error: ' + response.message);
            }
        },
        error: function(xhr, status, error) {
            alert('Error: Failed to process batch approval. Please try again.');
            console.error('AJAX Error:', error);
        },
        complete: function() {
            // Restore button state
            button.innerHTML = originalText;
            button.disabled = false;
        }
    });
}

function openBatchDisburseModal(batchNumber) {
    $('#batch_disburse_number').val(batchNumber);
    $('#batch_disburse_label').text(batchNumber);
    $('#batch_disbursement_date').val('');
    $('#batch_disburse_modal').modal('show');
}

function confirmBatchDisburse() {
    const batchNumber = $('#batch_disburse_number').val();
    const disbursementDate = $('#batch_disbursement_date').val();

    if (!confirm('Are you sure you want to disburse ALL APPROVED loans in batch ' + batchNumber + '? This action will:\n- Change status to ACTIVE\n- Create payment schedules\n- Process cash transactions\n- Send SMS notifications (if enabled)\n\nThis action cannot be undone.')) {
        return;
    }

    const button = document.getElementById('confirm_batch_disburse_btn');
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Processing...';
    button.disabled = true;

    $.ajax({
        url: '<?php echo base_url("loan/batch_disburse"); ?>',
        type: 'POST',
        data: {
            batch: batchNumber,
            disbursement_date: disbursementDate
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                alert('Success: ' + response.message);
                location.reload();
            } else {
                alert('Error: ' + response.message);
            }
        },
        error: function(xhr, status, error) {
            alert('Error: Failed to process batch disbursement. Please try again.');
            console.error('AJAX Error:', error);
        },
        complete: function() {
            button.innerHTML = originalText;
            button.disabled = false;
        }
    });
}

function payOffLoanBatch(loanId, loanNumber) {
    // Get loan pay off information via AJAX
    $.ajax({
        url: '<?php echo base_url("loan/get_payoff_info"); ?>',
        type: 'POST',
        data: {
            loan_id: loanId
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // Populate modal with loan information
                $('#payoff-loan-number').text(loanNumber);
                $('#payoff-customer-name').text(response.customer_name);
                $('#payoff-payment-number').text(response.payment_number);
                $('#payoff-amount').text('MK ' + response.payoff_amount_formatted);
                
                // Set form values
                $('#payoff-loan-id').val(loanId);
                $('#payoff-payment-num').val(response.payment_number);
                $('#payoff-repay-amounts').val(response.repay_amounts);
                $('#payoff-total-balance').val(response.total_balance);
                $('#payoff-middle-payment').val(response.middle_payment);
                $('#payoff-amount-input').val(response.payoff_amount);
                
                // Show the modal
                $("#batch_payoff_modal").modal('show');
            } else {
                alert('Error: ' + response.message);
            }
        },
        error: function() {
            alert('Error fetching pay off information');
        }
    });
}

function parseBatchAmount(value) {
    if (value === null || value === undefined) {
        return 0;
    }
    var normalized = value.toString().replace(/,/g, '').trim();
    if (normalized === '') {
        return 0;
    }
    var parsed = parseFloat(normalized);
    return isNaN(parsed) ? 0 : parsed;
}

function formatBatchMoney(amount) {
    var num = parseFloat(amount);
    if (isNaN(num)) {
        num = 0;
    }
    return 'MWK ' + num.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}

function roundToTwo(num) {
    var n = parseFloat(num);
    if (isNaN(n)) {
        return 0;
    }
    return Math.round(n * 100) / 100;
}

function updateBatchAllocationTotals() {
    var totalDeposited = parseBatchAmount($('#batch-total-amount').val());
    var allocated = 0;
    var hasExceededOutstanding = false;
    var exceededLoan = '';

    $('.batch-member-amount').each(function() {
        var currentAmount = parseBatchAmount($(this).val());
        var outstanding = parseBatchAmount($(this).data('outstanding'));
        if (currentAmount > outstanding + 0.01) {
            hasExceededOutstanding = true;
            exceededLoan = $(this).data('loan-number');
        }
        allocated += currentAmount;
    });

    allocated = roundToTwo(allocated);
    totalDeposited = roundToTwo(totalDeposited);

    $('#batch-allocated-total').text(formatBatchMoney(allocated));
    $('#batch-total-deposited-display').text(formatBatchMoney(totalDeposited));

    var diff = roundToTwo(totalDeposited - allocated);
    $('#batch-allocation-difference').text(formatBatchMoney(diff));

    var submitButton = $('#submit_group_batch_payment');
    var alertBox = $('#batch-sum-alert');
    var canSubmit = true;

    if (hasExceededOutstanding) {
        alertBox.text('Amount entered for loan ' + exceededLoan + ' exceeds its outstanding balance.').show();
        canSubmit = false;
    } else if (totalDeposited <= 0) {
        alertBox.text('Please enter the total deposited amount.').show();
        canSubmit = false;
    } else if (Math.abs(diff) > 0.01) {
        alertBox.text('Allocated repayment amount must equal total deposited amount.').show();
        canSubmit = false;
    } else if (allocated <= 0) {
        alertBox.text('Enter at least one member allocation amount.').show();
        canSubmit = false;
    } else {
        alertBox.hide();
    }

    if (canSubmit) {
        submitButton.prop('disabled', false).show();
    } else {
        submitButton.prop('disabled', true).hide();
    }
}

function openGroupEditModal() {
    $('#group_edit_modal').modal('show');
}

function showBatchStatus(message, type) {
    var box = $('#batch-status-alert');
    box.removeClass('alert-info alert-success alert-danger alert-warning');
    box.addClass(type || 'alert-info');
    box.text(message || '').show();
}

function doSubmitBatchPayment() {
    updateBatchAllocationTotals();
    $('#batch-debug-alert').hide();
    showBatchStatus('Submitting batch payment...', 'alert-info');

    var submitButton = $('#submit_group_batch_payment');
    if (submitButton.prop('disabled')) {
        var reason = $('#batch-sum-alert').is(':visible') ? $('#batch-sum-alert').text() : 'Submission is blocked by validation.';
        showBatchStatus(reason, 'alert-danger');
        return;
    }

    var originalText = submitButton.text();
    submitButton.prop('disabled', true).text('Processing batch payment...');

    $.ajax({
        url: '<?php echo base_url("loan/batch_pay"); ?>',
        type: 'POST',
        data: $('#group_batch_payment_form').serialize(),
        dataType: 'text',
        success: function(rawResponse) {
            var response = null;
            try {
                response = JSON.parse(rawResponse);
            } catch (parseErr) {
                $('#batch-debug-alert').text('Raw server response: ' + rawResponse).show();
                showBatchStatus('Server returned invalid JSON response.', 'alert-danger');
                submitButton.prop('disabled', false).text(originalText);
                return;
            }

            if (response && response.success) {
                showBatchStatus(response.message || 'Batch payment posted successfully.', 'alert-success');
                if (response.receipt_url) {
                    window.open(response.receipt_url, '_blank');
                }
                setTimeout(function() {
                    $('#group_batch_payment_modal').modal('hide');
                    if (response.redirect_url) {
                        window.location.href = response.redirect_url;
                    } else {
                        window.location.reload();
                    }
                }, 700);
            } else {
                var msg = (response && response.message) ? response.message : 'Unknown server error.';
                $('#batch-debug-alert').text('Server response: ' + msg).show();
                showBatchStatus(msg, 'alert-danger');
                submitButton.prop('disabled', false).text(originalText);
            }
        },
        error: function(xhr) {
            var details = 'Failed to post batch payment. Please try again.';
            if (xhr && xhr.responseText) {
                details += ' Server says: ' + xhr.responseText;
                $('#batch-debug-alert').text(details).show();
            }
            showBatchStatus(details, 'alert-danger');
            submitButton.prop('disabled', false).text(originalText);
        }
    });
}

function openBatchPaymentModal() {
    var now = new Date();
    var localNow = now.getFullYear() + '-' +
        String(now.getMonth() + 1).padStart(2, '0') + '-' +
        String(now.getDate()).padStart(2, '0') + 'T' +
        String(now.getHours()).padStart(2, '0') + ':' +
        String(now.getMinutes()).padStart(2, '0');

    $('#group_batch_payment_form')[0].reset();
    $('#group_batch_pdate').val(localNow);
    $('#group_batch_ref_feedback').html('');
    $('#group_batch_payment_reference').css('border-color', '');
    $('#group_batch_payment_type').val('');
    updateRefLabel('group_batch_payment_type','group_batch_ref_label','group_batch_payment_reference');
    $('#batch-debug-alert').hide();
    $('#batch-status-alert').hide();
    updateBatchAllocationTotals();
    $('#group_batch_payment_modal').modal('show');
}

var batchCalcInterval = null;

$(document).ready(function() {
    updateRefLabel('batch_payment_type','batch_ref_label','batch_payment_reference');
    updateRefLabel('batch_payoff_type','batch_payoff_ref_label','batch_payoff_reference');
    updateRefLabel('group_batch_payment_type','group_batch_ref_label','group_batch_payment_reference');

    $('#batch-total-amount').on('input keyup change blur', function() {
        var clean = this.value.replace(/[^\d.,]/g, '');
        this.value = clean;
        updateBatchAllocationTotals();
    });

    $(document).on('input keyup change blur', '.batch-member-amount', function() {
        updateBatchAllocationTotals();
    });

    // Prevent any accidental native form submission
    $('#group_batch_payment_form').on('submit', function(e) {
        e.preventDefault();
        doSubmitBatchPayment();
    });

    $('#group_batch_payment_modal').on('shown.bs.modal', function() {
        updateBatchAllocationTotals();
        if (batchCalcInterval) {
            clearInterval(batchCalcInterval);
        }
        batchCalcInterval = setInterval(function() {
            updateBatchAllocationTotals();
        }, 700);
    });

    $('#group_batch_payment_modal').on('hidden.bs.modal', function() {
        if (batchCalcInterval) {
            clearInterval(batchCalcInterval);
            batchCalcInterval = null;
        }
    });
});

</script>
