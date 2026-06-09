<?php
$settings = get_by_id('settings', 'settings_id', '1');
?>
<div class="section">
	<div class="content">
		<h2 style="text-align: center;">Loan Summary Report</h2>

		<table id="pattern-style-a">
			<tr>
				<td colspan="2">
					<table>
						<tr><td width="40%">Borrower Name:</td><td><strong><?= $loan_customer ?></strong></td></tr>
						<tr><td>Principal Amount:</td><td><strong><?php echo $settings->currency ?><?= number_format($loan_principal,2) ?></strong></td></tr>
						<tr><td>Total loan Amount:</td><td><strong><?php echo $settings->currency ?><?= number_format($loan_amount_total,2) ?></strong></td></tr>
						<tr><td>Interest rate:</td><td><strong><?= $loan_interest ?>%</strong></td></tr>
						<tr><td>Loan term:</td><td><strong><?= $loan_period ?> <?= $period_type ?></strong></td></tr>
						<tr><td>Amortization:</td><td><strong><?php echo $settings->currency ?><?= number_format($loan_amount_term,2) ?></strong></td></tr>
					</table>
				</td>
				<td colspan="4"></td>
				<td colspan="2">
					<table>
						<tr><td>Loan ID:</td><td><strong><?php echo $loan_number ?></strong></td></tr>
						<tr><td>Loan Date:</td><td><strong><?= $loan_date ?></strong></td></tr>
						<tr><td>Maturity Date:</td><td><strong><?= $maturity_date ?></strong></td></tr>
						<tr><td>Last Deduction:</td><td><strong><?php echo $settings->currency ?><?= number_format($maturity_pay,2) ?></strong></td></tr>
						<tr><td>First Deduction:</td><td><strong><?php echo $settings->currency ?><?=  number_format($first_payment,2)?></strong></td></tr>
						<tr><td>Deduction Date:</td><td><strong><?=  $first_payment_date ?></strong></td></tr>
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
				<th>Payment #</th>
				<th>Check Date</th>
				<th>Principal(<?php echo $settings->currency ?>)</th>
				<th>Interest(<?php echo $settings->currency ?>)</th>
				<th>Admin fee(<?php echo $settings->currency ?>)</th>
				<th>Loan cover(<?php echo $settings->currency ?>)</th>
				<th>Amount(<?php echo $settings->currency ?>)</th>
				<th>Amount Paid(<?php echo $settings->currency ?>)</th>
				<th>Loan Balance(<?php echo $settings->currency ?>)</th>
				<th>Status</th>
			</tr>
			</thead>
			<tbody>
			<?php foreach ($payments as $p): ?>
				<?php
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
					<td <?php echo $css; ?>><?php echo $p->payment_number ?></td>
					<td <?php echo $css; ?>><?php echo $p->payment_schedule ?></td>
					<td <?php echo $css; ?>><?php echo number_format($p->principal, 2) ?></td>
					<td <?php echo $css; ?>><?php echo number_format($p->interest, 2) ?></td>
					<td <?php echo $css; ?>><?php echo number_format($p->padmin_fee, 2) ?></td>
					<td <?php echo $css; ?>><?php echo number_format($p->ploan_cover, 2) ?></td>
					<td <?php echo $css; ?>><?php echo number_format($p->amount, 2) ?></td>
					<td <?php echo $css; ?>><?php echo number_format($p->paid_amount, 2) ?></td>
					<td <?php echo $css; ?>><?php echo number_format($p->loan_balance, 2) ?></td>
					<td><?php echo $p->status . $xstatus; ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<hr>
		<div class="title">Payment History/Transactions</div>
		<table class="collapse" id="pattern-style-a">
			<thead>
			<tr>
				<th>Ref ID</th>
				<th>Paid amount</th>
				<th>Payment number</th>
				<th>Paid Date</th>
			</tr>
			</thead>
			<tbody>
			<?php foreach ($transa as $trans): ?>
				<tr>
					<td><?php echo $trans->ref ?></td>
					<td><?php echo number_format($trans->amount, 2) ?></td>
					<td><?php echo $trans->payment_number ?></td>
					<td><?php
                        if (!($trans->payment_number) == 0) {
                            $paiddate = get_paid_date('payement_schedules', $trans->loan_id, $trans->payment_number);
                            echo $paiddate ? $paiddate->paid_date : '';
                        } else {
                            echo '0000-00-00 00:00:00';
                        }
                        ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	</div>

    <div class="title" style="page-break-before: always;">Debit and Credit Transactions</div>
    <table class="collapse" id="pattern-style-a">
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
        <?php foreach ($datadeposits as $trans): ?>
            <tr>
                <td><?php echo $trans->transaction_id ?></td>
                <td><?php echo $trans->account_number ?></td>
                <td><?php echo number_format($trans->credit, 2) ?></td>
                <td><?php echo number_format($trans->debit, 2) ?></td>
                <td><?php echo number_format($trans->balance, 2) ?></td>
                <td><?php echo $trans->system_time ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<div style="margin: auto"><strong>********** NOTHING FOLLOWS **********</strong></div>
