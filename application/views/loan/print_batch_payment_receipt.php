<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Batch Payment Receipt</title>
    <style>
        :root {
            --text: #1f2937;
            --muted: #6b7280;
            --line: #d1d5db;
            --head: #f3f4f6;
            --accent: #0f766e;
            --danger: #b91c1c;
        }

        body {
            margin: 0;
            font-family: "Segoe UI", Tahoma, Arial, sans-serif;
            color: var(--text);
            background: #ffffff;
        }

        .receipt {
            max-width: 980px;
            margin: 20px auto;
            border: 1px solid var(--line);
            border-radius: 8px;
            overflow: hidden;
        }

        .header {
            background: var(--head);
            padding: 18px 22px;
            border-bottom: 1px solid var(--line);
        }

        .header h1 {
            margin: 0;
            font-size: 22px;
            letter-spacing: 0.3px;
        }

        .header p {
            margin: 6px 0 0;
            color: var(--muted);
            font-size: 13px;
        }

        .meta {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px 24px;
            padding: 16px 22px;
            border-bottom: 1px solid var(--line);
            font-size: 14px;
        }

        .meta-item {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            border-bottom: 1px dashed #e5e7eb;
            padding: 6px 0;
        }

        .meta-label {
            color: var(--muted);
        }

        .meta-value {
            font-weight: 600;
            text-align: right;
        }

        .shared-reference {
            margin: 16px 22px 0;
            padding: 12px;
            border: 1px solid #99f6e4;
            background: #f0fdfa;
            border-radius: 6px;
            font-size: 14px;
        }

        .shared-reference strong {
            color: var(--accent);
        }

        .table-wrap {
            padding: 14px 22px 22px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        th,
        td {
            border: 1px solid var(--line);
            padding: 9px 10px;
            text-align: left;
        }

        th {
            background: var(--head);
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .amount {
            text-align: right;
            white-space: nowrap;
            font-weight: 600;
        }

        tfoot td {
            font-weight: 700;
            background: #f9fafb;
        }

        .footer {
            border-top: 1px solid var(--line);
            padding: 14px 22px 20px;
            font-size: 12px;
            color: var(--muted);
            line-height: 1.6;
        }

        .refs {
            margin-top: 8px;
            color: var(--danger);
            font-size: 12px;
            word-break: break-word;
        }

        .actions {
            max-width: 980px;
            margin: 0 auto 20px;
            text-align: right;
        }

        .actions button {
            background: #0f766e;
            color: #fff;
            border: none;
            border-radius: 4px;
            padding: 10px 14px;
            cursor: pointer;
            font-size: 14px;
        }

        .actions button:hover {
            background: #0d6b62;
        }

        @media (max-width: 760px) {
            .meta {
                grid-template-columns: 1fr;
            }

            .actions {
                padding: 0 10px;
            }
        }

        @media print {
            body {
                background: #fff;
            }

            .actions {
                display: none;
            }

            .receipt {
                border: none;
                margin: 0;
            }

            @page {
                margin: 10mm;
            }
        }
    </style>
</head>
<body>
<?php $settings = get_by_id('settings', 'settings_id', 1); ?>

<div class="receipt">
    <div class="header">
        <h1><?php echo !empty($settings->company_name) ? $settings->company_name : 'Batch Payment Receipt'; ?></h1>
        <p>Group Loan Batch Payment Allocation Receipt (Audit Copy)</p>
    </div>

    <div class="meta">
        <div class="meta-item">
            <span class="meta-label">Batch Number</span>
            <span class="meta-value"><?php echo htmlspecialchars((string)$batch, ENT_QUOTES, 'UTF-8'); ?></span>
        </div>
        <div class="meta-item">
            <span class="meta-label">Group</span>
            <span class="meta-value"><?php echo htmlspecialchars((string)$group_name . (!empty($group_code) ? ' (' . $group_code . ')' : ''), ENT_QUOTES, 'UTF-8'); ?></span>
        </div>
        <div class="meta-item">
            <span class="meta-label">Payment Type</span>
            <span class="meta-value"><?php echo htmlspecialchars((string)$payment_type, ENT_QUOTES, 'UTF-8'); ?></span>
        </div>
        <div class="meta-item">
            <span class="meta-label">Posted On</span>
            <span class="meta-value"><?php echo htmlspecialchars((string)$posted_on, ENT_QUOTES, 'UTF-8'); ?></span>
        </div>
        <div class="meta-item">
            <span class="meta-label">Posted By</span>
            <span class="meta-value"><?php echo htmlspecialchars((string)$officer, ENT_QUOTES, 'UTF-8'); ?></span>
        </div>
        <div class="meta-item">
            <span class="meta-label">Printed On</span>
            <span class="meta-value"><?php echo htmlspecialchars((string)$printed_on, ENT_QUOTES, 'UTF-8'); ?></span>
        </div>
    </div>

    <div class="shared-reference">
        Shared Transaction/Receipt Reference:
        <strong><?php echo htmlspecialchars((string)$receipt_reference, ENT_QUOTES, 'UTF-8'); ?></strong>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>#</th>
                <th>Loan Number</th>
                <th>Member Name</th>
                <th>Payment Schedule #</th>
                <th>Allocated Amount (MWK)</th>
            </tr>
            </thead>
            <tbody>
            <?php $i = 1; ?>
            <?php foreach ($allocations as $allocation): ?>
                <tr>
                    <td><?php echo $i; ?></td>
                    <td><?php echo htmlspecialchars((string)$allocation->loan_number, ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars(trim((string)$allocation->Firstname . ' ' . (string)$allocation->Lastname), ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo (int)$allocation->payment_number; ?></td>
                    <td class="amount"><?php echo number_format((float)$allocation->amount, 2); ?></td>
                </tr>
                <?php $i++; ?>
            <?php endforeach; ?>
            </tbody>
            <tfoot>
            <tr>
                <td colspan="4">Total Allocated Amount</td>
                <td class="amount"><?php echo number_format((float)$total_allocated, 2); ?></td>
            </tr>
            </tfoot>
        </table>
    </div>

    <div class="footer">
        This receipt confirms one shared external payment reference applied to multiple member-loan allocations in the selected group batch.
        <div class="refs">
            Internal System Transaction Refs:
            <?php echo htmlspecialchars(implode(', ', $internal_refs), ENT_QUOTES, 'UTF-8'); ?>
        </div>
    </div>
</div>

<div class="actions">
    <button type="button" onclick="window.print()">Print Receipt</button>
</div>
</body>
</html>
