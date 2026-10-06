<?php
$users = get_all('employees');
$products = get_all('loan_products');
$branches = get_all('branches');
?>

<div class="main-content">
    <div class="page-header">
        <h2 class="header-title">Loan Disbursement Report</h2>
        <div class="header-sub-title">
            <nav class="breadcrumb breadcrumb-dash">
                <a href="<?php echo base_url('Admin'); ?>" class="breadcrumb-item">
                    <i class="anticon anticon-home m-r-5"></i>Home
                </a>
                <a class="breadcrumb-item" href="<?php echo base_url('report'); ?>">Reports</a>
                <span class="breadcrumb-item active">Loan Disbursement</span>
            </nav>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h4 class="card-title"><i class="fas fa-filter text-primary"></i> Report Filters</h4>
        </div>
        <div class="card-body">
            <form action="<?php echo base_url('loan/loan_disbursement_filter'); ?>" method="post" class="form">
                <div class="row">
                    <div class="col-md-6 col-lg-4">
                        <div class="form-group">
                            <label class="font-weight-semibold" for="branch">Branch:</label>
                            <select name="branch" id="branch" class="form-control select2">
                                <option value="All">All Branches</option>
                                <?php foreach ($branches as $branch) { ?>
                                    <option value="<?php echo $branch->Code; ?>"><?php echo $branch->BranchName; ?></option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-4">
                        <div class="form-group">
                            <label class="font-weight-semibold" for="user">Loan Officer:</label>
                            <select name="officer" id="user" class="form-control select2">
                                <option value="All">All Officers</option>
                                <?php foreach ($users as $user) { ?>
                                    <option value="<?php echo $user->id; ?>"><?php echo $user->Firstname . ' ' . $user->Lastname; ?></option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-4">
                        <div class="form-group">
                            <?php $officer_select_id = 'user'; $this->load->view('reports/_relationship_supervisor_filter'); ?>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-4">
                        <div class="form-group">
                            <label class="font-weight-semibold" for="product">Loan Product:</label>
                            <select name="productid" id="product" class="form-control select2">
                                <option value="All">All Products</option>
                                <?php foreach ($products as $product) { ?>
                                    <option value="<?php echo $product->loan_product_id; ?>"><?php echo $product->product_name; ?></option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-4">
                        <div class="form-group">
                            <label class="font-weight-semibold" for="status">Loan Status:</label>
                            <select name="status" id="status" class="form-control select2">
                                <option value="All">All Statuses</option>
                                <option value="ACTIVE">Active</option>
                                <option value="CLOSED">Closed</option>
                                <option value="WRITTEN_OFF">Written Off</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-4">
                        <div class="form-group">
                            <label class="font-weight-semibold" for="date_from">Disbursement Date From:</label>
                            <div class="input-affix m-b-10">
                                <i class="prefix-icon anticon anticon-calendar"></i>
                                <input type="date" class="form-control" name="date_from" id="date_from">
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-4">
                        <div class="form-group">
                            <label class="font-weight-semibold" for="date_to">Disbursement Date To:</label>
                            <div class="input-affix m-b-10">
                                <i class="prefix-icon anticon anticon-calendar"></i>
                                <input type="date" class="form-control" name="date_to" id="date_to">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-group text-right">
                    <button type="submit" class="btn btn-primary">
                        <i class="anticon anticon-file-search m-r-5"></i>Generate Report
                    </button>
                    <button type="reset" class="btn btn-secondary m-l-5">
                        <i class="anticon anticon-reload m-r-5"></i>Reset
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
