
<div class="main-content">
	<div class="page-header">
		<h2 class="header-title">All arrears report</h2>
		<div class="header-sub-title">
			<nav class="breadcrumb breadcrumb-dash">
				<a href="<?php echo base_url('Admin')?>" class="breadcrumb-item"><i class="anticon anticon-home m-r-5"></i>Home</a>
				<a class="breadcrumb-item" href="#">-</a>
				<span class="breadcrumb-item active">All loans  arrears report</span>
			</nav>
		</div>
	</div>
	<div class="card">
		<div class="card-body" style="border: thick #153505 solid;border-radius: 14px;">
			<form action="<?php echo base_url('reports/financial_analysis') ?>" method="get">
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
					<td>interests income from Loans</td>
					<td style="text-align: left;"> MWK <?php echo number_format($interests_income->interest,2) ?></td>
				</tr>
				<tr>
					<td> Income from processing Fee</td>
					<td style="text-align: left;">MWK<?php
						echo number_format($admin_income->amount,2) ;
						?></td>
				</tr>
                <tr>
					<td> Income from Admin Fee</td>
					<td style="text-align: left;">MWK<?php
						echo number_format($admin_fee->admin_fee,2) ;
						?></td>
				</tr>
                <tr>
					<td> Income from Loan cover</td>
					<td style="text-align: left;">MWK<?php
						echo number_format($loan_cover->loan_cover,2) ;
						?></td>
				</tr>

				<tr>
					<td>Late Paying fees</td>
					<td style="text-align: left;">MWK<?php echo number_format($late_fee->amount,2)?></td>
				</tr>




			</table>
		</div>
	</div>
</div>
