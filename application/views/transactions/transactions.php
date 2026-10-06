<div class="main-content">
	<div class="page-header">
		<h2 class="header-title">Transactions</h2>
		<div class="header-sub-title">
			<nav class="breadcrumb breadcrumb-dash">
				<a href="<?php echo base_url('Admin')?>" class="breadcrumb-item"><i class="anticon anticon-home m-r-5"></i>Home</a>
				<a class="breadcrumb-item" href="#">-</a>
				<span class="breadcrumb-item active">All select transactions</span>
			</nav>
		</div>
	</div>
	<div class="card">
		<div class="card-body" style="border: thick green solid;border-radius: 14px;">
			<?php
			$customer = get_all('individual_customers')
			?>

            <form action="<?php echo base_url('transactions/search') ?>" method="GET">
                <!-- Customer Type Selection -->
                Customer Type:
                <select name="customer_type" id="customer_type" class="select2" required>
                    <option value="">--select customer type--</option>
                    <option value="individual">Individual</option>
                    <option value="group">Group</option>
                </select>

                <!-- Customer Search -->
                Customer:
                <input type="text" name="customer" id="customer_search" placeholder="Type customer name" autocomplete="off" disabled>

                <!-- Loan Selection -->

                <select id="customer_transact">
                    <option value="">--select customer--</option>
                </select>

                Customer Loan:
                <select name="loan_id" id="loan_dis" required>
                    <option value="">--select loan--</option>
                </select>

                <!-- Submit Button -->
                <input type="submit" class="btn btn-sm btn-info" value="Search transactions">
            </form>

            <hr>
            <!-- Print Report Form -->
            <p>
            <form action="<?php echo base_url('transactions/report') ?>" method="GET">
                <input type="hidden" name="loan_id" value="<?php echo $this->input->get('loan_id') ?>" required>
                Results: <button type="submit" class="btn btn-success"><i class="fa fa-file-pdf text-danger"></i> Print</button>
            </form>
            </p>
            </p>


			<table class="table table-bordered" id="data-table" >
				<thead>
				<tr>

					<th>Ref ID</th>
					<th>Account number</th>

					<th>CR</th>
					<th>DR</th>
					<th>Bal</th>

					<th>Date</th>

				</tr>
				</thead>
				<tbody>
<?php
foreach ($data as $trans)
{
	?>
<tr>
	<td><?php echo $trans->transaction_id  ?></td>
	<td><?php echo $trans->account_number  ?></td>
	<td><?php echo  number_format($trans->credit,2)  ?></td>
	<td><?php echo number_format($trans->debit,2)  ?></td>

	<td><?php  echo number_format($trans->balance,2)  ?></td>
	<td><?php echo $trans->system_time  ?></td>

</tr>
<?php


}
	?>
				</tbody>
			</table>
		</div>
	</div>
</div>
