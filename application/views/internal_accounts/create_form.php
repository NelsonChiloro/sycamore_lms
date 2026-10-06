<?php
$all = get_all('savings_products');
$at = get_all_by_id('account','account_type','3');
$ct = 1;
foreach ($at as $cc){
	$ct ++;
}
$account = 300000+$ct;
?>
<div class="main-content">
	<div class="page-header">
		<h2 class="header-title">internal account form</h2>
		<div class="header-sub-title">
			<nav class="breadcrumb breadcrumb-dash">
				<a href="<?php echo base_url('Admin')?>" class="breadcrumb-item"><i class="anticon anticon-home m-r-5"></i>Home</a>
				<a class="breadcrumb-item" href="#">Savings account form</a>

			</nav>
		</div>
	</div>
	<div class="card">
		<div class="card-body" style="border: thick orange solid;border-radius: 14px;">
			<h2 style="margin-top:0px">internal Account <?php echo $button ?></h2>
			<form action="<?php echo $action; ?>" method="post">

				<div class="form-group">
					<label for="varchar">Account Name <?php echo form_error('account_name') ?></label>
					<input type="text" class="form-control" name="account_name" id="account_name" placeholder="Account Name" value="<?php echo $account_name; ?>" />
				</div>
				<div class="form-group">
					<label for="enum">Is Cash Account <?php echo form_error('is_cash_account') ?></label>
					<input type="radio" class="form-control" name="is_cash_account" id="is_cash_account" placeholder="Is Cash Account" checked="checked" value="Yes" /> Yes
					<input type="radio" class="form-control" name="is_cash_account" id="is_cash_account" placeholder="Is Cash Account" value="No" /> No
				</div>
				<div class="form-group">
					<label for="enum">Is Teller/cashier Account? ></label>
					<input type="radio" class="form-control" name="is_cashier_account" id="is_cash_account" placeholder="Is Cash Account" checked="checked" value="Yes" /> Yes
					<input type="radio" class="form-control" name="is_cashier_account" id="is_cash_account" placeholder="Is Cash Account" value="No" /> No
				</div>
				<div class="form-group">
					<label for="varchar">Account Number <?php echo form_error('account_number') ?></label>
					<input readonly type="text" class="form-control" name="account_number" id="account_number" placeholder="Account Number" value="<?php echo $account; ?>" />
				</div>

				<div class="form-group">
					<label for="varchar">Account Description <?php echo form_error('account_desc') ?></label>
					<textarea class="form-control" name="account_desc" id="account_desc" cols="30" rows="10" placeholder="Account Description"><?php echo $account_desc; ?></textarea>

				</div>


				<input type="hidden" name="internal_account_id" value="<?php echo $internal_account_id; ?>" />
				<button type="submit" class="btn btn-primary"><?php echo $button ?></button>

			</form>
		</div>
	</div>
</div>
