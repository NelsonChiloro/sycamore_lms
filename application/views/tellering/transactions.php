<?php
$users = get_all('transaction_type');
$products = get_active_loan();
?>
<div class="main-content">
	<div class="page-header">
		<h2 class="header-title">All Payment collections report</h2>
		<div class="header-sub-title">
			<nav class="breadcrumb breadcrumb-dash">
				<a href="<?php echo base_url('Admin')?>" class="breadcrumb-item"><i class="anticon anticon-home m-r-5"></i>Home</a>
				<a class="breadcrumb-item" href="#">-</a>
				<span class="breadcrumb-item active">All payments collections report</span>
			</nav>
		</div>
	</div>
	<div class="card">
		<div class="card-body" style="border: thick #153505 solid;border-radius: 14px;">
		<form action="<?php echo base_url('Tellering/track_transaction') ?>" method="GET">
				<fieldset>
					<legend>Track transactions</legend>

						Loan Number:<input type="text"  name="loannumber" value="<?php echo $this->input->get('loannumber') ?>"  placeholder="Filter by loan number (leave empty for all)">
                        <button type="submit" name="search" value="filter">Filter</button>
						<button type="submit" name="search" value="pdf"><i class="fa fa-file-pdf text-danger"></i></button>
						<button type="submit" name="search" value="excel"><i class="fa fa-file-excel text-success"></i></button>
					</div>
				</fieldset>
			</form>
			<hr>
			<form action="<?php echo base_url('Tellering/generate_loan_deposits_report') ?>" method="POST">
				<fieldset>
					<legend>Generate Loan Deposits Report (Background)</legend>
					<div>
						From: <input type="date" name="from" value="" placeholder="From date">
						To: <input type="date" name="to" value="" placeholder="To date">
						<button type="submit" class="btn btn-sm btn-success">Generate Report</button>
						<small style="color:#666;">(Report will be processed in background. Check progress on Reports page.)</small>
					</div>
				</fieldset>
			</form>
			<hr>
			<p>Search results</p>
			<table  id="data-table" class="table">
				<thead>
				<tr>
					<th>#</th>
					<th>Account Number</th>
					<th>Reference Number</th>
					<th>Credit</th>
					<th>Debit</th>
					<th>Transaction type</th>
					<th>Date</th>
					<th>Actions</th>
				</tr>
				</thead>
				<tbody>
				<?php
				$n = 1;
foreach ($loan_data as $l){
	?>
				<tr>
					<td><?php echo $n;?></td>
					<td><?php echo $l->account_number ;?></td>
					<td><?php echo $l->transaction_id;?></td>
					<td><?php echo number_format($l->credit,2);?></td>
					<td><?php echo $l->debit;?></td>
					<td><?php echo $l->transaction_type;?></td>
					<td><?php echo $l->system_time;?></td>
					<td>
                        <?php
                        if($l->reversed=="Yes"){}
                        else{
                        $loan_info =  get_one_where('loan', 'loan_number ="'.$l->account_number.'"');
                        if(!$loan_info || $loan_info->paid_off=="YES"){}else{
                        ?>
                        <a href="<?php echo base_url('Loan/transaction_reversal?tid='.$l->transaction_id.'&account='.$l->account_number)?>" onclick="return confirm('Are you sure you want to reverse this transaction? this is not recoverable')">Reverse transaction</a>
                    <?php
                    }
                    }
                    ?>
					</td>
				</tr>

				<?php
	$n ++;
}
				?>
				</tbody>
			</table>
			<?php if(empty($loan_data)) { ?>
			<p>No transactions found.</p>
			<?php } ?>
		</div>
	</div>
</div>
