<div class="main-content">
    <div class="page-header">
        <h2 class="header-title">Group File Management</h2>
        <div class="header-sub-title">
            <nav class="breadcrumb breadcrumb-dash">
                <a href="<?php echo base_url('Admin')?>" class="breadcrumb-item"><i class="anticon anticon-home m-r-5"></i>Home</a>
                <a class="breadcrumb-item" href="#">-</a>
                <span class="breadcrumb-item active">Group File</span>
            </nav>
        </div>
    </div>
    
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0"><i class="fas fa-folder-open mr-2"></i>Select Group and Batch</h4>
        </div>
        <div class="card-body">
            <div class="row">
                <!-- Group Selection Section -->
                <div class="col-lg-6">
                    <div class="card shadow-sm">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">Step 1: Select Group</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label for="group_select">Group:</label>
                                <select id="group_select" class="form-control select2">
                                    <option value="">--select group--</option>
                                    <?php
                                    foreach ($groups as $group){
                                    ?>
                                        <option value="<?php echo $group->group_id;?>">
                                            <?php echo $group->group_name. " (".$group->group_code.")"?>
                                        </option>
                                    <?php
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Batch Selection Section -->
                <div class="col-lg-6">
                    <div class="card shadow-sm">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">Step 2: Select Batch</h5>
                        </div>
                        <div class="card-body">
                            <div id="batch-selection" style="display: none;">
                                <div class="form-group">
                                    <label for="batch_select">Batch:</label>
                                    <select id="batch_select" class="form-control">
                                        <option value="">--select batch--</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <button type="button" id="continue_btn" class="btn btn-success btn-block" disabled>
                                        <i class="fas fa-arrow-right mr-2"></i>Continue to View Loans
                                    </button>
                                </div>
                            </div>
                            
                            <div id="batch-loading" style="display: none;">
                                <div class="alert alert-info">
                                    <i class="fas fa-spinner fa-spin mr-2"></i>Loading batches...
                                </div>
                            </div>
                            
                            <div id="batch-message">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle mr-2"></i>Please select a group first
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Group Information Section -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card shadow-sm">
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
    /* Custom styles for the Group File page */
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
    
    label {
        font-weight: 500;
        margin-bottom: 0.5rem;
        color: #495057;
    }
</style>