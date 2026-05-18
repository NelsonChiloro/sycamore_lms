<div class="main-content">
	<div class="page-header">
		<h2 class="header-title">All loan Payment outstanding balances report</h2>
		<div class="header-sub-title">
			<nav class="breadcrumb breadcrumb-dash">
				<a href="<?php echo base_url('Admin')?>" class="breadcrumb-item"><i class="anticon anticon-home m-r-5"></i>Home</a>
				<a class="breadcrumb-item" href="#">-</a>
				<span class="breadcrumb-item active">All outstanding balances report</span>
			</nav>
		</div>
	</div>
	<?php
	$users = get_all('employees');
	$products = get_all('loan_products');
	?>
	<div class="card">
		<div class="card-body" style="border: thick #153505 solid;border-radius: 14px; ">
			<form action="<?php echo base_url('loan/balances') ?>" method="get">
				<fieldset>
					<legend>Report filter</legend>
					<div id="controlgroup">
						Loan officer:
						<select name="officer">
							<option value="">All Officers</option>
							<?php
							foreach ($users as $user){
								?>
								<option value="<?php echo $user->id;?>" <?php if($user->id==$this->input->get('officer')){echo 'selected';}  ?>><?php echo $user->Firstname." ".$user->Lastname;?></option>
								<?php
							}

							?>



						</select>
						Loan product:
						<select name="product">
							<option value="">All products</option>
							<?php
							foreach ($products as $product){
								?>
								<option value="<?php echo $product->loan_product_id;?>" <?php if($product->loan_product_id==$this->input->get('product')){echo 'selected';}  ?>><?php echo $product->product_name. " (".$product->product_code.")";?></option>
								<?php
							}

							?>
						</select>
						Loan number:
						<select name="loan" class="select2">
							<option value="">All loans</option>
							<?php
							$loan1 = get_active_loan();
							foreach ($loan1 as $l){
								?>
								<option value="<?php echo $l->loan_id;?>" <?php if($l->loan_id==$this->input->get('loan')){echo 'selected';}  ?>><?php echo $l->loan_number;?></option>
								<?php
							}

							?>
						</select>

						Scheduled Date from:<input type="text" class="dpicker" name="from" value="<?php  echo $this->input->get('from')?>" >
						Scheduled Date to:<input type="text" class="dpicker" name="to" value="<?php  echo $this->input->get('to')?>" >
						<button type="submit" name="search" value="filter">Filter</button>
<!--						<button type="submit" name="search" value="pdf"><i class="fa fa-file-pdf text-danger"></i></button>-->
<!--						<button type="submit" name="search" value="excel"><i class="fa fa-file-excel text-success"></i></button>-->
					</div>
				</fieldset>
			</form>

			<hr>
			<div style="overflow: scroll;">
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
						<th>Outstanding Balance</th>
						<th>Scheduled Date</th>
						<th>Paid date</th>
						<th>Officer</th>


					</tr>
					</thead>
					<tbody>
					<?php
					$count = 1;

					//				$total_secheduled_amounts = outstanding_sb_balances()->totals;
					//				$total_principal = outstanding_p_balances()->totals;
					//				$total_interests = outstanding_interest_balances()->totals;
					//				$total_loan_cover = outstanding_lc_balances()->totals;
					//				$total_loan_admin = outstanding_af_balances()->totals;
					$total_paid = 0;
					$total_secheduled_amounts = 0;
					$total_principal = 0;
					$total_interests = 0;
					$total_loan_cover = 0;
					$total_loan_admin = 0;
					
                    $totalunpaid = get_all_total_unpayments()->total_unpaid;
                    $total_unpaid_dd = 0;
					//				$total_paid = 0;
					foreach ($loan_data as  $item){
						$total_secheduled_amounts += $item->pamount;
						$total_principal += $item->pprincipal;
						$total_interests += $item->pinterest;
						$total_loan_cover += $item->ploan_cover;
						$total_loan_admin += $item->padmin_fee;
						$total_paid += $item->paid_amount;
                        $total_unpaid_dd  += ($item->amount - $item->paid_amount);
//					$total_amounts += $item->paid_amount;
						?>
						<tr>
							<td><?php echo $count ++; ?></td>
							<td><?php echo $item->customer_type; ?></td>
							<td><?php echo $item->ifname; ?></td>
							<td><?php echo $item->ilname; ?></td>
							<td><?php echo $item->group_name; ?></td>
							<td><a href="<?php echo base_url('loan/view/').$item->loan_id  ?>"><?php echo $item->loan_number; ?></a></td>
							<td><?php echo $item->product_name; ?></td>
							<td><?php echo $item->payment_number; ?></td>
							<td><?php echo $item->pamount; ?></td>
							<td><?php echo $item->pprincipal; ?></td>
							<td><?php echo $item->pinterest; ?></td>
							<td><?php echo $item->ploan_cover; ?></td>
							<td><?php echo $item->padmin_fee; ?></td>
							<td><?php echo $item->paid_amount; ?></td>
							<td><?php echo ($item->amount - $item->paid_amount); ?></td>
							<td><?php echo $item->payment_schedule; ?></td>
							<td><?php echo $item->paid_date; ?></td>
							<td><?php echo $item->efname." ".$item->elname; ?></td>


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
                        <th><?php echo number_format($total_unpaid_dd,2) ?></th>
<!--						<th>--><?php //echo number_format($totalunpaid,2)?><!--</th>-->
						<th></th>
						<th></th>
						<th></th>


					</tr>
					</tfoot>
				</table>
			</div>

		</div>
	</div>
</div>
