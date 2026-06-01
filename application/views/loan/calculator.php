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
        <h2 class="header-title">Loan Calculator</h2>
        <div class="header-sub-title">
            <nav class="breadcrumb breadcrumb-dash">
                <a href="<?php echo base_url('Admin')?>" class="breadcrumb-item"><i class="anticon anticon-home m-r-5"></i>Home</a>
                <a class="breadcrumb-item" href="#">-</a>
                <span class="breadcrumb-item active">Loan calculator</span>
            </nav>
        </div>
    </div>
    <div class="card">
        <div class="card-body shadow-sm">
            <div class="row">
                <div class="col-lg-4 border-right pr-4">
                    <div class="card bg-light shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Calculate Your Loan</h5>
                        </div>
                        <div class="card-body">
                            <form action="<?php echo base_url('loan/calculate')?>" method="get" class="needs-validation" novalidate>
                                <div class="form-group">
                                    <label for="amount" class="font-weight-bold">Loan Amount:</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fa fa-money-bill"></i></span>
                                        </div>
                                        <input type="text" class="form-control" id="amount" name="amount" value="<?php echo $this->input->get('amount'); ?>" required placeholder="Enter amount">
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="months" class="font-weight-bold">Loan Term (months):</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fa fa-calendar"></i></span>
                                        </div>
                                        <input type="number" class="form-control" id="months" name="months" value="<?php echo $this->input->get('months'); ?>" required placeholder="Enter term">
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="loan_type" class="font-weight-bold">Select Loan Type:</label>
                                    <select name="loan_type" id="loan_type" class="form-control" required>
                                        <option value="">--Select Loan Type--</option>
                                        <?php foreach ($loan_types as $lt): ?>
                                            <option value="<?php echo $lt->loan_product_id ?>" <?php if($this->input->get('loan_type')==$lt->loan_product_id) echo "selected"; ?>>
                                                <?php echo $lt->product_name.' ('.$lt->product_code.': '.$lt->frequency.'-'.$lt->method.')'; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="loan_date" class="font-weight-bold">Loan Date:</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fa fa-calendar-alt"></i></span>
                                        </div>
                                        <input type="date" class="form-control" id="loan_date" name="loan_date" value="<?php echo $this->input->get('loan_date'); ?>" required>
                                    </div>
                                </div>
                                
                                <div class="form-group mt-4">
                                    <button type="submit" name="submit_loan" class="btn btn-primary btn-block">
                                        <i class="fa fa-calculator mr-2"></i> Calculate Loan
                                    </button>
                                </div>
                                
                                <?php if (validation_errors()): ?>
                                    <div class="alert alert-danger mt-3">
                                        <?php echo validation_errors(); ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (isset($error)): ?>
                                    <div class="alert alert-danger mt-3">
                                        <?php echo $error; ?>
                                    </div>
                                <?php endif; ?>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0">
                                <i class="fa fa-chart-line mr-2"></i> Loan Calculation Results
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if (isset($result)): ?>
                                <div class="table-responsive">
                                    <?php echo $result; ?>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-info text-center p-5">
                                    <i class="fa fa-info-circle fa-3x mb-3"></i>
                                    <h4>Enter your loan details</h4>
                                    <p>Fill out the form to see your loan calculation results.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add this to your header if not already included -->
<style>
    /* Custom styles for the loan calculator */
    .card {
        border-radius: 8px;
        transition: all 0.3s ease;
    }
    
    .card-header {
        border-radius: 8px 8px 0 0 !important;
        font-weight: 600;
    }
    
    .btn-primary {
        background-color: #007bff;
        border-color: #007bff;
        transition: all 0.3s ease;
    }
    
    .btn-primary:hover {
        background-color: #0069d9;
        border-color: #0062cc;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }
    
    .form-control:focus {
        border-color: #80bdff;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
    }
    
    .shadow-sm {
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075) !important;
    }
    
    /* Table styles for the results */
    table.table {
        width: 100%;
        margin-bottom: 1rem;
        color: #212529;
        border-collapse: collapse;
    }
    
    table.table th,
    table.table td {
        padding: 0.75rem;
        vertical-align: top;
        border-top: 1px solid #dee2e6;
    }
    
    table.table thead th {
        vertical-align: bottom;
        border-bottom: 2px solid #dee2e6;
        background-color: #f8f9fa;
    }
    
    table.table tbody tr:nth-of-type(odd) {
        background-color: rgba(0, 0, 0, 0.05);
    }
    
    .table-responsive {
        display: block;
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
</style>

<script>
$(document).ready(function () {
    var GL_MONTHLY_CODES = ['ZTGLBT', 'ZTGLLL'];

    function getProductCode($select) {
        var m = $select.find('option:selected').text().match(/\(([^:]+):/);
        return m ? $.trim(m[1]) : '';
    }

    function isGroupZitsambaMonthly(optionText, code) {
        var text = (optionText || '').toUpperCase();
        return GL_MONTHLY_CODES.indexOf((code || '').toUpperCase()) !== -1
            || (text.indexOf('GROUP LOAN PRODUCT') !== -1
                && text.indexOf('ZITSAMBA') !== -1
                && (text.indexOf(' LL') !== -1 || text.indexOf(' BT') !== -1));
    }

    function enforcePeriodLimit() {
        var $months   = $('#months');
        var $selected = $('#loan_type').find('option:selected');
        var code      = getProductCode($('#loan_type'));
        var optionTxt = $selected.text();
        var $hint     = $('#zitsamba-calc-hint');
        if (isGroupZitsambaMonthly(optionTxt, code)) {
            $months.attr('max', 4);
            if (parseInt($months.val()) > 4) { $months.val(4); }
            if (!$hint.length) {
                $months.closest('.form-group').append('<small id="zitsamba-calc-hint" class="form-text text-warning font-weight-bold"><i class="fa fa-exclamation-triangle"></i> Maximum 4 months for Group Loan Product &ndash; Zitsamba.</small>');
            }
        } else {
            $months.removeAttr('max');
            $('#zitsamba-calc-hint').remove();
        }
    }

    $('#loan_type').on('change', enforcePeriodLimit);
    enforcePeriodLimit();
});
</script>