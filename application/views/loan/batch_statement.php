<?php
$statement_base = base_url('loan/batch_statement/' . rawurlencode($batch));
$report_params = array('run' => '1');
if (!empty($filters['from'])) {
    $report_params['from'] = $filters['from'];
}
if (!empty($filters['to'])) {
    $report_params['to'] = $filters['to'];
}
if (!empty($filters['loan_id'])) {
    $report_params['loan_id'] = $filters['loan_id'];
}
$report_query = http_build_query($report_params);
$batch_report_url = base_url('loan/batch_statement_report/' . rawurlencode($batch)) . ($report_query ? '?' . $report_query : '');
?>
<div class="main-content">
    <div class="page-header">
        <h2 class="header-title">Batch Account Statement</h2>
        <div class="header-sub-title">
            <nav class="breadcrumb breadcrumb-dash">
                <a href="<?php echo base_url('Admin'); ?>" class="breadcrumb-item"><i class="anticon anticon-home m-r-5"></i>Home</a>
                <a href="<?php echo base_url('loan/group_file'); ?>" class="breadcrumb-item">Group Loans</a>
                <a href="<?php echo html_escape($batch_page_url); ?>" class="breadcrumb-item">Batch <?php echo html_escape($batch); ?></a>
                <span class="breadcrumb-item active">Batch Account Statement</span>
            </nav>
        </div>
    </div>

    <div class="card">
        <div class="card-body" style="border: thick green solid;border-radius: 14px;">
            <p class="mb-2">
                <strong>Batch:</strong> <?php echo html_escape($batch); ?>
                &nbsp;|&nbsp;
                <strong>Group:</strong> <?php echo html_escape($group_name); ?> (<?php echo html_escape($group_code); ?>)
            </p>

            <form action="<?php echo html_escape($statement_base); ?>" method="GET">
                <input type="hidden" name="run" value="1">
                <div class="row">
                    <div class="col-md-4">
                        <label>Group Member</label>
                        <select name="loan_id" class="form-control">
                            <option value="">All members</option>
                            <?php foreach ($batch_members as $member): ?>
                                <option value="<?php echo (int) $member->loan_id; ?>" <?php echo ((string) $filters['loan_id'] === (string) $member->loan_id) ? 'selected' : ''; ?>>
                                    <?php echo html_escape($member->member_name . ' — ' . $member->loan_number); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label>From Date</label>
                        <input type="date" name="from" class="form-control" value="<?php echo html_escape($filters['from']); ?>">
                    </div>
                    <div class="col-md-3">
                        <label>To Date</label>
                        <input type="date" name="to" class="form-control" value="<?php echo html_escape($filters['to']); ?>">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <input type="submit" class="btn btn-sm btn-info" value="Search transactions">
                    </div>
                </div>
            </form>

            <hr>

            <?php if ($filters['run'] && !empty($member_statements)): ?>
            <p>
                <a href="<?php echo html_escape($batch_report_url); ?>" class="btn btn-success" target="_blank">
                    <i class="fa fa-file-pdf text-danger"></i> Print all batch statements (PDF)
                </a>
                <a href="<?php echo html_escape($batch_page_url); ?>" class="btn btn-default btn-sm ml-2">Back to batch</a>
            </p>
            <?php endif; ?>

            <?php if (!$filters['run']): ?>
                <p class="text-muted mt-3">Click <strong>Search transactions</strong> to load account statements for all members in this batch.</p>
            <?php elseif (empty($member_statements)): ?>
                <p class="text-muted mt-3">No members match the selected filters.</p>
            <?php else: ?>
                <?php foreach ($member_statements as $index => $statement): ?>
                    <?php if ($index > 0): ?>
                        <hr style="border-top: 2px solid #ccc; margin: 2em 0;">
                    <?php endif; ?>

                    <div class="mb-2">
                        <strong><?php echo html_escape($statement['member_name']); ?></strong>
                        &nbsp;|&nbsp; Loan: <strong><?php echo html_escape($statement['loan_number']); ?></strong>
                        &nbsp;|&nbsp; Status: <?php echo html_escape($statement['loan_status']); ?>
                        <form action="<?php echo base_url('transactions/report'); ?>" method="GET" style="display:inline-block; margin-left: 10px;">
                            <input type="hidden" name="loan_id" value="<?php echo (int) $statement['loan_id']; ?>">
                            <button type="submit" class="btn btn-success btn-sm">
                                <i class="fa fa-file-pdf text-danger"></i> Print
                            </button>
                        </form>
                    </div>

                    <table class="table table-bordered batch-member-statement-table">
                        <thead>
                        <tr>
                            <th>Ref ID</th>
                            <th>Account number</th>
                            <th>CR</th>
                            <th>DR</th>
                            <th>Bal</th>
                            <th>Date</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($statement['rows'])): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted">No transactions found for this account.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($statement['rows'] as $trans): ?>
                                <tr>
                                    <td><?php echo html_escape($trans->transaction_id); ?></td>
                                    <td><?php echo html_escape($trans->account_number); ?></td>
                                    <td><?php echo number_format((float) $trans->credit, 2); ?></td>
                                    <td><?php echo number_format((float) $trans->debit, 2); ?></td>
                                    <td><?php echo number_format((float) $trans->balance, 2); ?></td>
                                    <td><?php echo html_escape($trans->system_time); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
