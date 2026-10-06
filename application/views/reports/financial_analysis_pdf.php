<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Financial Analysis Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
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
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #333;
            padding: 8px;
        }
        th {
            text-align: left;
            background: #f3f3f3;
        }
        td.amount {
            text-align: right;
        }
    </style>
</head>
<body>
<?php $settings = get_by_id('settings', 'settings_id', '1'); ?>
<h1><?php echo $settings ? $settings->company_name : 'Financial Analysis Report'; ?></h1>
<div class="meta">
    <div>Financial Analysis Report</div>
    <div>Generated on <?php echo date('Y-m-d H:i:s'); ?></div>
</div>

<table>
    <thead>
    <tr>
        <th>Revenue Stream</th>
        <th>Amount</th>
    </tr>
    </thead>
    <tbody>
    <tr>
        <td>Interests Income From Loans</td>
        <td class="amount">MWK <?php echo number_format($interests_income->interest, 2); ?></td>
    </tr>
    <tr>
        <td>Income From Processing Fee</td>
        <td class="amount">MWK <?php echo number_format($admin_income->amount, 2); ?></td>
    </tr>
    <tr>
        <td>Income From Admin Fee</td>
        <td class="amount">MWK <?php echo number_format($admin_fee->admin_fee, 2); ?></td>
    </tr>
    <tr>
        <td>Income From Loan Cover</td>
        <td class="amount">MWK <?php echo number_format($loan_cover->loan_cover, 2); ?></td>
    </tr>
    <tr>
        <td>Late Paying Fees</td>
        <td class="amount">MWK <?php echo number_format($late_fee->amount, 2); ?></td>
    </tr>
    <tr>
        <td>Bad Debits</td>
        <td class="amount">MWK <?php echo number_format(isset($bad_debits->amount) ? $bad_debits->amount : 0, 2); ?></td>
    </tr>
    <tr>
        <td>Interest Paid</td>
        <td class="amount">MWK <?php echo number_format(isset($interest_paid->interest) ? $interest_paid->interest : 0, 2); ?></td>
    </tr>
    <tr>
        <td>Expenses</td>
        <td class="amount">MWK <?php echo number_format(isset($expenses->amount) ? $expenses->amount : 0, 2); ?></td>
    </tr>
    </tbody>
</table>
</body>
</html>