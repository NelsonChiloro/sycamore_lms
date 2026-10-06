<div class="main-content">
	<div class="page-header">
		<h2 class="header-title">All Loan Applications</h2>
		<div class="header-sub-title">
			<nav class="breadcrumb breadcrumb-dash">
				<a href="<?php echo base_url('Admin')?>" class="breadcrumb-item"><i class="anticon anticon-home m-r-5"></i>Home</a>
				<a class="breadcrumb-item" href="#">-</a>
				<span class="breadcrumb-item active">All loans Applied</span>
			</nav>
		</div>
	</div>
	<div class="card">
		<div class="card-body" style="border: thick orange solid;border-radius: 14px;">
        <table  id="data-table" class="table">
			<thead>
            <tr>

		<th>Loan Number</th>
		<th>Loan Product</th>
		<th>Loan Customer</th>
		<th>Loan Date</th>
		<th>Loan Principal</th>
		<th>Loan Period</th>
<!--		<th>Period Type</th>-->
		<th>Loan Interest</th>
		<th>Loan Amount Total</th>
		<th>Loan File</th>


		<th>Loan Status</th>
		<th>Funds Source</th>
		<th>Customer Group</th>
		<th>Batch</th>
		<th>Loan Added Date</th>

            </tr>
			</thead>
			<tbody><?php
            foreach ($loan_data as $loan)
            {
                ?>
                <tr>

			<td><?php echo $loan->loan_number ?></td>
			<td><?php echo $loan->product_name ?></td>
			<td>Misheck Kamuloni</td>
			<td><?php echo $loan->loan_date ?></td>
			<td>MK<?php echo number_format($loan->loan_principal,2) ?></td>
			<td><?php echo $loan->loan_period ?></td>
<!--			<td>--><?php //echo $loan->period_type ?><!--</td>-->
			<td><?php echo $loan->loan_interest ?>%</td>
			<td>MK<?php echo number_format($loan->loan_amount_total,2) ?></td>
			<td><a href="<?php echo base_url('uploads/').$loan->worthness_file?>" download >Download file <i class="fa fa-download fa-flip"></i></a></td>


			<td><?php echo $loan->loan_status ?></td>
			<td><?php echo isset($loan->funds_source_name) ? $loan->funds_source_name : 'N/A' ?></td>
			<td><?php echo isset($loan->customer_group_name) ? $loan->customer_group_name : 'N/A' ?></td>
			<td><?php echo isset($loan->batch_number) ? $loan->batch_number : 'N/A' ?></td>
			<td><?php echo $loan->loan_added_date ?></td>

		</tr>
                <?php
            }
            ?>
			</tbody>
        </table>
		</div>
	</div>
</div>
