<style>
    /* Classification row styling */
    .classification-standard {
        background-color: rgba(40, 167, 69, 0.1);
    }

    .classification-special-mention {
        background-color: rgba(255, 193, 7, 0.1);
    }

    .classification-substandard {
        background-color: rgba(23, 162, 184, 0.1);
    }

    .classification-doubtful {
        background-color: rgba(255, 193, 7, 0.2);
    }

    .classification-loss {
        background-color: rgba(220, 53, 69, 0.1);
    }
</style>
<div class="main-content">
    <div class="page-header">
        <h2 class="header-title">Risk Recovery Report</h2>
        <div class="header-sub-title">
            <nav class="breadcrumb breadcrumb-dash">
                <a href="<?php echo base_url('Admin')?>" class="breadcrumb-item"><i class="anticon anticon-home m-r-5"></i>Home</a>
                <a class="breadcrumb-item" href="<?php echo base_url('report'); ?>">Reports</a>
                <span class="breadcrumb-item active">Risk Recovery Report</span>
            </nav>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h4 class="card-title">Risk Recovery Report Filters</h4>
        </div>
        <div class="card-body">
            <form method="get" action="<?php echo base_url('reports/risk_recovery_report'); ?>" class="form-inline">
                <div class="form-group mx-sm-3 mb-2">
                    <label for="risk_category" class="mr-2">Risk Category:</label>
                    <select name="risk_category" id="risk_category" class="form-control">
                        <option value="">All Categories</option>
                        <option value="Standard" <?php echo $this->input->get('risk_category') == 'Standard' ? 'selected' : ''; ?>>Standard</option>
                        <option value="Special_Mention" <?php echo $this->input->get('risk_category') == 'Special_Mention' ? 'selected' : ''; ?>>Special Mention</option>
                        <option value="Substandard" <?php echo $this->input->get('risk_category') == 'Substandard' ? 'selected' : ''; ?>>Substandard</option>
                        <option value="Doubtful" <?php echo $this->input->get('risk_category') == 'Doubtful' ? 'selected' : ''; ?>>Doubtful</option>
                        <option value="Loss" <?php echo $this->input->get('risk_category') == 'Loss' ? 'selected' : ''; ?>>Loss</option>
                    </select>
                </div>

                <div class="form-group mx-sm-3 mb-2">
                    <label for="officer" class="mr-2">Risk Officer:</label>
                    <select name="officer" id="officer" class="form-control">
                        <option value="">All Officers</option>
                        <?php foreach ($employees as $employee): ?>
                            <option value="<?php echo $employee->id; ?>" <?php echo $this->input->get('officer') == $employee->id ? 'selected' : ''; ?>>
                                <?php echo $employee->Firstname . ' ' . $employee->Lastname; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group mx-sm-3 mb-2">
                    <label for="branch" class="mr-2">Branch:</label>
                    <select name="branch" id="branch" class="form-control">
                        <option value="">All Branches</option>
                        <?php foreach ($branches as $branch): ?>
                            <option value="<?php echo $branch->id; ?>" <?php echo $this->input->get('branch') == $branch->id ? 'selected' : ''; ?>>
                                <?php echo $branch->BranchName; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group mx-sm-3 mb-2">
                    <label for="writeoff" class="mr-2">Write-off Status:</label>
                    <select name="writeoff" id="writeoff" class="form-control">
                        <option value="">All</option>
                        <option value="1" <?php echo $this->input->get('writeoff') == '1' ? 'selected' : ''; ?>>Recommended for Write-off</option>
                        <option value="0" <?php echo $this->input->get('writeoff') == '0' ? 'selected' : ''; ?>>Not Recommended</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary mb-2">Apply Filters</button>
                <a href="<?php echo base_url('reports/risk_recovery_report'); ?>" class="btn btn-default mb-2 ml-2">Clear Filters</a>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">

        </div>
        <div class="card-body">
            <!-- Summary cards -->
            <div class="row">
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="text-muted">Total Loans</h5>
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="h1 m-0"><?php echo $summary['total_count']; ?></div>
                                <div class="avatar avatar-icon avatar-cyan">
                                    <i class="anticon anticon-team"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="text-muted">Total Principal</h5>
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="h1 m-0"><?php echo number_format($summary['total_principal'], 2); ?></div>
                                <div class="avatar avatar-icon avatar-blue">
                                    <i class="anticon anticon-dollar"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="text-muted">Total Interest</h5>
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="h1 m-0"><?php echo number_format($summary['total_interest'], 2); ?></div>
                                <div class="avatar avatar-icon avatar-gold">
                                    <i class="anticon anticon-property-safety"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="text-muted">Write-off Recommendations</h5>
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="h1 m-0"><?php echo $summary['total_writeoffs']; ?></div>
                                <div class="avatar avatar-icon avatar-red">
                                    <i class="anticon anticon-warning"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main report table -->
            <div class="table-responsive mt-4">
                <table id="risk-recovery-table" class="table table-bordered table-hover">
                    <thead>
                    <tr>
                        <th>Loan #</th>
                        <th>Client Name</th>
                        <th>Product</th>
                        <th>Status</th>
                        <th>RBM Classification</th>
                        <th>Risk Officer</th>
                        <th>Loan Balance</th>
                        <th>Principal Balance</th>
                        <th>Interest Charges</th>
                        <th>Last Payment</th>
                        <th>Amount Paid</th>
                        <th>Days in Arrears</th>
                        <th>Collateral Value</th>
                        <th>Write-off Rec.</th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($loans)): ?>
                        <tr>
                            <td colspan="15" class="text-center">No loans found matching the criteria</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($loans as $loan): ?>
                            <tr class="classification-<?php echo strtolower(str_replace(' ', '-', $loan->rbm_classification)); ?>">
                                <td><?php echo $loan->loan_number; ?></td>
                                <td><?php echo $loan->customer_name; ?></td>
                                <td><?php echo $loan->product_name; ?></td>
                                <td><?php echo $loan->loan_status; ?></td>
                                <td>
                                        <span class="badge badge-<?php echo get_classification_color($loan->rbm_classification); ?>">
                                            <?php echo $loan->rbm_classification; ?>
                                        </span>
                                </td>
                                <td>
                                    <?php if ($loan->risk_officer_firstname): ?>
                                        <?php echo $loan->risk_officer_firstname . ' ' . $loan->risk_officer_lastname; ?>
                                    <?php else: ?>
                                        <span class="text-muted">Not assigned</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo number_format($loan->principal_balance + $loan->interest_balance, 2); ?></td>
                                <td><?php echo number_format($loan->principal_balance, 2); ?></td>
                                <td><?php echo number_format($loan->interest_balance, 2); ?></td>
                                <td><?php echo $loan->last_payment_date ? date('Y-m-d', strtotime($loan->last_payment_date)) : 'No payments'; ?></td>
                                <td><?php echo number_format($loan->amount_paid, 2); ?></td>
                                <td><?php echo $loan->days_in_arrears; ?></td>
                                <td><?php echo number_format($loan->collateral_total_value, 2); ?></td>
                                <td>
                                    <?php if ($loan->write_off_recommendation): ?>
                                        <span class="badge badge-danger">Yes</span>
                                    <?php else: ?>
                                        <span class="badge badge-secondary">No</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <a href="<?php echo base_url('reports/rbm_loan_details/' . $loan->loan_id); ?>" class="btn btn-primary btn-sm" data-toggle="tooltip" title="View Details">
                                            <i class="anticon anticon-eye"></i>
                                        </a>
                                        <button type="button" class="btn btn-info btn-sm collateral-btn" data-toggle="tooltip" title="View Collaterals" data-loan-id="<?php echo $loan->loan_id; ?>">
                                            <i class="anticon anticon-safety"></i>
                                        </button>
                                        <button type="button" class="btn btn-warning btn-sm comments-btn" data-toggle="tooltip" title="View Comments" data-comments="<?php echo htmlspecialchars($loan->risk_comments ?? 'No comments'); ?>">
                                            <i class="anticon anticon-message"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal for collateral details -->
<div class="modal fade" id="collateralModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Collateral Details</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <i class="anticon anticon-close"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                        <tr>
                            <th>Type</th>
                            <th>Description</th>
                            <th>Value</th>
                            <th>Status</th>
                        </tr>
                        </thead>
                        <tbody id="collateralTableBody">
                        <!-- Collateral items will be loaded here -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal for risk comments -->
<div class="modal fade" id="commentsModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Risk Officer Comments</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <i class="anticon anticon-close"></i>
                </button>
            </div>
            <div class="modal-body">
                <div id="commentsContent" class="p-3 bg-light rounded">
                    <!-- Comments will be loaded here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<?php
function get_classification_color($classification) {
    switch ($classification) {
        case 'Standard':
            return 'success';
        case 'Special Mention':
            return 'warning';
        case 'Substandard':
            return 'info';
        case 'Doubtful':
            return 'warning';
        case 'Loss':
            return 'danger';
        default:
            return 'secondary';
    }
}
?>

