<?php
$linkk = base_url('admin_assets/images/pattern.png');
$imgg = 'data:image;base64,'.base64_encode(file_get_contents($linkk))
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN""http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en"><head>
	<style>

		p {
			text-align: justify;
			margin:0;
		}
		table {width:100%;}
		table.collapse {
			border-collapse: collapse;
		}

		tr td, tr th {
			text-align: right;
		}

		tr.total {
			font-weight: 900;
		}
		hr {
			margin: 15px 0;
		}
		h1 {
			margin:0;
		}
		.title {
			color: #000;
			font-size: 18px;
			font-weight: normal;
		}

		.section {
			border-bottom: 1px #D4D4D4 solid;
			padding: 10px 0;
			margin-bottom: 20px;
		}

		.section .content {
			margin-left: 10px;
		}

		#hor-minimalist-b
		{
			font-family: "Lucida Sans Unicode", "Lucida Grande", Sans-Serif;
			font-size: 12px;
			background: #fff;
			width: 480px;
			border-collapse: collapse;
			text-align: center;
		}
		#hor-minimalist-b th
		{
			font-size: 14px;
			font-weight: 900;
			padding: 10px 8px;
			border-bottom: 2px solid #000;
			text-align: center;
		}
		#hor-minimalist-b td
		{
			border-bottom: 1px solid #ccc;
			padding: 6px 8px;
		}

		#pattern-style-a
		{
			font-family: "Lucida Sans Unicode", "Lucida Grande", Sans-Serif;
			font-size: 12px;
			width: 100%;
			text-align: left;
			border-collapse: collapse;
			background: url('<?php echo $imgg; ?>');
		}

		#pattern-style-a th
		{
			font-size: 13px;
			font-weight: normal;
			padding: 8px;
			border-bottom: 1px solid #fff;
			color: #039;
		}
		#pattern-style-a td
		{
			padding: 3px;
			border-bottom: 1px solid #fff;
			color: #000;
			border-top: 1px solid transparent;
		}
		#pattern-style-a tbody tr:hover td
		{
			color: #339;
			background: #fff;
		}

		* {
			box-sizing: border-box;
		}

		html {
			font-family: sans-serif;
		}

		.page-break {
			page-break-before: always;
		}

		.loan-summary-header {
			background-color: #f8f9fc;
			padding: 10px;
			margin: 20px 0;
			border: 2px solid #e3e6f0;
			text-align: center;
		}

		/* Payment schedule specific styles */
		#pattern-style-a thead th {
			font-size: 11px;
			padding: 6px 4px;
			white-space: nowrap;
		}

		#pattern-style-a tbody td {
			font-size: 10px;
			padding: 4px 3px;
			text-align: center;
		}

		#pattern-style-a tbody tr:nth-child(even) {
			background-color: #f9f9f9;
		}

	</style>

</head><body>

	<div class="section">
		<div class="content">
			<h1 style="text-align: center;"><?php
				$settings = get_by_id('settings','settings_id','1');
				echo $settings->company_name ?></h1>
			<table width="100%">
				<?php

				$link = base_url('uploads/').$settings->logo;
				$img = 'data:image;base64,'.base64_encode(file_get_contents($link))
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
			<h2 style="text-align: center;">Batch Loan Summary Report</h2>
			<div style="text-align: center; margin: 20px 0;">
				<strong>Batch Number: <?php echo $batch_number; ?></strong><br>
				<strong>Group: <?php echo $group_name . ' (' . $group_code . ')'; ?></strong><br>
				<strong>Report Generated: <?php echo date('Y-m-d H:i:s'); ?></strong>
			</div>
		</div>
	</div>

	<!-- Batch Summary -->
	<div class="section">
		<div class="title">Batch Summary</div>
		<br>
		<div class="content">
			<table id="pattern-style-a">
				<tr>
					<td><strong>Total Number of Loans:</strong></td>
					<td><strong><?php echo count($loans); ?></strong></td>
					<td><strong>Total Principal Amount:</strong></td>
					<td><strong><?php echo $settings->currency ?><?php echo number_format(array_sum(array_column($loans, 'loan_principal')), 2); ?></strong></td>
				</tr>
				<tr>
					<td><strong>Total Loan Amount (Principal + Interest):</strong></td>
					<td><strong><?php echo $settings->currency ?><?php echo number_format(array_sum(array_column($loans, 'loan_amount_total')), 2); ?></strong></td>
					<td><strong>Average Loan Size:</strong></td>
					<td><strong><?php echo $settings->currency ?><?php echo number_format(array_sum(array_column($loans, 'loan_principal')) / count($loans), 2); ?></strong></td>
				</tr>
			</table>
		</div>
	</div>

	<?php 
	$loan_counter = 1;
	foreach ($loans as $loan): 
		// Get payment schedules for this loan
		$payments = $payment_schedules[$loan->loan_id];
	?>

	<!-- Page break for each loan except the first one -->
	<?php if ($loan_counter > 1): ?>
	<div class="page-break"></div>
	<?php endif; ?>

	<div class="loan-summary-header">
		<h3>LOAN <?php echo chr(64 + $loan_counter); ?> - <?php echo $loan->Firstname . ' ' . $loan->Lastname; ?></h3>
		<p>Loan Number: <?php echo $loan->loan_number; ?></p>
	</div>

	<div class="section">
		<div class="content">
			<table id="pattern-style-a">
				<tr>
					<td colspan="2">
						<table>
							<tr><td width="40%">Borrower Name:</td><td><strong><?= $loan->Firstname . ' ' . $loan->Lastname ?></strong></td></tr>
							<tr><td>Principal Amount:</td><td><strong><?php echo $settings->currency ?><?= number_format($loan->loan_principal,2) ?></strong></td></tr>
							<tr><td>Principal + Interest and Charges:</td><td><strong><?php echo $settings->currency ?><?= number_format($loan->loan_amount_total,2) ?></strong></td></tr>
							<tr><td>Loan product:</td><td><strong><?= $loan->product_name ?></strong></td></tr>
							<tr><td>Branch:</td><td><strong><?= isset($loan->branch_name) ? $loan->branch_name : 'N/A' ?></strong></td></tr>
							<tr><td>Interest rate:</td><td><strong><?= $loan->loan_interest ?>%</strong></td></tr>
							<tr><td>Loan term ( <?= $loan->period_type ?> ):</td><td><strong><?= $loan->loan_period ?> terms</strong></td></tr>
							<tr><td>Amortization:</td><td><strong><?php echo $settings->currency ?><?= number_format($loan->loan_amount_term,2) ?></strong></td></tr>

						</table>
					</td>
					<td colspan="4"></td>
					<td colspan="2">
						<table>
							<tr><td>Loan ID:</td><td><strong><?php echo $loan->loan_number ?></strong></td></tr>
							<tr><td>Loan Date:</td><td><strong><?= $loan->loan_date ?></strong></td></tr>
							<tr><td>Maturity Date:</td><td><strong><?= isset($loan->maturity_date) ? $loan->maturity_date : 'N/A' ?></strong></td></tr>
							<tr><td>Customer ID:</td><td><strong><?= $loan->ClientId ?></strong></td></tr>
							<tr><td>Group:</td><td><strong><?= $group_name . ' (' . $group_code . ')' ?></strong></td></tr>
							<tr><td>Batch:</td><td><strong><?= $batch_number ?></strong></td></tr>
							<tr><td>Loan Officer:</td><td><strong><?= isset($loan->officer_name) ? $loan->officer_name : 'N/A' ?></strong></td></tr>

						</table>
					</td>
				</tr>
			</table>
		</div>
	</div>

	<div class="section">
		<div class="title">Payment Schedule (Amortization) - Loan <?php echo chr(64 + $loan_counter); ?></div>
		<br>
		<div class="content">
			<table class="collapse" id="pattern-style-a">
				<thead>
				<tr>
					<th>Pay #</th>
					<th>Date Due</th>
					<th>Principal(<?php echo $settings->currency ?>)</th>
					<th>Interest(<?php echo $settings->currency ?>)</th>
					<th>Admin Fee(<?php echo $settings->currency ?>)</th>
					<th>Loan Cover(<?php echo $settings->currency ?>)</th>
					<th>Total Amount(<?php echo $settings->currency ?>)</th>
					<th>Amount Paid(<?php echo $settings->currency ?>)</th>
					<th>Balance(<?php echo $settings->currency ?>)</th>
					<th>Status</th>
				</tr>
				</thead>
				<tbody>
				<?php
				if(!empty($payments)) {
					$outstanding_balance = get_loan_outstanding_balance($loan->loan_id);
					foreach ($payments as $p){
						$css = '';
						$xstatus = '';
						if($p->payment_schedule < date('Y-m-d') AND $p->status == 'NOT PAID') {
							$css = ' style="background-color: #ffdddd;"';
							$xstatus = ' | OVERDUE';
						} elseif($p->status=='PAID') {
							$css = ' style="background-color: #ddffdd;"';
						} elseif($p->payment_schedule == date('Y-m-d') AND $p->status == 'NOT PAID') {
							$css = ' style="background-color: #ffffdd;"';
							$xstatus = ' | DUE TODAY';
						}
						?>
						<tr>
							<td<?php echo $css; ?>><?php echo $p->payment_number?></td>
							<td<?php echo $css; ?>><?php echo $p->payment_schedule?></td>
							<td<?php echo $css; ?>><?php echo number_format($p->principal, 2) ?></td>
							<td<?php echo $css; ?>><?php echo number_format($p->interest, 2) ?></td>
							<td<?php echo $css; ?>><?php echo number_format($p->padmin_fee, 2) ?></td>
							<td<?php echo $css; ?>><?php echo number_format($p->ploan_cover, 2) ?></td>
							<td<?php echo $css; ?>><strong><?php echo number_format($p->amount, 2) ?></strong></td>
							<td<?php echo $css; ?>><?php echo number_format($p->paid_amount, 2)?></td>
							<td<?php echo $css; ?>><?php echo number_format($p->loan_balance, 2)?></td>
							<td<?php echo $css; ?>><?php echo $p->status.$xstatus; ?></td>
						</tr>
						<?php
					}
				} else {
					echo '<tr><td colspan="10" style="text-align:center;">No payment schedule found</td></tr>';
				}
				?>
				</tbody>
			</table>
		</div>
	</div>

	<div class="section">
		<div class="title">Collateral - Loan <?php echo chr(64 + $loan_counter); ?> (NB collateral attachment should be downloaded and attached to this report)</div>
		<br>
		<div class="content">
			<?php
			$collaterals = get_all_by_id('collateral','loan_id', $loan->loan_id);
			?>
			<table class="collapse" id="pattern-style-a">
				<tr>
					<th>Collateral Name</th>
					<th>Collateral Type</th>
					<th>Serial</th>
					<th>Estimated Price</th>
					<th>Description</th>
					<th>Date Added</th>
				</tr><?php
				foreach ($collaterals as $collateral)
				{
					?>
					<tr>
						<td><?php echo $collateral->collateral_name ?></td>
						<td><?php echo $collateral->collateral_type ?></td>
						<td><?php echo $collateral->serial ?></td>
						<td><?php echo $settings->currency ?><?php echo number_format($collateral->estimated_price, 2) ?></td>
						<td><?php echo $collateral->description ?></td>
						<td><?php echo $collateral->date_added ?></td>
					</tr>
					<?php
				}
				?>
			</table>
		</div>
	</div>

	<?php 
	$loan_counter++;
	endforeach; 
	?>

	<div style="margin: auto; text-align: center; margin-top: 30px;"><strong>********** END OF BATCH REPORT **********</strong></div>

</body></html>