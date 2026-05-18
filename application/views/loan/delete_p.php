<div class="main-content">
	<div class="page-header">
		<h2 class="header-title">All Loan active</h2>
		<div class="header-sub-title">
			<nav class="breadcrumb breadcrumb-dash">
				<a href="<?php echo base_url('Admin')?>" class="breadcrumb-item"><i class="anticon anticon-home m-r-5"></i>Home</a>
				<a class="breadcrumb-item" href="#">-</a>
				<span class="breadcrumb-item active">All loans active</span>
			</nav>
		</div>
	</div>
	<div class="card">
		<div class="card-body" style="border: thick #153505 solid;border-radius: 14px;">

			<table  id="data-table" class="tableCss">
				<thead>
				<tr>

					<th>#</th>
					<th>Loan Number</th>
					<th>Loan Product</th>
					<th>Loan Customer</th>
					<th>Loan Date</th>
					<th>Loan Principal</th>
					<th>Loan Period</th>
					<!--					<th>Period Type</th>-->
					<th>Loan Interest</th>
					<th>Loan Amount Total</th>
					<th>Loan File</th>


					<th>Loan Status</th>
					<th>Loan Added Date</th>
					<th>Action</th>

				</tr>
				</thead>
				<tbody><?php
				$n = 1;

				foreach ($loan_data as $loan)
				{
					?>
					<tr>

						<td><?php echo $n ?></td>
						<td><?php echo $loan->loan_number ?></td>
						<td><?php echo $loan->product_name ?></td>
						<td><a href="<?php echo base_url('individual_customers/view/').$loan->id?>""><?php echo $loan->Firstname." ".$loan->Lastname?></a></td>
						<td><?php echo $loan->loan_date ?></td>
						<td>MK<?php echo number_format($loan->loan_principal,2) ?></td>
						<td><?php echo $loan->loan_period ?></td>
						<!--						<td>--><?php //echo $loan->period_type ?><!--</td>-->
						<td><?php echo $loan->loan_interest ?>%</td>
						<td>MK<?php echo number_format($loan->loan_amount_total,2) ?></td>

						<td><a href="<?php echo base_url('uploads/').$loan->worthness_file?>" download >Download file <i class="fa fa-download fa-flip"></i></a></td>

						<td><?php echo $loan->loan_status ?></td>
						<td><?php echo $loan->loan_added_date ?></td>
						<td><a href="<?php echo base_url('loan/view/').$loan->loan_id?>">View</a>
						<a href="<?php echo base_url('loan/delete_data/').$loan->loan_id?> "onclick="confirm('Are you sure you want to delete this loan?')">delete</a></td>

					</tr>
					<?php
					$n ++;
				}
				?>
				</tbody>
			</table>
		</div>
	</div>
</div>
