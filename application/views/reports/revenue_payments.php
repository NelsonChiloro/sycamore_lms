<div class="main-content">
	<div class="page-header">
		<h2 class="header-title">All loan Payment collections report</h2>
		<div class="header-sub-title">
			<nav class="breadcrumb breadcrumb-dash">
				<a href="<?php echo base_url('Admin')?>" class="breadcrumb-item"><i class="anticon anticon-home m-r-5"></i>Home</a>
				<a class="breadcrumb-item" href="#">-</a>
				<span class="breadcrumb-item active">All payments collections report</span>
			</nav>
		</div>
	</div>
	<div class="card">
		<div class="card-body" style="border: thick #153505 solid;border-radius: 14px; overflow: scroll;">
			<table  id="data-table1" class="table">
				<thead>
				<tr>

					<th>#</th>
					<th>Customer Type</th>
					<th>First name </th>
					<th>Last name</th>
					<th>Group name</th>
					<th>Loan number</th>
					<th>Loan product</th>
					<th>Payment number</th>
					<th>Scheduled amount</th>
					<th>Principal</th>
					<th>Interest</th>
					<th>Loan cover</th>
					<th>Admin Fee</th>
					<th>Paid Amount</th>
					<th>Balance after payment</th>
					<th>Scheduled Date</th>
					<th>Paid date</th>
					<th>Officer</th>


				</tr>
				</thead>
				<tbody>
				<?php
				$count = 1;
				
				$total_secheduled_amounts = paid_sm_balances()->totals;
				$total_principal = paid_ps_balances()->totals;
				$total_interests = paid_interest_balances()->totals;
				$total_loan_cover = paid_lc_balances()->totals;
				$total_loan_admin = paid_af_balances()->totals;
				$total_paid = paid_balances()->totals;
//				$total_secheduled_amounts = 0;
//				$total_principal = 0;
//				$total_interests = 0;
//				$total_loan_cover = 0;
//				$total_loan_admin = 0;
//				$total_paid = 0;
				foreach ($loan_data as  $item){
//					$total_secheduled_amounts +=$item->pamount;
//					$total_principal +=$item->pprincipal;
//					$total_interests += $item->pinterest;
//					$total_loan_cover += $item->ploan_cover;
//					$total_loan_admin += $item->padmin_fee;
//					$total_amounts += $item->paid_amount;
					?>
					<tr>
						<td><?php echo $count ++; ?></td>
						<td><?php echo $item->customer_type; ?></td>
						<td><?php echo $item->Firstname; ?></td>
						<td><?php echo $item->Lastname; ?></td>
						<td><?php echo $item->group_name; ?></td>
						<td><?php echo $item->loan_number; ?></td>
						<td><?php echo $item->product_name; ?></td>
						<td><?php echo $item->payment_number; ?></td>
						<td><?php echo $item->pamount; ?></td>
						<td><?php echo $item->pprincipal; ?></td>
						<td><?php echo $item->pinterest; ?></td>
						<td><?php echo $item->ploan_cover; ?></td>
						<td><?php echo $item->padmin_fee; ?></td>
						<td><?php echo $item->paid_amount; ?></td>
						<td><?php echo $item->loan_balance; ?></td>
						<td><?php echo $item->payment_schedule; ?></td>
						<td><?php echo $item->paid_date; ?></td>
						<td><?php echo $item->paid_by; ?></td>


					</tr>
				<?php
				}

				?>
				</tbody>
<tfoot>
<tr>

	<th></th>
	<th></th>
	<th> </th>
	<th></th>
	<th></th>
	<th></th>
	<th></th>
	<th></th>
	<th><?php echo number_format($total_secheduled_amounts,2)?></th>
	<th><?php echo number_format($total_principal,2)?></th>
	<th><?php echo number_format($total_interests,2)?></th>
	<th><?php echo number_format($total_loan_cover,2)?></th>
	<th><?php echo number_format($total_loan_admin,2)?></th>
	<th><?php echo number_format($total_paid,2)?></th>
	<th></th>
	<th></th>
	<th></th>
	<th></th>


</tr>
</tfoot>
			</table>
		</div>
	</div>
</div>
