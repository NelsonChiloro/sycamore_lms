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
			background: url('<?php echo $imgg; ?>');;
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
		<h2 style="text-align: center;">Loan Arrears  Report</h2>

		<table id="pattern-style-a">
			<tr>
				<td colspan="2">
					<table>
						<tr><td width="40%">Loan Report date:</td><td><strong><?= date('Y-m-d') ?></strong></td></tr>
						<tr><td>Loan Number:</td><td><strong><?= $product ?></strong></td></tr>

					</table>
				</td>
				<td colspan="4"></td>
				<td colspan="2">
					<table>
						<tr><td>Date from:</td><td><strong><?= $from ?></strong></td></tr>
						<tr><td>Date to:</td><td><strong><?= $to ?></strong></td></tr>

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
				<th>#</th>
				<th>Loan Customer</th>
				<th>Loan Number</th>
				<th>Check Date</th>
				<th>Amount Due</th>
				<th>Payment number</th>


			</tr>
			</thead>
			<tbody>
			<?php
			$n = 1;

			foreach ($loan_data as $loan)
			{
				?>
				<tr>

					<td><?php echo $n ?></td>
					<td><?php echo $loan->Firstname." ".$loan->Firstname?></td>

					<td><a href="<?php echo base_url('loan/view/').$loan->loan_id?>"><?php echo $loan->loan_number ?></a></td>

					<td><?php echo $loan->payment_schedule ?></td>
					<!--						<td>MK--><?php //echo number_format($loan->loan_principal,2) ?><!--</td>-->
					<td><?php echo $loan->amount ?></td>
					<td><?php echo $loan->payment_number ?></td>



				</tr>
				<?php
				$n ++;
			}
			?>
			</tbody>
		</table>
	</div>
</div>
<div style="margin: auto"><strong>********** NOTHING FOLLOWS **********</strong></div>


</body></html>
