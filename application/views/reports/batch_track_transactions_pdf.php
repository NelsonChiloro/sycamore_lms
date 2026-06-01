<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Batch Loan Account Transactions</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            color: #111;
        }
        h1, h2, h3 {
            margin: 0 0 8px;
        }
        h1, h2 {
            text-align: center;
        }
        .meta, .member-meta {
            margin: 12px 0;
        }
        .meta {
            text-align: center;
        }
        .member-meta p {
            margin: 4px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
        }
        th, td {
            border: 1px solid #333;
            padding: 6px;
        }
        th {
            background: #f3f3f3;
        }
        td.number {
            text-align: right;
        }
        .page-break {
            page-break-before: always;
        }
        .empty-note {
            margin-top: 12px;
            font-style: italic;
        }
    </style>
</head>
<body>
<?php $settings = get_by_id('settings', 'settings_id', '1'); ?>
<h1><?php echo $settings ? html_escape($settings->company_name) : 'Loan Account Transactions'; ?></h1>
<div class="meta">
    <h2>Group Batch — Loan Account Transactions</h2>
    <p><strong>Batch:</strong> <?php echo html_escape($batch); ?></p>
    <p><strong>Group:</strong> <?php echo html_escape($group_name); ?> (<?php echo html_escape($group_code); ?>)</p>
    <p><strong>Members:</strong> <?php echo (int) $member_count; ?></p>
    <p>Generated on <?php echo date('Y-m-d H:i:s'); ?></p>
</div>

<?php foreach ($members as $index => $member): ?>
<?php if ($index > 0): ?>
<div class="page-break"></div>
<?php endif; ?>

<h3>Loan Account Transactions</h3>
<div class="member-meta">
    <p><strong>Member:</strong> <?php echo html_escape($member['member_name']); ?></p>
    <p><strong>Loan Number:</strong> <?php echo html_escape($member['loan_number']); ?></p>
    <p><strong>Status:</strong> <?php echo html_escape($member['loan_status']); ?></p>
</div>

<?php if (!empty($member['transactions'])): ?>
<table>
    <thead>
    <tr>
        <th>#</th>
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
    <?php foreach ($member['transactions'] as $payment_row): ?>
        <tr>
            <td><?php echo $row_number; ?></td>
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
<p class="empty-note">No loan account transactions were found for this loan number.</p>
<?php endif; ?>
<?php endforeach; ?>
</body>
</html>
