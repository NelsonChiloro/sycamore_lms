<?php
$transaction_types = get_all('transaction_type');
$employees = get_all('employees');
$products = get_all('loan_products');
$branches= get_all('branches');
?>
<?php
// This is the view file that will show the payment report filter form
// It should be placed in your application/views/reports/ directory
?>
<div class="main-content">
    <div class="page-header">
        <h2 class="header-title">Payment Transactions Report</h2>
        <div class="header-sub-title">
            <nav class="breadcrumb breadcrumb-dash">
                <a href="<?php echo base_url('Admin')?>" class="breadcrumb-item"><i class="anticon anticon-home m-r-5"></i>Home</a>
                <a class="breadcrumb-item" href="#">Reports</a>
                <span class="breadcrumb-item active">Payment Transactions Report</span>
            </nav>
        </div>
    </div>
    <div class="card">
        <div class="card-body" style="border: thick #153505 solid;border-radius: 14px; padding: 2em;">
            <form action="<?php echo base_url('reports/payments_filter') ?>" method="post">
                <fieldset>
                    <legend>Report Filter</legend>
                    <div id="controlgroup" class="p-3">
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label>Branch:</label>
                                <select name="branch" class="form-control select2">
                                    <option value="">All Branches</option>
                                    <?php foreach ($branches as $branch): ?>
                                        <option value="<?php echo $branch->id; ?>"><?php echo $branch->BranchName; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-3 mb-3">
                                <label>Transaction Type:</label>
                                <select name="transaction_type_id" class="form-control select2">
                                    <option value="">All Transaction Types</option>
                                    <?php foreach ($transaction_types as $type): ?>
                                        <option value="<?php echo $type->transaction_type_id; ?>"><?php echo $type->name; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-3 mb-3">
                                <label>Loan Product:</label>
                                <select name="product" class="form-control select2">
                                    <option value="">All Products</option>
                                    <?php foreach ($products as $product): ?>
                                        <option value="<?php echo $product->loan_product_id; ?>"><?php echo $product->product_name; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-3 mb-3">
                                <label>Loan Officer:</label>
                                <select name="officer" class="form-control select2">
                                    <option value="">All Officers</option>
                                    <?php foreach ($employees as $employee): ?>
                                        <option value="<?php echo $employee->id; ?>"><?php echo $employee->Firstname . " " . $employee->Lastname; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label>Date From:</label>
                                <input type="text" class="form-control dpicker" name="from" placeholder="YYYY-MM-DD">
                            </div>

                            <div class="col-md-3 mb-3">
                                <label>Date To:</label>
                                <input type="text" class="form-control dpicker" name="to" placeholder="YYYY-MM-DD">
                            </div>

                            <div class="col-md-6 mb-3 d-flex align-items-end">
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

            <div class="alert alert-info mt-3">
                <i class="anticon anticon-info-circle"></i>
                <span>Select your filter criteria and click "Generate Report" to create a payment transactions report. The report will be processed in the background and will be available on the reports page when completed.</span>
            </div>

            <div class="alert alert-warning mt-3">
                <i class="anticon anticon-warning"></i>
                <span>Large date ranges may take longer to process. Please be patient while your report is being generated.</span>
            </div>
        </div>
    </div>
</div>

