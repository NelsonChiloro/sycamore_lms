<?php
if (!isset($settings)) {
    $settings = get_by_id('settings', 'settings_id', '1');
}
$statement_title = !empty($statement_title) ? $statement_title : 'Loan Summary Report';
$batch_label = !empty($batch_label) ? $batch_label : '';
?>
<div class="section">
    <div class="content">
        <h1 style="text-align: center;"><?php echo htmlspecialchars($settings->company_name); ?></h1>
        <table width="100%">
            <?php
            $link = base_url('uploads/') . $settings->logo;
            $img = 'data:image;base64,' . base64_encode(file_get_contents($link));
            ?>
            <tr>
                <td style="float: left;padding-right: 5em; margin-left: 1em;">
                    <img src="<?php echo $img; ?>" alt="">
                </td>
                <td style="float: right;margin-left: 5em;">
                    <?php echo $settings->address ?>
                    <?php echo $settings->company_email ?>/<?php echo $settings->phone_number ?>
                </td>
            </tr>
        </table>
        <hr>
        <h2 style="text-align: center;"><?php echo htmlspecialchars($statement_title); ?></h2>
        <?php if ($batch_label !== ''): ?>
        <p style="text-align: center;"><strong>Batch:</strong> <?php echo htmlspecialchars($batch_label); ?></p>
        <?php endif; ?>
        <?php if (!empty($group_label)): ?>
        <p style="text-align: center;"><strong>Group:</strong> <?php echo htmlspecialchars($group_label); ?></p>
        <?php endif; ?>

        <table id="pattern-style-a">
            <tr>
                <td colspan="2">
                    <table>
                        <tr><td width="40%">Borrower Name:</td><td><strong><?php echo htmlspecialchars($loan_customer); ?></strong></td></tr>
                        <tr><td>Principal Amount:</td><td><strong><?php echo $settings->currency ?><?php echo number_format($loan_principal, 2); ?></strong></td></tr>
                        <tr><td>Principal + Interest and Charges:</td><td><strong><?php echo $settings->currency ?><?php echo number_format($loan_amount_total, 2); ?></strong></td></tr>
                        <tr><td>Loan product:</td><td><strong><?php echo htmlspecialchars($product_name); ?></strong></td></tr>
                        <tr><td>Branch:</td><td><strong><?php echo htmlspecialchars($branch_name); ?></strong></td></tr>
                        <tr><td>Interest rate:</td><td><strong><?php echo $loan_interest; ?>%</strong></td></tr>
                        <tr><td>Loan term ( <?php echo htmlspecialchars($period_type); ?> ):</td><td><strong><?php echo (int) $loan_period; ?> terms</strong></td></tr>
                        <tr><td>Amortization:</td><td><strong><?php echo $settings->currency ?><?php echo number_format($loan_amount_term, 2); ?></strong></td></tr>
                    </table>
                </td>
                <td colspan="4"></td>
                <td colspan="2">
                    <table>
                        <tr><td>Loan ID:</td><td><strong><?php echo htmlspecialchars($loan_number); ?></strong></td></tr>
                        <tr><td>Loan Date:</td><td><strong><?php echo htmlspecialchars($loan_date); ?></strong></td></tr>
                        <tr><td>Maturity Date:</td><td><strong><?php echo htmlspecialchars($maturity_date); ?></strong></td></tr>
                        <tr><td>Last Deduction:</td><td><strong><?php echo $settings->currency ?><?php echo number_format($maturity_pay, 2); ?></strong></td></tr>
                        <tr><td>First Deduction:</td><td><strong><?php echo $settings->currency ?><?php echo number_format($first_payment, 2); ?></strong></td></tr>
                        <tr><td>Deduction Date:</td><td><strong><?php echo htmlspecialchars($first_payment_date); ?></strong></td></tr>
                        <tr><td>Loan Officer:</td><td><strong><?php echo htmlspecialchars($officer); ?></strong></td></tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>
</div>

<div class="section">
    <div class="title">Summary</div>
    <br>
    <div class="content">
        <table class="collapse" id="pattern-style-a">
            <thead>
            <tr>
                <th>Pay #</th>
                <th>Date Due</th>
                <th><?php echo ($period_type == '2 Weeks') ? 'Principal + Other Charges' : 'Principal'; ?>(<?php echo $settings->currency ?>)</th>
                <th>Interest + Charges(<?php echo $settings->currency ?>)</th>
                <th>Amount Due(<?php echo $settings->currency ?>)</th>
                <th>Amount Paid(<?php echo $settings->currency ?>)</th>
                <th>Theoretical Bal*(<?php echo $settings->currency ?>)</th>
                <th>Actual Bal(<?php echo $settings->currency ?>)</th>
                <th>Status</th>
            </tr>
            </thead>
            <tbody>
            <?php
            $outstanding_balance = get_loan_outstanding_balance($loan_id);
            foreach ($payments as $p) {
                $css = '';
                $xstatus = '';
                if ($p->payment_schedule < date('Y-m-d') && $p->status == 'NOT PAID') {
                    $css = ' class="due"';
                    $xstatus = ' | OVER DUE';
                } elseif ($p->status == 'PAID') {
                    $css = 'class="paid"';
                } elseif ($p->payment_schedule == date('Y-m-d') && $p->status == 'NOT PAID') {
                    $css = ' class="due_now"';
                    $xstatus = ' | DUE TODAY';
                }
            ?>
                <tr>
                    <td <?php echo $css; ?>><?php echo (int) $p->payment_number; ?></td>
                    <td <?php echo $css; ?>><?php echo htmlspecialchars($p->payment_schedule); ?></td>
                    <td <?php echo $css; ?>><?php echo number_format($p->principal, 2); ?></td>
                    <td <?php echo $css; ?>><?php echo number_format($p->interest, 2); ?></td>
                    <td <?php echo $css; ?>><?php echo number_format($p->amount, 2); ?></td>
                    <td <?php echo $css; ?>><?php echo number_format($p->paid_amount, 2); ?></td>
                    <td <?php echo $css; ?>><?php echo number_format($p->loan_balance, 2); ?></td>
                    <td <?php echo $css; ?>><?php echo number_format($outstanding_balance, 2); ?></td>
                    <td><?php echo htmlspecialchars($p->status . $xstatus); ?></td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
    </div>
</div>

<div class="section">
    <div class="title">Collateral (NB collateral attachment should be downloaded and attached to this report)</div>
    <br>
    <div class="content">
        <?php $collaterals = get_all_by_id('collateral', 'loan_id', $loan_id); ?>
        <table class="collapse" id="pattern-style-a">
            <tr>
                <th>Collateral Name</th>
                <th>Collateral Type</th>
                <th>Serial</th>
                <th>Estimated Price</th>
                <th>Description</th>
                <th>Date Added</th>
            </tr>
            <?php foreach ($collaterals as $collateral): ?>
            <tr>
                <td><?php echo htmlspecialchars($collateral->collateral_name); ?></td>
                <td><?php echo htmlspecialchars($collateral->collateral_type); ?></td>
                <td><?php echo htmlspecialchars($collateral->serial); ?></td>
                <td>MK<?php echo number_format($collateral->estimated_price, 2); ?></td>
                <td><?php echo htmlspecialchars($collateral->description); ?></td>
                <td><?php echo htmlspecialchars($collateral->date_added); ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>
<div style="margin: auto"><strong>********** NOTHING FOLLOWS **********</strong></div>
