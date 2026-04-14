<?php
$loan_types = $this->Loan_products_model->get_all();
?>

<div class="main-content">
    <div class="page-header">
        <h2 class="header-title">Group Member Loans</h2>
        <div class="header-sub-title">
            <nav class="breadcrumb breadcrumb-dash">
                <a href="<?php echo base_url('Admin')?>" class="breadcrumb-item"><i class="anticon anticon-home m-r-5"></i>Home</a>
                <a class="breadcrumb-item" href="#">-</a>
                <span class="breadcrumb-item active">Add loans for group members</span>
            </nav>
        </div>
    </div>
    
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0"><i class="fas fa-users mr-2"></i>Create Loans for Group Members</h4>
        </div>
        <div class="card-body">
            <div class="row">
                <!-- Loan Form Section -->
                <div class="col-lg-8">
                    <div class="card shadow-sm">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">Loan Details for All Members</h5>
                        </div>
                        <div class="card-body">
                            <form action="<?php echo base_url('loan/create_group_member_loans_act')?>" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                                
                                <div class="form-group row">
                                    <label class="col-sm-3 col-form-label">Officer:</label>
                                    <div class="col-sm-9">
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
                                    <label class="col-sm-3 col-form-label">Group:</label>
                                    <div class="col-sm-9">
                                        <select name="group_id" id="group_select" class="form-control select2" required>
                                            <option value="">--select group--</option>
                                            <?php
                                            foreach ($customers as $c){
                                            ?>
                                                <option value="<?php echo $c->group_id;?>">
                                                    <?php echo $c->group_name. " ".$c->group_code?>
                                                </option>
                                            <?php
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="form-group row">
                                    <label class="col-sm-3 col-form-label">Loan Type:</label>
                                    <div class="col-sm-9">
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
                                    <label class="col-sm-3 col-form-label">Loan Date:</label>
                                    <div class="col-sm-9">
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                                            </div>
                                            <input type="date" name="loan_date" class="form-control" value="<?php echo set_value('loan_date'); ?>" required>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-group row">
                                    <label class="col-sm-3 col-form-label">Loan Term:</label>
                                    <div class="col-sm-9">
                                        <div class="input-group">
                                            <input type="number" name="loan_period" class="form-control" placeholder="12" min="1" max="120" required>
                                            <div class="input-group-append">
                                                <span class="input-group-text">months</span>
                                            </div>
                                        </div>
                                        <small class="form-text text-muted">This loan term will apply to all group members</small>
                                    </div>
                                </div>
                                
                                <div class="form-group row">
                                    <label class="col-sm-3 col-form-label">Batch Number:</label>
                                    <div class="col-sm-9">
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fas fa-hashtag"></i></span>
                                            </div>
                                            <input type="text" name="batch_number" class="form-control" value="<?php echo $batch_number; ?>" readonly style="background-color: #f8f9fa;">
                                        </div>
                                        <small class="form-text text-muted">This batch number will be assigned to all member loans for tracking purposes</small>
                                    </div>
                                </div>
                                
                                <div class="form-group row">
                                    <label class="col-sm-3 col-form-label">Funds Source:</label>
                                    <div class="col-sm-9">
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
                                    <label class="col-sm-3 col-form-label">Credit Details:</label>
                                    <div class="col-sm-9">
                                        <div class="custom-file">
                                            <input type="file" class="custom-file-input" id="id_front" onchange="uploadprofile('id_front')" accept="application/msword, application/vnd.ms-excel, application/vnd.ms-powerpoint, text/plain, application/pdf">
                                            <label class="custom-file-label" for="id_front">Upload loan worthiness file</label>
                                        </div>
                                        <input type="text" id="id_front1" name="worthness_file" hidden>
                                    </div>
                                </div>
                                
                                <div class="form-group row">
                                    <label class="col-sm-3 col-form-label">Narration:</label>
                                    <div class="col-sm-9">
                                        <textarea name="narration" class="form-control" rows="3" placeholder="Enter loan notes here..."></textarea>
                                    </div>
                                </div>
                                
                                <!-- Group Members Section -->
                                <div class="mt-4">
                                    <div class="card bg-light shadow-sm">
                                        <div class="card-header">
                                            <h5 class="mb-0">Group Members - Individual Loan Amounts & Terms</h5>
                                        </div>
                                        <div class="card-body">
                                            <div id="members-list">
                                                <div class="alert alert-info">
                                                    <i class="fas fa-info-circle mr-2"></i>Select a group to load members
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-group mt-4">
                                    <button type="submit" name="submit_loans" class="btn btn-danger btn-block" onclick="return validateFormSubmission()">
                                        <i class="fas fa-check-circle mr-2"></i>Create Member Loans
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Group Information Section -->
                <div class="col-lg-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0"><i class="fas fa-info-circle mr-2"></i>Group Information</h5>
                        </div>
                        <div class="card-body">
                            <div id="group-info">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle mr-2"></i>Select a group to view information
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<style>
    /* Custom styles for the Group Member Loans page */
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
    
    .btn {
        border-radius: 4px;
        font-weight: 500;
        transition: all 0.3s ease;
    }
    
    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }
    
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
    
    .form-control:focus {
        border-color: #80bdff;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
    }
    
    .table-responsive {
        display: block;
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    
    .table-hover tbody tr:hover {
        background-color: rgba(0, 123, 255, 0.05);
    }
    
    label {
        font-weight: 500;
        margin-bottom: 0.5rem;
        color: #495057;
    }
    
    .input-group-text {
        background-color: #f8f9fa;
        border-color: #ced4da;
    }
</style>