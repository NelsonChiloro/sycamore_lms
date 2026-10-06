<!doctype html>
<html>
    <head>
        <title>harviacode.com - codeigniter crud generator</title>
        <link rel="stylesheet" href="<?php echo base_url('assets/bootstrap/css/bootstrap.min.css') ?>"/>
        <style>
            body{
                padding: 15px;
            }
        </style>
    </head>
    <body>
        <h2 style="margin-top:0px">Internal_accounts <?php echo $button ?></h2>
        <form action="<?php echo $action; ?>" method="post">
	    <div class="form-group">
            <label for="varchar">Account Name <?php echo form_error('account_name') ?></label>
            <input type="text" class="form-control" name="account_name" id="account_name" placeholder="Account Name" value="<?php echo $account_name; ?>" />
        </div>
	    <div class="form-group">
            <label for="enum">Is Cash Account <?php echo form_error('is_cash_account') ?></label>
            <input type="text" class="form-control" name="is_cash_account" id="is_cash_account" placeholder="Is Cash Account" value="<?php echo $is_cash_account; ?>" />
        </div>
	    <div class="form-group">
            <label for="varchar">Adde By <?php echo form_error('adde_by') ?></label>
            <input type="text" class="form-control" name="adde_by" id="adde_by" placeholder="Adde By" value="<?php echo $adde_by; ?>" />
        </div>
	    <div class="form-group">
            <label for="datetime">Date Created <?php echo form_error('date_created') ?></label>
            <input type="text" class="form-control" name="date_created" id="date_created" placeholder="Date Created" value="<?php echo $date_created; ?>" />
        </div>
	    <input type="hidden" name="internal_account_id" value="<?php echo $internal_account_id; ?>" /> 
	    <button type="submit" class="btn btn-primary"><?php echo $button ?></button> 
	    <a href="<?php echo site_url('internal_accounts') ?>" class="btn btn-default">Cancel</a>
	</form>
    </body>
</html>