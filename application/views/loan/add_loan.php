<?php
$loan_types = isset($loan_types) && is_array($loan_types) ? $loan_types : array();
if (empty($loan_types) && isset($this->Loan_products_model)) {
    $loan_types = $this->Loan_products_model->get_all();
}
if (empty($loan_types) && function_exists('get_all')) {
    $loan_types = get_all('loan_products');
}
if (!is_array($loan_types)) {
    $loan_types = array();
}
?>

<div class="main-content">
    <div class="page-header">
        <h2 class="header-title">Loan Management</h2>
        <div class="header-sub-title">
            <nav class="breadcrumb breadcrumb-dash">
                <a href="<?php echo base_url('Admin')?>" class="breadcrumb-item"><i class="anticon anticon-home m-r-5"></i>Home</a>
                <a class="breadcrumb-item" href="#">-</a>
                <span class="breadcrumb-item active">Add loan</span>
            </nav>
        </div>
    </div>
    
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0"><i class="fas fa-money-check-alt mr-2"></i>Create New Loan</h4>
        </div>
        <div class="card-body">
            <div class="row">
                <!-- Loan Form Section -->
                <div class="col-lg-5 pr-lg-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">Loan Details</h5>
                        </div>
                        <div class="card-body">
                            <form action="<?php echo base_url('loan/create_act')?>" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                                <div class="form-group row">
                                    <label class="col-sm-4 col-form-label">Officer:</label>
                                    <div class="col-sm-8">
                                        <select name="user" class="form-control select2" required>
                                            <option value="All">All officers</option>
                                            <?php
                                            $officer = get_all('employees');
                                            foreach ($officer as $item){
                                            ?>
                                                <option value="<?php echo $item->id; ?>">
                                                    <?php echo $item->Firstname." ".$item->Lastname?>
                                                </option>
                                            <?php
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="form-group row">
                                    <label class="col-sm-4 col-form-label">Customer:</label>
                                    <div class="col-sm-8">
                                        <input type="text" name="customer_type" value="individual" hidden>
                                        <select name="customer" id="customer_loan" class="form-control select2" required>
                                            <option value="">--select customer--</option>
                                            <?php
                                            foreach ($customers as $c){
                                            ?>
                                                <option value="<?php echo $c->id;?>">
                                                    <?php echo $c->Firstname. " ".$c->Lastname?>
                                                </option>
                                            <?php
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="form-group row">
                                    <label class="col-sm-4 col-form-label">Loan Amount:</label>
                                    <div class="col-sm-8">
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fas fa-money-bill"></i></span>
                                            </div>
                                            <input type="text" name="amount" class="form-control" value="<?php echo set_value('amount'); ?>" required>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-group row">
                                    <label class="col-sm-4 col-form-label">Loan Term:</label>
                                    <div class="col-sm-8">
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fas fa-calendar"></i></span>
                                            </div>
                                            <input type="text" name="months" class="form-control" value="<?php echo set_value('months'); ?>" required>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-group row">
                                    <label class="col-sm-4 col-form-label">Loan Type:</label>
                                    <div class="col-sm-8">
                                        <select name="loan_type" class="form-control select2" required>
                                            <option value="">--select loan type--</option>
                                            <?php
                                            foreach ($loan_types as $lt){
                                            ?>
                                                <option value="<?php echo $lt->loan_product_id ?>">
                                                    <?php echo $lt->product_name.' ('.$lt->product_code.': '.$lt->frequency.'-'.$lt->method.')'; ?>
                                                </option>
                                            <?php
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="form-group row">
                                    <label class="col-sm-4 col-form-label">Loan Date:</label>
                                    <div class="col-sm-8">
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                                            </div>
                                            <input type="date" name="loan_date" class="form-control" value="<?php echo set_value('loan_date'); ?>" required>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-group row">
                                    <label class="col-sm-4 col-form-label">Funds Source:</label>
                                    <div class="col-sm-8">
                                        <select name="funds_source" class="form-control select2">
                                            <option value="">--select funds source--</option>
                                            <?php
                                            foreach ($funds_sources as $fs){
                                                $selected = set_value('funds_source') == $fs->funds_source ? 'selected' : '';
                                            ?>
                                                <option value="<?php echo $fs->funds_source; ?>" <?php echo $selected; ?>>
                                                    <?php echo $fs->source_name; ?>
                                                </option>
                                            <?php
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="form-group row">
                                    <label class="col-sm-4 col-form-label">Credit Details:</label>
                                    <div class="col-sm-8">
                                        <div class="custom-file">
                                            <input type="file" class="custom-file-input" id="id_front" onchange="uploadprofile('id_front')" accept="application/msword, application/vnd.ms-excel, application/vnd.ms-powerpoint, text/plain, application/pdf">
                                            <label class="custom-file-label" for="id_front">Upload loan worthiness file</label>
                                        </div>
                                        <input type="text" id="id_front1" name="worthness_file" hidden>
                                    </div>
                                </div>
                                
                                <div class="form-group row">
                                    <label class="col-sm-4 col-form-label">Narration:</label>
                                    <div class="col-sm-8">
                                        <textarea name="narration" class="form-control" rows="4" placeholder="Enter loan notes here..."></textarea>
                                    </div>
                                </div>
                                
                                <!-- Collateral Section -->
                                <div class="mt-4">
                                    <div class="card bg-light shadow-sm">
                                        <div class="card-header d-flex justify-content-between align-items-center">
                                            <h5 class="mb-0">Collateral Information</h5>
                                            <button type="button" class="btn btn-sm btn-success" onclick="addField();">
                                                <i class="fas fa-plus mr-1"></i>Add Item
                                            </button>
                                        </div>
                                        <div class="card-body">
                                            <div id="forms">
                                                <div class="collateral-item mb-3 p-3 border rounded bg-white">
                                                    <div class="row">
                                                        <div class="col-md-6 mb-2">
                                                            <label for="name_0">Collateral Name</label>
                                                            <input type="text" id="name_0" name="name[]" placeholder="Enter name" class="form-control">
                                                        </div>
                                                        <div class="col-md-6 mb-2">
                                                            <label for="type_0">Collateral Type</label>
                                                            <input type="text" id="type_0" name="type[]" placeholder="Enter type" class="form-control">
                                                        </div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-md-6 mb-2">
                                                            <label for="serial_0">Serial Number</label>
                                                            <input type="text" id="serial_0" name="serial[]" placeholder="Enter serial number" class="form-control">
                                                        </div>
                                                        <div class="col-md-6 mb-2">
                                                            <label for="value_0">Value</label>
                                                            <div class="input-group">
                                                                <div class="input-group-prepend">
                                                                    <span class="input-group-text"><i class="fas fa-tag"></i></span>
                                                                </div>
                                                                <input type="text" id="value_0" name="value[]" placeholder="Enter value" class="form-control">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-12 mb-2">
                                                            <label for="files_0">Attachment</label>
                                                            <div class="custom-file">
                                                                <input type="file" class="custom-file-input" id="files_0" name="files[]">
                                                                <label class="custom-file-label" for="files_0">Choose file</label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-12">
                                                            <label for="desc_0">Description</label>
                                                            <textarea class="form-control" id="desc_0" name="desc[]" rows="3" placeholder="Enter detailed description"></textarea>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-group mt-4">
                                    <button type="submit" name="submit_loan" class="btn btn-danger btn-block" onclick="return confirm('Are you sure you want to create this loan?')">
                                        <i class="fas fa-check-circle mr-2"></i>Create Loan
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Customer Info Section -->
                <div class="col-lg-3 pl-lg-0">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0"><i class="fas fa-user mr-2"></i>Customer Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-4">
                                <h6 class="text-muted border-bottom pb-2">Customer Details</h6>
                                <div id="customer-results" class="mt-3">
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle mr-2"></i>Select a customer to view details
                                    </div>
                                </div>
                            </div>
                            
                            <div>
                                <h6 class="text-muted border-bottom pb-2">Previous Loans</h6>
                                <ul id="loandd" class="list-group mt-3">
                                    <li class="list-group-item text-center text-muted">No loan history found</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- KYC Section -->
                <div class="col-lg-4 pl-lg-0">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0"><i class="fas fa-id-card mr-2"></i>KYC Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover" id="kyc_data">
                                    <thead>
                                        <tr>
                                            <th>Document Type</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td colspan="3" class="text-center">
                                                <div class="alert alert-info mb-0">
                                                    <i class="fas fa-info-circle mr-2"></i>Select a customer to view KYC details
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    /* Custom styles for the Loan Management page */
    .card {
        border-radius: 8px;
        border: none;
        margin-bottom: 1.5rem;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        transition: all 0.3s ease;
    }
    
    .card:hover {
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
    }
    
    .card-header {
        border-radius: 8px 8px 0 0 !important;
        font-weight: 500;
    }
    
    .custom-file-label::after {
        content: "Browse";
    }
    
    .shadow-sm {
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075) !important;
    }
    
    /* Button styles */
    .btn {
        border-radius: 4px;
        font-weight: 500;
        transition: all 0.3s ease;
    }
    
    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }
    
    /* Select2 override */
    .select2-container--default .select2-selection--single {
        height: calc(1.5em + 0.75rem + 2px);
        border: 1px solid #ced4da;
        border-radius: 0.25rem;
    }
    
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: calc(1.5em + 0.75rem);
    }
    
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: calc(1.5em + 0.75rem);
    }
    
    /* Collateral section styles */
    .collateral-item {
        position: relative;
        transition: all 0.3s ease;
    }
    
    .collateral-item:hover {
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    }
    
    /* Form control focus */
    .form-control:focus {
        border-color: #80bdff;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
    }

    /* Responsive table */
    .table-responsive {
        display: block;
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    
    /* Custom hover effect for table rows */
    .table-hover tbody tr:hover {
        background-color: rgba(0, 123, 255, 0.05);
    }
    
    /* List group styling */
    .list-group-item {
        border-left: none;
        border-right: none;
        padding: 0.75rem 1.25rem;
    }
    
    .list-group-item:first-child {
        border-top-left-radius: 0.25rem;
        border-top-right-radius: 0.25rem;
    }
    
    /* Label styling */
    label {
        font-weight: 500;
        margin-bottom: 0.5rem;
        color: #495057;
    }
</style>
