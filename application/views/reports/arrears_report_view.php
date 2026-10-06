<style>
    .report-header {
        background-color: #f8f9fa;
        padding: 15px;
        border-radius: 5px;
        margin-bottom: 20px;
        border-left: 4px solid #3498db;
    }
    .page-title {
        border-bottom: 2px solid #3498db;
        padding-bottom: 10px;
        margin-bottom: 20px;
    }
    .export-buttons {
        margin-bottom: 15px;
    }
    .table th {
        background-color: #f2f2f2;
        font-weight: bold;
    }
    .table-hover tbody tr:hover {
        background-color: #f5f5f5;
    }
    .badge-arrears {
        font-size: 85%;
        padding: 5px 8px;
    }
    .badge-danger {
        background-color: #e74c3c;
    }
    .badge-warning {
        background-color: #f39c12;
    }
    .badge-info {
        background-color: #3498db;
    }
</style>
<div class="main-content">
    <div class="page-header">
        <h2 class="header-title">All Loan Applications</h2>
        <div class="header-sub-title">
            <nav class="breadcrumb breadcrumb-dash">
                <a href="<?php echo base_url('Admin')?>" class="breadcrumb-item"><i class="anticon anticon-home m-r-5"></i>Home</a>
                <a class="breadcrumb-item" href="#">-</a>
                <span class="breadcrumb-item active">All loans Applied</span>
            </nav>
        </div>
    </div>
    <div class="card">
        <div class="card-body" style="border: thick #153505 solid;border-radius: 14px;">
    <div class="row">
        <div class="col-md-12">
            <h1 class="page-title">Arrears Report</h1>

            <div class="report-header">
                <div class="row">
                    <div class="col-md-6">
                        <h5>Report Parameters:</h5>
                        <p><strong>Date Range:</strong> <?php echo $date_range_display; ?></p>
                        <p><strong>Loan Officer:</strong> <?php echo $officer_name; ?></p>
                    </div>
                    <div class="col-md-6 text-right">
                        <p><strong>Generated on:</strong> <?php echo date('d M Y H:i:s'); ?></p>
                    </div>
                </div>
            </div>

            <div class="export-buttons text-right">

                <a href="<?php echo site_url('arrears_report'); ?>" class="btn btn-secondary">
                    <i class="fa fa-filter"></i> Change Filters
                </a>
            </div>

            <?php if (empty($loans_in_arrears)): ?>
                <div class="alert alert-info">
                    <i class="fa fa-info-circle"></i> No loans in arrears found for the selected criteria.
                </div>
            <?php else: ?>
                <!-- Summary stats -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <h5 class="card-title">Total Loans in Arrears</h5>
                                <h2 class="card-text"><?php echo count($loans_in_arrears); ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-danger text-white">
                            <div class="card-body">
                                <h5 class="card-title">Total Arrears Amount</h5>
                                <h2 class="card-text">K<?php echo number_format(array_sum(array_column($loans_in_arrears, 'total_arrears')), 2); ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body">
                                <h5 class="card-title">Average Days in Arrears</h5>
                                <h2 class="card-text"><?php echo round(array_sum(array_column($loans_in_arrears, 'arrear_days')) / count($loans_in_arrears)); ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <h5 class="card-title">Total Disbursed</h5>
                                <h2 class="card-text">K<?php echo number_format(array_sum(array_column($loans_in_arrears, 'amount_disbursed')), 2); ?></h2>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Loans table -->
                <div class="table-responsive">
                    <table class="table  table-bordered table-hover" id="data-table-arrears">
                        <thead>
                        <tr>

                            <th>Branch Name</th>
                            <th>Loan No.</th>
                            <th>Loan Product.</th>
                            <th>Client Name</th>
                            <th>Group Name</th>
                            <th>Amount Disbursed(MWK)</th>
                            <th>Charges(loan cover, admin fee, interest, late fees)</th>
                            <th>Term</th>
                            <th>Repayment Frequency</th>
                            <th>Repayment Amount (MWK)</th>
                            <th>Due Date</th>
                            <th>No. of Missed Payments</th>
                            <th>Total Arrears</th>
                            <th>Last Transaction Date</th>
                            <th>Arrears Days</th>
                            <th>Loan Officer</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($loans_in_arrears as $loan): ?>
                            <tr>
                                <td><?php echo $loan['BranchName']; ?></td>
                                <td><?php echo $loan['loan_number']; ?></td>
                                <td><?php echo $loan['product_name']; ?></td>
                                <td><?php echo $loan['client_name']; ?></td>
                                <td><?php echo $loan['group_name']; ?></td>
                                <td class="text-right"><?php echo number_format($loan['amount_disbursed'], 2); ?></td>
                                <td class="text-right"><?php echo number_format($loan['loan_charges'], 2); ?></td>
                                <td><?php echo $loan['term']; ?></td>
                                <td><?php echo $loan['repayment_frequency']; ?></td>
                                <td class="text-right"><?php echo number_format($loan['repayment_amount'], 2); ?></td>
                                <td><?php echo date('d M Y', strtotime($loan['due_date'])); ?></td>
                                <td class="text-center"><?php echo $loan['num_missed_payments']; ?></td>
                                <td class="text-right"><?php echo number_format($loan['total_arrears'], 2); ?></td>
                                <td><?php echo $loan['last_transaction_date'] ? date('d M Y', strtotime($loan['last_transaction_date'])) : 'None'; ?></td>
                                <td class="text-center">
                                    <?php
                                    $arrear_days = $loan['arrear_days'];
                                    $badge_class = 'badge-info';

                                    if ($arrear_days > 90) {
                                        $badge_class = 'badge-danger';
                                    } elseif ($arrear_days > 30) {
                                        $badge_class = 'badge-warning';
                                    }
                                    ?>
                                    <span class="badge <?php echo $badge_class; ?> badge-arrears"><?php echo $arrear_days; ?> days</span>
                                </td>
                                <td><?php echo $loan['officer_name']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
        </div>
    </div>
</div>






<!-- Print-specific styles -->
<style type="text/css" media="print">
    .export-buttons, .dataTables_filter, .dataTables_length, .dataTables_paginate, .dataTables_info {
        display: none !important;
    }
    .container-fluid {
        width: 100% !important;
        max-width: none !important;
    }
    table {
        font-size: 12px !important;
    }
    .badge {
        border: 1px solid #000 !important;
    }
</style>
