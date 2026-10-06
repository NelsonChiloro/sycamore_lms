<!doctype html>
<html>
<head>
	<title>Loan report</title>

	<style>
		.word-table {
			border:1px solid black !important;
			border-collapse: collapse !important;
			width: 100%;
		}
		.word-table tr th, .word-table tr td{
			border:1px solid black !important;
			padding: 5px 10px;
		}
	</style>
</head>
<body>
<h2>Loan List</h2>
<table class="word-table" style="margin-bottom: 10px">
	<tr>
		<th>No</th>
		<th>Loan Number</th>
		<th>Loan Product</th>
		<th>Loan Customer</th>
		<th>Loan Date</th>
		<th>Loan Principal</th>
		<th>Loan Period</th>
		<th>Period Type</th>
		<th>Loan Interest</th>
		<th>Loan Interest Amount</th>
		<th>Admin Fee</th>
		<th>Admin Fees Amount</th>
		<th>Loan Cover</th>
		<th>Loan Cover Amount</th>
		<th>Loan Amount Term</th>
		<th>Loan Amount Total</th>
		<th>Next Payment Id</th>
		<th>Worth File</th>
		<th>Narration</th>
		<th>Loan Added By</th>
		<th>Loan Approved By</th>
		<th>Approved Date</th>
		<th>Rejected By</th>
		<th>Rejected Date</th>

		<th>Loan Status</th>
		<th>Disbursed</th>
		<th>Disbursed By</th>
		<th>Disbursed Date</th>
		<th>Written Off By</th>
		<th>Write Off Approved By</th>
		<th>Write Off Approval Date</th>
		<th>Written Off Date</th>
		<th>Loan Added Date</th>

	</tr><?php
	foreach ($loan_data as $loan)
	{
		?>
		<tr>
			<td><?php echo ++$start ?></td>
			<td><?php echo $loan->loan_number ?></td>
			<td><?php echo $loan->product_name ?></td>
			<td><?php echo $loan->customer_nam ?></td>
			<td><?php echo $loan->loan_date ?></td>
			<td><?php echo $loan->loan_principal ?></td>
			<td><?php echo $loan->loan_period ?></td>
			<td><?php echo $loan->period_type ?></td>
			<td><?php echo $loan->loan_interest ?></td>
			<td><?php echo $loan->loan_interest_amount ?></td>
			<td><?php echo $loan->admin_fee ?></td>
			<td><?php echo $loan->admin_fees_amount ?></td>
			<td><?php echo $loan->loan_cover ?></td>
			<td><?php echo $loan->loan_cover_amount ?></td>
			<td><?php echo $loan->loan_amount_term ?></td>
			<td><?php echo $loan->loan_amount_total ?></td>
			<td><?php echo $loan->next_payment_id ?></td>
			<td><?php echo $loan->worthness_file ?></td>
			<td><?php echo $loan->narration ?></td>
			<td><?php echo $loan->efname." ".$loan->elname ?></td>
			<td><?php echo $loan->loan_approved_by ?></td>
			<td><?php echo $loan->approved_date ?></td>
			<td><?php echo $loan->rejected_by ?></td>
			<td><?php echo $loan->rejected_date ?></td>

			<td><?php echo $loan->loan_status ?></td>
			<td><?php echo $loan->disbursed ?></td>
			<td><?php echo $loan->disbursed_by ?></td>
			<td><?php echo $loan->disbursed_date ?></td>
			<td><?php echo $loan->written_off_by ?></td>
			<td><?php echo $loan->write_off_approved_by ?></td>
			<td><?php echo $loan->write_off_approval_date ?></td>
			<td><?php echo $loan->written_off_date ?></td>
			<td><?php echo $loan->loan_added_date ?></td>
		</tr>
		<?php
	}
	?>
</table>
</body>
</html>
