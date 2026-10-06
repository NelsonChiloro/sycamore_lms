<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Batch Statement</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 10px; color: #111; }
        h1, h2 { margin: 0 0 6px; text-align: center; }
        .meta { margin: 12px 0 16px; text-align: center; font-size: 11px; }
        .meta p { margin: 3px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #333; padding: 5px; }
        th { background: #f3f3f3; }
        td.number { text-align: right; }
    </style>
</head>
<body>
<?php $settings = get_by_id('settings', 'settings_id', '1'); ?>
<h1><?php echo $settings ? html_escape($settings->company_name) : 'Batch Statement'; ?></h1>
<div class="meta">
    <h2>Batch Statement — Loan Account Transactions</h2>
    <p><strong>Batch:</strong> <?php echo html_escape($batch); ?></p>
    <p><strong>Group:</strong> <?php echo html_escape($group_name); ?> (<?php echo html_escape($group_code); ?>)</p>
    <?php if (!empty($filters['from']) || !empty($filters['to'])): ?>
        <p><strong>Period:</strong>
            <?php echo !empty($filters['from']) ? html_escape($filters['from']) : '—'; ?>
            to
            <?php echo !empty($filters['to']) ? html_escape($filters['to']) : '—'; ?>
        </p>
    <?php endif; ?>
    <?php if (!empty($filters['loan_number'])): ?>
        <p><strong>Loan Number:</strong> <?php echo html_escape($filters['loan_number']); ?></p>
    <?php endif; ?>
    <p><strong>Transactions:</strong> <?php echo count($transactions); ?></p>
    <p>Generated on <?php echo html_escape($generated_at); ?></p>
</div>

<?php if (!empty($transactions)): ?>
<table>
    <thead>
    <tr>
        <th>#</th>
        <th>Group Member</th>
        <th>Account Number</th>
        <th>Reference Number</th>
        <th>Credit</th>
        <th>Debit</th>
        <th>Transaction type</th>
        <th>Date</th>
    </tr>
    </thead>
    <tbody>
    <?php $row_number = 1; ?>
    <?php foreach ($transactions as $payment_row): ?>
        <tr>
            <td><?php echo $row_number; ?></td>
            <td><?php echo html_escape($payment_row->member_name); ?></td>
            <td><?php echo html_escape($payment_row->account_number); ?></td>
            <td><?php echo html_escape($payment_row->transaction_id); ?></td>
            <td class="number"><?php echo number_format((float) $payment_row->credit, 2); ?></td>
            <td class="number"><?php echo number_format((float) $payment_row->debit, 2); ?></td>
            <td><?php echo html_escape($payment_row->transaction_type); ?></td>
            <td><?php echo !empty($payment_row->system_time) ? html_escape($payment_row->system_time) : '-'; ?></td>
        </tr>
        <?php $row_number++; ?>
    <?php endforeach; ?>
    </tbody>
</table>
<?php else: ?>
<p style="text-align:center;">No transactions found for the selected filters.</p>
<?php endif; ?>
</body>
</html>
