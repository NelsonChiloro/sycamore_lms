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
		<div class="card-body" style="border: thick #153505 solid;border-radius: 14px;">
            <?php if (!empty($show_loan_filters)) { $this->load->view('loan/_loan_list_filters'); } ?>
            <hr>
            <div style="overflow-y: auto"">
        <table  id="data-table" class="table">
			<thead>
            <tr>

		<th>Loan Number</th>
		<th>Loan Product</th>
		<th>Loan Customer</th>
		<th>Loan Date</th>
		<th>Loan Principal</th>
		<th>Loan Period</th>
		<th>Period Type</th>
		<th>Loan Interest</th>
		<th>Admin fee</th>
		<th>Loan cover</th>
		<th>Loan Amount Total</th>
		<th>Loan File</th>


		<th>Loan Status</th>
		<th>Funds Source</th>
		<th>Customer Group</th>
		<th>Batch</th>
		<th>Action</th>
				

            </tr>
			</thead>
			<tbody><?php
            foreach ($loan_data as $loan)
            {
                $preview_url = ($loan->customer_type == 'group') ? 'Customer_groups/members/' : 'Individual_customers/view/';
                $customer_name = !empty($loan->customer_display_name) ? $loan->customer_display_name : (!empty($loan->customer_nam) ? $loan->customer_nam : 'Unknown');
                ?>
                <tr>

			<td><?php echo $loan->loan_number ?></td>
			<td><?php echo $loan->product_name ?></td>
                    <td><a href="<?php echo base_url($preview_url).$loan->loan_customer?>""><?php echo $customer_name?></a></td>
			<td><?php echo $loan->loan_date ?></td>
			<td>MK<?php echo number_format($loan->loan_principal,2) ?></td>
			<td><?php echo $loan->loan_period ?></td>
			<td><?php echo $loan->period_type ?></td>
			<td><?php echo $loan->loan_interest ?>%</td>
                    <td><?php echo $loan->admin_fee ?>%</td>
                    <td><?php echo $loan->loan_cover ?>%</td>
			<td>MK<?php echo number_format($loan->loan_amount_total,2) ?></td>

			<td><a href="<?php echo base_url('uploads/').$loan->worthness_file?>" download >Download file <i class="fa fa-download fa-flip"></i></a></td>

			<td><?php echo $loan->loan_status ?></td>
			<td><?php echo isset($loan->funds_source_name) ? $loan->funds_source_name : 'N/A' ?></td>
			<td><?php echo isset($loan->customer_group_name) ? $loan->customer_group_name : 'N/A' ?></td>
			<td><?php echo isset($loan->batch_number) ? $loan->batch_number : 'N/A' ?></td>
			<td><a href="<?php echo base_url('loan/view/').$loan->loan_id?>" class="btn btn-sm btn-info">View</a><a href="<?php echo base_url('loan/approval_action?id=').$loan->loan_id."&action=APPROVED"?>"  onclick="return confirm('Are you sure you want to approve?')" class="btn btn-sm btn-warning">Approve</a><a href="<?php echo base_url('loan/approval_action?id=').$loan->loan_id."&action=REJECT"?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to reject?')">Reject</a></td>

		</tr>
                <?php
            }
            ?>
			</tbody>
        </table>
        </div>
        <?php $this->load->view('loan/_loan_list_pagination'); ?>
		</div>
	</div>
</div>
