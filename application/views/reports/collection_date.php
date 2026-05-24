<?php
$users = get_all('employees');
$branches = get_all('branches');
$products = get_all('loan_products');
?>
<div class="main-content">
	<div class="page-header">
		<h2 class="header-title">Collection report</h2>
		<div class="header-sub-title">
			<nav class="breadcrumb breadcrumb-dash">
				<a href="<?php echo base_url('Admin')?>" class="breadcrumb-item"><i class="anticon anticon-home m-r-5"></i>Home</a>
				<a class="breadcrumb-item" href="#">-</a>
				<span class="breadcrumb-item active">All loans  payments <?php  echo $d_title; ?></span>
			</nav>
		</div>
	</div>
	<div class="card">
		<div class="card-body" style="border: thick #153505 solid;border-radius: 14px;">
			<form action="<?php echo base_url('reports/collection_date') ?>" method="get">

				Branch: <select name="branch" id="" class="select2">
					<option value="">All Branches</option>
					<?php

					foreach ($branches as $branch){
						?>
						<option value="<?php  echo $branch->id; ?>" <?php if($this->input->get('branch')==$branch->id){echo "selected";} ?>><?php echo $branch->BranchName ?></option>
						<?php
					}
					?>
                </select>
                    Officer: <select name="user" id="" class="select2">
					<option value="">All officers</option>
					<?php

					foreach ($users as $item){
						?>
						<option value="<?php  echo $item->id; ?>" <?php if($this->input->get('user')==$item->id){echo "selected";} ?>><?php echo $item->Firstname." ".$item->Lastname ?></option>
						<?php
					}
					?>
				</select>
                Product: <select name="product" id="" class="select2">
					<option value="">All product</option>
					<?php

					foreach ($products as $lp){
						?>
						<option value="<?php  echo $lp->loan_product_id; ?>" <?php if($this->input->get('product')==$lp->loan_product_id){echo "selected";} ?>><?php echo $lp->product_name ?></option>
						<?php
					}
					?>
				</select> Scheduled from:
				<input type="date" name="from" value="<?php  echo $this->input->get('from'); ?>" > Scheduled to: <input type="date" name="to" value="<?php  echo $this->input->get('to'); ?>"> <input type="submit" value="filter" name="search">
			</form>
			<hr>
			<p>Search results</p>
			<h5><?php  echo $d_title; ?></h5>
			<table class="table tab-content" id="data-table1">
				<thead>
				<tr>
					<th>#</th>
					<th>Branch</th>
					<th>Loan Customer</th>
					<th>Customer Group</th>
					<th>Loan Number</th>
					<th>Loan product</th>
					<th>Payment Scheduled date</th>
					<th>Amount to collect</th>
					<th>Payment number</th>
					<th>Officer</th>
					<th>Action</th>

				</tr>
				</thead>
				<tbody>
				<?php
				$n = 1;
				$totals =0;
				foreach ($loan_data as $loan)
				{
					$totals +=$loan->amount;
					if($loan->customer_type=='group'){
						$group = $this->Groups_model->get_by_id($loan->loan_customer);

						$customer_name = $group->group_name.'('.$group->group_code.')';
						$preview_url = "Customer_groups/members/";
					}elseif($loan->customer_type=='individual'){
						$indi = $this->Individual_customers_model->get_by_id($loan->loan_customer);
						$customer_name = $indi->Firstname.' '.$indi->Lastname;
						$preview_url = "Individual_customers/view/";
					}
					?>
					<tr>

						<td><?php echo $n ?></td>
						<td><?php echo $loan->BranchName  ?></td>
						<td><a href="<?php echo base_url($preview_url).$loan->loan_customer?>"><?php echo $customer_name?></a></td>
						<td><?php echo isset($loan->customer_group_name) ? $loan->customer_group_name : 'N/A' ?></td>

						<td><a href="<?php echo base_url('loan/view/').$loan->loan_id?>"><?php echo $loan->loan_number ?></a></td>
						<td><?php echo $loan->product_name ?></td>
						<td><?php echo $loan->payment_schedule ?></td>
						<!--						<td>MK--><?php //echo number_format($loan->loan_principal,2) ?><!--</td>-->
						<td><?php echo $loan->amount ?></td>
						<td><?php echo $loan->payment_number ?></td>
						<td><?php echo $loan->efname." ".$loan->elname ?></td>

						<td><a href="<?php echo base_url('loan/view/').$loan->loan_id?>">View</a></td>

					</tr>
					<?php
					$n ++;
				}
				?>
				</tbody>
				<tfoot>
				<tr>
				<th colspan="7" style="text-align:right;">Total</th>
				<th class="collection-total-cell">MK<?php echo number_format($totals,2)?></th>
                <th></th>
				<th></th>
				<th></th>
				</tr>
				</tfoot>
			</table>
		</div>
	</div>
</div>
