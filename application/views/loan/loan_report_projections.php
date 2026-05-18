<?php
//$users = get_all('employees');
//$products = get_all('loan_products');
?>
<div class="main-content">
	<div class="page-header">
		<h2 class="header-title">Projections</h2>
		<div class="header-sub-title">
			<nav class="breadcrumb breadcrumb-dash">
				<a href="<?php echo base_url('Admin')?>" class="breadcrumb-item"><i class="anticon anticon-home m-r-5"></i>Home</a>
				<a class="breadcrumb-item" href="#">-</a>
				<span class="breadcrumb-item active">Projections</span>
			</nav>
		</div>
	</div>
	<div class="card">
		<div class="card-body" style="border: thick #153505 solid;border-radius: 14px;">
			<form action="<?php echo base_url('loan/loan_report_search_projection') ?>" method="get">
				<fieldset>
					<legend>Loan Projections</legend>
					<div id="controlgroup">


						Date from:<input type="text" class="dpicker" name="from" value="<?php  echo $this->input->get('from')?>" required>
						Date to:<input type="text" class="dpicker" name="to" value="<?php  echo $this->input->get('to')?>" required>
						<button type="submit" name="search" value="filter">Filter</button>
<!--						<button type="submit" name="search" value="pdf"><i class="fa fa-file-pdf text-danger"></i></button>-->
<!--						<button type="submit" name="search" value="excel"><i class="fa fa-file-excel text-success"></i></button>-->
					</div>
				</fieldset>
			</form>
			<hr>
			<p>Search results</p>
			<table  id=" " class="tableCss">
				<thead>
				<tr>

					<th bgcolor="red" >Total Loan Amount Disbursed </th>

					<th bgcolor="#00ced1">Total Principal Disbursed</th>
					<th>Total Loan Interest</th>
					<th bgcolor="green">Total Loans Collected</th>

				</tr>
				</thead>
				<tbody>
				<?php

				?>

				<tr>

					<td>MK<?php echo number_format($amount,2) ?></td>

					<td >MK<?php echo number_format($principal,2) ?></td>
					<td >MK<?php echo number_format($interest,2) ?></td>

					<td bgcolor="#90ee90">MK<?php echo number_format($paid_amount,2) ?></td>

				</tr>

				</tbody>
			</table>
		</div>
	</div>
</div>
