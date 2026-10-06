<?php
$transaction_types = get_all('transaction_type');
$employees = get_all('employees');
$products = get_all('loan_products');
$branches= get_all('branches');
?>
<div class="main-content">
    <div class="page-header">
        <h2 class="header-title">RBM Loan Classification Report</h2>
        <div class="header-sub-title">
            <nav class="breadcrumb breadcrumb-dash">
                <a href="<?php echo base_url('Admin')?>" class="breadcrumb-item"><i class="anticon anticon-home m-r-5"></i>Home</a>
                <a class="breadcrumb-item" href="#">Reports</a>
                <span class="breadcrumb-item active">RBM Loan Classification Report</span>
            </nav>
        </div>
    </div>
    <div class="card">
        <div class="card-body" style="border: thick #153505 solid;border-radius: 14px;">
            <div class="alert alert-info">
                <h5><i class="anticon anticon-info-circle"></i> About This Report</h5>
                <p>This report classifies loans according to the Reserve Bank of Malawi (RBM) regulations based on the number of days in arrears:</p>
                <ul>
                    <li><strong>Standard:</strong> 0-29 days in arrears (1% provision)</li>
                    <li><strong>Special Mention:</strong> 30-59 days in arrears (3% provision)</li>
                    <li><strong>Substandard:</strong> 60-89 days in arrears (20% provision)</li>
                    <li><strong>Doubtful:</strong> 90-179 days in arrears (50% provision)</li>
                    <li><strong>Loss:</strong> 180+ days in arrears (100% provision)</li>
                </ul>
            </div>

            <form action="<?php echo base_url('reports/rbm_classification_filter') ?>" method="post">
                <fieldset>
                    <legend>Report Filter</legend>
                    <div id="controlgroup">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label>Branch:</label>
                                <select name="branch" class="form-control select2">
                                    <option value="All">All Branches</option>
                                    <?php foreach ($branches as $branch): ?>
                                        <option value="<?php echo $branch->id; ?>"><?php echo $branch->BranchName; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label>Loan Product:</label>
                                <select name="product" class="form-control select2">
                                    <option value="All">All Products</option>
                                    <?php foreach ($products as $product): ?>
                                        <option value="<?php echo $product->loan_product_id; ?>"><?php echo $product->product_name; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label>Loan Officer:</label>
                                <select name="officer" class="form-control select2">
                                    <option value="All">All Officers</option>
                                    <?php foreach ($employees as $employee): ?>
                                        <option value="<?php echo $employee->id; ?>"><?php echo $employee->Firstname . " " . $employee->Lastname; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12 mb-3 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary" style="background-color: #153505; border-color: #153505;">
                                    <i class="anticon anticon-filter"></i> Generate Report
                                </button>

                                <a href="<?php echo base_url('report'); ?>" class="btn btn-default ml-2">
                                    <i class="anticon anticon-rollback"></i> Back to Reports
                                </a>
                            </div>
                        </div>
                    </div>
                </fieldset>
            </form>

            <hr>

            <div class="alert alert-warning mt-3">
                <i class="anticon anticon-warning"></i>
                <span>This report analyzes all active loans and may take longer to process for large portfolios. Please be patient while your report is being generated.</span>
            </div>
        </div>
    </div>
</div>

