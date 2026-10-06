<div class="main-content">
    <div class="page-header">
        <h2 class="header-title">CRB Report Generator</h2>
        <div class="header-sub-title">
            <nav class="breadcrumb breadcrumb-dash">
                <a href="<?php echo base_url('Admin')?>" class="breadcrumb-item"><i class="anticon anticon-home m-r-5"></i>Home</a>
                <a class="breadcrumb-item" href="#">Reports</a>
                <span class="breadcrumb-item active">CRB Report</span>
            </nav>
        </div>
    </div>
    <div class="card">
        <div class="card-body" style="border: thick #153505 solid;border-radius: 14px;">
            <div class="text-center mb-4">
                <h4>Generate CRB Report</h4>
                <p class="text-muted">Click the button below to generate and download the CRB report in Excel format.</p>
            </div>

            <div class="text-center">
                <a href="<?php echo base_url('Reports/export_crb')?>" id="generateCrbReport" class="btn btn-lg btn-success bg-gradient" style="padding: 15px 30px;">
                    <i class="anticon anticon-file-excel m-r-10"></i>
                    Generate CRB Report
                </a>
                <div id="loadingIndicator" class="mt-3" style="display: none;">
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">Generating report...</span>
                    </div>
                    <p class="mt-2">Please wait while we generate your report...</p>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
document.getElementById('generateCrbReport').addEventListener('click', function(e) {
    const button = this;
    const loadingIndicator = document.getElementById('loadingIndicator');

    // Show loading state
    button.style.pointerEvents = 'none';
    button.innerHTML = '<i class="anticon anticon-loading m-r-10"></i>Generating...';
    loadingIndicator.style.display = 'block';

    // Let the link proceed to the controller
    // The loading state will be reset when the page reloads or user returns
});
</script>
</div>
