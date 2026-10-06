<div class="main-content">
    <div class="page-header">
        <h2 class="header-title">Risk Recovery Report</h2>
        <div class="header-sub-title">
            <nav class="breadcrumb breadcrumb-dash">
                <a href="<?php echo base_url('Admin'); ?>" class="breadcrumb-item"><i class="anticon anticon-home m-r-5"></i>Home</a>
                <a href="<?php echo base_url('report'); ?>" class="breadcrumb-item">Reports</a>
                <span class="breadcrumb-item active">Risk Recovery Report</span>
            </nav>
        </div>
    </div>

    <div class="card">
        <div class="card-body" style="border: thick #153505 solid; border-radius: 14px;">
            <h3>Generate Risk Recovery Report</h3>
            <p class="text-muted">Select the required filters. The report will be generated in the background and stored under Generated Reports.</p>

            <form method="post" action="<?php echo base_url('reports/generate_risk_recovery_report'); ?>">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="risk_category">Risk Category</label>
                            <select name="risk_category" id="risk_category" class="form-control">
                                <option value="">All Categories</option>
                                <option value="Standard">Standard</option>
                                <option value="Special_Mention">Special Mention</option>
                                <option value="Substandard">Substandard</option>
                                <option value="Doubtful">Doubtful</option>
                                <option value="Loss">Loss</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="officer">Risk Officer</label>
                            <select name="officer" id="officer" class="form-control">
                                <option value="">All Officers</option>
                                <?php foreach ($employees as $employee): ?>
                                    <option value="<?php echo (int)$employee->id; ?>">
                                        <?php echo htmlspecialchars(trim($employee->Firstname . ' ' . $employee->Lastname)); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="branch">Branch</label>
                            <select name="branch" id="branch" class="form-control">
                                <option value="">All Branches</option>
                                <?php foreach ($branches as $branch): ?>
                                    <option value="<?php echo htmlspecialchars($branch->id); ?>">
                                        <?php echo htmlspecialchars($branch->BranchName); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="writeoff">Write-off Status</label>
                            <select name="writeoff" id="writeoff" class="form-control">
                                <option value="">All</option>
                                <option value="1">Recommended for Write-off</option>
                                <option value="0">Not Recommended</option>
                            </select>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="anticon anticon-file-done"></i> Generate Report
                </button>
                <button type="reset" class="btn btn-secondary">Reset</button>
            </form>
        </div>
    </div>
</div>
