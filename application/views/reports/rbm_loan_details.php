<?php
$employees1 = get_all('employees');
?>
<div class="main-content">
    <div class="page-header">
        <h2 class="header-title">RBM Loan Classification Details</h2>
        <div class="header-sub-title">
            <nav class="breadcrumb breadcrumb-dash">
                <a href="<?php echo base_url('Admin')?>" class="breadcrumb-item"><i class="anticon anticon-home m-r-5"></i>Home</a>
                <a class="breadcrumb-item" href="<?php echo base_url('report'); ?>">Reports</a>
                <a class="breadcrumb-item" href="<?php echo base_url('reports/rbm_risk_management'); ?>">RBM Risk Management</a>
                <span class="breadcrumb-item active">Loan Details</span>
            </nav>
        </div>
    </div>

    <?php if ($this->session->flashdata('success')): ?>
        <div class="alert alert-success">
            <?php echo $this->session->flashdata('success'); ?>
        </div>
    <?php endif; ?>

    <?php if ($this->session->flashdata('error')): ?>
        <div class="alert alert-danger">
            <?php echo $this->session->flashdata('error'); ?>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Loan Information</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="alert alert-<?php echo get_rbm_classification_color($rbm_classification); ?>">
                                <strong>RBM Classification:</strong> <?php echo $rbm_classification; ?> (<?php echo $days_in_arrears; ?> days in arrears)
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-bordered">
                                <tr>
                                    <th>Loan Number</th>
                                    <td><?php echo $loan->loan_number; ?></td>
                                </tr>
                                <tr>
                                    <th>Client Name</th>
                                    <td><?php echo $customer_name; ?></td>
                                </tr>
                                <tr>
                                    <th>Product</th>
                                    <td><?php echo $loan->product_name; ?></td>
                                </tr>
                                <tr>
                                    <th>Status</th>
                                    <td><?php echo $loan->loan_status; ?></td>
                                </tr>
                                <tr>
                                    <th>Classification</th>
                                    <td><?php echo $rbm_classification; ?></td>
                                </tr>
                                <tr>
                                    <th>Officer Assigned</th>
                                    <td><?php
                                        $officer = $this->Employees_model->get_by_id($loan->loan_added_by);
                                        echo $officer ? $officer->Firstname . ' ' . $officer->Lastname : 'N/A';
                                        ?></td>
                                </tr>
                                <tr>
                                    <th>Risk Officer</th>
                                    <td><?php echo $risk_officer ? $risk_officer->Firstname . ' ' . $risk_officer->Lastname : 'Not assigned'; ?></td>
                                </tr>
                            </table>
                        </div>

                        <div class="col-md-6">
                            <table class="table table-bordered">
                                <tr>
                                    <th>Loan Amount</th>
                                    <td><?php echo number_format($loan->loan_principal, 2); ?></td>
                                </tr>
                                <tr>
                                    <th>Advanced Principal Balance</th>
                                    <td><?php echo number_format($loan->loan_principal - ($loan->amount_paid ?? 0), 2); ?></td>
                                </tr>
                                <tr>
                                    <th>Interest</th>
                                    <td><?php echo number_format($loan->loan_interest_amount, 2); ?></td>
                                </tr>
                                <tr>
                                    <th>Total Loan + Interest</th>
                                    <td><?php echo number_format($loan->loan_amount_total, 2); ?></td>
                                </tr>
                                <tr>
                                    <th>Principal Loan Balance</th>
                                    <td><?php echo number_format($loan->loan_principal - ($loan->amount_paid ?? 0), 2); ?></td>
                                </tr>
                                <tr>
                                    <th>Interest Loan Charges</th>
                                    <td><?php echo number_format($loan->loan_interest_amount, 2); ?></td>
                                </tr>
                                <tr>
                                    <th>Last Payment Date</th>
                                    <td><?php echo $loan->last_payment_date ? date('Y-m-d', strtotime($loan->last_payment_date)) : 'No payments'; ?></td>
                                </tr>
                                <tr>
                                    <th>Amount Paid</th>
                                    <td><?php echo number_format($loan->amount_paid ?? 0, 2); ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Collateral Information</h4>
                </div>
                <div class="card-body">
                    <?php if (empty($collaterals)): ?>
                        <div class="alert alert-info">No collaterals recorded for this loan</div>
                    <?php else: ?>
                        <table class="table table-bordered">
                            <thead>
                            <tr>
                                <th>Type</th>
                                <th>Description</th>
                                <th>Value</th>
                                <th>Status</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php $total_collateral = 0; ?>
                            <?php foreach ($collaterals as $collateral): ?>
                                <tr>
                                    <td><?php echo $collateral->type; ?></td>
                                    <td><?php echo $collateral->description; ?></td>
                                    <td><?php echo number_format($collateral->value, 2); ?></td>
                                    <td><?php echo $collateral->status; ?></td>
                                </tr>
                                <?php $total_collateral += $collateral->value; ?>
                            <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                            <tr>
                                <th colspan="2">Total Collateral Amount</th>
                                <th colspan="2"><?php echo number_format($total_collateral, 2); ?></th>
                            </tr>
                            </tfoot>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Risk Management</h4>
                </div>
                <div class="card-body">
                    <form action="<?php echo base_url('reports/assign_risk_officer'); ?>" method="post">
                        <input type="hidden" name="loan_id" value="<?php echo $loan->loan_id; ?>">

                        <div class="form-group">
                            <label for="risk_officer_id">Assign Risk Officer</label>
                            <select name="risk_officer_id" id="risk_officer_id" class="form-control">
                                <option value="">-- Select Risk Officer --</option>
                                <?php foreach ($employees1 as $employee): ?>
                                    <option value="<?php echo $employee->id; ?>">
                                        <?php echo $employee->Firstname . ' ' . $employee->Lastname; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="risk_comments">Risk Comments</label>
                            <textarea name="risk_comments" id="risk_comments" class="form-control" rows="5"><?php echo $loan->risk_comments ?? ''; ?></textarea>
                        </div>

                        <div class="form-group">
                            <div class="checkbox">
                                <input type="checkbox" name="write_off_recommendation" id="write_off_recommendation" value="1" <?php echo ($loan->write_off_recommendation ?? 0) ? 'checked' : ''; ?>>
                                <label for="write_off_recommendation">Recommend for Write-Off</label>
                            </div>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">Save Risk Information</button>
                            <a href="<?php echo base_url('reports/rbm_risk_management'); ?>" class="btn btn-default">Back to List</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Helper function to get color based on RBM classification
function get_rbm_classification_color($classification) {
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