<?php $users = get_all('employees'); $products = get_all('loan_products'); $branches = get_all('branches'); ?>
<div class="main-content">
    <div class="page-header">
        <h2 class="header-title">PAR Report</h2>
        <div class="header-sub-title">
            <nav class="breadcrumb breadcrumb-dash">
                <a href="<?php echo base_url('Admin')?>" class="breadcrumb-item"><i class="anticon anticon-home m-r-5"></i>Home</a>
                <a class="breadcrumb-item" href="#">-</a>
                <span class="breadcrumb-item active">Loan PAR report</span>
            </nav>
        </div>
    </div>
    <div class="card">
        <div class="card-body" style="border: thick #153505 solid;border-radius: 14px;">
            <form action="<?php echo base_url('reports/par_filter') ?>" method="post">
                <fieldset>
                    <legend>Report filter</legend>
                    <div id="controlgroup" class="p-3">
                        <div class="row">
                            <div class="col-md-3">
                                <label for="productid">Loan Product:</label>
                                <select name="productid" id="productid" class="select2 form-control">
                                    <option value="All">All Products</option>
                                    <?php foreach ($products as $product){ ?>
                                        <option value="<?php echo $product->loan_product_id; ?>"><?php echo $product->product_name; ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="officer">Officer:</label>
                                <select name="officer" id="officer" class="select2 form-control">
                                    <option value="All">All Officers</option>
                                    <?php foreach ($users as $user){ ?>
                                        <option value="<?php echo $user->id; ?>" <?php if(isset($_GET['id']) && $user->id == $_GET['id']){echo 'selected';} ?>><?php echo $user->Firstname." ".$user->Lastname; ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="branch">Branch:</label>
                                <select name="branch" id="branch" class="select2 form-control">
                                    <option value="All">All Branches</option>
                                    <?php foreach ($branches as $branch){ ?>
                                        <option value="<?php echo $branch->id; ?>"><?php echo $branch->BranchName; ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-3">
                                <label for="date_from">Loan Effective Date From:</label>
                                <input type="date" name="date_from" id="date_from" class="form-control">
                            </div>
                            <div class="col-md-3">
                                <label for="date_to">Loan Effective Date To:</label>
                                <input type="date" name="date_to" id="date_to" class="form-control">
                            </div>
                            <div class="col-md-3 mt-4">
                                <button type="submit" name="search" value="filter" class="btn btn-primary">Generate Report</button>
                            </div>
                        </div>
                    </div>
                </fieldset>
            </form>
            <hr>
        </div>
    </div>
</div>