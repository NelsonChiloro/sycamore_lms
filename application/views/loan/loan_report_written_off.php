<?php
// Get data for dropdowns
$users = get_all('employees');
$products = get_all('loan_products');
$branches = get_all('branches');
?>

<div class="main-content">
    <div class="page-header">
        <h2 class="header-title">Loan Portfolio Written Off Report</h2>
        <div class="header-sub-title">
            <nav class="breadcrumb breadcrumb-dash">
                <a href="<?php echo base_url('Admin')?>" class="breadcrumb-item">
                    <i class="anticon anticon-home m-r-5"></i>Home
                </a>
                <a class="breadcrumb-item" href="<?php echo base_url('report')?>">Reports</a>
                <span class="breadcrumb-item active">Loan Portfolio</span>
            </nav>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h4 class="card-title">
                <i class="fas fa-filter text-primary"></i> Report Filters
            </h4>
        </div>
        <div class="card-body">
            <form action="<?php echo base_url('loan/portfolio_filter_write_off') ?>" method="post" class="form">
                <div class="row">
                    <!-- Branch Filter -->
                    <div class="col-md-6 col-lg-4">
                        <div class="form-group">
                            <label class="font-weight-semibold" for="branch">Branch:</label>
                            <select name="branch" id="branch" class="form-control select2">
                                <option value="All">All Branches</option>
                                <?php foreach ($branches as $branch) { ?>
                                    <option value="<?php echo $branch->Code; ?>" <?php if($this->input->get('branch') == $branch->Code) echo "selected"; ?>>
                                        <?php echo $branch->BranchName; ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>

                    <!-- Loan Officer Filter -->
                    <div class="col-md-6 col-lg-4">
                        <div class="form-group">
                            <label class="font-weight-semibold" for="user">Loan Officer:</label>
                            <select name="officer" id="user" class="form-control select2">
                                <option value="All">All Officers</option>
                                <?php foreach ($users as $user) { ?>
                                    <option value="<?php echo $user->id; ?>" <?php if($user->id == $this->input->get('user')) echo 'selected'; ?>>
                                        <?php echo $user->Firstname . " " . $user->Lastname; ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-4">
                        <div class="form-group">
                            <?php $officer_select_id = 'user'; $this->load->view('reports/_relationship_supervisor_filter'); ?>
                        </div>
                    </div>

                    <!-- Loan Product Filter -->
                    <div class="col-md-6 col-lg-4">
                        <div class="form-group">
                            <label class="font-weight-semibold" for="product">Loan Product:</label>
                            <select name="productid" id="product" class="form-control select2">
                                <option value="All">All Products</option>
                                <?php foreach ($products as $product) { ?>
                                    <option value="<?php echo $product->loan_product_id; ?>" <?php if($product->loan_product_id == $this->input->get('product')) echo 'selected'; ?>>
                                        <?php echo $product->product_name; ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>

                    <!-- Loan Status Filter -->
                    <div class="col-md-6 col-lg-4">
                        <div class="form-group">
                            <label class="font-weight-semibold" for="status">Loan Status:</label>
                            <select name="status" id="status" class="form-control">
                                <option value="All">All Statuses</option>

                                <option value="WRITTEN_OFF" <?php if($this->input->get('status') == "WRITTEN_OFF") echo 'selected'; ?>>Written Off</option>
                            </select>
                        </div>
                    </div>

                    <!-- Date Range Filter -->
                    <div class="col-md-6 col-lg-4">
                        <div class="form-group">
                            <label class="font-weight-semibold" for="date_from">Date From:</label>
                            <div class="input-affix m-b-10">
                                <i class="prefix-icon anticon anticon-calendar"></i>
                                <input type="date" class="form-control datepicker-input" name="date_from" id="date_from" value="<?php echo $this->input->get('from'); ?>" placeholder="Start Date" >
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-4">
                        <div class="form-group">
                            <label class="font-weight-semibold" for="date_to">Date To:</label>
                            <div class="input-affix m-b-10">
                                <i class="prefix-icon anticon anticon-calendar"></i>
                                <input type="date" class="form-control datepicker-input" name="date_to" id="date_to" value="<?php echo $this->input->get('to'); ?>" placeholder="End Date" >
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-12">
                        <div class="form-group text-right">
                            <button type="submit" class="btn btn-primary">
                                <i class="anticon anticon-file-search m-r-5"></i>Generate Report
                            </button>

                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>


</div>