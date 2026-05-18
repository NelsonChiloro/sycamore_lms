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
        <h2 style="margin-top:0px">Vault_cashier_pends <?php echo $button ?></h2>
        <form action="<?php echo $action; ?>" method="post">
	    <div class="form-group">
            <label for="varchar">Vault Account <?php echo form_error('vault_account') ?></label>
            <input type="text" class="form-control" name="vault_account" id="vault_account" placeholder="Vault Account" value="<?php echo $vault_account; ?>" />
        </div>
	    <div class="form-group">
            <label for="varchar">Teller Account <?php echo form_error('teller_account') ?></label>
            <input type="text" class="form-control" name="teller_account" id="teller_account" placeholder="Teller Account" value="<?php echo $teller_account; ?>" />
        </div>
	    <div class="form-group">
            <label for="decimal">Amount <?php echo form_error('amount') ?></label>
            <input type="text" class="form-control" name="amount" id="amount" placeholder="Amount" value="<?php echo $amount; ?>" />
        </div>
	    <div class="form-group">
            <label for="varchar">Creator <?php echo form_error('creator') ?></label>
            <input type="text" class="form-control" name="creator" id="creator" placeholder="Creator" value="<?php echo $creator; ?>" />
        </div>
	    <div class="form-group">
            <label for="varchar">Teller <?php echo form_error('teller') ?></label>
            <input type="text" class="form-control" name="teller" id="teller" placeholder="Teller" value="<?php echo $teller; ?>" />
        </div>
	    <div class="form-group">
            <label for="date">System Date <?php echo form_error('system_date') ?></label>
            <input type="text" class="form-control" name="system_date" id="system_date" placeholder="System Date" value="<?php echo $system_date; ?>" />
        </div>
	    <div class="form-group">
            <label for="enum">Status <?php echo form_error('status') ?></label>
            <input type="text" class="form-control" name="status" id="status" placeholder="Status" value="<?php echo $status; ?>" />
        </div>
	    <div class="form-group">
            <label for="datetime">Stamp <?php echo form_error('stamp') ?></label>
            <input type="text" class="form-control" name="stamp" id="stamp" placeholder="Stamp" value="<?php echo $stamp; ?>" />
        </div>
	    <input type="hidden" name="cvpid" value="<?php echo $cvpid; ?>" /> 
	    <button type="submit" class="btn btn-primary"><?php echo $button ?></button> 
	    <a href="<?php echo site_url('vault_cashier_pends') ?>" class="btn btn-default">Cancel</a>
	</form>
    </body>
</html>