<?php
$users = get_all('employees');
$products = get_all('loan_products');
$this->load->helper('report_service');
?>
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
	<div class="card">
		<div class="card-body" style="border: thick #153505 solid;border-radius: 14px;">
			<form action="<?php echo base_url('loan/balances_filter') ?>" method="post">
				<fieldset>
					<legend>Report filter</legend>
					<div id="controlgroup" class="p-3">
						<div class="row">
							<div class="col-md-3">
								<label for="balances-officer">Loan Officer:</label>
								<select name="officer" id="balances-officer" class="select2 form-control">
									<option value="All">All Officers</option>
									<?php foreach ($users as $user) { ?>
										<option value="<?php echo $user->id; ?>"><?php echo htmlspecialchars($user->Firstname . ' ' . $user->Lastname); ?></option>
									<?php } ?>
								</select>
							</div>
							<div class="col-md-3">
								<?php $officer_select_id = 'balances-officer'; $this->load->view('reports/_relationship_supervisor_filter'); ?>
							</div>
							<div class="col-md-3">
								<label for="product">Loan Product:</label>
								<select name="product" id="product" class="select2 form-control">
									<option value="All">All Products</option>
									<?php foreach ($products as $product) { ?>
										<option value="<?php echo $product->loan_product_id; ?>"><?php echo htmlspecialchars($product->product_name . ' (' . $product->product_code . ')'); ?></option>
									<?php } ?>
								</select>
							</div>
							<div class="col-md-3">
								<label for="loan_number">Loan Number:</label>
								<input type="text" name="loan_number" id="loan_number" class="form-control" placeholder="All loans (leave blank)">
							</div>
						</div>
						<div class="row mt-3">
							<div class="col-md-3">
								<label for="from">Scheduled Date From:</label>
								<input type="date" name="from" id="from" class="form-control">
							</div>
							<div class="col-md-3">
								<label for="to">Scheduled Date To:</label>
								<input type="date" name="to" id="to" class="form-control">
							</div>
							<div class="col-md-3 mt-4">
								<button type="submit" name="search" value="filter" class="btn btn-primary">Generate Report</button>
							</div>
						</div>
					</div>
				</fieldset>
			</form>
			<hr>
			<p class="text-muted mb-0" style="font-size:13px;">
				Report is generated in the background by the bulk_report service (same flow as Portfolio Analysis).
				After submitting, open <a href="<?php echo base_url('report'); ?>">Reports</a> to track progress and view the completed HTML report.
			</p>
		</div>
	</div>
</div>
