
<div class="main-content">
	<div class="page-header">
		<h2 class="header-title">All arrears report</h2>
		<div class="header-sub-title">
			<nav class="breadcrumb breadcrumb-dash">
				<a href="<?php echo base_url('Admin')?>" class="breadcrumb-item"><i class="anticon anticon-home m-r-5"></i>Home</a>
				<a class="breadcrumb-item" href="#">-</a>
				<span class="breadcrumb-item active">Period Analysis report</span>
			</nav>
		</div>
	</div>
	<div class="card">
		<div class="card-body" style="border: thick #153505 solid;border-radius: 14px;">
			<form action="<?php echo base_url('reports/period_analysis') ?>" method="get">
				<fieldset>
					<legend>Report filter</legend>
					<div id="controlgroup">

						Date from:<input type="text" class="dpicker" name="from" value="<?php  echo $this->input->get('from')?>" >
						Date to:<input type="text" class="dpicker" name="to" value="<?php  echo $this->input->get('to')?>" >
						<button type="submit" name="search" value="filter">Filter</button>
						<button type="submit" name="search" value="pdf"><i class="fa fa-file-pdf text-danger"></i></button>
						<button type="submit" name="search" value="excel"><i class="fa fa-file-excel text-success"></i></button>
					</div>
				</fieldset>
			</form>
			<hr>
			<p>Search results</p>
			<table class="table table-bordered">
				<tr>
					<td>(a) Total MWK value of loans disbursed during the period</td>
					<td style="text-align: left;"> MWK <?php echo number_format($total_loan_principal->total,2) ?></td>
				</tr>
				<tr>
					<td>(b) Total Number of loans disbursed during the period</td>
					<td style="text-align: left;"><?php
						echo $total_loans;
						?></td>
				</tr>
				<tr>
					<td>(c)Total Number of  client in book at last day of reporting period
					<table class="table">
						<tr>
							<th>Male</th>
							<th>Female</th>
						</tr>
						<tr>
							<td><?php echo $customers_male?></td>
							<td><?php echo $customers_female?></td>

						</tr>
					</table>
					</td>
					<td style="text-align: left;"><?php
						echo $customers;
						?></td>
				</tr>
				<tr>
					<td>(d) Total Number of  groups in a book on last day of reporting period</td>
					<td style="text-align: left;"><?php echo $groups; ?></td>
				</tr>
				<tr>
					<td>(f) Total Number of  Employees on last day of reporting period</td>
					<td style="text-align: left;"><?php echo $employees?></td>
				</tr>
				<tr>
					<td>(g) Total Number of  agents and brokers (If any)</td>
					<td style="text-align: left;">0</td>
				</tr>
				<tr>
					<td>(g) Total Number of people employed by  agents and brokers (If any)</td>
					<td style="text-align: left;">0</td>
				</tr>
			</table>
		</div>
	</div>
</div>
