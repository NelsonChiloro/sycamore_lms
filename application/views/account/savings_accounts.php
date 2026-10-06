<div class="main-content">
	<div class="page-header">
		<h2 class="header-title">Savings account list</h2>
		<div class="header-sub-title">
			<nav class="breadcrumb breadcrumb-dash">
				<a href="<?php echo base_url('Admin')?>" class="breadcrumb-item"><i class="anticon anticon-home m-r-5"></i>Home</a>
				<a class="breadcrumb-item" href="#">Savings accounts list</a>

			</nav>
		</div>
	</div>
	<div class="card">
		<div class="card-body" style="border: thick #24C16B solid;border-radius: 14px;">
<!--			<a href="--><?php //echo base_url('account/create')?><!--" class="btn btn-sm btn-primary"><i class="anticon anticon-plus-square" style="color: white; font-size: 20px;"></i>Add Savings account</a>-->
			<div class="m-t-25">
				<table id="data-table" class="table">
					<thead>


	<tr>
		<th>No</th>
		<th>Client Id</th>
		<th>Account Number</th>
		<th>Balance</th>
		<th>Account Type</th>
		<th>Account Type Product</th>
		<th>Account Status</th>
		<th>Added By</th>
		<th>Date Added</th>

	</tr>
					</thead>
					<tbody>
	<?php
	$start = 0;
	foreach ($account_data as $account)
	{
		?>
		<tr>
			<td width="80px"><?php echo ++$start ?></td>
			<td><?php echo $account->ifname." ".$account->ilname ?></td>
			<td><?php echo $account->account_number ?></td>
			<td><?php echo $account->balance ?></td>
			<td><?php echo $account->account_type_name ?></td>
			<td><?php echo $account->sname ?></td>
			<td><?php echo $account->account_status ?></td>
			<td><?php echo $account->lname." ".$account->fname ?></td>
			<td><?php echo $account->date_added ?></td>

		</tr>
					</tbody>
		<?php
	}
	?>
</table>
			</div>
		</div>
	</div>
</div>
