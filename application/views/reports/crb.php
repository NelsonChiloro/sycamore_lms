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
                <p class="text-muted">Choose filters, then generate your CRB report.</p>
            </div>

            <form method="get" action="<?php echo site_url('Reports/export_crb'); ?>" id="crbReportForm">
                <div class="row">
                    <div class="col-md-4 form-group">
                        <label for="crbStatus">Loan status</label>
                        <select name="loan_status" id="crbStatus" class="form-control">
                            <option value="ALL">All disbursed loans</option>
                            <option value="ACTIVE">Active</option>
                            <option value="PAID_UP">Completely paid up</option>
                            <option value="WRITTEN_OFF">Written off</option>
                        </select>
                    </div>
                    <div class="col-md-4 form-group">
                        <label for="crbProduct">Loan product</label>
                        <select name="product" id="crbProduct" class="form-control">
                            <option value="">All products</option>
                            <?php foreach($products as $product): ?>
                            <option value="<?php echo (int)$product->loan_product_id; ?>"><?php echo htmlspecialchars($product->product_name,ENT_QUOTES,'UTF-8'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 form-group">
                        <label for="crbBranch">Branch</label>
                        <select name="branch" id="crbBranch" class="form-control">
                            <option value="">All branches</option>
                            <?php foreach($branches as $branch): ?>
                            <option value="<?php echo (int)$branch->id; ?>"><?php echo htmlspecialchars($branch->BranchName,ENT_QUOTES,'UTF-8'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 form-group">
                        <label for="crbFrom">Loan date from</label>
                        <input type="date" name="date_from" id="crbFrom" class="form-control">
                    </div>
                    <div class="col-md-6 form-group">
                        <label for="crbTo">Loan date to</label>
                        <input type="date" name="date_to" id="crbTo" class="form-control">
                    </div>
                </div>
                <p class="text-muted">Completely paid up includes loans with all installments and late charges settled.</p>
                <div class="text-center">
                <button type="submit" id="generateCrbReport" class="btn btn-lg btn-success bg-gradient" style="padding: 15px 30px;">
                    <i class="anticon anticon-file-excel m-r-10"></i>
                    Generate CRB Report
                </button>
                <div id="loadingIndicator" class="mt-3" style="display: none;">
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">Generating report...</span>
                    </div>
                    <p class="mt-2">Please wait while we generate your report...</p>
                </div>
            </div>
            </form>
        </div>

    </div>
</div>

<script>
document.getElementById('crbReportForm').addEventListener('submit', function(e) {
    const from = document.getElementById('crbFrom').value;
    const to = document.getElementById('crbTo').value;
    if (from && to && from > to) {
        e.preventDefault();
        alert('Loan date from must not exceed loan date to.');
        return;
    }
    const button = document.getElementById('generateCrbReport');
    const loadingIndicator = document.getElementById('loadingIndicator');

    // Show loading state
    button.disabled = true;
    button.innerHTML = '<i class="anticon anticon-loading m-r-10"></i>Generating...';
    loadingIndicator.style.display = 'block';

    // Let the link proceed to the controller
    // The loading state will be reset when the page reloads or user returns
});
</script>
