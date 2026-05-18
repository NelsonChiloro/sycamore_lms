<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Transactions Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            color: #111;
        }
        h1, h2 {
            margin: 0;
            text-align: center;
        }
        .meta {
            margin: 16px 0;
            text-align: center;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 16px;
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
    </style>
</head>
<body>
<?php $settings = get_by_id('settings', 'settings_id', '1'); ?>
<h1><?php echo $settings ? $settings->company_name : 'Transactions Report'; ?></h1>
<div class="meta">
    <div>Transactions Report</div>
    <div>Generated on <?php echo date('Y-m-d H:i:s'); ?></div>
</div>

<table>
    <thead>
    <tr>
        <th>#</th>
        <th>Account Number</th>
        <th>Reference Number</th>
        <th>Credit</th>
        <th>Debit</th>
        <th>Transaction Type</th>
        <th>Date</th>
    </tr>
    </thead>
    <tbody>
    <?php $index = 1; ?>
    <?php foreach ($loan_data as $transaction): ?>
        <tr>
            <td><?php echo $index; ?></td>
            <td><?php echo $transaction->account_number; ?></td>
            <td><?php echo $transaction->transaction_id; ?></td>
            <td class="number"><?php echo number_format((float) $transaction->credit, 2); ?></td>
            <td class="number"><?php echo number_format((float) $transaction->debit, 2); ?></td>
            <td><?php echo $transaction->transaction_type; ?></td>
            <td><?php echo $transaction->system_time; ?></td>
        </tr>
        <?php $index++; ?>
    <?php endforeach; ?>
    </tbody>
</table>
</body>
</html>