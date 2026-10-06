<style>
.report-card {
    border: 2px solid #153505;
    border-radius: 14px;
    padding: 25px;
    margin-bottom: 20px;
    background: #fff;
}
.report-card h4 {
    color: #153505;
    font-weight: 700;
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 2px solid #e9ecef;
}
.report-card .form-section {
    background: #f8f9fa;
    border-radius: 10px;
    padding: 20px;
    margin-bottom: 15px;
}
.report-card label {
    font-weight: 600;
    color: #333;
    margin-bottom: 5px;
    display: block;
    font-size: 13px;
}
.report-card input[type="date"],
.report-card input[type="text"],
.report-card select {
    border: 1px solid #ced4da;
    border-radius: 6px;
    padding: 8px 12px;
    width: 100%;
    font-size: 14px;
    transition: border-color 0.2s;
}
.report-card input[type="date"]:focus,
.report-card input[type="text"]:focus,
.report-card select:focus {
    border-color: #153505;
    outline: none;
    box-shadow: 0 0 0 3px rgba(21, 53, 5, 0.1);
}
.btn-green {
    background-color: #153505;
    color: #fff;
    border: none;
    border-radius: 6px;
    padding: 10px 20px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: background-color 0.2s;
}
.btn-green:hover {
    background-color: #1e4a08;
    color: #fff;
}
.info-badge {
    display: inline-block;
    background: #e8f5e9;
    color: #2e7d32;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    margin-top: 10px;
}
.loan-payments-card {
    border: 2px solid #153505;
    border-radius: 14px;
    background: #fff;
}
.loan-payments-card .card-body {
    border-radius: 14px;
}
.loan-payments-head {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 16px;
    margin-bottom: 18px;
}
.loan-payments-head h4 {
    color: #153505;
    font-weight: 700;
    margin-bottom: 4px;
}
.loan-payments-head p {
    color: #5f6b63;
    margin: 0;
}
.loan-payments-empty {
    background: #f8f9fa;
    border: 1px dashed #c7d2c2;
    border-radius: 10px;
    color: #5f6b63;
    padding: 18px;
}
.loan-payments-card .table {
    margin-bottom: 0;
}
.loan-payments-card .table,
.loan-payments-card .table thead th,
.loan-payments-card .table tbody td,
.loan-payments-card .table tbody tr,
.loan-payments-card .table-striped tbody tr:nth-of-type(odd),
.loan-payments-card .table-striped tbody tr:nth-of-type(even) {
    background-color: #ffffff !important;
    color: #1f2933 !important;
}
.loan-payments-card .table tbody tr:hover,
.loan-payments-card .table-striped tbody tr:hover {
    background-color: #f5f8f6 !important;
}
.loan-payments-card .table a {
    color: #153505;
}
.loan-payments-card .table thead th {
    white-space: nowrap;
}
@media (max-width: 767px) {
    .loan-payments-head {
        flex-direction: column;
    }
}
#data-table {
    margin-bottom: 0;
}
#data-table tbody tr,
#data-table tbody tr:nth-of-type(odd),
#data-table tbody tr:nth-of-type(even) {
    background-color: #fff;
}
#data-table tbody td,
#data-table thead th {
    background-color: #fff;
    vertical-align: middle;
}
#data-table tbody tr + tr td {
    border-top: 1px solid #e6e6e6;
}
</style>
<div class="main-content">
    <div class="page-header">
        <h2 class="header-title">Loan Transactions</h2>
        <div class="header-sub-title">
            <nav class="breadcrumb breadcrumb-dash">
                <a href="<?php echo base_url('Admin')?>" class="breadcrumb-item"><i class="anticon anticon-home m-r-5"></i>Home</a>
                <a class="breadcrumb-item" href="#">-</a>
                <span class="breadcrumb-item active">Loan Transactions</span>
            </nav>
        </div>
    </div>

    <div class="card">
        <div class="card-body" style="border: thick #153505 solid; border-radius: 14px;">
            <form id="track-transactions-filter-form" action="<?php echo base_url('Tellering/track_transactions_view') ?>" method="GET">
                <input type="hidden" name="run" value="1">
                <fieldset>
                    <legend>Track Transactions</legend>
                    <div class="row">
                        <div class="col-md-3">
                            <label for="from">From Date</label>
                            <input type="date" id="from" name="from" value="<?php echo html_escape($filters['from']); ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="to">To Date</label>
                            <input type="date" id="to" name="to" value="<?php echo html_escape($filters['to']); ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="customer_name">Customer Name</label>
                            <input type="text" id="customer_name" name="customer_name" value="<?php echo html_escape($filters['customer_name']); ?>" placeholder="Filter by customer name">
                        </div>
                        <div class="col-md-3">
                            <label for="loan_number">Loan Number</label>
                            <input type="text" id="loan_number" name="loan_number" value="<?php echo html_escape($filters['loan_number']); ?>" placeholder="Filter by loan number">
                        </div>
                    </div>
                    <div class="row" style="margin-top: 15px;">
                        <div class="col-md-3">
                            <label for="transaction_type">Transaction Type</label>
                            <select id="transaction_type" name="transaction_type" class="form-control">
                                <option value="">All transaction types</option>
                                <?php foreach ($transaction_types as $transaction_type) { ?>
                                    <option value="<?php echo html_escape($transaction_type->transaction_type_id); ?>" <?php echo ((string)$filters['transaction_type'] === (string)$transaction_type->transaction_type_id) ? 'selected' : ''; ?>>
                                        <?php echo html_escape($transaction_type->name); ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="col-md-9" style="display:flex; align-items:flex-end; gap:10px; flex-wrap:wrap;">
                            <button type="submit" class="btn-green"><i class="fa fa-search" style="margin-right:5px;"></i>Filter Transactions</button>
                            <button type="button" id="generate-track-transactions-report" class="btn btn-primary" style="background-color:#153505; border-color:#153505;"><i class="fa fa-file-text-o" style="margin-right:5px;"></i>Generate Report</button>
                            <a href="<?php echo base_url('Tellering/track_transactions_view') ?>" class="btn btn-default">Reset</a>
                            <?php if ($should_fetch) { ?>
                                <span class="info-badge"><i class="fa fa-database" style="margin-right:5px;"></i><?php echo count($transactions_data); ?> transaction(s) found</span>
                            <?php } ?>
                        </div>
                    </div>
                </fieldset>
            </form>

            <form id="track-transactions-report-form" action="<?php echo base_url('Tellering/generate_track_transactions_report') ?>" method="POST" style="display:none;">
                <input type="hidden" name="from" value="">
                <input type="hidden" name="to" value="">
                <input type="hidden" name="customer_name" value="">
                <input type="hidden" name="loan_number" value="">
                <input type="hidden" name="transaction_type" value="">
            </form>

            <hr>

            <div class="table-responsive">
                <table id="data-table" class="table table-bordered">
                    <thead>
                    <tr>
                        <th>Date</th>
                        <th>Transaction Type</th>
                        <th>Amount</th>
                        <th>Transaction Reference</th>
                        <th>Payment Type</th>
                        <th>Payment Reference</th>
                        <th>Customer</th>
                        <th>Loan Number</th>
                        <th>Added By</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($transactions_data as $transaction) { ?>
                        <tr>
                            <td><?php echo !empty($transaction->display_date_stamp) ? html_escape(date('Y-m-d H:i:s', strtotime($transaction->display_date_stamp))) : '-'; ?></td>
                            <td><?php echo !empty($transaction->transaction_type_name) ? html_escape($transaction->transaction_type_name) : '-'; ?></td>
                            <td><?php echo is_numeric($transaction->amount) ? number_format((float)$transaction->amount, 2) : html_escape($transaction->amount); ?></td>
                            <td><?php echo !empty($transaction->ref) ? html_escape($transaction->ref) : '-'; ?></td>
                            <td><?php echo !empty($transaction->payment_type_value) ? html_escape(ucwords(strtolower($transaction->payment_type_value))) : '-'; ?></td>
                            <td><?php echo !empty($transaction->payment_reference_value) ? html_escape($transaction->payment_reference_value) : '-'; ?></td>
                            <td><?php echo !empty($transaction->customer_name) ? html_escape($transaction->customer_name) : '-'; ?></td>
                            <td><?php echo !empty($transaction->loan_number) ? html_escape($transaction->loan_number) : '-'; ?></td>
                            <td><?php echo !empty($transaction->added_by_name) ? html_escape($transaction->added_by_name) : '-'; ?></td>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>
                <?php if ($should_fetch && empty($transactions_data)) { ?>
                    <p>No transactions found for the selected filters.</p>
                <?php } elseif (!$should_fetch) { ?>
                    <p>Use the filters above, then click Filter Transactions to load data or Generate Report to process the report in the background.</p>
                <?php } ?>
            </div>
        </div>
    </div>

    <div class="card loan-payments-card">
        <div class="card-body">
            <div class="loan-payments-head">
                <div>
                    <h4>Loan Account Transactions</h4>
                    <p>This section shows the exact account transactions posted against the filtered loan number.</p>
                </div>
                <?php if (!empty($filters['loan_number'])) { ?>
                    <span class="info-badge">Loan Number: <?php echo html_escape($filters['loan_number']); ?></span>
                <?php } ?>
            </div>

            <?php if (empty($filters['loan_number'])) { ?>
                <div class="loan-payments-empty">Enter a loan number in the filter above to load loan account transactions.</div>
            <?php } else { ?>
                <?php if (!empty($loan_payment_rows)) { ?>
                    <div class="table-responsive">
                        <table id="loan-account-transactions-table" class="table table-bordered table-striped">
                            <thead>
                            <tr>
                                <th>#</th>
                                <th>Account Number</th>
                                <th>Reference Number</th>
                                <th>Credit</th>
                                <th>Debit</th>
                                <th>Transaction type</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php $row_number = 1; ?>
                            <?php foreach ($loan_payment_rows as $payment_row) { ?>
                                <tr>
                                    <td><?php echo $row_number; ?></td>
                                    <td><?php echo html_escape($payment_row->account_number); ?></td>
                                    <td><?php echo html_escape($payment_row->transaction_id); ?></td>
                                    <td><?php echo number_format((float)$payment_row->credit, 2); ?></td>
                                    <td><?php echo number_format((float)$payment_row->debit, 2); ?></td>
                                    <td><?php echo html_escape($payment_row->transaction_type); ?></td>
                                    <td><?php echo !empty($payment_row->system_time) ? html_escape($payment_row->system_time) : '-'; ?></td>
                                    <td>
                                        <?php if (!empty($payment_row->reversed) && $payment_row->reversed === 'Yes') { ?>
                                            Reversed
                                        <?php } elseif (!empty($loan_payment_loan) && isset($loan_payment_loan->paid_off) && $loan_payment_loan->paid_off === 'YES') { ?>
                                            -
                                        <?php } else { ?>
                                            <a href="<?php echo base_url('Loan/transaction_reversal?tid=' . $payment_row->transaction_id . '&account=' . $payment_row->account_number); ?>" onclick="return confirm('Are you sure you want to reverse this transaction? this is not recoverable')">Reverse transaction</a>
                                        <?php } ?>
                                    </td>
                                </tr>
                                <?php $row_number++; ?>
                            <?php } ?>
                            </tbody>
                        </table>
                    </div>
                <?php } else { ?>
                    <div class="loan-payments-empty"><?php echo html_escape($loan_payment_message); ?></div>
                <?php } ?>
            <?php } ?>
        </div>
    </div>

</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var reportButton = document.getElementById('generate-track-transactions-report');
    var reportForm = document.getElementById('track-transactions-report-form');

    if (!reportButton || !reportForm) {
        return;
    }

    reportButton.addEventListener('click', function () {
        reportForm.elements.from.value = document.getElementById('from').value;
        reportForm.elements.to.value = document.getElementById('to').value;
        reportForm.elements.customer_name.value = document.getElementById('customer_name').value;
        reportForm.elements.loan_number.value = document.getElementById('loan_number').value;
        reportForm.elements.transaction_type.value = document.getElementById('transaction_type').value;
        reportForm.submit();
    });

    if (window.jQuery && window.jQuery.fn && window.jQuery.fn.DataTable) {
        var loanTransactionsTable = window.jQuery('#loan-account-transactions-table');
        if (loanTransactionsTable.length && !window.jQuery.fn.DataTable.isDataTable('#loan-account-transactions-table')) {
            loanTransactionsTable.DataTable({
                order: [],
                pageLength: 10,
                lengthChange: false
            });
        }
    }
});
</script>
