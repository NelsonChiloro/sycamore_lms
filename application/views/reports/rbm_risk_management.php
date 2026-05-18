<style>
    .classification-legend {
        margin-bottom: 10px;
    }

    .classification-legend .badge {
        margin-right: 10px;
        padding: 5px 10px;
    }

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
        <h2 class="header-title">RBM Risk Management</h2>
        <div class="header-sub-title">
            <nav class="breadcrumb breadcrumb-dash">
                <a href="<?php echo base_url('Admin')?>" class="breadcrumb-item"><i class="anticon anticon-home m-r-5"></i>Home</a>
                <a class="breadcrumb-item" href="<?php echo base_url('report'); ?>">Reports</a>
                <span class="breadcrumb-item active">RBM Risk Management</span>
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

    <div class="card">
        <div class="card-header">
            <h4 class="card-title">Loans by RBM Classification</h4>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-12">
                    <div class="classification-legend">
                        <span class="badge badge-success">Standard</span>
                        <span class="badge badge-warning">Special Mention</span>
                        <span class="badge badge-info">Substandard</span>
                        <span class="badge badge-warning">Doubtful</span>
                        <span class="badge badge-danger">Loss</span>
                    </div>
                </div>
            </div>

            <table id="data-table" class="table table-bordered table-hover">
                <thead>
                <tr>
                    <th>Loan #</th>
                    <th>Client</th>
                    <th>Product</th>
                    <th>Status</th>
                    <th>Classification</th>
                    <th>Officer</th>
                    <th>Risk Officer</th>
                    <th>Amount</th>
                    <th>Principal Balance</th>
                    <th>Days in Arrears</th>
                    <th>Write-Off</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($loans as $loan):
                    // Get customer name
                    if ($loan->customer_type == 'individual') {
                        $customer = $this->Individual_customers_model->get_by_id($loan->loan_customer);
                        $customer_name = $customer->Firstname . ' ' . $customer->Lastname;
                    } else {
                        $group = $this->Groups_model->get_by_id($loan->loan_customer);
                        $customer_name = $group->group_name . ' (' . $group->group_code . ')';
                    }

                    // Determine RBM classification
                    $rbm_classification = determine_rbm_classification($loan->days_in_arrears ?? 0);

                    // Calculate outstanding principal
                    $principal_balance = $loan->loan_principal - calculate_paid_principal($loan->loan_id);
                    ?>
                    <tr class="classification-<?php echo strtolower(str_replace(' ', '-', $rbm_classification)); ?>">
                        <td><?php echo $loan->loan_number; ?></td>
                        <td><?php echo $customer_name; ?></td>
                        <td><?php echo $loan->product_name; ?></td>
                        <td><?php echo $loan->loan_status; ?></td>
                        <td>
                                <span class="badge badge-<?php echo get_rbm_classification_color($rbm_classification); ?>">
                                    <?php echo $rbm_classification; ?>
                                </span>
                        </td>
                        <td><?php echo $loan->officer_firstname . ' ' . $loan->officer_lastname; ?></td>
                        <td>
                            <?php if ($loan->risk_officer_firstname): ?>
                                <?php echo $loan->risk_officer_firstname . ' ' . $loan->risk_officer_lastname; ?>
                            <?php else: ?>
                                <span class="text-muted">Not assigned</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo number_format($loan->loan_principal, 2); ?></td>
                        <td><?php echo number_format($principal_balance, 2); ?></td>
                        <td><?php echo $loan->days_in_arrears ?? 0; ?></td>
                        <td>
                            <?php if ($loan->write_off_recommendation): ?>
                                <span class="badge badge-danger">Recommended</span>
                            <?php else: ?>
                                <span class="badge badge-secondary">No</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="<?php echo base_url('reports/rbm_loan_details/' . $loan->loan_id); ?>" class="btn btn-primary btn-sm">
                                <i class="anticon anticon-eye"></i> View
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
// Helper function to determine RBM classification
function determine_rbm_classification($days_in_arrears) {
    if ($days_in_arrears < 30) {
        return 'Standard';
    } else if ($days_in_arrears >= 30 && $days_in_arrears < 60) {
        return 'Special Mention';
    } else if ($days_in_arrears >= 60 && $days_in_arrears < 90) {
        return 'Substandard';
    } else if ($days_in_arrears >= 90 && $days_in_arrears < 180) {
        return 'Doubtful';
    } else {
        return 'Loss';
    }
}

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

// Helper function to calculate paid principal
function calculate_paid_principal($loan_id) {
    $CI =& get_instance();

    $CI->db->select('SUM(principal) as paid_principal');
    $CI->db->from('payement_schedules');
    $CI->db->where('loan_id', $loan_id);
    $CI->db->where('status', 'PAID');

    $query = $CI->db->get();
    $result = $query->row();

    return $result ? $result->paid_principal : 0;
}
?>


