<div class="main-content">
	<div class="page-header">
		<h2 class="header-title">Expenses</h2>
		<div class="header-sub-title">
			<nav class="breadcrumb breadcrumb-dash">
				<a href="<?php echo base_url('Admin')?>" class="breadcrumb-item"><i class="anticon anticon-home m-r-5"></i>Home</a>
				<a class="breadcrumb-item" href="#">-</a>
				<span class="breadcrumb-item active">All select transactions</span>
			</nav>
		</div>
	</div>
	<div class="card">
		<div class="card-body" style="border: thick #153505 solid;border-radius: 14px;">
			<div class="controlgroup">
				<form action="<?php echo base_url('Transactions/expenses')?>" method="get">
				Date from:<input type="text" class="dpicker" name="from" value="<?php  echo $this->input->get('from')?>" >
				Date to:<input type="text" class="dpicker" name="to" value="<?php  echo $this->input->get('to')?>" >
				<button type="submit" name="search" value="filter">Filter</button>
				<button type="submit" name="search" value="pdf"><i class="fa fa-file-pdf text-danger"></i></button>
				<button type="submit" name="search" value="excel"><i class="fa fa-file-excel text-success"></i></button>
				</form>
			</div>
			<hr/>

<button class="btn btn-info" onclick="add_expense()">Add expense</button>
			<table class="table table-bordered" id="data-table" >
				<thead>
				<tr>

					<th>Expense Desc</th>
					<th>Ref ID</th>
					<th>Loan number</th>

					<th>Paid amount</th>
					<th>Payment number</th>
					<th>Added By</th>
					<th>Date</th>
					<th>Actions</th>

				</tr>
				</thead>
				<tbody>
<?php
foreach ($data as $trans)
{
	?>
<tr>
	<td><?php echo $trans->description  ?></td>
	<td><?php echo $trans->ref  ?></td>
	<td><?php echo $trans->loan_number  ?></td>
	<td>MWK<?php echo number_format($trans->amount,2)  ?></td>
	<td><?php echo $trans->payment_number  ?></td>
	<td><?php echo $trans->Firstname." ".$trans->Lastname  ?></td>
	<td><?php echo $trans->date_stamp  ?></td>
	<td><a class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this?')" href="<?php echo base_url('Transactions/delete/').$trans->transaction_id?>"><i class="fa fa-trash"></i>Delete</a></td>

</tr>
<?php


}
	?>
				</tbody>
			</table>
		</div>
	</div>
</div>

<div aria-hidden="true" class="onboarding-modal modal fade" id="add_expense_modal" role="dialog" tabindex="-1">
	<div class="modal-dialog modal-lg modal-centered" role="document">
		<div class="modal-content text-center">
			<button style="float: right;" aria-label="Close" class="close" data-dismiss="modal" type="button"><span class="close-label">Close</span><span class="anticon anticon-close"></span></button>
			<div class="onboarding-content" style="padding: 1em;">
				<h4 class="onboarding-title">Add An expense</h4>
				<form action="<?php echo base_url('Transactions/create_expense')?>" method="post" class="form-row" enctype="multipart/form-data">
					<input type="text" name="loan_id" id="loan_d" hidden>
					<div class="form-group col-6">
						<label for="varchar">Loan number </label>
						<?php
						$customer = get_all('loan')
						?>

							Loan <select name="loan_id" id="customer_transact" class="form-control" required>
								<option value="">--select customer</option>
								<?php

								foreach ($customer as $c){
									?>
									<option value="<?php  echo  $c->loan_id;?>"><?php echo $c->loan_number?></option>
									<?php
								}
								?>

							</select>

					</div>
					<div class="form-group col-6">
						<label for="date">Expense amount MK</label>
						<input type="number" class="form-control" name="amount" id="charge_amount"  required />
					</div>
					<div class="form-group col-12">
						<label for="date">Description</label>
						<textarea cols="10" rows="10" class="form-control" name="description">

						</textarea>
					</div>


					<button type="submit" class="btn btn-primary">Save Changes</button>

				</form>
			</div>
		</div>
	</div>
</div>
