<div class="main-content">
    <div class="page-header">
        <h2 class="header-title">All Loan in arrears</h2>
        <div class="header-sub-title">
            <nav class="breadcrumb breadcrumb-dash">
                <a href="<?php echo base_url('Admin')?>" class="breadcrumb-item"><i class="anticon anticon-home m-r-5"></i>Home</a>
                <a class="breadcrumb-item" href="#">-</a>
                <span class="breadcrumb-item active">All Arrears</span>
            </nav>
        </div>
    </div>
    <div class="card">
        <div class="card-body" style="border: thick #153505 solid;border-radius: 14px;">
    <div class="row">
        <div class="col-md-12">
            <h1 class="page-header">Arrears Report</h1>

            <div class="report-filter-container">
                <h4>Filter Options</h4>

                <?php echo form_open('arrears_report/generate', ['class' => 'form-horizontal', 'id' => 'arrears-report-form']); ?>
                <!-- Updated date fields in the filter form -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="start_date">Start Date: <small class="text-muted">(Optional - leave empty for all loans)</small></label>
                            <input type="date" class="form-control" id="start_date" name="start_date" >
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="end_date">End Date: <small class="text-muted">(Optional - leave empty for all loans)</small></label>
                            <input type="date" class="form-control" id="end_date" name="end_date" >
                        </div>
                    </div>
                </div>

                <!-- Updated help text section -->
                <div class="alert alert-info">
                    <i class="fa fa-info-circle"></i> <strong>Tip:</strong> Leave date fields empty to search for all loans in arrears regardless of when they were issued.
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="officer_id">Loan Officer:</label>
                            <select class="form-control" id="report-filter-officer" name="officer_id">
                                <option value="">All Officers</option>
                                <?php foreach ($officers as $officer): ?>
                                    <option value="<?php echo $officer->id; ?>"><?php echo $officer->Firstname . ' ' . $officer->Lastname; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <?php $this->load->view('reports/_relationship_supervisor_filter'); ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="branch_id">Branch:</label>
                            <select class="form-control" id="branch_id" name="branch_id">
                                <option value="">All Branches</option>
                                <?php
                                $branches = get_all('branches');
                                foreach ($branches as $branch): ?>
                                    <option value="<?php echo $branch->id; ?>"><?php echo $branch->BranchName; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Generate Report</button>
                    <button type="reset" class="btn btn-secondary">Reset</button>
                </div>
                <?php echo form_close(); ?>
            </div>
        </div>
    </div>

        </div>
    </div>
</div>