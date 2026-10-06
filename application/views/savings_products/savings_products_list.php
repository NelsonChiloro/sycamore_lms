<div class="main-content">
	<div class="page-header">
		<h2 class="header-title">Savings products</h2>
		<div class="header-sub-title">
			<nav class="breadcrumb breadcrumb-dash">
				<a href="<?php echo base_url('Admin')?>" class="breadcrumb-item"><i class="anticon anticon-home m-r-5"></i>Home</a>
				<a class="breadcrumb-item" href="#">Savings products</a>
				<span class="breadcrumb-item active">All products</span>
			</nav>
		</div>
	</div>
	<div class="card">
		<div class="card-body" style="border: thick green solid;border-radius: 14px;">
				<div class="m-t-25">
				<table id="data-table" class="table">
					<thead>
            <tr>
                <th>No</th>
		<th>Name</th>
		<th>Added By</th>
		<th>Date Added</th>
		<th>Interest Per Anum</th>
		<th>Interest Method</th>
		<th>Interest Posting Frequency</th>
		<th>When To Post</th>
		<th>Minimum Balance For Interest</th>
		<th>Minimum Balance Withdrawal</th>
		<th>Overdraft</th>

            </tr>

					</thead>
					<tbody>
			<?php
			$start = 0;
            foreach ($savings_products_data as $savings_products)
            {
                ?>
                <tr>
			<td width="80px"><?php echo ++$start ?></td>
			<td><?php echo $savings_products->name ?></td>
			<td><?php echo $savings_products->Firstname." ".$savings_products->Lastname ?></td>
			<td><?php echo $savings_products->date_added ?></td>
			<td><?php echo $savings_products->interest_per_anum ?></td>
			<td><?php echo $savings_products->interest_method ?></td>
			<td><?php echo $savings_products->interest_posting_frequency ?></td>
			<td><?php echo $savings_products->when_to_post ?></td>
			<td><?php echo $savings_products->minimum_balance_for_interest ?></td>
			<td><?php echo $savings_products->minimum_balance_withdrawal ?></td>
			<td><?php echo $savings_products->overdraft ?></td>

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
