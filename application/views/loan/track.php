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
        <table  id="data-table1" class="tableCss">
			<thead>
            <tr>

		<th>#</th>
		<th>Loan Number</th>
		<th>Loan Product</th>
		<th>Loan Customer</th>
		<th>Loan Date</th>
		<th>Loan Principal (MWK)</th>
		<th>Loan Period</th>
		<th>Period Type</th>
		<th>Loan Interest</th>
		<th>Loan Interest amount (MWK) </th>
		<th>Loan Amount Total (MWK)</th>
		<th>Loan Installment Amount  (MWK)</th>
		<th>Loan File</th>
		<th>Loan Status</th>
        <th>Branch</th>
        <th>RBM Loan Classification</th>
		<th>Loan officer</th>
		<th>Funds Source</th>
		<th>Customer Group</th>
		<th>Batch</th>
		<th>Loan Added Date</th>
		<th>Action</th>

            </tr>
			</thead>
			<tbody><?php
			$n = isset($list_offset) ? ($list_offset + 1) : 1;

            foreach ($loan_data as $loan)
            {
                $preview_url = ($loan->customer_type == 'group') ? 'Customer_groups/members/' : 'Individual_customers/view/';
                $customer_name = !empty($loan->customer_display_name) ? $loan->customer_display_name : (!empty($loan->customer_nam) ? $loan->customer_nam : 'Unknown');
                ?>
                <tr>

			<td><?php echo $n ?></td>
			<td><?php echo $loan->loan_number ?></td>
			<td><?php echo $loan->product_name.'('.$loan->product_code.')' ?></td>
                    <td><a href="<?php echo base_url($preview_url).$loan->loan_customer?>"><?php echo $customer_name?></a></td>
<!--			<td><a href="--><?php //echo base_url('individual_customers/view/').$loan->id?><!--"">--><?php //echo $loan->Firstname." ".$loan->Lastname?><!--</a></td>-->
			<td><?php echo $loan->loan_date ?></td>
			<td><?php echo number_format($loan->loan_principal,2) ?></td>
			<td><?php echo $loan->loan_period ?></td>
			<td><?php echo $loan->period_type ?></td>
			<td><?php echo $loan->loan_interest ?>%</td>
                    <td><?php echo number_format($loan->loan_interest_amount,2) ?></td>
			<td><?php echo number_format($loan->loan_amount_total,2) ?></td>
			<td><?php echo number_format($loan->loan_amount_term,2) ?></td>


			<td><a href="<?php echo base_url('uploads/').$loan->worthness_file?>" download >Download file <i class="fa fa-download fa-flip"></i></a></td>

			<td><?php echo $loan->loan_status ?></td>
			<td><?php echo !empty($loan->branch_display_name) ? htmlspecialchars($loan->branch_display_name) : 'N/A'; ?></td>
			<td><?php echo !empty($loan->rbm_classification) ? htmlspecialchars($loan->rbm_classification) : 'Standard'; ?></td>
			<td><?php echo $loan->efname,' '.$loan->elname ?></td>
			<td><?php echo $loan->funds_source_name ?></td>
			<td><?php echo isset($loan->customer_group_name) ? $loan->customer_group_name : 'N/A' ?></td>
			<td><?php echo isset($loan->batch_number) ? $loan->batch_number : 'N/A' ?></td>
			<td><?php echo $loan->loan_added_date ?></td>
			<td>
				<a href="<?php echo base_url('loan/view/').$loan->loan_id?>" class="btn btn-primary btn-sm">View</a>
				<a href="<?php echo base_url('loan/report/').$loan->loan_id?>" class="btn btn-info btn-sm" target="_blank">Report</a>
			</td>

		</tr>
                <?php
				$n ++;
            }
            ?>
			</tbody>
        </table>
        </div>
        <?php $this->load->view('loan/_loan_list_pagination'); ?>
		</div>
	</div>
</div>
