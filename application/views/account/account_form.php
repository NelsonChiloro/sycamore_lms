<?php
$all = get_all('savings_products');
$at = get_all_by_id('account','account_type','1');
$ct = 0;
foreach ($at as $cc){
	$ct ++;
}
$account = 100000+$ct;
?>
<div class="main-content">
	<div class="page-header">
		<h2 class="header-title">Savings account form</h2>
		<div class="header-sub-title">
			<nav class="breadcrumb breadcrumb-dash">
				<a href="<?php echo base_url('Admin')?>" class="breadcrumb-item"><i class="anticon anticon-home m-r-5"></i>Home</a>
				<a class="breadcrumb-item" href="#">Savings account form</a>

			</nav>
		</div>
	</div>
	<div class="card">
		<div class="card-body" style="border: thick orange solid;border-radius: 14px;">
        <h2 style="margin-top:0px">Savings Account <?php echo $button ?></h2>
        <form action="<?php echo $action; ?>" method="post">
	    <div class="form-group">
            <label for="varchar">Client Id <?php echo form_error('client_id') ?></label>
			<select name="client_id" id="client_id" class="form-control">
				<option value="">--select customer--</option>
				<?php

				foreach ($customers as $c){
					?>
					<option value="<?php  echo  $c->id;?>"><?php echo $c->Firstname. " ".$c->Lastname?></option>
					<?php
				}
				?>
			</select>

        </div>
	    <div class="form-group">
            <label for="varchar">Account Number <?php echo form_error('account_number') ?></label>
            <input readonly type="text" class="form-control" name="account_number" id="account_number" placeholder="Account Number" value="<?php echo $account; ?>" />
        </div>


	    <div class="form-group">
            <label for="int">Account Type Product <?php echo form_error('account_type_product') ?></label>
			<select  class="form-control" name="account_type_product" id="account_type_product">
				<option value="">--select--</option>
				<?php
				foreach ($all as $value){
					?>
					<option value="<?php echo $value->saviings_product_id ?>"><?php echo $value->name ?></option>
					<?php
				}
				?>
			</select>

        </div>

	    <input type="hidden" name="account_id" value="<?php echo $account_id; ?>" />
	    <button type="submit" class="btn btn-primary"><?php echo $button ?></button>
	    <a href="<?php echo site_url('account') ?>" class="btn btn-default">Cancel</a>
	</form>
		</div>
	</div>
</div>
