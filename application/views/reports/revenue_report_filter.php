

    <style>
        .report-filter-container {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #3498db;
        }
        .page-header {
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .card-header {
            background-color: #3498db;
            color: white;
        }
    </style>
    <div class="main-content">
        <div class="page-header">
            <h2 class="header-title">Revenue Analysis</h2>
            <div class="header-sub-title">
                <nav class="breadcrumb breadcrumb-dash">
                    <a href="<?php echo base_url('Admin')?>" class="breadcrumb-item"><i class="anticon anticon-home m-r-5"></i>Home</a>
                    <a class="breadcrumb-item" href="#">-</a>
                    <span class="breadcrumb-item active">Revenue Analysis</span>
                </nav>
            </div>
        </div>
        <div class="card">
            <div class="card-body" style="border: thick #153505 solid;border-radius: 14px;">
    <div class="row">
        <div class="col-md-12">
            <h1 class="page-header">
                <i class="fa fa-chart-line"></i> Revenue Analysis Report
            </h1>

            <div class="card mb-4">
                <div class="card-header">
                    <h4 class="mb-0"><i class="fa fa-filter"></i> Filter Options</h4>
                </div>
                <div class="card-body">
                    <?php echo form_open('revenue_report/generate', ['class' => 'form-horizontal', 'id' => 'revenue-report-form']); ?>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="start_date">Start Date: <small class="text-muted">(Optional - leave empty for all data)</small></label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fa fa-calendar"></i></span>
                                    </div>
                                    <input type="date" class="form-control" id="start_date" name="start_date" >
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="end_date">End Date: <small class="text-muted">(Optional - leave empty for all data)</small></label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fa fa-calendar"></i></span>
                                    </div>
                                    <input type="date" class="form-control" id="end_date" name="end_date">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="officer_id">Loan Officer:</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fa fa-user"></i></span>
                                    </div>
                                    <select class="form-control" id="officer_id" name="officer_id">
                                        <option value="">All Officers</option>
                                        <?php foreach ($officers as $officer): ?>
                                            <option value="<?php echo $officer->id; ?>"><?php echo $officer->Firstname . ' ' . $officer->Lastname; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="branch_id">Branch:</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fa fa-building"></i></span>
                                    </div>
                                    <select class="form-control" id="branch_id" name="branch_id">
                                        <option value="">All Branches</option>
                                        <?php foreach ($branches as $branch): ?>
                                            <option value="<?php echo $branch->id; ?>"><?php echo $branch->BranchName; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group text-center mt-4">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fa fa-search"></i> Generate Report
                        </button>
                        <button type="reset" class="btn btn-secondary btn-lg">
                            <i class="fa fa-undo"></i> Reset
                        </button>
                    </div>
                    <?php echo form_close(); ?>
                </div>
            </div>

            <div class="alert alert-info">
                <i class="fa fa-info-circle"></i> <strong>Tip:</strong> This report analyzes revenue from different sources including interest, admin fees, loan cover, processing fees, penalties and write-offs. Leave date fields empty to analyze all historical data.
            </div>

            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0"><i class="fa fa-question-circle"></i> About This Report</h4>
                </div>
                <div class="card-body">
                    <p>The Revenue Analysis Report provides a detailed breakdown of all revenue streams across different branches and loan products. Use this report to:</p>
                    <ul>
                        <li>Analyze revenue composition by branch</li>
                        <li>Compare performance of different loan products</li>
                        <li>Track fee income vs interest income</li>
                        <li>Monitor penalties and write-off income</li>
                        <li>Evaluate revenue trends over time</li>
                    </ul>
                    <p>You can filter the report by date range, loan officer, and/or branch to focus on specific areas of interest.</p>
                </div>
            </div>
        </div>
    </div>

            </div>
        </div>
    </div>

