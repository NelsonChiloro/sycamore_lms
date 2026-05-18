<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
// Get data for dropdowns
$users = get_all('employees');
$products = get_all('loan_products');
$branches = get_all('branches');
?>
<div class="main-content">
    <div class="page-header">
        <h2 class="header-title">Upcoming Installment Report</h2>
        <div class="header-sub-title">
            <nav class="breadcrumb breadcrumb-dash">
                <a href="<?php echo base_url('Admin')?>" class="breadcrumb-item"><i class="anticon anticon-home m-r-5"></i>Home</a>
                <a class="breadcrumb-item" href="#">Reports</a>
                <span class="breadcrumb-item active">Upcoming Installment Report</span>
            </nav>
        </div>
    </div>
    <div class="card">
        <div class="card-body" style="border: thick #153505 solid;border-radius: 14px;">
            <form action="<?php echo base_url('upcoming_installment_report/upcoming_installment_filter') ?>" method="post">
                <fieldset>
                    <legend>Report filter</legend>
                    <div id="controlgroup">
                        Branch: <select name="branch" id="branch" class="select2">
                            <option value="">All Branches</option>
                            <?php
                            foreach ($branches as $branch){
                                ?>
                                <option value="<?php echo $branch->Code; ?>" <?php if(isset($selected_branch) && $selected_branch==$branch->Code){ echo "selected"; }?>><?php echo $branch->BranchName; ?></option>
                                <?php
                            }
                            ?>
                        </select>
                        Loan Officer:
                        <select name="user" id="officer" class="select2">
                            <option value="">All Officers</option>
                            <?php
                            foreach ($users as $user){
                                ?>
                                <option value="<?php echo $user->id;?>" <?php if(isset($selected_officer) && $selected_officer==$user->id){echo 'selected';}  ?>><?php echo $user->Firstname." ".$user->Lastname;?></option>
                                <?php
                            }
                            ?>
                        </select>
                        Loan Product:
                        <select name="product" id="product" class="select2">
                            <option value="">All Products</option>
                            <?php
                            foreach ($products as $product){
                                ?>
                                <option value="<?php echo $product->loan_product_id;?>" <?php if(isset($selected_product) && $selected_product==$product->loan_product_id){echo 'selected';}  ?>><?php echo $product->product_name;?></option>
                                <?php
                            }
                            ?>
                        </select>
                        From Date:
                        <input type="date" name="from_date" id="from_date" value="<?php echo isset($selected_from_date) ? $selected_from_date : ''; ?>">
                        To Date:
                        <input type="date" name="to_date" id="to_date" value="<?php echo isset($selected_to_date) ? $selected_to_date : ''; ?>">
                        <button type="submit" class="btn btn-primary">Generate Report</button>
                    </div>
                </fieldset>
            </form>
            <hr>
            <div class="alert alert-info">
                <p><i class="anticon anticon-info-circle"></i> Generated reports will be available in the Reports section. You will be redirected there after submitting the form.</p>
            </div>
        </div>
    </div>
</div>